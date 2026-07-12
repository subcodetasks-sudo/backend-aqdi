<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DraftContractStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'color_text',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = ['created_at_label'];

    public function getCreatedAtLabelAttribute()
    {
        return $this->created_at
            ? date('Y-m-d H:i A', strtotime($this->created_at))
            : null;
    }
}
