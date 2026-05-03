<?php

namespace App\Models;

use App\Models\Contract;
use App\Models\RealEstate;
use App\Models\UnitType;
use App\Models\UsageUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitsReal extends Model
{
    use HasFactory;
    protected $table='real_units';
    protected $fillable = [
            'user_id','sub_delay',
            'tootal_rooms', 'The_number_of_toilets','split_ac','window_ac' ,'real_estates_units',
            'The_number_of_halls', 'The_number_of_kitchens', 'property_city_id', 'unit_area','water_meter_number','electricity_meter_number',
            'unit_number','unit_usage_id','unit_type_id','floor_number', 'real_estates_units_id','Number_parking_spaces',
            'kitchen_tank', 'furnished', 'type_furnished', 'electricity_meter', 'water_meter',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function realEstate()
    {
      return $this->belongsTo(RealEstate::class,'real_estates_units_id');        
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'real_units_id');
    }
    
    
    public function unitUsage()
    {
        return $this->belongsTo(UsageUnit::class, 'unit_usage_id');
    }

    
    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }
}
