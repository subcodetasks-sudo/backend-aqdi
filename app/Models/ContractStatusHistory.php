<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractStatusHistory extends Model
{
    protected $fillable = [
        'contract_id',
        'status_type',
        'status_id',
        'status',
        'status_label',
        'status_color',
        'status_description',
        'source',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
