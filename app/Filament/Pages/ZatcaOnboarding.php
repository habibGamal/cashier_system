<?php

namespace App\Filament\Pages;

use App\Enums\ZatcaEnum;
use App\Filament\Traits\AdminAccess;
use App\Models\Zatca;
use App\Services\Zatca\ZatcaOnboardingService;
use Exception;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class ZatcaOnboarding extends Page implements HasForms
{
    use AdminAccess, InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationLabel = 'تفعيل الفاتورة الإلكترونية';

    protected static ?string $title = 'معالج تفعيل الفاتورة الإلكترونية';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة النظام';

    protected static ?int $navigationSort = 102;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.zatca-onboarding';

    public ?array $data = [];

    public ?string $currentStep = 'instructions';

    public ?array $certificateStatus = [];

    public function mount(): void
    {
        $onboardingService = app(ZatcaOnboardingService::class);
        $this->certificateStatus = $onboardingService->getCertificateStatus();

        // Check onboarding status
        $onboardingStatus = Zatca::getValue(ZatcaEnum::ONBOARDING_STATUS->value, 'pending');

        if ($onboardingStatus === 'production_obtained') {
            Notification::make()
                ->title('تم التفعيل بالفعل')
                ->body('تم الحصول على شهادة الإنتاج بنجاح. يمكنك الآن إصدار الفواتير.')
                ->success()
                ->send();

            $this->redirect(route('filament.admin.pages.zatca-settings'));

            return;
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('التعليمات')
                        ->description('معلومات مهمة قبل البدء')
                        ->schema([
                            Placeholder::make('instructions')
                                ->content(view('filament.components.zatca-onboarding-instructions')),
                        ]),

                    Step::make('رمز OTP')
                        ->description('أدخل رمز OTP من بوابة فاتورة')
                        ->schema([
                            TextInput::make('otp')
                                ->label('رمز التفعيل (OTP)')
                                ->required()
                                ->maxLength(6)
                                ->placeholder('123456')
                                ->helperText('احصل على رمز OTP من بوابة فاتورة (fatoora.zatca.gov.sa)')
                                ->live(),
                        ]),

                    Step::make('التفعيل')
                        ->description('جاري تفعيل النظام')
                        ->schema([
                            Placeholder::make('activation')
                                ->content(view('filament.components.zatca-activation-progress')),
                        ]),

                    Step::make('الاكتمال')
                        ->description('تم التفعيل بنجاح')
                        ->schema([
                            Placeholder::make('completion')
                                ->content(view('filament.components.zatca-completion')),
                        ]),
                ])
                    ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                        <x-filament::button
                            type="submit"
                            size="sm"
                            wire:click="submitOnboarding"
                        >
                            بدء التفعيل
                        </x-filament::button>
                    BLADE)))
                    ->skippable(false)
                    ->persistStepInQueryString(),
            ])
            ->statePath('data');
    }

    public function submitOnboarding(): void
    {
        try {
            $data = $this->form->getState();

            if (empty($data['otp'])) {
                throw new Exception('يرجى إدخال رمز OTP');
            }

            $onboardingService = app(ZatcaOnboardingService::class);

            // Start onboarding process
            $onboardingService->onboard($data['otp']);

            Notification::make()
                ->title('تم التفعيل بنجاح')
                ->body('تم الحصول على شهادة الإنتاج. يمكنك الآن إصدار الفواتير الإلكترونية.')
                ->success()
                ->duration(10000)
                ->send();

            // Redirect to settings page
            $this->redirect(route('filament.admin.pages.zatca-settings'));

        } catch (Exception $e) {
            Notification::make()
                ->title('فشل التفعيل')
                ->body($e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
