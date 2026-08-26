<?php

namespace App\Models;

use App\Services\Admin\RolePermissionResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = ['employee'];

    protected $fillable = [
        'name',
        'base_salary',
        'phone',
        'is_active',
        'is_online',
        'email',
        'profile_image',
        'facebook',
        'instagram',
        'whatsapp',
        'snapchat',
        'tiktok',
        'twitter',
        'blocked_until',
        'password',
        'role',
        'role_id',
        'reason_of_block',
        'fcm_token',

    ];

    protected $hidden = ['password'];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'base_salary' => 'decimal:2',
    ];

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function notes()
    {
        return $this->hasMany(NotesEmployee::class);
    }

    public function receivedContract()
    {
        return $this->hasMany(ReceivedContract::class);
    }

    public function refundableContract()
    {
        return $this->hasMany(RefundableContract::class);
    }

    public function contractPaidByEmployees()
    {
        return $this->hasMany(ContractPaidByEmployee::class, 'employee_id');
    }

    public function authHistory()
    {
        return $this->hasMany(AuthHistory::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(EmployeeRefreshToken::class);
    }

    /**
     * Relationship: Employee belongs to a role
     */
    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Role slug from `roles.name` (falls back to legacy `employees.role` text).
     */
    public function resolvedRoleName(): ?string
    {
        if ($this->roleRelation) {
            return $this->roleRelation->name;
        }

        $legacy = $this->getRawOriginal('role');

        return is_string($legacy) && $legacy !== '' ? $legacy : null;
    }

    /**
     * Role display title from `roles` (falls back to legacy text column).
     */
    public function resolvedRoleTitle(): ?string
    {
        if ($this->roleRelation) {
            return $this->roleRelation->title_trans
                ?? $this->roleRelation->title_ar
                ?? $this->roleRelation->name;
        }

        return $this->resolvedRoleName();
    }

    /**
     * Whether this employee's role has the given "section.action" permission
     * (e.g. "analytics.view"). Employees without a linked role are denied,
     * except system admins who have full access.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSystemAdmin()) {
            return true;
        }

        if (! $this->roleRelation) {
            return false;
        }

        $this->loadMissing('roleRelation.permissions');

        return $this->roleRelation->permissions
            ->where('is_active', true)
            ->contains('name', $permissionName);
    }

    /**
     * System admin / super-admin roles are not limited by the permission matrix.
     */
    public function isSystemAdmin(): bool
    {
        $this->loadMissing('roleRelation');

        if ($this->roleRelation?->isFullAccess()) {
            return true;
        }

        $names = array_map('strtolower', (array) config('permissions.full_access_roles', ['admin']));
        $roleName = strtolower((string) $this->resolvedRoleName());

        return $roleName !== '' && in_array($roleName, $names, true);
    }

    /**
     * @return array{names: array<int, string>, matrix: array<string, array<int, string>>}
     */
    public function effectivePermissions(): array
    {
        return app(RolePermissionResolver::class)->effectivePermissionsFor($this);
    }
}
