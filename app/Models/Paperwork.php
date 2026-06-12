<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paperwork extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $guarded = ['id'];
    protected $appends = ['created_at_label', 'name_trans', 'icon_url'];

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    public function getCreatedAtLabelAttribute()
    {
        return date('Y-m-d H:i A', strtotime($this->created_at));
    }

    public function getNameTransAttribute()
    {
        return getTransAttribute($this, 'name');
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? url("storage/{$this->icon}") : null;
    }
}
