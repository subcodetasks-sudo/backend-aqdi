<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    use HasFactory;

    protected $table = 'content_pages';

    protected $guarded = ['id'];

    protected $casts = [
        'content_json' => 'array',
    ];
}
