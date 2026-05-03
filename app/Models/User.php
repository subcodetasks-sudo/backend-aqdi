<?php

namespace App\Models;

use App\Models\RealEstate;
use App\Models\UnitsReal;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;


    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $guarded = ['id'];
    protected $appends = ['name', 'photo_path', 'status', 'fcm_token', 'created_at_label', 'mobile', 'email', 'password'];

 
    public function getCreatedAtLabelAttribute()
    {
        return date('Y-m-d H:i A', strtotime($this->created_at));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**1
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    /*
    |--------------------------------------------------------------------------
    | BOOTS
    |----------------------b----------------------------------------------------
    */

    public static function boot()
    {
   
        parent::boot();
        self::creating(function ($model) {
            $model->verification_code = User::generateVerificationCode();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    
    public function getMobileAttribute($value)
    {
        return $this->attributes['mobile'] ?? null;   
    }
     
    public function getEmailAttribute($value)
    {
    return $this->attributes['email'] ?? null;   
    }
    public function isVerified()
    {
        return $this->email_verified_at != null;
    }

    public function isActive()
    {
        return $this->is_active == 1;
    }

    public static function generateVerificationCode()
    {
        return mt_rand(1000, 9999);
    }

    public static function generateResetPasswordCode()
    {
        return mt_rand(1000, 9999);
    }
    
    public function getSingle($id)
    {
        return User::find($id);
    }
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

   
    
    public function realEstate()
    {
      return $this->hasMany(RealEstate::class);        
    }

    public function unitReal()
    {
      return $this->hasMany(UnitsReal::class);        
    }

     public function devicesToken()
    {
        return $this->hasMany(Device_token::class);  
    }
    
    
     public function payments()
    {
        return $this->hasMany(Payment::class);  
    }

    public function notifications()
    {
        return $this->hasMany(Offer::class, 'user_id', 'id');
    }
    
    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    public function getNameAttribute()
    {
        return $this->fname . ' ' . $this->lname;
    }

    public function getPhotoPathAttribute()
    {
        return isset($this->photo) ? getFilePath($this->photo) : '';
    }

    public function getStatusAttribute()
    {
        return $this->is_active == 1 ? 'active' : 'inactive';
    }

    
     /*
    |--------------------------------------------------------------------------
    | relation
    |--------------------------------------------------------------------------
    */

     public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
    public function getFcmTokenAttribute()
    {
        return $this->attributes['fcm_token'] ?? null;   
    }
       
    public function authHistory()
    {
        return $this->hasMany(AuthHistory::class);
    }
    public function fullname()
    {
        return $this->fname . ' ' . $this->lname;
    }

    

}