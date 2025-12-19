<?php

namespace App\Services\PrintStrategies;

class PrintStrategyFactory
{
    /**
     * Create a print strategy by name
     */
    public static function create(string $strategyName): PrintStrategyInterface
    {
        return match (strtolower($strategyName)) {
            'browsershot' => new BrowsershotPrintStrategy(),
            'wkhtmltoimage' => new WkhtmltoimagePrintStrategy(),
            default => throw new \InvalidArgumentException("Unknown print strategy: {$strategyName}")
        };
    }

    /**
     * Get all available print strategies
     */
    public static function getAvailableStrategies(): array
    {
        $strategies = [
            'browsershot' => new BrowsershotPrintStrategy(),
            'wkhtmltoimage' => new WkhtmltoimagePrintStrategy(),
        ];

        return array_filter($strategies, fn($strategy) => $strategy->isAvailable());
    }

    /**
     * Get all strategy names
     */
    public static function getAllStrategyNames(): array
    {
        return ['browsershot', 'wkhtmltoimage'];
    }
}
