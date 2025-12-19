<?php

namespace App\Services\PrintStrategies;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class WkhtmltoimagePrintStrategy implements PrintStrategyInterface
{

    public function __construct()
    {
    }

    private function getWkhtmltoimagePath(): string
    {
        if (PHP_OS_FAMILY === 'Windows' || strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return "C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltoimage.exe";
        }

        // On Linux/macOS assume wkhtmltoimage is available in PATH
        return '/usr/local/bin/wkhtmltoimage';
    }

    /**
     * Convert HTML content to image using wkhtmltoimage
     *
     * @param string $html The HTML content to convert
     * @param int $width The width for the image
     * @param int $height The height for the image
     * @return string The path to the generated image file
     */
    public function generateImageFromHtml(string $html, int $width = 572, int $height = 1200): string
    {
        try {
            // Create temporary files
            $tempHtmlPath = tempnam(sys_get_temp_dir(), 'wkhtml_input_') . '.html';
            $tempImagePath = tempnam(sys_get_temp_dir(), 'wkhtml_print_') . '.png';

            // Write HTML to temporary file
            file_put_contents($tempHtmlPath, $html);

            Log::info("Starting wkhtmltoimage image generation");
            $start = microtime(true);

            // Build wkhtmltoimage command
            $command = [
                // 'wkhtmltoimage',
                $this->getWkhtmltoimagePath(),
                '--width', (string) $width,
                // '--height', (string) $height,
                '--format', 'png',
                '--quiet',
                '--quality', '100',
                '--encoding', 'UTF-8',
                '--disable-smart-width',
                '--disable-javascript',
                '--javascript-delay', '0',
                '--load-error-handling', 'ignore',
                '--load-media-error-handling', 'ignore',
                $tempHtmlPath,
                $tempImagePath
            ];

            // Execute the command
            $result = Process::run(implode(' ', array_map('escapeshellarg', $command)));

            $end = microtime(true);
            Log::info("wkhtmltoimage processing time: " . ($end - $start) . " seconds");

            // Clean up HTML file
            unlink($tempHtmlPath);

            // Check if the command was successful
            if (!$result->successful()) {
                $error = $result->errorOutput() ?: $result->output();
                Log::error("wkhtmltoimage command failed: " . $error);
                throw new \Exception("wkhtmltoimage failed: " . $error);
            }

            // Verify that the image file was created
            if (!file_exists($tempImagePath) || filesize($tempImagePath) === 0) {
                throw new \Exception("wkhtmltoimage did not generate a valid image file");
            }

            Log::info("wkhtmltoimage image generated successfully");

            return $tempImagePath;

        } catch (\Exception $e) {
            // Clean up temporary files if they exist
            if (isset($tempHtmlPath) && file_exists($tempHtmlPath)) {
                unlink($tempHtmlPath);
            }
            if (isset($tempImagePath) && file_exists($tempImagePath)) {
                unlink($tempImagePath);
            }

            Log::error("Error generating image with wkhtmltoimage: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if wkhtmltoimage is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            if (empty($this->wkhtmltoimagePath)) {
                return false;
            }

            // Test if wkhtmltoimage is executable and working
            $result = Process::run([$this->wkhtmltoimagePath, '--version']);

            return $result->successful();

        } catch (\Exception $e) {
            Log::warning("wkhtmltoimage availability check failed: " . $e->getMessage());
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
        return 'wkhtmltoimage';
    }

}
