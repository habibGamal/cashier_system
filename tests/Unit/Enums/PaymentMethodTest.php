<?php

use App\Enums\PaymentMethod;

describe('PaymentMethod Enum', function () {
    it('has correct values', function () {
        expect(PaymentMethod::CASH->value)->toBe('cash')
            ->and(PaymentMethod::CARD->value)->toBe('card')
            ->and(PaymentMethod::TALABAT_CARD->value)->toBe('talabat_card');
    });

    it('returns correct labels', function () {
        expect(PaymentMethod::CASH->label())->toBe('نقدي')
            ->and(PaymentMethod::CARD->label())->toBe('بطاقة')
            ->and(PaymentMethod::TALABAT_CARD->label())->toBe('بطاقة طلبات');
    });

    it('correctly identifies methods that affect cash balance', function () {
        expect(PaymentMethod::CASH->affectsCashBalance())->toBeTrue()
            ->and(PaymentMethod::CARD->affectsCashBalance())->toBeFalse()
            ->and(PaymentMethod::TALABAT_CARD->affectsCashBalance())->toBeFalse();
    });

    it('can be created from string value', function () {
        expect(PaymentMethod::from('cash'))->toBe(PaymentMethod::CASH)
            ->and(PaymentMethod::from('card'))->toBe(PaymentMethod::CARD)
            ->and(PaymentMethod::from('talabat_card'))->toBe(PaymentMethod::TALABAT_CARD);
    });
});
