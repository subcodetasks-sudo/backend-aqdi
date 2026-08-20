<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $fillable = [
        'key',
        'label_ar',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
