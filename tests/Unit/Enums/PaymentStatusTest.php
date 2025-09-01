<?php

use App\Enums\PaymentStatus;

describe('PaymentStatus Enum', function () {
    it('has correct values', function () {
        expect(PaymentStatus::PENDING->value)->toBe('pending')
            ->and(PaymentStatus::PARTIAL_PAID->value)->toBe('partial_paid')
            ->and(PaymentStatus::FULL_PAID->value)->toBe('full_paid');
    });

    it('returns correct labels', function () {
        expect(PaymentStatus::PENDING->label())->toBe('في الانتظار')
            ->and(PaymentStatus::PARTIAL_PAID->label())->toBe('مدفوع جزئياً')
            ->and(PaymentStatus::FULL_PAID->label())->toBe('مدفوع بالكامل');
    });

    it('correctly identifies fully paid status', function () {
        expect(PaymentStatus::FULL_PAID->isFullyPaid())->toBeTrue()
            ->and(PaymentStatus::PARTIAL_PAID->isFullyPaid())->toBeFalse()
            ->and(PaymentStatus::PENDING->isFullyPaid())->toBeFalse();
    });

    it('correctly identifies statuses that require payment', function () {
        expect(PaymentStatus::PENDING->requiresPayment())->toBeTrue()
            ->and(PaymentStatus::PARTIAL_PAID->requiresPayment())->toBeTrue()
            ->and(PaymentStatus::FULL_PAID->requiresPayment())->toBeFalse();
    });

    it('can be created from string value', function () {
        expect(PaymentStatus::from('pending'))->toBe(PaymentStatus::PENDING)
            ->and(PaymentStatus::from('partial_paid'))->toBe(PaymentStatus::PARTIAL_PAID)
            ->and(PaymentStatus::from('full_paid'))->toBe(PaymentStatus::FULL_PAID);
    });
});
