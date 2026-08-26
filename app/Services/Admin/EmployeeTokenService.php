<?php

namespace App\Services\Admin;

use App\Models\Employee;
use App\Models\EmployeeRefreshToken;
use Illuminate\Support\Facades\DB;

class EmployeeTokenService
{
    /**
     * @return array{token: string, refresh_token: string, token_expires_in: int}
     */
    public function issueTokenPair(Employee $employee, bool $remembered): array
    {
        return DB::transaction(fn () => $this->createTokenPair($employee, $remembered));
    }

    /**
     * Rotate a valid refresh token. A concurrent reuse of the old token fails.
     *
     * @return array{
     *     employee: Employee,
     *     tokens: array{token: string, refresh_token: string, token_expires_in: int}
     * }|null
     */
    public function rotate(string $plainTextToken): ?array
    {
        return DB::transaction(function () use ($plainTextToken) {
            $refreshToken = EmployeeRefreshToken::query()
                ->where('token_hash', $this->hash($plainTextToken))
                ->lockForUpdate()
                ->first();

            if (! $refreshToken
                || $refreshToken->revoked_at
                || $refreshToken->expires_at->isPast()) {
                return null;
            }

            $employee = Employee::query()->find($refreshToken->employee_id);

            if (! $employee
                || ! $employee->is_active
                || ($employee->blocked_until && now()->lessThan($employee->blocked_until))) {
                $refreshToken->update(['revoked_at' => now()]);

                return null;
            }

            $refreshToken->update(['revoked_at' => now()]);

            return [
                'employee' => $employee,
                'tokens' => $this->createTokenPair($employee, $refreshToken->remembered),
            ];
        });
    }

    public function revoke(Employee $employee, ?string $plainTextToken): void
    {
        if (! filled($plainTextToken)) {
            return;
        }

        EmployeeRefreshToken::query()
            ->where('employee_id', $employee->getKey())
            ->where('token_hash', $this->hash($plainTextToken))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * @return array{token: string, refresh_token: string, token_expires_in: int}
     */
    private function createTokenPair(Employee $employee, bool $remembered): array
    {
        $accessTtl = max(1, (int) config('admin_auth.access_token_ttl_seconds', 15));
        $refreshTtlConfig = $remembered
            ? 'admin_auth.remembered_refresh_token_ttl_seconds'
            : 'admin_auth.refresh_token_ttl_seconds';
        $refreshTtl = max(1, (int) config($refreshTtlConfig));
        $plainTextRefreshToken = $this->generateRefreshToken();

        $accessToken = $employee->createToken(
            'admin-employee',
            ['*'],
            now()->addSeconds($accessTtl)
        )->plainTextToken;

        $employee->refreshTokens()->create([
            'token_hash' => $this->hash($plainTextRefreshToken),
            'remembered' => $remembered,
            'expires_at' => now()->addSeconds($refreshTtl),
        ]);

        return [
            'token' => $accessToken,
            'refresh_token' => $plainTextRefreshToken,
            'token_expires_in' => $accessTtl,
        ];
    }

    private function generateRefreshToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    private function hash(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
