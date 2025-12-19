<?php

namespace App\Services\PrintStrategies;

use App\Models\Order;

interface PrintStrategyInterface
{
    /**
     * Convert HTML content to image
     *
     * @param string $html The HTML content to convert
     * @param int $width The width for the image
     * @param int $height The height for the image
     * @return string The path to the generated image file
     */
    public function generateImageFromHtml(string $html, int $width = 572, int $height = 1200): string;

    /**
     * Check if the strategy's dependencies are available
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get the strategy name
     *
     * @return string
     */
    public function getName(): string;
}
