<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title_ar',
        'title_en',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['title_trans', 'created_at_label', 'permissions_count'];

    /**
     * Get translated title based on current locale
     */
    public function getTitleTransAttribute()
    {
        return \getTransAttribute($this, 'title');
    }

    /**
     * Get formatted created at label
     */
    public function getCreatedAtLabelAttribute()
    {
        return date('Y-m-d H:i A', strtotime($this->created_at));
    }

    /**
     * Get permissions count
     */
    public function getPermissionsCountAttribute()
    {
        if (array_key_exists('permissions_count', $this->attributes)) {
            return (int) $this->attributes['permissions_count'];
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->count();
        }

        return $this->permissions()->count();
    }

    /**
     * Relationship: Role has many permissions
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    /**
     * Relationship: Role has many employees
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'role_id');
    }

    /**
     * Whether this role bypasses individual permission grants.
     */
    public function isFullAccess(): bool
    {
        $configuredNames = array_map(
            'strtolower',
            (array) config('permissions.full_access_roles', ['admin'])
        );
        $name = strtolower((string) $this->name);

        if ($name !== '' && in_array($name, $configuredNames, true)) {
            return true;
        }

        $titleEn = strtolower((string) $this->title_en);

        return str_contains($titleEn, 'super admin')
            || $titleEn === 'system admin'
            || (string) $this->title_ar === 'مدير النظام';
    }
}
