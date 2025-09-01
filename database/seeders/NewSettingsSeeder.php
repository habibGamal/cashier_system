<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Enums\SettingKey;
use Illuminate\Database\Seeder;

class NewSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $newSettings = [
            SettingKey::RESTAURANT_NAME->value => SettingKey::RESTAURANT_NAME->defaultValue(),
            SettingKey::RESTAURANT_PRINT_LOGO->value => SettingKey::RESTAURANT_PRINT_LOGO->defaultValue(),
            SettingKey::RESTAURANT_OFFICIAL_LOGO->value => SettingKey::RESTAURANT_OFFICIAL_LOGO->defaultValue(),
            SettingKey::NODE_TYPE->value => SettingKey::NODE_TYPE->defaultValue(),
            SettingKey::MASTER_NODE_LINK->value => SettingKey::MASTER_NODE_LINK->defaultValue(),
        ];

        foreach ($newSettings as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->command->info('New settings have been seeded successfully.');
    }
}
