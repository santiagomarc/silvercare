<?php

namespace App\Services;

use App\Models\GoogleFitToken;
use App\Models\HealthMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleFitService
{
    /**
     * Google Fit API scopes needed for vitals and activity sync.
     */
    public const SCOPES = [
        'https://www.googleapis.com/auth/fitness.heart_rate.read',
        'https://www.googleapis.com/auth/fitness.activity.read',
        'https://www.googleapis.com/auth/fitness.body.read',
        'https://www.googleapis.com/auth/fitness.blood_pressure.read',
        'https://www.googleapis.com/auth/fitness.body_temperature.read',
    ];

    /**
     * Build Google OAuth authorization URL.
     */
    public function buildAuthorizationUrl(string $redirectUri, ?string $state = null): string
    {
        $params = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state ?? csrf_token(),
        ]);

        return "https://accounts.google.com/o/oauth2/v2/auth?{$params}";
    }

    /**
     * Exchange OAuth authorization code for Google access and refresh tokens.
     */
    public function exchangeCodeForTokens(string $code, string $redirectUri): ?array
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $tokenData = $response->json();
            $tokenData['scopes'] = self::SCOPES;

            return $tokenData;
        } catch (\Exception $e) {
            Log::warning('Google Fit token exchange failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Store Google Fit OAuth tokens
     */
    public function storeTokens(int $userId, array $tokenData): GoogleFitToken
    {
        return GoogleFitToken::updateOrCreate(
            ['user_id' => $userId],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
                'scopes' => $tokenData['scopes'] ?? [],
            ]
        );
    }

    /**
     * Remove Google Fit link for a user.
     */
    public function disconnectUser(int $userId): void
    {
        GoogleFitToken::where('user_id', $userId)->delete();
    }

    /**
     * Return Google Fit connection status payload for a user.
     */
    public function getStatus(int $userId): array
    {
        $token = GoogleFitToken::where('user_id', $userId)->first();

        return [
            'connected' => $token !== null,
            'expires_at' => $token?->expires_at?->toISOString(),
            'is_expired' => $token?->isExpired() ?? true,
        ];
    }

    /**
     * Sync all supported vitals from Google Fit into health_metrics.
     *
     * @throws \RuntimeException when the account is disconnected or token refresh fails.
     */
    public function syncAll(int $elderlyId, int $userId): array
    {
        $token = GoogleFitToken::where('user_id', $userId)->first();

        if (!$token) {
            throw new \RuntimeException('Google Fit not connected. Please connect first.', 400);
        }

        if ($token->isExpired()) {
            $token = $this->refreshAccessToken($token);
            if (!$token) {
                throw new \RuntimeException('Google Fit session expired. Please reconnect.', 401);
            }
        }

        $synced = [];
        $accessToken = $token->access_token;

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
                        'value' => $reading['systolic'],
                        'value_text' => $reading['systolic'] . '/' . $reading['diastolic'],
                        'unit' => 'mmHg',
                    ]
                );
            }
            $synced['blood_pressure'] = count($bpData) . ' readings';
        }

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

        return $synced;
    }

    /**
     * Refresh expired access token.
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
            Log::warning('Google Fit token refresh failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all available Google Fit data sources.
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
     * Fetch heart-rate readings from data sources and aggregate fallback.
     */
    private function fetchHeartRateFromSources(string $accessToken): array
    {
        $dataSources = $this->getDataSources($accessToken);
        $allData = [];

        $endTime = Carbon::now();
        $startTime = Carbon::now()->subDays(7);
        $startNanos = $startTime->timestamp * 1000000000;
        $endNanos = $endTime->timestamp * 1000000000;

        foreach ($dataSources as $source) {
            $dataType = $source['dataType']['name'] ?? '';
            if ($dataType !== 'com.google.heart_rate.bpm') {
                continue;
            }

            $dataSourceId = $source['dataStreamId'];
            $datasetId = "{$startNanos}-{$endNanos}";

            try {
                $response = Http::withToken($accessToken)
                    ->get("https://www.googleapis.com/fitness/v1/users/me/dataSources/{$dataSourceId}/datasets/{$datasetId}");

                if (!$response->successful()) {
                    continue;
                }

                $points = $response->json()['point'] ?? [];
                foreach ($points as $point) {
                    $values = $point['value'] ?? [];
                    $startTimeNanos = $point['startTimeNanos'] ?? 0;

                    if (empty($values)) {
                        continue;
                    }

                    $heartRate = $values[0]['fpVal'] ?? $values[0]['intVal'] ?? null;
                    if (!$heartRate || $heartRate <= 0 || $heartRate >= 300) {
                        continue;
                    }

                    $timestamp = Carbon::createFromTimestampMs((int) ($startTimeNanos / 1000000))
                        ->setTimezone(config('app.timezone'));

                    $allData[] = [
                        'value' => (int) round($heartRate),
                        'timestamp' => $timestamp,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch heart rate from source {$dataSourceId}: " . $e->getMessage());
            }
        }

        $aggregatedData = $this->fetchHeartRateAggregated($accessToken);
        if ($aggregatedData) {
            $allData[] = [
                'value' => $aggregatedData,
                'timestamp' => Carbon::now(),
            ];
        }

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
     * Fetch blood-pressure readings from Google Fit data sources.
     */
    private function fetchBloodPressureFromSources(string $accessToken): array
    {
        $dataSources = $this->getDataSources($accessToken);
        $allData = [];

        $endTime = Carbon::now();
        $startTime = Carbon::now()->subDays(7);
        $startNanos = $startTime->timestamp * 1000000000;
        $endNanos = $endTime->timestamp * 1000000000;

        foreach ($dataSources as $source) {
            $dataType = $source['dataType']['name'] ?? '';
            if ($dataType !== 'com.google.blood_pressure') {
                continue;
            }

            $dataSourceId = $source['dataStreamId'];
            $datasetId = "{$startNanos}-{$endNanos}";

            try {
                $response = Http::withToken($accessToken)
                    ->get("https://www.googleapis.com/fitness/v1/users/me/dataSources/{$dataSourceId}/datasets/{$datasetId}");

                if (!$response->successful()) {
                    continue;
                }

                $points = $response->json()['point'] ?? [];
                foreach ($points as $point) {
                    $values = $point['value'] ?? [];
                    $startTimeNanos = $point['startTimeNanos'] ?? 0;

                    if (count($values) < 2) {
                        continue;
                    }

                    $systolic = $values[0]['fpVal'] ?? null;
                    $diastolic = $values[1]['fpVal'] ?? null;

                    if (!$systolic || !$diastolic || $systolic <= 0 || $diastolic <= 0) {
                        continue;
                    }

                    $timestamp = Carbon::createFromTimestampMs((int) ($startTimeNanos / 1000000))
                        ->setTimezone(config('app.timezone'));

                    $allData[] = [
                        'systolic' => (int) round($systolic),
                        'diastolic' => (int) round($diastolic),
                        'timestamp' => $timestamp,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch blood pressure from source {$dataSourceId}: " . $e->getMessage());
            }
        }

        return $allData;
    }

    /**
     * Fetch body-temperature readings from Google Fit data sources.
     */
    private function fetchTemperatureFromSources(string $accessToken): array
    {
        $dataSources = $this->getDataSources($accessToken);
        $allData = [];

        $endTime = Carbon::now();
        $startTime = Carbon::now()->subDays(7);
        $startNanos = $startTime->timestamp * 1000000000;
        $endNanos = $endTime->timestamp * 1000000000;

        foreach ($dataSources as $source) {
            $dataType = $source['dataType']['name'] ?? '';
            if ($dataType !== 'com.google.body.temperature' && $dataType !== 'com.google.body_temperature') {
                continue;
            }

            $dataSourceId = $source['dataStreamId'];
            $datasetId = "{$startNanos}-{$endNanos}";

            try {
                $response = Http::withToken($accessToken)
                    ->get("https://www.googleapis.com/fitness/v1/users/me/dataSources/{$dataSourceId}/datasets/{$datasetId}");

                if (!$response->successful()) {
                    continue;
                }

                $points = $response->json()['point'] ?? [];
                foreach ($points as $point) {
                    $values = $point['value'] ?? [];
                    $startTimeNanos = $point['startTimeNanos'] ?? 0;

                    if (empty($values)) {
                        continue;
                    }

                    $temperature = $values[0]['fpVal'] ?? null;
                    if (!$temperature || $temperature < 35 || $temperature > 42) {
                        continue;
                    }

                    $timestamp = Carbon::createFromTimestampMs((int) ($startTimeNanos / 1000000))
                        ->setTimezone(config('app.timezone'));

                    $allData[] = [
                        'value' => round($temperature, 1),
                        'timestamp' => $timestamp,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch temperature from source {$dataSourceId}: " . $e->getMessage());
            }
        }

        return $allData;
    }

    /**
     * Fetch aggregated average heart rate for fallback when source data is absent.
     */
    private function fetchHeartRateAggregated(string $accessToken): ?int
    {
        $now = Carbon::now();
        $startOfDay = Carbon::today();

        try {
            $response = Http::withToken($accessToken)
                ->post('https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate', [
                    'aggregateBy' => [
                        ['dataTypeName' => 'com.google.heart_rate.bpm'],
                    ],
                    'bucketByTime' => [
                        'durationMillis' => 86400000,
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
     * Fetch aggregated day steps.
     */
    private function fetchStepsAggregated(string $accessToken): ?int
    {
        $now = Carbon::now();
        $startOfDay = Carbon::today();

        try {
            $response = Http::withToken($accessToken)
                ->post('https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate', [
                    'aggregateBy' => [
                        ['dataTypeName' => 'com.google.step_count.delta'],
                    ],
                    'bucketByTime' => [
                        'durationMillis' => 86400000,
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
