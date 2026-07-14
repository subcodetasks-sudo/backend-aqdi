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
        'electricity_meter_fee_commercial_tenant' => 'decimal:2',
        'electricity_meter_fee_housing_tenant' => 'decimal:2',
        'water_meter_fee_commercial_tenant' => 'decimal:2',
        'water_meter_fee_housing_tenant' => 'decimal:2',
    ];
}
