<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $settingsService = app(SettingsService::class);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'receiptFooter' => $settingsService->getReceiptFooter(),
            'scaleBarcodePrefix' => $settingsService->getScaleBarcodePrefix(),
            'allowCashierDiscounts' => (bool) $settingsService->get('allow_cashier_discounts', '0'),
            'allowCashierCancelOrders' => (bool) $settingsService->get('allow_cashier_cancel_orders', '0'),
            'allowCashierItemChanges' => (bool) $settingsService->get('allow_cashier_item_changes', '0'),
        ];
    }
}
