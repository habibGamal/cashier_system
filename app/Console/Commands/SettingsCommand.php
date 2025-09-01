<?php

namespace App\Console\Commands;

use App\Services\SettingsService;
use Illuminate\Console\Command;

class SettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'settings {action} {key?} {value?}';

    /**
     * The console command description.
     */
    protected $description = 'Manage application settings (get, set, list, reset)';

    /**
     * Execute the console command.
     */
    public function handle(SettingsService $settingsService): int
    {
        $action = $this->argument('action');
        $key = $this->argument('key');
        $value = $this->argument('value');

        switch ($action) {
            case 'get':
                if (!$key) {
                    $this->error('Key is required for get action.');
                    return self::FAILURE;
                }

                $setting = $settingsService->get($key);
                if ($setting === null) {
                    $this->warn("Setting '{$key}' not found.");
                    return self::FAILURE;
                }

                $this->info("Setting '{$key}': {$setting}");
                break;

            case 'set':
                if (!$key || $value === null) {
                    $this->error('Key and value are required for set action.');
                    return self::FAILURE;
                }

                $settingsService->set($key, $value);
                $this->info("Setting '{$key}' has been set to '{$value}'.");
                break;

            case 'list':
                $settings = $settingsService->all();
                if (empty($settings)) {
                    $this->info('No settings found.');
                    return self::SUCCESS;
                }

                $this->table(['Key', 'Value'], collect($settings)->map(function ($value, $key) {
                    return [$key, $value];
                })->toArray());
                break;

            case 'reset':
                $defaults = $settingsService->getDefaults();
                $settingsService->setMultiple($defaults);
                $this->info('Settings have been reset to default values.');
                break;

            default:
                $this->error("Unknown action '{$action}'. Available actions: get, set, list, reset");
                return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
