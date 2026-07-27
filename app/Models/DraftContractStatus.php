<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DraftContractStatus extends Model
{
    use HasFactory;

    /** Default draft status name for newly created drafts. */
    public const NEW_NAME = 'جديد';

    protected $fillable = [
        'name',
        'color',
        'color_text',
        'description',
        'client_explanation',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['created_at_label'];

    public function getCreatedAtLabelAttribute()
    {
        return $this->created_at
            ? date('Y-m-d H:i A', strtotime($this->created_at))
            : null;
    }

    public static function newStatusId(): ?int
    {
        $id = static::query()->where('name', self::NEW_NAME)->value('id');

        return $id !== null ? (int) $id : null;
    }
}
