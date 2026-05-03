<?php

namespace App\Services;
use GuzzleHttp\Client as GuzzleClient;
use Google_Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use Exception;


/**
 * Class FirebaseNotificationService
 
 * It interacts with Firebase using a service account's credentials and sends notifications
 
 * The credentials used for Firebase authorization are retrieved from a JSON file stored
 * 
 * in the application's storage path, which is configured via `$this->credentialsPath`.
 * 
 * It uses Google API client and Guzzle client for making HTTP requests to FCM.
 *
 * Methods:
 * - sendNotification(string $title, string $body): Sends a notification to the FCM topic
 *   'all' with the specified title and body.
 */
class FirebaseNotificationService
{
    protected $credentialsPath;

    public function __construct()
    {
        $this->credentialsPath =  storage_path('app/contrat-77651-348b72e9ab54.json');
    }

    /**
     * Send a notification to Firebase using FCM
     *
     * @param string $title
     * @param string $body
     * @return void
     * @throws Exception
     */
    public function sendNotification($title, $body)
    {
        if (!file_exists($this->credentialsPath)) {
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
            $response = $httpClient->post('https://fcm.googleapis.com/v1/projects/contrat-77651/messages:send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken['access_token'],
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            Log::info('Firebase Notification Sent Successfully', [
                'title' => $title,
                'response' => json_decode($response->getBody()->getContents(), true),
            ]);
        } catch (Exception $e) {
            Log::error('Firebase Notification Error: ' . $e->getMessage(), [
                'title' => $title,
                'body' => $body,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
