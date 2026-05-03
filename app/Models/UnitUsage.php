<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitUsage extends Model
{
 
    use HasFactory;
    
        /*
        |--------------------------------------------------------------------------
        | GLOBAL VARIABLES
        |--------------------------------------------------------------------------
        */
        
        protected $table='unit_usages';
    
        protected $guarded = ['id'];
        protected $appends = ['created_at_label', 'name_trans'];
    
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
            return \getTransAttribute($this, 'name');
        }
    }