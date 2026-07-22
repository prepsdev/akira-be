<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    protected $fillable = [
        'setting_name',
        'is_enabled',
        'setting_value',
        'description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public static function isEnabled(string $settingName): bool
    {
        $setting = self::where('setting_name', $settingName)->first();
        return $setting ? $setting->is_enabled : false;
    }

    public static function getValue(string $settingName, $default = null)
    {
        $setting = self::where('setting_name', $settingName)->first();
        return $setting ? $setting->setting_value : $default;
    }
}
