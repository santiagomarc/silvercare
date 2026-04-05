<?php

namespace App\Http\Controllers;

use App\Models\LinkCode;
use App\Models\UserProfile;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CareLinkController extends Controller
{
    /**
    * Generate a 6-digit caregiver linking code valid for 24 hours.
    * Also generates a QR code SVG using a temporary signed link for safer sharing.
     */
    public function generate(Request $request): RedirectResponse
    {
        $profile = $request->user()?->profile;

        if (! $profile || ! $profile->isCaregiver()) {
            abort(403);
        }

        $activeCode = LinkCode::where('caregiver_profile_id', $profile->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($activeCode) {
            $signedLink = $this->buildSignedLink($activeCode->code, $profile->id);
            $qrSvg = $this->generateQrSvg($activeCode->code, $profile->id);

            return back()
                ->with('link_code', $activeCode->code)
                ->with('link_qr_svg', $qrSvg)
                ->with('link_signed_url', $signedLink)
                ->with('success', 'Your active PIN is ready to share.');
        }

        $code = $this->generateUniqueCode();

        $newCode = LinkCode::create([
            'code'               => $code,
            'caregiver_profile_id' => $profile->id,
            'expires_at'         => now()->addDay(),
        ]);

        $signedLink = $this->buildSignedLink($code, $profile->id);
        $qrSvg = $this->generateQrSvg($code, $profile->id);

        return back()
            ->with('link_code', $newCode->code)
            ->with('link_qr_svg', $qrSvg)
            ->with('link_signed_url', $signedLink)
            ->with('success', 'Linking PIN generated. It expires in 24 hours.');
    }

    /**
     * Open a temporary signed caregiver link from QR and prefill the PIN
     * in the elderly dashboard's confirmation flow.
     */
    public function openSignedLink(Request $request): RedirectResponse
    {
        $profile = $request->user()?->profile;

        if (! $profile || ! $profile->isElderly()) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'caregiver' => ['required', 'integer'],
        ]);

        $linkCode = LinkCode::where('code', $validated['code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->with('caregiverProfile.user')
            ->first();

        if (! $linkCode || ! $linkCode->caregiverProfile?->isCaregiver()) {
            return redirect()->route('dashboard')
                ->withErrors(['code' => 'This QR code is invalid or expired. Please ask your caregiver for a new one.']);
        }

        if ((int) $validated['caregiver'] !== (int) $linkCode->caregiver_profile_id) {
            return redirect()->route('dashboard')
                ->withErrors(['code' => 'This QR code does not match the caregiver profile.']);
        }

        return redirect()->route('dashboard')
            ->with('prefill_link_code', $linkCode->code)
            ->with('prefill_link_source', 'qr')
            ->with('info', 'Caregiver link verified. Please confirm to complete linking.');
    }

    /**
     * Step 1 of linking: validate the PIN and return caregiver preview data.
     * Does NOT commit the link — that requires a separate confirmation POST.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $profile = $request->user()?->profile;
        if (! $profile || ! $profile->isElderly()) {
            return response()->json(['valid' => false, 'message' => 'Unauthorized.'], 403);
        }

        $linkCode = LinkCode::where('code', $validated['code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->with('caregiverProfile.user')
            ->first();

        if (! $linkCode || ! $linkCode->caregiverProfile?->isCaregiver()) {
            return response()->json([
                'valid'   => false,
                'message' => 'Invalid or expired PIN. Please ask your caregiver for a new one.',
            ]);
        }

        $caregiverProfile = $linkCode->caregiverProfile;
        $caregiverUser    = $caregiverProfile->user;

        return response()->json([
            'valid'            => true,
            'code'             => $validated['code'],
            'caregiver_name'   => $caregiverUser?->name ?? $caregiverProfile->username ?? 'Your Caregiver',
            'caregiver_role'   => $caregiverProfile->relationship ?? 'Caregiver',
            'caregiver_avatar' => $caregiverUser?->google_avatar ?? null,
            'expires_at'       => $linkCode->expires_at->format('M d, Y g:i A'),
        ]);
    }

    /**
     * Step 2 of linking: confirm and persist the caregiver link.
     * Requires the same validated PIN from step 1.
     */
    public function confirmLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $profile = $request->user()?->profile;
        if (! $profile || ! $profile->isElderly()) {
            abort(403);
        }

        $linkCode = LinkCode::where('code', $validated['code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->with('caregiverProfile')
            ->first();

        if (! $linkCode || ! $linkCode->caregiverProfile?->isCaregiver()) {
            return back()->withErrors(['code' => 'Invalid or expired PIN. Please ask your caregiver for a new one.']);
        }

        $profile->update([
            'caregiver_id' => $linkCode->caregiver_profile_id,
        ]);

        $linkCode->update([
            'used_by_profile_id' => $profile->id,
            'used_at'            => now(),
        ]);

        return back()->with('success', '✅ You are now connected to your caregiver!');
    }

    /**
     * Unlink the elderly user from their current caregiver.
     */
    public function unlink(Request $request): RedirectResponse
    {
        $profile = $request->user()?->profile;

        if (! $profile || ! $profile->isElderly()) {
            abort(403);
        }

        if (! $profile->caregiver_id) {
            return back()->with('info', 'You are not currently linked to a caregiver.');
        }

        $caregiver = $profile->caregiver;
        $caregiverName = $caregiver?->user?->name ?? 'your caregiver';

        $profile->update(['caregiver_id' => null]);

        app(NotificationService::class)->createNotification([
            'elderly_id' => $profile->id,
            'type' => 'caregiver_unlinked',
            'title' => 'Caregiver Unlinked',
            'message' => "Connection to {$caregiverName} has been removed.",
            'severity' => 'warning',
            'metadata' => [
                'caregiver_name' => $caregiverName,
                'caregiver_profile_id' => $caregiver?->id,
                'action' => 'elderly_unlinked',
                'unlinked_at' => now()->toIso8601String(),
            ],
        ]);

        return back()->with('success', 'You have been unlinked from your caregiver.');
    }

    /**
     * Build a temporary signed URL used by QR and share actions.
     */
    protected function buildSignedLink(string $code, int $caregiverId): string
    {
        return URL::temporarySignedRoute(
            'elderly.link',
            now()->addDay(),
            [
                'code' => $code,
                'caregiver' => $caregiverId,
            ]
        );
    }

    /**
     * Generate a QR code SVG string encoding the temporary signed link.
     */
    protected function generateQrSvg(string $code, int $caregiverId): string
    {
        $signedUrl = $this->buildSignedLink($code, $caregiverId);

        return (string) QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($signedUrl);
    }

    /**
     * Generate a unique 6-digit code with collision retry.
     */
    protected function generateUniqueCode(): string
    {
        $attempts = 0;

        do {
            $attempts++;
            $code   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $exists = LinkCode::where('code', $code)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->exists();
        } while ($exists && $attempts < 10);

        return $code;
    }
}

