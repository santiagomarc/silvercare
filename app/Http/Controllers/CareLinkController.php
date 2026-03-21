<?php

namespace App\Http\Controllers;

use App\Models\LinkCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CareLinkController extends Controller
{
    /**
     * Generate a 6-digit caregiver linking code valid for 24 hours.
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
            return back()->with('link_code', $activeCode->code)
                ->with('success', 'Your active PIN is ready to share.');
        }

        $code = $this->generateUniqueCode();

        $newCode = LinkCode::create([
            'code' => $code,
            'caregiver_profile_id' => $profile->id,
            'expires_at' => now()->addDay(),
        ]);

        return back()
            ->with('link_code', $newCode->code)
            ->with('success', 'Linking PIN generated. It expires in 24 hours.');
    }

    /**
     * Link an elderly profile to a caregiver via a valid 6-digit code.
     */
    public function link(Request $request): RedirectResponse
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

        if (! $linkCode || ! $linkCode->caregiverProfile || ! $linkCode->caregiverProfile->isCaregiver()) {
            return back()->withErrors(['code' => 'Invalid or expired PIN. Please ask your caregiver for a new one.']);
        }

        $profile->update([
            'caregiver_id' => $linkCode->caregiver_profile_id,
        ]);

        $linkCode->update([
            'used_by_profile_id' => $profile->id,
            'used_at' => now(),
        ]);

        return back()->with('success', 'Caregiver linked successfully.');
    }

    /**
     * Generate a code and ensure uniqueness under concurrent requests.
     */
    protected function generateUniqueCode(): string
    {
        $attempts = 0;

        do {
            $attempts++;
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $exists = LinkCode::where('code', $code)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->exists();
        } while ($exists && $attempts < 10);

        return $code;
    }
}
