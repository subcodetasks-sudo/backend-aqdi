<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingContract extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'realestate' => 'boolean',
        'contract' => 'boolean',
    ];
}
