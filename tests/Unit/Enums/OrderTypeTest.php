<?php

use App\Enums\OrderType;

describe('OrderType Enum', function () {
    it('has correct values', function () {
        expect(OrderType::TAKEAWAY->value)->toBe('takeaway')
            ->and(OrderType::DELIVERY->value)->toBe('delivery')
            ->and(OrderType::WEB_DELIVERY->value)->toBe('web_delivery')
            ->and(OrderType::WEB_TAKEAWAY->value)->toBe('web_takeaway');
    });

    it('returns correct labels', function () {
        expect(OrderType::TAKEAWAY->label())->toBe('تيك أواي')
            ->and(OrderType::DELIVERY->label())->toBe('دليفري')
            ->and(OrderType::WEB_DELIVERY->label())->toBe('اونلاين دليفري')
            ->and(OrderType::WEB_TAKEAWAY->label())->toBe('اونلاين تيك أواي');
    });

    it('correctly identifies types that require table', function () {
        expect(OrderType::TAKEAWAY->requiresTable())->toBeFalse()
            ->and(OrderType::DELIVERY->requiresTable())->toBeFalse()
            ->and(OrderType::WEB_DELIVERY->requiresTable())->toBeFalse()
            ->and(OrderType::WEB_TAKEAWAY->requiresTable())->toBeFalse();
    });

    it('correctly identifies types with delivery fee', function () {
        expect(OrderType::DELIVERY->hasDeliveryFee())->toBeTrue()
            ->and(OrderType::WEB_DELIVERY->hasDeliveryFee())->toBeTrue()
            ->and(OrderType::TAKEAWAY->hasDeliveryFee())->toBeFalse()
            ->and(OrderType::WEB_TAKEAWAY->hasDeliveryFee())->toBeFalse();
    });

    it('can be created from string value', function () {
        expect(OrderType::from('takeaway'))->toBe(OrderType::TAKEAWAY)
            ->and(OrderType::from('delivery'))->toBe(OrderType::DELIVERY)
            ->and(OrderType::from('web_delivery'))->toBe(OrderType::WEB_DELIVERY)
            ->and(OrderType::from('web_takeaway'))->toBe(OrderType::WEB_TAKEAWAY);
    });
});
