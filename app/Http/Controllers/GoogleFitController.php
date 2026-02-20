<?php

namespace App\Http\Controllers;

use App\Models\GoogleFitToken;
use App\Models\HealthMetric;
use App\Services\GoogleFitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleFitController extends Controller
{
    protected GoogleFitService $googleFitService;

    /**
     * Google Fit API Scopes needed - includes all vital types
     */
    const SCOPES = [
        'https://www.googleapis.com/auth/fitness.heart_rate.read',
        'https://www.googleapis.com/auth/fitness.activity.read',
        'https://www.googleapis.com/auth/fitness.body.read',
        'https://www.googleapis.com/auth/fitness.blood_pressure.read',
        'https://www.googleapis.com/auth/fitness.body_temperature.read',
    ];

    public function __construct(GoogleFitService $googleFitService)
    {
        $this->googleFitService = $googleFitService;
    }

    /**
     * Redirect to Google OAuth for Google Fit authorization
     */
    public function connect()
    {
        $clientId = config('services.google.client_id');
        $redirectUri = route('elderly.googlefit.callback');
        $scopes = implode(' ', self::SCOPES);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scopes,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => csrf_token(),
        ]);

        return redirect("https://accounts.google.com/o/oauth2/v2/auth?{$params}");
    }

    /**
     * Handle OAuth callback from Google
     */
    public function callback(Request $request)
    {
        $user = Auth::user();

        // Check for errors
        if ($request->has('error')) {
            return redirect()->route('dashboard')->with('error', 'Failed to connect Google Fit: ' . $request->input('error'));
        }

        $code = $request->input('code');
        
        if (!$code) {
            return redirect()->route('dashboard')->with('error', 'No authorization code received');
        }

        try {
            // Exchange code for tokens
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => route('elderly.googlefit.callback'),
            ]);

            if (!$response->successful()) {
                return redirect()->route('dashboard')->with('error', 'Failed to get access token from Google');
            }

            $tokenData = $response->json();
            $tokenData['scopes'] = self::SCOPES;

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

        $token = GoogleFitToken::where('user_id', $user->id)->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Google Fit not connected. Please connect first.'
            ], 400);
        }

        try {
            // Check if token expired and refresh if needed
            if ($token->isExpired()) {
                $token = $this->refreshAccessToken($token);
                if (!$token) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Google Fit session expired. Please reconnect.'
                    ], 401);
                }
            }

            $synced = [];
            $accessToken = $token->access_token;

            // Fetch and sync heart rate data
            $heartRateData = $this->fetchHeartRateFromSources($accessToken);
            if (!empty($heartRateData)) {
                foreach ($heartRateData as $reading) {
                    HealthMetric::updateOrCreate(
                        [
                            'elderly_id' => $elderlyId,
                            'type' => 'heart_rate',
                            'source' => 'google_fit',
                            'measured_at' => $reading['timestamp'],
                        ],
                        [
                            'value' => $reading['value'],
                            'unit' => 'bpm',
                        ]
                    );
                }
                $synced['heart_rate'] = count($heartRateData) . ' readings';
            }

            // Fetch and sync blood pressure data
            $bpData = $this->fetchBloodPressureFromSources($accessToken);
            if (!empty($bpData)) {
                foreach ($bpData as $reading) {
                    HealthMetric::updateOrCreate(
                        [
                            'elderly_id' => $elderlyId,
                            'type' => 'blood_pressure',
                            'source' => 'google_fit',
                            'measured_at' => $reading['timestamp'],
                        ],
                        [
                            'value' => $reading['systolic'], // Store systolic as main value
                            'value_text' => $reading['systolic'] . '/' . $reading['diastolic'],
                            'unit' => 'mmHg',
                        ]
                    );
                }
                $synced['blood_pressure'] = count($bpData) . ' readings';
            }

            // Fetch and sync temperature data
            $tempData = $this->fetchTemperatureFromSources($accessToken);
            if (!empty($tempData)) {
                foreach ($tempData as $reading) {
                    HealthMetric::updateOrCreate(
                        [
                            'elderly_id' => $elderlyId,
                            'type' => 'temperature',
                            'source' => 'google_fit',
                            'measured_at' => $reading['timestamp'],
                        ],
                        [
                            'value' => $reading['value'],
                            'unit' => '°C',
                        ]
                    );
                }
                $synced['temperature'] = count($tempData) . ' readings';
            }

            // Fetch and sync steps data
            $steps = $this->fetchStepsAggregated($accessToken);
            if ($steps !== null && $steps > 0) {
                HealthMetric::updateOrCreate(
                    [
                        'elderly_id' => $elderlyId,
                        'type' => 'steps',
                        'source' => 'google_fit',
                        'measured_at' => Carbon::today(),
                    ],
                    [
                        'value' => $steps,
                        'unit' => 'steps',
                    ]
                );
                $synced['steps'] = $steps;
            }

            return response()->json([
                'success' => true,
                'message' => 'Google Fit data synced successfully!',
                'synced' => $synced,
            ]);

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

        GoogleFitToken::where('user_id', $user->id)->delete();

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
        $token = GoogleFitToken::where('user_id', $user->id)->first();

        return response()->json([
            'connected' => $token !== null,
            'expires_at' => $token?->expires_at?->toISOString(),
            'is_expired' => $token?->isExpired() ?? true,
        ]);
    }

    /**
     * Refresh expired access token
     */
    private function refreshAccessToken(GoogleFitToken $token): ?GoogleFitToken
    {
        if (!$token->refresh_token) {
            return null;
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $token->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            
            $token->update([
                'access_token' => $data['access_token'],
                'expires_at' => now()->addSeconds($data['expires_in']),
            ]);

            return $token->fresh();

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all available data sources from Google Fit
     */
    private function getDataSources(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/fitness/v1/users/me/dataSources');

            if ($response->successful()) {
                return $response->json()['dataSource'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get data sources: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Fetch heart rate data from direct data sources (raw data)
     */
    private function fetchHeartRateFromSources(string $accessToken): array
    {
        $dataSources = $this->getDataSources($accessToken);
        $allData = [];

        // Time range: last 7 days to catch all recent data
        $endTime = Carbon::now();
        $startTime = Carbon::now()->subDays(7);

        // Convert to nanoseconds for dataset ID
        $startNanos = $startTime->timestamp * 1000000000;
        $endNanos = $endTime->timestamp * 1000000000;

        foreach ($dataSources as $source) {
            $dataType = $source['dataType']['name'] ?? '';
            
            if ($dataType === 'com.google.heart_rate.bpm') {
                $dataSourceId = $source['dataStreamId'];
                $datasetId = "{$startNanos}-{$endNanos}";
                
                try {
                    $response = Http::withToken($accessToken)
                        ->get("https://www.googleapis.com/fitness/v1/users/me/dataSources/{$dataSourceId}/datasets/{$datasetId}");

                    if ($response->successful()) {
                        $points = $response->json()['point'] ?? [];
                        
                        foreach ($points as $point) {
                            $values = $point['value'] ?? [];
                            $startTimeNanos = $point['startTimeNanos'] ?? 0;
                            
                            if (!empty($values)) {
                                $heartRate = $values[0]['fpVal'] ?? $values[0]['intVal'] ?? null;
                                
                                if ($heartRate && $heartRate > 0 && $heartRate < 300) {
                                    $timestamp = Carbon::createFromTimestampMs((int)($startTimeNanos / 1000000))
                                        ->setTimezone(config('app.timezone'));
                                    
                                    $allData[] = [
                                        'value' => (int) round($heartRate),
                                        'timestamp' => $timestamp,
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to fetch heart rate from source {$dataSourceId}: " . $e->getMessage());
                }
            }
        }

        // Also try aggregated data as fallback
        $aggregatedData = $this->fetchHeartRateAggregated($accessToken);
        if ($aggregatedData) {
            $allData[] = [
                'value' => $aggregatedData,
                'timestamp' => Carbon::now(),
            ];
        }

        // Remove duplicates (within same minute)
        $uniqueData = [];
        $seenTimestamps = [];
        
        foreach ($allData as $data) {
            $key = $data['timestamp']->format('Y-m-d H:i');
            if (!isset($seenTimestamps[$key])) {
                $seenTimestamps[$key] = true;
                $uniqueData[] = $data;
            }
        }

        return $uniqueData;
    }

    /**
     * Fetch blood pressure from direct data sources (raw data)
     */
    private function fetchBloodPressureFromSources(string $accessToken): array
    {
        $dataSources = $this->getDataSources($accessToken);
        $allData = [];

        // Time range: last 7 days
        $endTime = Carbon::now();
        $startTime = Carbon::now()->subDays(7);

        $startNanos = $startTime->timestamp * 1000000000;
        $endNanos = $endTime->timestamp * 1000000000;

        foreach ($dataSources as $source) {
            $dataType = $source['dataType']['name'] ?? '';
            
            if ($dataType === 'com.google.blood_pressure') {
                $dataSourceId = $source['dataStreamId'];
                $datasetId = "{$startNanos}-{$endNanos}";
                
                try {
                    $response = Http::withToken($accessToken)
                        ->get("https://www.googleapis.com/fitness/v1/users/me/dataSources/{$dataSourceId}/datasets/{$datasetId}");

                    if ($response->successful()) {
                        $points = $response->json()['point'] ?? [];
                        
                        foreach ($points as $point) {
                            $values = $point['value'] ?? [];
                            $startTimeNanos = $point['startTimeNanos'] ?? 0;
                            
                            // Blood pressure format:
                            // values[0] = systolic (fpVal)
                            // values[1] = diastolic (fpVal)
                            if (count($values) >= 2) {
                                $systolic = $values[0]['fpVal'] ?? null;
                                $diastolic = $values[1]['fpVal'] ?? null;
                                
                                if ($systolic && $diastolic && $systolic > 0 && $diastolic > 0) {
                                    $timestamp = Carbon::createFromTimestampMs((int)($startTimeNanos / 1000000))
                                        ->setTimezone(config('app.timezone'));
                                    
                                    $allData[] = [
                                        'systolic' => (int) round($systolic),
                                        'diastolic' => (int) round($diastolic),
                                        'timestamp' => $timestamp,
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to fetch blood pressure from source {$dataSourceId}: " . $e->getMessage());
                }
            }
        }

        return $allData;
    }

    /**
     * Fetch temperature from direct data sources (raw data)
     */
    private function fetchTemperatureFromSources(string $accessToken): array
    {
        $dataSources = $this->getDataSources($accessToken);
        $allData = [];

        // Time range: last 7 days
        $endTime = Carbon::now();
        $startTime = Carbon::now()->subDays(7);

        $startNanos = $startTime->timestamp * 1000000000;
        $endNanos = $endTime->timestamp * 1000000000;

        foreach ($dataSources as $source) {
            $dataType = $source['dataType']['name'] ?? '';
            
            // Check for body temperature data type
            if ($dataType === 'com.google.body.temperature' || $dataType === 'com.google.body_temperature') {
                $dataSourceId = $source['dataStreamId'];
                $datasetId = "{$startNanos}-{$endNanos}";
                
                try {
                    $response = Http::withToken($accessToken)
                        ->get("https://www.googleapis.com/fitness/v1/users/me/dataSources/{$dataSourceId}/datasets/{$datasetId}");

                    if ($response->successful()) {
                        $points = $response->json()['point'] ?? [];
                        
                        foreach ($points as $point) {
                            $values = $point['value'] ?? [];
                            $startTimeNanos = $point['startTimeNanos'] ?? 0;
                            
                            if (!empty($values)) {
                                $temperature = $values[0]['fpVal'] ?? null;
                                
                                // Temperature in Celsius should be between 35 and 42
                                if ($temperature && $temperature >= 35 && $temperature <= 42) {
                                    $timestamp = Carbon::createFromTimestampMs((int)($startTimeNanos / 1000000))
                                        ->setTimezone(config('app.timezone'));
                                    
                                    $allData[] = [
                                        'value' => round($temperature, 1),
                                        'timestamp' => $timestamp,
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to fetch temperature from source {$dataSourceId}: " . $e->getMessage());
                }
            }
        }

        return $allData;
    }

    /**
     * Fetch heart rate from aggregated API (fallback)
     */
    private function fetchHeartRateAggregated(string $accessToken): ?int
    {
        $now = Carbon::now();
        $startOfDay = Carbon::today();

        try {
            $response = Http::withToken($accessToken)
                ->post('https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate', [
                    'aggregateBy' => [
                        ['dataTypeName' => 'com.google.heart_rate.bpm']
                    ],
                    'bucketByTime' => [
                        'durationMillis' => 86400000
                    ],
                    'startTimeMillis' => $startOfDay->timestamp * 1000,
                    'endTimeMillis' => $now->timestamp * 1000,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                foreach ($data['bucket'] ?? [] as $bucket) {
                    foreach ($bucket['dataset'] ?? [] as $dataset) {
                        foreach ($dataset['point'] ?? [] as $point) {
                            foreach ($point['value'] ?? [] as $value) {
                                if (isset($value['fpVal'])) {
                                    return (int) round($value['fpVal']);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Aggregated heart rate fetch failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fetch steps from aggregated API
     */
    private function fetchStepsAggregated(string $accessToken): ?int
    {
        $now = Carbon::now();
        $startOfDay = Carbon::today();

        try {
            $response = Http::withToken($accessToken)
                ->post('https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate', [
                    'aggregateBy' => [
                        ['dataTypeName' => 'com.google.step_count.delta']
                    ],
                    'bucketByTime' => [
                        'durationMillis' => 86400000
                    ],
                    'startTimeMillis' => $startOfDay->timestamp * 1000,
                    'endTimeMillis' => $now->timestamp * 1000,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $totalSteps = 0;

                foreach ($data['bucket'] ?? [] as $bucket) {
                    foreach ($bucket['dataset'] ?? [] as $dataset) {
                        foreach ($dataset['point'] ?? [] as $point) {
                            foreach ($point['value'] ?? [] as $value) {
                                if (isset($value['intVal'])) {
                                    $totalSteps += $value['intVal'];
                                }
                            }
                        }
                    }
                }

                return $totalSteps > 0 ? $totalSteps : null;
            }
        } catch (\Exception $e) {
            Log::warning('Aggregated steps fetch failed: ' . $e->getMessage());
        }

        return null;
    }
}
