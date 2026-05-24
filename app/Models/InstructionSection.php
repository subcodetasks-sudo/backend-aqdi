<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstructionSection extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(InstructionSectionImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
