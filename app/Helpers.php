<?php

use App\Enums\SettingKey;
use App\Services\InventoryDailyAggregationService;
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
        if (app(InventoryDailyAggregationService::class)->dayStatus() === null) {
            throw new Exception('يجب فتح اليوم قبل إجراء أي عمليات على المخزون');
        }

        return true;
    }
}

if (! function_exists('currency_symbol')) {
    /**
     * Get the currency symbol (e.g., 'ج.م', '$', '€')
     */
    function currency_symbol(): string
    {
        return setting(SettingKey::CURRENCY_SYMBOL);
    }
}

if (! function_exists('currency_code')) {
    /**
     * Get the currency code (e.g., 'EGP', 'USD', 'EUR')
     */
    function currency_code(): string
    {
        return setting(SettingKey::CURRENCY_CODE);
    }
}

if (! function_exists('currency_name')) {
    /**
     * Get the currency name in Arabic (e.g., 'جنيه', 'دولار', 'يورو')
     */
    function currency_name(): string
    {
        return setting(SettingKey::CURRENCY_NAME);
    }
}

if (! function_exists('currency_decimals')) {
    /**
     * Get the number of decimal places for currency display
     */
    function currency_decimals(): int
    {
        return (int) setting(SettingKey::CURRENCY_DECIMALS);
    }
}

if (! function_exists('format_money')) {
    /**
     * Format a monetary amount with currency symbol
     *
     * @param  float  $amount  The amount to format
     * @param  int|null  $decimals  Number of decimal places (null = use system default)
     * @param  bool  $showSymbol  Whether to show currency symbol
     * @return string Formatted amount with currency
     */
    function format_money(float $amount, ?int $decimals = null, bool $showSymbol = true): string
    {
        $decimals = $decimals ?? currency_decimals();
        $formatted = number_format($amount, $decimals);

        if ($showSymbol) {
            return $formatted.' '.currency_symbol();
        }

        return $formatted;
    }
}
