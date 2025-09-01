<?php

use App\Services\InventoryDailyAggregationService;
use App\Enums\SettingKey;
use App\Services\SettingsService;

/*
 * Here you can define your own helper functions.
 * Make sure to use the `function_exists` check to not declare the function twice.
 */

if (! function_exists('setting')) {
    /**
     * Get a setting value by key enum
     */
    function setting(SettingKey $key): string
    {
        return app(abstract: SettingsService::class)->get($key->value, $key->defaultValue());
    }
}

if (! function_exists('example')) {
    function example(): string
    {
        return 'This is an example function you can use in your project.';
    }
}

if (! function_exists('shouldDayBeOpen')) {
    /**
     * Check if the current day is marked as closed
     */
    function shouldDayBeOpen(): bool
    {
        if (app(InventoryDailyAggregationService::class)->dayStatus() === null)
            throw new Exception('يجب فتح اليوم قبل إجراء أي عمليات على المخزون');
        return true;
    }
}
