<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractWhatsApp extends Model
{
    use HasFactory;

    protected $table = 'contract_whatsapp';

    protected $fillable = [
        'mobile_number',
        'addition_date',
        'contract_type',
        'without',
        'derived_from_bank',
        'waqf',
        'paper_deed',
        'paper_deed_2',
        'is_documented',
        'contract_duration',
        'amount_paid_by_client',
        'rental_fees',
        'notes',
        'time',
        'date',
        'is_complete',
    ];

    protected $casts = [
        'addition_date' => 'datetime',
        'date' => 'date',
        'time' => 'string',
        'without' => 'boolean',
        'derived_from_bank' => 'boolean',
        'waqf' => 'boolean',
        'paper_deed' => 'boolean',
        'paper_deed_2' => 'boolean',
        'is_documented' => 'boolean',
        'is_complete' => 'boolean',
        'contract_duration' => 'integer',
        'amount_paid_by_client' => 'decimal:2',
        'rental_fees' => 'decimal:2',
    ];

    /**
     * Get the contract period relationship
     */
    public function contractPeriod()
    {
        return $this->belongsTo(ContractPeriod::class, 'contract_duration');
    }
}
