<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
 
class Employee extends Authenticatable 
{
    use HasFactory, Notifiable, HasApiTokens; 
 
  
    protected $guarded=['employee'];
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

    public function authHistory()
    {
        return $this->hasMany(AuthHistory::class);
    }

    /**
     * Relationship: Employee belongs to a role
     */
    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

   
}
