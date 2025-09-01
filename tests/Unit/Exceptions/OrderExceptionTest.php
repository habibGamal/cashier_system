<?php

use App\Exceptions\OrderException;

describe('OrderException', function () {
    it('can be created with message', function () {
        $exception = new OrderException('Test error message');

        expect($exception->getMessage())->toBe('Test error message');
    });

    it('can be created with message and code', function () {
        $exception = new OrderException('Test error', 422);

        expect($exception->getMessage())->toBe('Test error')
            ->and($exception->getCode())->toBe(422);
    });

    it('maintains inheritance from Exception', function () {
        $exception = new OrderException('Test');

        expect($exception)->toBeInstanceOf(Exception::class);
    });

    it('can be thrown and caught', function () {
        expect(function () {
            throw new OrderException('Test exception');
        })->toThrow(OrderException::class, 'Test exception');
    });

    it('supports previous exception parameter', function () {
        $previousException = new Exception('Previous error');
        $orderException = new OrderException('Order error', 422, $previousException);

        expect($orderException->getPrevious())->toBe($previousException);
    });
});
