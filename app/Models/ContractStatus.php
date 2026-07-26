<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractStatus extends Model
{
    use HasFactory;

    /** جديد — default status for contracts arriving from the app/website (not received yet). */
    public const NEW_ID = 1;

    /** مسترجع — return orders list / refund flow. */
    public const RETURN_ID = 2;

    /** مستلم — set automatically when an employee receives the contract. */
    public const RECEIVED_ID = 6;

    protected $fillable = [
        'name',
        'color',
        'color_text',
        'description',
        'client_explanation',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = ['created_at_label'];

    /**
     * Get formatted created at label
     */
    public function getCreatedAtLabelAttribute()
    {
        return date('Y-m-d H:i A', strtotime($this->created_at));
    }

    
}
