<?php

use App\Enums\OrderStatus;

describe('OrderStatus Enum', function () {
    it('has correct values', function () {
        expect(OrderStatus::PROCESSING->value)->toBe('processing')
            ->and(OrderStatus::COMPLETED->value)->toBe('completed')
            ->and(OrderStatus::CANCELLED->value)->toBe('cancelled');
    });

    it('returns correct labels', function () {
        expect(OrderStatus::PROCESSING->label())->toBe('تحت التشغيل')
            ->and(OrderStatus::COMPLETED->label())->toBe('مكتمل')
            ->and(OrderStatus::CANCELLED->label())->toBe('ملغي');
    });

    it('correctly identifies statuses that can be modified', function () {
        expect(OrderStatus::PROCESSING->canBeModified())->toBeTrue()
            ->and(OrderStatus::COMPLETED->canBeModified())->toBeFalse()
            ->and(OrderStatus::CANCELLED->canBeModified())->toBeFalse();
    });

    it('correctly identifies statuses that can be cancelled', function () {
        expect(OrderStatus::PROCESSING->canBeCancelled())->toBeTrue()
            ->and(OrderStatus::COMPLETED->canBeCancelled())->toBeFalse()
            ->and(OrderStatus::CANCELLED->canBeCancelled())->toBeFalse();
    });

    it('can be created from string value', function () {
        expect(OrderStatus::from('processing'))->toBe(OrderStatus::PROCESSING)
            ->and(OrderStatus::from('completed'))->toBe(OrderStatus::COMPLETED)
            ->and(OrderStatus::from('cancelled'))->toBe(OrderStatus::CANCELLED);
    });
});
