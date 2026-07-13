<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
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
        $this->sendToTopic('all', (string) $title, (string) $body);
    }

    /**
     * Notify active employees about a newly created contract.
     * Sends to topic `employees` + each employee FCM token when available.
     */
    public function notifyEmployeesOfNewContract(Contract $contract): void
    {
        $title = 'عقد جديد';
        $body = 'تم إنشاء عقد جديد رقم '.str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT);
        $data = [
            'type' => 'new_contract',
            'contract_id' => (string) $contract->id,
            'contract_uuid' => (string) ($contract->uuid ?? ''),
            'contract_type' => (string) ($contract->contract_type ?? ''),
        ];

        $topic = (string) config('services.firebase.employees_topic', 'employees');

        try {
            $this->sendToTopic($topic, $title, $body, $data);
        } catch (\Throwable $e) {
            Log::warning('Firebase employee topic notification failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }

        $tokens = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->unique()
            ->filter()
            ->values();

        foreach ($tokens as $token) {
            try {
                $this->sendToToken((string) $token, $title, $body, $data);
            } catch (\Throwable $e) {
                Log::warning('Firebase employee token notification failed', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, string>  $data
     *
     * @throws Exception
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $this->sendMessage([
            'topic' => $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->stringifyData($data),
            'android' => [
                'priority' => 'high',
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'category' => 'new_contract',
                    ],
                ],
            ],
        ], $title, $body);
    }

    /**
     * @param  array<string, string>  $data
     *
     * @throws Exception
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $this->sendMessage([
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->stringifyData($data),
            'android' => [
                'priority' => 'high',
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'category' => 'new_contract',
                    ],
                ],
            ],
        ], $title, $body);
    }

    /**
     * @param  array<string, mixed>  $message
     *
     * @throws Exception
     */
    private function sendMessage(array $message, string $title, string $body): void
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

            $httpClient = new GuzzleClient();
            $response = $httpClient->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$accessToken['access_token'],
                        'Content-Type' => 'application/json',
                    ],
                    'json' => ['message' => $message],
                ]
            );

            Log::info('Firebase Notification Sent Successfully', [
                'title' => $title,
                'project_id' => $this->projectId,
                'target' => $message['topic'] ?? 'token',
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stringifyData(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[(string) $key] = is_scalar($value) || $value === null
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $out;
    }
}
