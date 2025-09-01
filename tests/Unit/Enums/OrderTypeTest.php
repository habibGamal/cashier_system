<?php

use App\Enums\OrderType;

describe('OrderType Enum', function () {
    it('has correct values', function () {
        expect(OrderType::DINE_IN->value)->toBe('dine_in')
            ->and(OrderType::TAKEAWAY->value)->toBe('takeaway')
            ->and(OrderType::DELIVERY->value)->toBe('delivery')
            ->and(OrderType::COMPANIES->value)->toBe('companies')
            ->and(OrderType::TALABAT->value)->toBe('talabat');
    });

    it('returns correct labels', function () {
        expect(OrderType::DINE_IN->label())->toBe('صالة')
            ->and(OrderType::TAKEAWAY->label())->toBe('تيك أواي')
            ->and(OrderType::DELIVERY->label())->toBe('دليفري')
            ->and(OrderType::COMPANIES->label())->toBe('شركات')
            ->and(OrderType::TALABAT->label())->toBe('طلبات');
    });

    it('correctly identifies types that require table', function () {
        expect(OrderType::DINE_IN->requiresTable())->toBeTrue()
            ->and(OrderType::TAKEAWAY->requiresTable())->toBeFalse()
            ->and(OrderType::DELIVERY->requiresTable())->toBeFalse()
            ->and(OrderType::COMPANIES->requiresTable())->toBeFalse()
            ->and(OrderType::TALABAT->requiresTable())->toBeFalse();
    });

    it('correctly identifies types with delivery fee', function () {
        expect(OrderType::DELIVERY->hasDeliveryFee())->toBeTrue()
            ->and(OrderType::DINE_IN->hasDeliveryFee())->toBeFalse()
            ->and(OrderType::TAKEAWAY->hasDeliveryFee())->toBeFalse()
            ->and(OrderType::COMPANIES->hasDeliveryFee())->toBeFalse()
            ->and(OrderType::TALABAT->hasDeliveryFee())->toBeFalse();
    });

    it('can be created from string value', function () {
        expect(OrderType::from('dine_in'))->toBe(OrderType::DINE_IN)
            ->and(OrderType::from('takeaway'))->toBe(OrderType::TAKEAWAY)
            ->and(OrderType::from('delivery'))->toBe(OrderType::DELIVERY);
    });
});
