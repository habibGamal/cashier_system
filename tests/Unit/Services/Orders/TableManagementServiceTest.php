<?php

use Tests\Unit\TestCase;
use App\Services\Orders\TableManagementService;
use App\Models\DineTable;
use App\Exceptions\OrderException;

uses(Tests\Unit\TestCase::class);

describe('TableManagementService', function () {
    beforeEach(function () {
        $this->service = app(TableManagementService::class);
    });

    describe('validateTableAvailability', function () {
        it('passes when table is available', function () {
            DineTable::factory()->create([
                'table_number' => 'T001',
                'order_id' => null,
            ]);

            expect(fn() => $this->service->validateTableAvailability('T001'))
                ->not->toThrow(OrderException::class);
        });

        it('throws exception when table is occupied', function () {
            DineTable::factory()->create([
                'table_number' => 'T001',
                'order_id' => 1,
            ]);

            expect(fn() => $this->service->validateTableAvailability('T001'))
                ->toThrow(OrderException::class, 'هذه الطاولة محجوزة');
        });

        it('passes when table does not exist', function () {
            expect(fn() => $this->service->validateTableAvailability('T999'))
                ->not->toThrow(OrderException::class);
        });
    });

    describe('isTableAvailable', function () {
        it('returns true when table is available', function () {
            DineTable::factory()->create([
                'table_number' => 'T001',
                'order_id' => null,
            ]);

            expect($this->service->isTableAvailable('T001'))->toBeTrue();
        });

        it('returns false when table is occupied', function () {
            DineTable::factory()->create([
                'table_number' => 'T001',
                'order_id' => 1,
            ]);

            expect($this->service->isTableAvailable('T001'))->toBeFalse();
        });

        it('returns true when table does not exist', function () {
            expect($this->service->isTableAvailable('T999'))->toBeTrue();
        });
    });

    describe('reserveTable', function () {
        it('reserves an available table', function () {
            $table = $this->service->reserveTable('T001', 1);

            expect($table->table_number)->toBe('T001')
                ->and($table->order_id)->toBe(1);
        });

        it('creates table if it does not exist', function () {
            expect(DineTable::where('table_number', 'T001')->exists())->toBeFalse();

            $table = $this->service->reserveTable('T001', 1);

            expect($table->table_number)->toBe('T001')
                ->and($table->order_id)->toBe(1)
                ->and(DineTable::where('table_number', 'T001')->exists())->toBeTrue();
        });

        it('throws exception when trying to reserve occupied table', function () {
            DineTable::factory()->create([
                'table_number' => 'T001',
                'order_id' => 1,
            ]);

            expect(fn() => $this->service->reserveTable('T001', 2))
                ->toThrow(OrderException::class, 'هذه الطاولة محجوزة');
        });
    });

    describe('freeTable', function () {
        it('frees an occupied table', function () {
            DineTable::factory()->create([
                'table_number' => 'T001',
                'order_id' => 1,
            ]);

            $this->service->freeTable('T001');

            $table = DineTable::where('table_number', 'T001')->first();
            expect($table->order_id)->toBeNull();
        });

        it('does nothing when table does not exist', function () {
            expect(fn() => $this->service->freeTable('T999'))
                ->not->toThrow(Exception::class);
        });
    });

    describe('getAvailableTables', function () {
        it('returns only available tables', function () {
            DineTable::factory()->create(['table_number' => 'T001', 'order_id' => null]);
            DineTable::factory()->create(['table_number' => 'T002', 'order_id' => 1]);
            DineTable::factory()->create(['table_number' => 'T003', 'order_id' => null]);

            $availableTables = $this->service->getAvailableTables();

            expect($availableTables)->toHaveCount(2)
                ->and($availableTables)->toContain('T001')
                ->and($availableTables)->toContain('T003')
                ->and($availableTables)->not->toContain('T002');
        });

        it('returns empty array when no tables available', function () {
            DineTable::factory()->create(['table_number' => 'T001', 'order_id' => 1]);
            DineTable::factory()->create(['table_number' => 'T002', 'order_id' => 2]);

            $availableTables = $this->service->getAvailableTables();

            expect($availableTables)->toBeEmpty();
        });
    });

    describe('getOccupiedTables', function () {
        it('returns only occupied tables with orders', function () {
            DineTable::factory()->create(['table_number' => 'T001', 'order_id' => null]);
            DineTable::factory()->create(['table_number' => 'T002', 'order_id' => 1]);
            DineTable::factory()->create(['table_number' => 'T003', 'order_id' => 2]);

            $occupiedTables = $this->service->getOccupiedTables();

            expect($occupiedTables)->toHaveCount(2);
        });

        it('returns empty array when no tables occupied', function () {
            DineTable::factory()->create(['table_number' => 'T001', 'order_id' => null]);
            DineTable::factory()->create(['table_number' => 'T002', 'order_id' => null]);

            $occupiedTables = $this->service->getOccupiedTables();

            expect($occupiedTables)->toBeEmpty();
        });
    });
});
