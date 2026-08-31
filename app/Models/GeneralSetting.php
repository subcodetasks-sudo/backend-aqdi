<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    public const WEBSITE_STATUS = 'website_status';

    public const MOBILE_STATUS = 'mobile_status';

    protected $fillable = [
        'key',
        'label_ar',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public static function syncFromConfig(): void
    {
        foreach (config('general_settings', []) as $key => $definition) {
            static::query()->firstOrCreate(
                ['key' => $key],
                [
                    'label_ar' => $definition['label_ar'] ?? $key,
                    'enabled' => (bool) ($definition['default'] ?? true),
                ]
            );
        }
    }

    public static function isEnabled(string $key, bool $default = true): bool
    {
        $row = static::query()->where('key', $key)->first();
        if ($row === null) {
            return (bool) config("general_settings.{$key}.default", $default);
        }

        return (bool) $row->enabled;
    }

    public static function setEnabled(string $key, bool $enabled): self
    {
        $definition = config("general_settings.{$key}", []);
        $row = static::query()->firstOrCreate(
            ['key' => $key],
            [
                'label_ar' => $definition['label_ar'] ?? $key,
                'enabled' => $enabled,
            ]
        );
        $row->update(['enabled' => $enabled]);

        return $row->fresh();
    }
}
