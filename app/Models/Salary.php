<?php

namespace App\Models;

use App\Enums\Month;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
     protected $fillable = [
        'employee_id',
        'month',
        'is_paid',
    ];


 
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
