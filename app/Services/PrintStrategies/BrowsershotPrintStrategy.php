<?php

namespace App\Services\PrintStrategies;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;

class BrowsershotPrintStrategy implements PrintStrategyInterface
{
    /**
     * Convert HTML content to image using Browsershot
     *
     * @param string $html The HTML content to convert
     * @param int $width The width for the image
     * @param int $height The height for the image
     * @return string The path to the generated image file
     */
    public function generateImageFromHtml(string $html, int $width = 572, int $height = 1200): string
    {
        try {
            $tempImagePath = tempnam(sys_get_temp_dir(), 'browsershot_print_') . '.png';

            Log::info("Starting Browsershot image generation");
            $start = microtime(true);

            Browsershot::html($html)
                ->windowSize($width, $height)
                ->setOption('executablePath', '/usr/bin/chromium-browser')
                ->setEnvironmentOptions([
                    'XDG_CONFIG_HOME' => base_path('.puppeteer'),
                    'HOME' => base_path('.puppeteer')
                ])
                ->setRemoteInstance('127.0.0.1', 9222)
                ->dismissDialogs()
                ->ignoreHttpsErrors()
                ->fullPage()
                ->save($tempImagePath);

            $end = microtime(true);
            Log::info("Browsershot processing time: " . ($end - $start) . " seconds");

            return $tempImagePath;

        } catch (\Exception $e) {
            Log::error("Error generating image with Browsershot: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if Browsershot dependencies are available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            // Check if chromium-browser exists
            $chromiumPath = '/usr/bin/chromium-browser';
            if (!file_exists($chromiumPath)) {
                return false;
            }

            // Check if the remote Chrome instance is accessible
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true
                ]
            ]);

            $response = @file_get_contents('http://127.0.0.1:9222/json/version', false, $context);

            return $response !== false;

        } catch (\Exception $e) {
            Log::warning("Browsershot availability check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the strategy name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Browsershot';
    }
}
