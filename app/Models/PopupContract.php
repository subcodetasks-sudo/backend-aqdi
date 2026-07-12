<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopupContract extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'popup_status_contract' => 'boolean',
        'popup_status_realestate' => 'boolean',
    ];
}
