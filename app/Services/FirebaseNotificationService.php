<?php

namespace App\Services;

use Exception;
use Google_Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

/**
 * Sends FCM notifications via Firebase HTTP v1 API using a service-account JSON.
 */
class FirebaseNotificationService
{
    protected string $credentialsPath;

    protected string $projectId;

    public function __construct()
    {
        $credentials = (string) config(
            'services.firebase.credentials',
            storage_path('app/aqdi-5f575-ea6541aff561.json')
        );

        $this->credentialsPath = $this->resolveCredentialsPath($credentials);
        $this->projectId = (string) config('services.firebase.project_id', 'aqdi-5f575');
    }

    private function resolveCredentialsPath(string $path): string
    {
        if ($path !== '' && (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path))) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @throws Exception
     */
    public function sendNotification($title, $body)
    {
        if (! file_exists($this->credentialsPath)) {
            Log::error('Firebase credentials file missing', ['path' => $this->credentialsPath]);
            throw new Exception(trans('dashboard.firebase_credentials_missing'));
        }

        try {
            $client = new GoogleClient();
            $client->setAuthConfig($this->credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $client->refreshTokenWithAssertion();
            $accessToken = $client->getAccessToken();

            if (empty($accessToken['access_token'])) {
                throw new Exception(trans('dashboard.firebase_token_failed'));
            }

            $payload = [
                'message' => [
                    'topic' => 'all',
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'category' => 'new_offer',
                            ],
                        ],
                    ],
                ],
            ];

            $httpClient = new GuzzleClient();
            $response = $httpClient->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$accessToken['access_token'],
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]
            );

            Log::info('Firebase Notification Sent Successfully', [
                'title' => $title,
                'project_id' => $this->projectId,
                'response' => json_decode($response->getBody()->getContents(), true),
            ]);
        } catch (Exception $e) {
            Log::error('Firebase Notification Error: '.$e->getMessage(), [
                'title' => $title,
                'body' => $body,
                'project_id' => $this->projectId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
