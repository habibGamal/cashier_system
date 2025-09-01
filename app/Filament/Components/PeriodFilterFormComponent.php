<?php

namespace App\Filament\Components;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

class PeriodFilterFormComponent
{
    public static function make(
        string $description = 'اختر الفترة الزمنية للتقرير',
        string $defaultPeriod = 'last_30_days',
        int $defaultDaysBack = 29
    ): Section {
        return Section::make('فترة التقرير')
            ->description($description)
            ->schema([
                Select::make('presetPeriod')
                    ->label('فترات محددة مسبقاً')
                    ->options([
                        'today' => 'اليوم',
                        'yesterday' => 'أمس',
                        'last_7_days' => 'آخر 7 أيام',
                        'last_14_days' => 'آخر 14 يوم',
                        'last_30_days' => 'آخر 30 يوم',
                        'this_week' => 'هذا الأسبوع',
                        'last_week' => 'الأسبوع الماضي',
                        'this_month' => 'هذا الشهر',
                        'last_month' => 'الشهر الماضي',
                        'last_3_months' => 'آخر 3 شهور',
                        'this_year' => 'هذا العام',
                        'custom' => 'فترة مخصصة',
                    ])
                    ->default($defaultPeriod)
                    ->live()
                    ->afterStateUpdated(function (callable $set, $state) {
                        match ($state) {
                            'today' => [
                                $set('startDate', now()->startOfDay()->toDateString()),
                                $set('endDate', now()->endOfDay()->toDateString())
                            ],
                            'yesterday' => [
                                $set('startDate', now()->subDay()->startOfDay()->toDateString()),
                                $set('endDate', now()->subDay()->endOfDay()->toDateString())
                            ],
                            'last_7_days' => [
                                $set('startDate', now()->subDays(6)->startOfDay()->toDateString()),
                                $set('endDate', now()->endOfDay()->toDateString())
                            ],
                            'last_14_days' => [
                                $set('startDate', now()->subDays(13)->startOfDay()->toDateString()),
                                $set('endDate', now()->endOfDay()->toDateString())
                            ],
                            'last_30_days' => [
                                $set('startDate', now()->subDays(29)->startOfDay()->toDateString()),
                                $set('endDate', now()->endOfDay()->toDateString())
                            ],
                            'this_week' => [
                                $set('startDate', now()->startOfWeek()->toDateString()),
                                $set('endDate', now()->endOfWeek()->toDateString())
                            ],
                            'last_week' => [
                                $set('startDate', now()->subWeek()->startOfWeek()->toDateString()),
                                $set('endDate', now()->subWeek()->endOfWeek()->toDateString())
                            ],
                            'this_month' => [
                                $set('startDate', now()->startOfMonth()->toDateString()),
                                $set('endDate', now()->endOfMonth()->toDateString())
                            ],
                            'last_month' => [
                                $set('startDate', now()->subMonth()->startOfMonth()->toDateString()),
                                $set('endDate', now()->subMonth()->endOfMonth()->toDateString())
                            ],
                            'last_3_months' => [
                                $set('startDate', now()->subMonths(3)->startOfMonth()->toDateString()),
                                $set('endDate', now()->endOfMonth()->toDateString())
                            ],
                            'this_year' => [
                                $set('startDate', now()->startOfYear()->toDateString()),
                                $set('endDate', now()->endOfYear()->toDateString())
                            ],
                            default => null
                        };
                    }),
                DatePicker::make('startDate')
                    ->label('تاريخ البداية')
                    ->default(now()->subDays($defaultDaysBack)->startOfDay())
                    ->maxDate(now())
                    ->disabled(fn(callable $get) => $get('presetPeriod') !== 'custom')
                    ->live(),
                DatePicker::make('endDate')
                    ->label('تاريخ النهاية')
                    ->default(now()->endOfDay())
                    ->maxDate(now())
                    ->disabled(fn(callable $get) => $get('presetPeriod') !== 'custom')
                    ->live(),
            ])
            ->columns(3);
    }
}
