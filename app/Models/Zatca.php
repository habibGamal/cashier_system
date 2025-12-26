<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zatca extends Model
{
    protected $fillable = ['key', 'value'];

    public static function setValue($key, $value)
    {
        // JSON encode arrays and objects for storage
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function getValue($key, $default = null)
    {
        $value = static::where('key', $key)->value('value') ?? $default;

        // Try to decode JSON values back to arrays/objects
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }
}
