<?php

namespace App\Http\Controllers;

use App\Services\GoogleFitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleFitController extends Controller
{
    protected GoogleFitService $googleFitService;

    public function __construct(GoogleFitService $googleFitService)
    {
        $this->googleFitService = $googleFitService;
    }

    /**
     * Redirect to Google OAuth for Google Fit authorization
     */
    public function connect()
    {
        $oauthState = Str::random(40);
        session(['google_fit_oauth_state' => $oauthState]);

        return redirect(
            $this->googleFitService->buildAuthorizationUrl(route('elderly.googlefit.callback'), $oauthState)
        );
    }

    /**
     * Handle OAuth callback from Google
     */
    public function callback(Request $request)
    {
        $user = Auth::user();

        $state = (string) $request->input('state', '');
        $expectedState = (string) $request->session()->pull('google_fit_oauth_state', '');

        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            return redirect()->route('dashboard')->with('error', 'Invalid Google Fit authorization state. Please try again.');
        }

        // Check for errors
        if ($request->has('error')) {
            return redirect()->route('dashboard')->with('error', 'Failed to connect Google Fit: ' . $request->input('error'));
        }

        $code = $request->input('code');
        
        if (!$code) {
            return redirect()->route('dashboard')->with('error', 'No authorization code received');
        }

        try {
            $tokenData = $this->googleFitService->exchangeCodeForTokens(
                $code,
                route('elderly.googlefit.callback')
            );

            if (!$tokenData) {
                return redirect()->route('dashboard')->with('error', 'Failed to get access token from Google');
            }

            // Store tokens
            $this->googleFitService->storeTokens($user->id, $tokenData);

            return redirect()->route('dashboard')->with('success', '✅ Google Fit connected successfully! Your health data will now sync automatically.');

        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Error connecting Google Fit: ' . $e->getMessage());
        }
    }

    /**
     * Sync data from Google Fit
     */
    public function sync()
    {
        $user = Auth::user();
        $elderlyId = $user->profile?->id;

        if (!$elderlyId) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        try {
            $synced = $this->googleFitService->syncAll($elderlyId, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Google Fit data synced successfully!',
                'synced' => $synced,
            ]);

        } catch (\RuntimeException $e) {
            $status = $e->getCode();
            if (in_array($status, [400, 401], true)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $status);
            }

            Log::error('Google Fit sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Google Fit sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect Google Fit
     */
    public function disconnect()
    {
        $user = Auth::user();
        $this->googleFitService->disconnectUser($user->id);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Google Fit disconnected successfully'
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Google Fit disconnected');
    }

    /**
     * Check if Google Fit is connected
     */
    public function status()
    {
        $user = Auth::user();
        return response()->json($this->googleFitService->getStatus($user->id));
    }
}
