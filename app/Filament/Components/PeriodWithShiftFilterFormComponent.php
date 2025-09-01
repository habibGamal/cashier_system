<?php

namespace App\Filament\Components;

use Filament\Schemas\Components\Section;
use App\Models\Shift;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;

class PeriodWithShiftFilterFormComponent
{
    public static function make(
        string $periodDescription = 'اختر الفترة الزمنية لعرض التقارير',
        string $shiftDescription = 'اختر الشفتات المحددة',
        string $defaultPeriod = 'last_7_days',
        int $defaultDaysBack = 6
    ): array {
        return [
            Radio::make('filterType')
                ->label('نوع التصفية')
                ->options([
                    'period' => 'فترة زمنية',
                    'shifts' => 'شفتات محددة',
                ])
                ->default('period')
                ->inline()
                ->reactive(),

            Section::make('اختيار الشفتات')
                ->description($shiftDescription)
                ->schema([
                    Select::make('shifts')
                        ->label('الشفتات')
                        ->options(function () {
                            return Shift::with('user')
                                ->orderBy('start_at', 'desc')
                                ->get()
                                ->mapWithKeys(function ($shift) {
                                    $userLabel = $shift->user ? $shift->user->name : 'غير محدد';
                                    $startDate = $shift->start_at ? $shift->start_at->format('d/m/Y H:i') : 'غير محدد';
                                    $endDate = $shift->end_at ? $shift->end_at->format('d/m/Y H:i') : 'لم ينته';

                                    return [
                                        $shift->id => "شفت #{$shift->id} - {$userLabel} ({$startDate} - {$endDate})"
                                    ];
                                });
                        })
                        ->searchable()
                        ->placeholder('اختر الشفتات')
                        ->multiple()
                        ->preload(),
                ])
                ->visible(fn(callable $get) => $get('filterType') === 'shifts'),

            Section::make('فترة التقرير')
                ->description($periodDescription)
                ->schema([
                    Select::make('presetPeriod')
                        ->label('فترات محددة مسبقاً')
                        ->options([
                            'today' => 'اليوم',
                            'yesterday' => 'أمس',
                            'last_7_days' => 'آخر 7 أيام',
                            'last_30_days' => 'آخر 30 يوم',
                            'this_week' => 'هذا الأسبوع',
                            'last_week' => 'الأسبوع الماضي',
                            'this_month' => 'هذا الشهر',
                            'last_month' => 'الشهر الماضي',
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
                ->columns(3)
                ->visible(fn(callable $get) => $get('filterType') === 'period'),
        ];
    }
}
