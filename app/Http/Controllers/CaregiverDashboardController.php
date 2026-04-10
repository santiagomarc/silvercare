<?php

namespace App\Http\Controllers;

use App\Models\LinkCode;
use App\Services\CaregiverDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CaregiverDashboardController extends Controller
{
    public function __construct(
        protected CaregiverDashboardService $dashboardService,
    ) {
    }

    public function index(Request $request)
    {
        $caregiver = Auth::user()->profile;
        $activeLinkCode = null;
        $activeLinkQrSvg = null;
        $activeLinkSignedUrl = null;
        
        // Ensure the user has a profile
        if (!$caregiver) {
            return redirect()->route('profile.completion');
        }

        $elderlyPatients = $caregiver->elderlyPatients()->with('user')->orderBy('id')->get();
        $requestedElderlyId = $request->integer('elderly');
        $elderly = $requestedElderlyId
            ? $elderlyPatients->firstWhere('id', $requestedElderlyId)
            : null;
        $elderly = $elderly ?? $elderlyPatients->first();
        $selectedElderlyId = $elderly?->id;

        if ($caregiver) {
            $activeLinkCode = LinkCode::where('caregiver_profile_id', $caregiver->id)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first();

            if ($activeLinkCode) {
                $activeLinkSignedUrl = URL::temporarySignedRoute(
                    'elderly.link',
                    $activeLinkCode->expires_at,
                    [
                        'code' => $activeLinkCode->code,
                        'caregiver' => $caregiver->id,
                    ]
                );

                $activeLinkQrSvg = (string) QrCode::format('svg')
                    ->size(200)
                    ->margin(1)
                    ->errorCorrection('M')
                    ->generate($activeLinkSignedUrl);
            }
        }

        if (!$elderly) {
            return view('caregiver.dashboard', [
                'elderly' => null,
                'elderlyPatients' => $elderlyPatients,
                'selectedElderlyId' => null,
                'elderlyUser' => null,
                'mood' => null,
                'vitals' => [],
                'recentActivity' => collect(),
                'stats' => [],
                'activeLinkCode' => $activeLinkCode,
                'activeLinkQrSvg' => $activeLinkQrSvg,
                'activeLinkSignedUrl' => $activeLinkSignedUrl,
            ]);
        }

        $elderlyUser = $elderly->user;

        $todayHealth = $this->dashboardService->getTodayVitalsAndMood($elderly->id);
        $mood = $todayHealth['mood'];
        $vitals = $todayHealth['vitals'];

        $recentActivity = $this->dashboardService->getRecentActivity($elderly->id);
        $stats = $this->dashboardService->getStats($elderly);

        $conditions = $elderly->resolvedMedicalConditions();
        $medications = $elderly->resolvedMedications();
        $allergies = $elderly->resolvedAllergies();

        return view('caregiver.dashboard', compact('elderly', 'elderlyPatients', 'selectedElderlyId', 'elderlyUser', 'mood', 'vitals', 'recentActivity', 'stats', 'conditions', 'medications', 'allergies', 'activeLinkCode', 'activeLinkQrSvg', 'activeLinkSignedUrl'));
    }
}
