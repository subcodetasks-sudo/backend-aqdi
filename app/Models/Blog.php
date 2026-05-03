<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;
    
    protected $fillable = ['description', 'image', 'slug', 'title', 'is_active', 'meta_title', 'meta_description', 'status', 'publish_at'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($blog) {
            if (!$blog->slug) {
                $slug = $blog->title;
                $slug = trim($slug);
                $slug = mb_strtolower($slug, "UTF-8");

            
                $slug = str_replace(['/', '\\'], '-', $slug);
                $slug = preg_replace("/[^a-z0-9_\sءاأإآؤئبتثجحخدذرزسشصضطظعغفقكلمنهويةى]/u", "", $slug);
                $slug = preg_replace("/[\s-]+/", " ", $slug);
                $slug = preg_replace("/[\s_]/", '-', $slug);
                $blog->slug = $slug;
            }
        });
    }
}
