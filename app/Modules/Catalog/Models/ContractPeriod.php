<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Models\Concerns\HasCreatedAtLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractPeriod extends Model
{
    use HasCreatedAtLabel;
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['created_at_label', 'note_trans'];

    public function getNoteTransAttribute()
    {
        return getTransAttribute($this, 'note');
    }
}
