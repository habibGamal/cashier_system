<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsService = app(SettingsService::class);
    }

    public function test_can_get_default_website_url(): void
    {
        $url = $this->settingsService->getWebsiteLink();
        $this->assertEquals('http://127.0.0.1:38794', $url);
    }

    public function test_can_get_default_cashier_printer_ip(): void
    {
        $ip = $this->settingsService->getCashierPrinterIp();
        $this->assertEquals('192.168.1.100', $ip);
    }

    public function test_can_set_and_get_setting(): void
    {
        $this->settingsService->set('test_key', 'test_value');
        $value = $this->settingsService->get('test_key');

        $this->assertEquals('test_value', $value);
        $this->assertDatabaseHas('settings', [
            'key' => 'test_key',
            'value' => 'test_value'
        ]);
    }

    public function test_can_set_multiple_settings(): void
    {
        $settings = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];

        $this->settingsService->setMultiple($settings);

        $this->assertEquals('value1', $this->settingsService->get('key1'));
        $this->assertEquals('value2', $this->settingsService->get('key2'));
    }

    public function test_get_all_settings(): void
    {
        Setting::create(['key' => 'test1', 'value' => 'value1']);
        Setting::create(['key' => 'test2', 'value' => 'value2']);

        $all = $this->settingsService->all();

        $this->assertArrayHasKey('test1', $all);
        $this->assertArrayHasKey('test2', $all);
        $this->assertEquals('value1', $all['test1']);
        $this->assertEquals('value2', $all['test2']);
    }

    public function test_validation_methods(): void
    {
        // Valid URL
        $this->assertTrue($this->settingsService->validate(
            SettingsService::WEBSITE_URL,
            'https://example.com'
        ));

        // Invalid URL
        $this->assertFalse($this->settingsService->validate(
            SettingsService::WEBSITE_URL,
            'not-a-url'
        ));

        // Valid IP
        $this->assertTrue($this->settingsService->validate(
            SettingsService::CASHIER_PRINTER_IP,
            '192.168.1.100'
        ));

        // Invalid IP
        $this->assertFalse($this->settingsService->validate(
            SettingsService::CASHIER_PRINTER_IP,
            '300.300.300.300'
        ));
    }

    public function test_setting_model_static_methods(): void
    {
        // Test setValue and getValue
        Setting::setValue('static_test', 'static_value');
        $this->assertEquals('static_value', Setting::getValue('static_test'));

        // Test hasKey
        $this->assertTrue(Setting::hasKey('static_test'));
        $this->assertFalse(Setting::hasKey('non_existent_key'));

        // Test getAllAsArray
        Setting::setValue('key1', 'value1');
        Setting::setValue('key2', 'value2');

        $array = Setting::getAllAsArray();
        $this->assertArrayHasKey('key1', $array);
        $this->assertArrayHasKey('key2', $array);
        $this->assertArrayHasKey('static_test', $array);
    }
}
