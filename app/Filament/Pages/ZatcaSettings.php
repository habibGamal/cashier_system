<?php

namespace App\Filament\Pages;

use App\Enums\ZatcaEnum;
use App\Filament\Traits\AdminAccess;
use App\Models\Zatca;
use App\Services\Zatca\ZatcaOnboardingService;
use App\Services\Zatca\ZatcaService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ZatcaSettings extends Page implements HasForms
{
    use AdminAccess, InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'إعدادات الفاتورة الإلكترونية';

    protected static ?string $title = 'إعدادات الفاتورة الإلكترونية (ZATCA)';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة النظام';

    protected static ?int $navigationSort = 101;

    protected string $view = 'filament.pages.zatca-settings';

    public ?array $data = [];

    public ?array $certificateStatus = [];

    public function mount(): void
    {
        $zatcaService = app(ZatcaService::class);
        $this->data = $zatcaService->getZatcaConfig();
        $this->certificateStatus = $zatcaService->getCertificateStatus();
        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('الإعدادات العامة')
                    ->description('اختر البيئة والمرحلة')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('environment')
                                    ->label('البيئة')
                                    ->options([
                                        'sandbox' => 'بيئة الاختبار (Sandbox)',
                                        'simulation' => 'بيئة المحاكاة (Simulation)',
                                        'production' => 'بيئة الإنتاج (Production)',
                                    ])
                                    ->default('sandbox')
                                    ->required()
                                    ->helperText('اختر sandbox للتطوير والاختبار، production للعمل الفعلي'),

                                Select::make('phase')
                                    ->label('المرحلة')
                                    ->options([
                                        'phase_1' => 'المرحلة الأولى',
                                        'phase_2' => 'المرحلة الثانية',
                                    ])
                                    ->default('phase_1')
                                    ->required()
                                    ->helperText('المرحلة الحالية من متطلبات الفاتورة الإلكترونية'),
                            ]),
                    ]),

                Section::make('معلومات الشركة')
                    ->description('المعلومات الأساسية للشركة المطلوبة لإصدار الشهادات')
                    ->icon('heroicon-m-building-office-2')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('organization_identifier')
                                    ->label('الرقم الضريبي')
                                    ->required()
                                    ->maxLength(15)
                                    ->placeholder('310000000000003')
                                    ->helperText('الرقم الضريبي للشركة (15 رقم)'),

                                TextInput::make('organization_name')
                                    ->label('اسم الشركة')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('اسم الشركة'),

                                TextInput::make('common_name')
                                    ->label('الاسم الشائع')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('TST-886431145-399999999900003')
                                    ->helperText('يتم إنشاؤه تلقائياً أو يمكن تخصيصه'),

                                TextInput::make('organizational_unit_name')
                                    ->label('اسم الوحدة التنظيمية')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Riyad Branch'),

                                TextInput::make('country_name')
                                    ->label('رمز الدولة')
                                    ->required()
                                    ->maxLength(2)
                                    ->default('SA')
                                    ->placeholder('SA'),

                                TextInput::make('business_category')
                                    ->label('فئة العمل')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Restaurant'),
                            ]),

                        Textarea::make('address')
                            ->label('العنوان')
                            ->required()
                            ->rows(2)
                            ->placeholder('العنوان الكامل للشركة'),
                    ]),

                Section::make('معلومات الجهاز')
                    ->description('معلومات نظام نقطة البيع')
                    ->icon('heroicon-m-computer-desktop')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('serial_number_solution')
                                    ->label('الرقم التسلسلي للحل')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('1')
                                    ->helperText('رقم الحل البرمجي'),

                                TextInput::make('serial_number_model')
                                    ->label('الرقم التسلسلي للموديل')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Cashier System')
                                    ->helperText('اسم النظام'),

                                TextInput::make('serial_number_device')
                                    ->label('الرقم التسلسلي للجهاز')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('1')
                                    ->helperText('رقم الجهاز أو الفرع'),
                            ]),
                    ]),

                Section::make('نوع الفاتورة')
                    ->description('حدد أنواع الفواتير التي ستصدرها')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        Select::make('invoice_type')
                            ->label('نوع الفاتورة')
                            ->options([
                                'standard' => 'فواتير قياسية (B2B) فقط',
                                'simplified' => 'فواتير مبسطة (B2C) فقط',
                                'both' => 'كلا النوعين',
                            ])
                            ->default('both')
                            ->required()
                            ->helperText('القياسية للشركات، المبسطة للعملاء الأفراد'),
                    ]),
            ])
            ->statePath('data');
    }

    public function saveSettings(): void
    {
        try {
            $data = $this->form->getState();

            // Save all settings to database
            Zatca::setValue(ZatcaEnum::ZATCA_ENVIRONMENT->value, $data['environment']);
            Zatca::setValue(ZatcaEnum::ZATCA_PHASE->value, $data['phase']);
            Zatca::setValue(ZatcaEnum::ORGANIZATION_IDENTIFIER->value, $data['organization_identifier']);
            Zatca::setValue(ZatcaEnum::ORGANIZATION_NAME->value, $data['organization_name']);
            Zatca::setValue(ZatcaEnum::COMMON_NAME->value, $data['common_name']);
            Zatca::setValue(ZatcaEnum::ORGANIZATIONAL_UNIT_NAME->value, $data['organizational_unit_name']);
            Zatca::setValue(ZatcaEnum::COUNTRY_NAME->value, $data['country_name']);
            Zatca::setValue(ZatcaEnum::BUSINESS_CATEGORY->value, $data['business_category']);
            Zatca::setValue(ZatcaEnum::ADDRESS->value, $data['address']);
            Zatca::setValue(ZatcaEnum::SERIAL_NUMBER_SOLUTION->value, $data['serial_number_solution']);
            Zatca::setValue(ZatcaEnum::SERIAL_NUMBER_MODEL->value, $data['serial_number_model']);
            Zatca::setValue(ZatcaEnum::SERIAL_NUMBER_DEVICE->value, $data['serial_number_device']);
            Zatca::setValue(ZatcaEnum::INVOICE_TYPE->value, $data['invoice_type']);

            Notification::make()
                ->title('تم حفظ الإعدادات بنجاح')
                ->success()
                ->send();

            $this->redirect(self::getUrl());

        } catch (Exception $e) {
            Notification::make()
                ->title('خطأ في حفظ الإعدادات')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function generateCSR(): void
    {
        try {
            $data = $this->form->getState();
            $onboardingService = app(ZatcaOnboardingService::class);

            $result = $onboardingService->generateCsr($data);

            Notification::make()
                ->title('تم إنشاء CSR بنجاح')
                ->body('تم إنشاء شهادة الطلب والمفتاح الخاص')
                ->success()
                ->send();

            $this->mount(); // Refresh data

        } catch (Exception $e) {
            Notification::make()
                ->title('فشل في إنشاء CSR')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }



    public function deleteCertificates(): void
    {
        try {
            $onboardingService = app(ZatcaOnboardingService::class);
            $onboardingService->deleteCertificates();

            Notification::make()
                ->title('تم حذف الشهادات بنجاح')
                ->body('تم حذف جميع الشهادات والملفات المرتبطة')
                ->success()
                ->send();

            $this->mount(); // Refresh data

        } catch (Exception $e) {
            Notification::make()
                ->title('فشل في حذف الشهادات')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->saveAction(),
            $this->generateCSRAction(),
            $this->startOnboardingAction(),
            $this->deleteCertificatesAction(),
        ];
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('حفظ الإعدادات')
            ->icon('heroicon-m-check')
            ->color('primary')
            ->action('saveSettings');
    }

    public function generateCSRAction(): Action
    {
        return Action::make('generateCSR')
            ->label('إنشاء CSR')
            ->icon('heroicon-m-key')
            ->color('success')
            ->visible(fn() => !$this->certificateStatus['csr_exists'])
            ->requiresConfirmation()
            ->modalHeading('إنشاء شهادة طلب CSR')
            ->modalDescription('سيتم إنشاء شهادة طلب CSR ومفتاح خاص. تأكد من صحة البيانات قبل المتابعة.')
            ->modalSubmitActionLabel('نعم، إنشاء')
            ->action('generateCSR');
    }

    public function startOnboardingAction(): Action
    {
        return Action::make('startOnboarding')
            ->label('بدء عملية التفعيل')
            ->icon('heroicon-m-rocket-launch')
            ->color('warning')
            ->visible(fn() => $this->certificateStatus['csr_exists'] && !$this->certificateStatus['production_certificate_exists'])
            ->form([
                TextInput::make('otp')
                    ->label('رمز التفعيل (OTP)')
                    ->required()
                    ->maxLength(6)
                    ->placeholder('123456')
                    ->helperText('احصل على رمز OTP من بوابة فاتورة (fatoora.zatca.gov.sa)'),
            ])
            ->modalHeading('تفعيل الفاتورة الإلكترونية')
            ->modalDescription('أدخل رمز OTP للحصول على شهادة الإنتاج وبدء إصدار الفواتير.')
            ->modalSubmitActionLabel('بدء التفعيل')
            ->action(function (array $data) {
                try {
                    $onboardingService = app(ZatcaOnboardingService::class);
                    $onboardingService->onboard($data['otp']);

                    Notification::make()
                        ->title('تم التفعيل بنجاح')
                        ->body('تم الحصول على شهادة الإنتاج. يمكنك الآن إصدار الفواتير الإلكترونية.')
                        ->success()
                        ->duration(10000)
                        ->send();

                    $this->mount(); // Refresh data
    
                } catch (Exception $e) {
                    Notification::make()
                        ->title('فشل التفعيل')
                        ->body($e->getMessage())
                        ->danger()
                        ->duration(10000)
                        ->send();

                    throw $e;
                }
            });
    }

    public function deleteCertificatesAction(): Action
    {
        return Action::make('deleteCertificates')
            ->label('حذف الشهادات')
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->visible(fn() => $this->certificateStatus['csr_exists'])
            ->requiresConfirmation()
            ->modalHeading('حذف جميع الشهادات')
            ->modalDescription('سيتم حذف جميع الشهادات والملفات. لن يمكنك التراجع عن هذا الإجراء.')
            ->modalSubmitActionLabel('نعم، حذف')
            ->action('deleteCertificates');
    }
}
