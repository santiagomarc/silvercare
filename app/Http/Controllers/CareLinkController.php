<?php

namespace App\Http\Controllers;

use App\Models\LinkCode;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CareLinkController extends Controller
{
    /**
     * Generate a 6-digit caregiver linking code valid for 24 hours.
     * Also generates a QR code SVG encoding the same code for easy sharing.
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
            $qrSvg = $this->generateQrSvg($activeCode->code);

            return back()
                ->with('link_code', $activeCode->code)
                ->with('link_qr_svg', $qrSvg)
                ->with('success', 'Your active PIN is ready to share.');
        }

        $code = $this->generateUniqueCode();

        $newCode = LinkCode::create([
            'code'               => $code,
            'caregiver_profile_id' => $profile->id,
            'expires_at'         => now()->addDay(),
        ]);

        $qrSvg = $this->generateQrSvg($code);

        return back()
            ->with('link_code', $newCode->code)
            ->with('link_qr_svg', $qrSvg)
            ->with('success', 'Linking PIN generated. It expires in 24 hours.');
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

        $profile->update(['caregiver_id' => null]);

        return back()->with('success', 'You have been unlinked from your caregiver.');
    }

    /**
     * Generate a QR code SVG string encoding just the 6-digit PIN.
     * The elderly user scans it on their phone — it pre-fills the PIN input.
     */
    protected function generateQrSvg(string $code): string
    {
        return (string) QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($code);
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

