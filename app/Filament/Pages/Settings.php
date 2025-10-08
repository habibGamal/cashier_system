<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Exception;
use App\Filament\Traits\AdminAccess;
use App\Services\SettingsService;
use App\Services\PrintService;
use App\Services\BranchService;
use App\Models\Category;
use App\Enums\SettingKey;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class Settings extends Page implements HasForms
{
    use InteractsWithForms, AdminAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected string $view = 'filament.pages.settings';
    protected static ?string $navigationLabel = 'الإعدادات';
    protected static ?string $title = 'إعدادات النظام';
    protected static string | \UnitEnum | null $navigationGroup = 'إدارة النظام';
    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $settingsService = app(SettingsService::class);
        $settings = $settingsService->all();
        $defaults = $settingsService->getDefaults();

        // Initialize data with defaults first
        $this->data = $defaults;

        // Override with existing settings, ensuring they are strings
        foreach ($settings as $key => $value) {
            if (in_array($key, array_keys($defaults))) {
                $this->data[$key] = is_array($value) ? json_encode($value) : (string) $value;
            }
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('الإعدادات العامة')
                    ->description('إعدادات النظام الأساسية')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make(SettingKey::WEBSITE_URL->value)
                                    ->label(SettingKey::WEBSITE_URL->label())
                                    ->helperText(SettingKey::WEBSITE_URL->helperText())
                                    ->url()
                                    ->required()
                                    ->placeholder(SettingKey::WEBSITE_URL->placeholder()),

                                TextInput::make(SettingKey::CASHIER_PRINTER_IP->value)
                                    ->label(SettingKey::CASHIER_PRINTER_IP->label())
                                    ->helperText(SettingKey::CASHIER_PRINTER_IP->helperText())
                                    ->required()
                                    ->placeholder(SettingKey::CASHIER_PRINTER_IP->placeholder()),

                                TextInput::make(SettingKey::SCALE_BARCODE_PREFIX->value)
                                    ->label(SettingKey::SCALE_BARCODE_PREFIX->label())
                                    ->helperText(SettingKey::SCALE_BARCODE_PREFIX->helperText())
                                    ->required()
                                    ->maxLength(4)
                                    ->placeholder(SettingKey::SCALE_BARCODE_PREFIX->placeholder()),
                            ]),

                        Textarea::make(SettingKey::RECEIPT_FOOTER->value)
                            ->label(SettingKey::RECEIPT_FOOTER->label())
                            ->helperText(SettingKey::RECEIPT_FOOTER->helperText())
                            ->placeholder(SettingKey::RECEIPT_FOOTER->placeholder())
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                Section::make('إعدادات المطعم')
                    ->description('معلومات المطعم الأساسية')
                    ->icon('heroicon-m-building-storefront')
                    ->schema([
                        TextInput::make(SettingKey::RESTAURANT_NAME->value)
                            ->label(SettingKey::RESTAURANT_NAME->label())
                            ->helperText(SettingKey::RESTAURANT_NAME->helperText())
                            ->required()
                            ->placeholder(SettingKey::RESTAURANT_NAME->placeholder())
                            ->maxLength(255),

                        Grid::make(2)
                            ->schema([
                                FileUpload::make(SettingKey::RESTAURANT_PRINT_LOGO->value)
                                    ->label(SettingKey::RESTAURANT_PRINT_LOGO->label())
                                    ->helperText(SettingKey::RESTAURANT_PRINT_LOGO->helperText())
                                    ->image()
                                    ->imageEditor()
                                    ->directory('logos')
                                    ->visibility('public')
                                    ->moveFiles()
                                    ->acceptedFileTypes(['image/png'])
                                    ->maxSize(2048) // 2MB limit
                                    ->afterStateUpdated(function ($state) {
                                        if ($state && is_string($state)) {
                                            // Copy to public/images/logo.png
                                            $sourcePath = Storage::disk('public')->path($state);
                                            $destPath = public_path('images/logo.png');

                                            if (file_exists($sourcePath)) {
                                                @mkdir(dirname($destPath), 0755, true);
                                                copy($sourcePath, $destPath);
                                            }
                                        }
                                    }),

                                FileUpload::make(SettingKey::RESTAURANT_OFFICIAL_LOGO->value)
                                    ->label(SettingKey::RESTAURANT_OFFICIAL_LOGO->label())
                                    ->helperText(SettingKey::RESTAURANT_OFFICIAL_LOGO->helperText())
                                    ->image()
                                    ->imageEditor()
                                    ->directory('logos')
                                    ->visibility('public')
                                    ->moveFiles()
                                    ->acceptedFileTypes(['image/jpeg', 'image/jpg'])
                                    ->maxSize(2048) // 2MB limit
                                    ->afterStateUpdated(function ($state) {
                                        if ($state && is_string($state)) {
                                            // Copy to public/images/logo.jpg
                                            $sourcePath = Storage::disk('public')->path($state);
                                            $destPath = public_path('images/logo.jpg');

                                            if (file_exists($sourcePath)) {
                                                @mkdir(dirname($destPath), 0755, true);
                                                copy($sourcePath, $destPath);
                                            }
                                        }
                                    }),
                            ]),
                    ]),

                Section::make('إدارة الفروع')
                    ->description('إعدادات شبكة الفروع والاتصال')
                    ->icon('heroicon-m-building-office')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make(SettingKey::NODE_TYPE->value)
                                    ->label(SettingKey::NODE_TYPE->label())
                                    ->helperText(SettingKey::NODE_TYPE->helperText())
                                    ->options([
                                        'master' => 'رئيسية',
                                        'slave' => 'فرع',
                                        'independent' => 'مستقلة',
                                    ])
                                    ->required()
                                    ->live(),

                                TextInput::make(SettingKey::MASTER_NODE_LINK->value)
                                    ->label(SettingKey::MASTER_NODE_LINK->label())
                                    ->helperText(SettingKey::MASTER_NODE_LINK->helperText())
                                    ->url()
                                    ->placeholder(SettingKey::MASTER_NODE_LINK->placeholder())
                                    ->visible(fn(Get $get): bool => $get(SettingKey::NODE_TYPE->value) === 'slave')
                                    ->required(fn(Get $get): bool => $get(SettingKey::NODE_TYPE->value) === 'slave'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // Ensure all values are strings before saving
            $cleanData = [];
            foreach ($data as $key => $value) {
                $cleanData[$key] = is_array($value) ? json_encode($value) : (string) $value;
            }

            $settingsService = app(SettingsService::class);
            $settingsService->setMultiple($cleanData);

            Notification::make()
                ->title('تم حفظ الإعدادات بنجاح')
                ->body('تم حفظ جميع الإعدادات في قاعدة البيانات')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->send();

        } catch (Exception $e) {
            logger()->error('Error saving settings: ' . $e->getMessage());
            Notification::make()
                ->title('خطأ في حفظ الإعدادات')
                ->body('حدث خطأ أثناء محاولة حفظ الإعدادات. يرجى المحاولة مرة أخرى.')
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->send();
        }
    }

    public function resetToDefaults(): void
    {
        try {
            $settingsService = app(SettingsService::class);
            $defaults = $settingsService->getDefaults();

            $settingsService->setMultiple($defaults);
            $this->data = $defaults;
            $this->form->fill($this->data);

            Notification::make()
                ->title('تم إعادة تعيين الإعدادات إلى القيم الافتراضية')
                ->body('تم إعادة تعيين جميع الإعدادات بنجاح')
                ->icon('heroicon-o-arrow-path')
                ->iconColor('success')
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->title('خطأ في إعادة تعيين الإعدادات')
                ->body('حدث خطأ أثناء محاولة إعادة تعيين الإعدادات.')
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->send();
        }
    }

    public function testCashierPrinter(): void
    {
        try {
            $printService = app(PrintService::class);
            $printService->testCashierPrinter();

            Notification::make()
                ->title('تم إرسال الاختبار إلى الطابعة')
                ->body('تم إرسال اختبار الطباعة بنجاح. تحقق من الطابعة للتأكد من وصول الاختبار.')
                ->icon('heroicon-o-printer')
                ->iconColor('success')
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->title('فشل في اختبار الطابعة')
                ->body('حدث خطأ أثناء محاولة اختبار الطابعة: ' . $e->getMessage())
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->send();
        }
    }
    protected function getFormActions(): array
    {
        $actions = [
            Action::make('save')
                ->label('حفظ الإعدادات')
                ->icon('heroicon-m-check')
                ->color('primary')
                ->action('save'),

            Action::make('testPrinter')
                ->label('اختبار الطابعة')
                ->icon('heroicon-m-printer')
                ->color('info')
                ->action('testCashierPrinter'),

            Action::make('reset')
                ->label('إعادة تعيين للافتراضي')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('إعادة تعيين الإعدادات')
                ->modalDescription('هل أنت متأكد من أنك تريد إعادة تعيين جميع الإعدادات إلى القيم الافتراضية؟ سيتم فقدان التغييرات الحالية.')
                ->modalSubmitActionLabel('نعم، إعادة تعيين')
                ->action('resetToDefaults'),

        ];

        return $actions;
    }

}
