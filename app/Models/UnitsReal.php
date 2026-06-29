<?php

namespace App\Models;

use App\Models\Contract;
use App\Models\RealEstate;
use App\Models\UnitType;
use App\Models\UsageUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
            'number_of_rooms', 'The_number_of_the_toilet', 'Services', 'is_deleted',
    ];

    /**
     * Map API unit fields to columns that exist on real_units.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function attributesForApi(array $data): array
    {
        $table = (new self)->getTable();

        if (! Schema::hasColumn($table, 'tootal_rooms') && array_key_exists('tootal_rooms', $data)) {
            $data['number_of_rooms'] = $data['tootal_rooms'];
            unset($data['tootal_rooms']);
        }

        if (! Schema::hasColumn($table, 'The_number_of_toilets') && array_key_exists('The_number_of_toilets', $data)) {
            $data['The_number_of_the_toilet'] = $data['The_number_of_toilets'];
            unset($data['The_number_of_toilets']);
        }

        foreach (['window_ac', 'split_ac'] as $column) {
            if (! Schema::hasColumn($table, $column)) {
                unset($data[$column]);
            }
        }

        if (Schema::hasColumn($table, 'Services') && ! array_key_exists('Services', $data)) {
            $data['Services'] = 0;
        }

        if (Schema::hasColumn($table, 'is_deleted') && ! array_key_exists('is_deleted', $data)) {
            $data['is_deleted'] = 0;
        }

        return $data;
    }


    protected $casts = [
        'kitchen_tank' => 'boolean',
        'furnished' => 'boolean',
        'type_furnished' => 'boolean',
        'electricity_meter' => 'boolean',
        'water_meter' => 'boolean',
    ];

    public function getTootalRoomsAttribute(?string $value): ?string
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        $legacy = $this->attributes['number_of_rooms'] ?? null;

        return $legacy !== null && $legacy !== '' ? (string) $legacy : null;
    }

    public function getTheNumberOfToiletsAttribute(?string $value): ?string
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        $legacy = $this->attributes['The_number_of_the_toilet'] ?? null;

        return $legacy !== null && $legacy !== '' ? (string) $legacy : null;
    }

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
