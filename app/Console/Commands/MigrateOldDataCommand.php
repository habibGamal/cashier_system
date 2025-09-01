<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Printer;
use App\Models\Driver;
use App\Models\DineTable;
use App\Models\Region;
use App\Models\ExpenceType;
use App\Models\Setting;
use App\Models\User;
use App\Models\Shift;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\InventoryItem;
use App\Models\Expense;
use App\Models\PurchaseInvoice;
use App\Models\ReturnPurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\ReturnPurchaseInvoiceItem;
use App\Models\Stocktaking;
use App\Models\StocktakingItem;
use App\Models\Waste;
use App\Models\WastedItem;
use App\Models\ProductComponent;
use App\Models\DailySnapshot;
use Carbon\Carbon;

class MigrateOldDataCommand extends Command
{
    protected $signature = 'migrate:old-data
                            {--connection=old_system : The database connection for old system}
                            {--dry-run : Run without actually inserting data}
                            {--table= : Migrate specific table only}
                            {--chunk=1000 : Number of records to process at once}';

    protected $description = 'Migrate data from old Turbo Restaurant system to new Laravel schema';

    protected $oldConnection;
    protected $isDryRun;
    protected $chunkSize;
    protected $migratedData = [];

    public function handle()
    {
        $this->oldConnection = $this->option('connection');
        $this->isDryRun = $this->option('dry-run');
        $this->chunkSize = (int) $this->option('chunk');
        $specificTable = $this->option('table');

        $this->info('Starting migration from old Turbo Restaurant system...');

        if ($this->isDryRun) {
            $this->warn('DRY RUN MODE - No data will actually be inserted');
        }

        // Check old database connection
        if (!$this->checkOldConnection()) {
            $this->error('Cannot connect to old database. Please check your connection settings.');
            return 1;
        }

        // Clear existing data if not dry run
        if (!$this->isDryRun && !$specificTable) {
            if ($this->confirm('This will clear all existing data. Are you sure?')) {
                $this->clearExistingData();
            } else {
                $this->info('Migration cancelled.');
                return 0;
            }
        }

        try {
            DB::beginTransaction();

            // Disable foreign key checks and prepare for ID preservation
            if (!$this->isDryRun) {
                $this->prepareDatabaseForMigration();
            }

            // Migration order is important due to foreign key constraints
            $migrationMethods = [
                'categories' => 'migrateCategories',
                'regions' => 'migrateRegions',
                'customers' => 'migrateCustomers',
                'suppliers' => 'migrateSuppliers',
                'printers' => 'migratePrinters',
                'products' => 'migrateProducts',
                'product_components' => 'migrateProductComponents',
                'printer_products' => 'migratePrinterProducts',
                'drivers' => 'migrateDrivers',
                'expense_types' => 'migrateExpenseTypes',
                'settings' => 'migrateSettings',
                'users' => 'migrateUsers',
                'shifts' => 'migrateShifts',
                'orders' => 'migrateOrders',
                'order_items' => 'migrateOrderItems',
                'payments' => 'migratePayments',
                'inventory_items' => 'migrateInventoryItems',
                'expenses' => 'migrateExpenses',
                'purchase_invoices' => 'migratePurchaseInvoices',
                'purchase_invoice_items' => 'migratePurchaseInvoiceItems',
                'return_purchase_invoices' => 'migrateReturnPurchaseInvoices',
                'return_purchase_invoice_items' => 'migrateReturnPurchaseInvoiceItems',
                // 'stocktakings' => 'migrateStocktakings',
                // 'stocktaking_items' => 'migrateStocktakingItems',
                // 'wastes' => 'migrateWastes',
                // 'wasted_items' => 'migrateWastedItems',
                // 'daily_snapshots' => 'migrateDailySnapshots',
            ];

            if ($specificTable) {
                if (!isset($migrationMethods[$specificTable])) {
                    $this->error("Table '{$specificTable}' is not supported for migration.");
                    return 1;
                }
                $this->{$migrationMethods[$specificTable]}();
            } else {
                foreach ($migrationMethods as $table => $method) {
                    $this->info("Migrating {$table}...");
                    $this->{$method}();
                }
            }

            if (!$this->isDryRun) {
                DB::commit();
                $this->finalizeDatabaseAfterMigration();
                $this->info('Migration completed successfully!');
            } else {
                DB::rollBack();
                $this->info('Dry run completed. No data was actually migrated.');
            }

            $this->showMigrationSummary();

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    protected function checkOldConnection(): bool
    {
        try {
            $result = DB::connection($this->oldConnection)->select('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function clearExistingData(): void
    {
        $this->info('Clearing existing data...');

        Schema::disableForeignKeyConstraints();

        $tables = [
            'daily_snapshots',
            'wasted_items',
            'wastes',
            'stocktaking_items',
            'stocktakings',
            'printer_product',
            'return_purchase_invoice_items',
            'return_purchase_invoices',
            'purchase_invoice_items',
            'purchase_invoices',
            'expenses',
            'expense_types',
            'inventory_items',
            'payments',
            'order_items',
            'orders',
            'shifts',
            'users',
            'settings',
            'dine_tables',
            'drivers',
            'product_components',
            'products',
            'printers',
            'suppliers',
            'customers',
            'regions',
            'categories',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("Cleared table: {$table}");
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    protected function migrateCategories(): void
    {
        $oldCategories = DB::connection($this->oldConnection)
            ->table('categories')
            ->get();

        $migrated = 0;
        foreach ($oldCategories as $oldCategory) {
            if (!$this->isDryRun) {
                DB::table('categories')->insert([
                    'id' => $oldCategory->id,
                    'name' => $oldCategory->name,
                    'created_at' => $oldCategory->created_at ?? now(),
                    'updated_at' => $oldCategory->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['categories'] = $migrated;
        $this->line("Migrated {$migrated} categories");
    }

    protected function migrateRegions(): void
    {
        $oldRegions = DB::connection($this->oldConnection)
            ->table('regions')
            ->get();

        $migrated = 0;
        foreach ($oldRegions as $oldRegion) {
            if (!$this->isDryRun) {
                Region::insert([
                    'id' => $oldRegion->id,
                    'name' => $oldRegion->name,
                    'delivery_cost' => $oldRegion->delivery_cost ?? 0,
                    'created_at' => $oldRegion->created_at ?? now(),
                    'updated_at' => $oldRegion->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['regions'] = $migrated;
        $this->line("Migrated {$migrated} regions");
    }

    protected function migrateCustomers(): void
    {
        $oldCustomers = DB::connection($this->oldConnection)
            ->table('customers')
            ->get();

        $migrated = 0;
        foreach ($oldCustomers as $oldCustomer) {
            if (!$this->isDryRun) {
                DB::table('customers')->insert([
                    'id' => $oldCustomer->id,
                    'name' => $oldCustomer->name,
                    'phone' => $oldCustomer->phone ?? '',
                    'has_whatsapp' => $oldCustomer->has_whatsapp ?? false,
                    'address' => $oldCustomer->address,
                    'region' => $oldCustomer->region,
                    'delivery_cost' => $oldCustomer->delivery_cost ?? 0,
                    'created_at' => $oldCustomer->created_at ?? now(),
                    'updated_at' => $oldCustomer->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['customers'] = $migrated;
        $this->line("Migrated {$migrated} customers");
    }

    protected function migrateSuppliers(): void
    {
        $oldSuppliers = DB::connection($this->oldConnection)
            ->table('suppliers')
            ->get();

        $migrated = 0;
        foreach ($oldSuppliers as $oldSupplier) {
            if (!$this->isDryRun) {
                Supplier::insert([
                    'id' => $oldSupplier->id,
                    'name' => $oldSupplier->name,
                    'phone' => $oldSupplier->phone ?? '',
                    'address' => property_exists($oldSupplier, 'address') ? $oldSupplier->address : null,
                    'created_at' => $oldSupplier->created_at ?? now(),
                    'updated_at' => $oldSupplier->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['suppliers'] = $migrated;
        $this->line("Migrated {$migrated} suppliers");
    }

    protected function migratePrinters(): void
    {
        $oldPrinters = DB::connection($this->oldConnection)
            ->table('printers')
            ->get();

        $migrated = 0;
        foreach ($oldPrinters as $oldPrinter) {
            if (!$this->isDryRun) {
                Printer::insert([
                    'id' => $oldPrinter->id,
                    'name' => $oldPrinter->name,
                    'ip_address' => $oldPrinter->ip_address ?? '',
                    'created_at' => $oldPrinter->created_at ?? now(),
                    'updated_at' => $oldPrinter->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['printers'] = $migrated;
        $this->line("Migrated {$migrated} printers");
    }

    protected function migrateProducts(): void
    {
        $oldProducts = DB::connection($this->oldConnection)
            ->table('products')
            ->get();

        $migrated = 0;
        foreach ($oldProducts as $oldProduct) {
            if (!$this->isDryRun) {
                Product::insert([
                    'id' => $oldProduct->id,
                    'category_id' => $oldProduct->category_id,
                    'product_ref' => $oldProduct->product_ref ?? '',
                    'name' => $oldProduct->name,
                    'price' => $oldProduct->price ?? 0,
                    'cost' => $oldProduct->cost ?? 0,
                    'type' => ($oldProduct->type ?? 'manufactured') === 'manifactured' ? 'manufactured' : ($oldProduct->type ?? 'manufactured'),
                    'unit' => $oldProduct->unit ?? 'piece',
                    'legacy' => $oldProduct->legacy ?? false,
                    'created_at' => $oldProduct->created_at ?? now(),
                    'updated_at' => $oldProduct->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['products'] = $migrated;
        $this->line("Migrated {$migrated} products");
    }

    protected function migrateProductComponents(): void
    {
        $oldComponents = DB::connection($this->oldConnection)
            ->table('product_components')
            ->get();

        $migrated = 0;
        foreach ($oldComponents as $oldComponent) {
            if (!$this->isDryRun) {
                ProductComponent::insert([
                    'id' => $oldComponent->id,
                    'product_id' => $oldComponent->product_id,
                    'component_id' => $oldComponent->component_id,
                    'quantity' => $oldComponent->quantity ?? 1,
                    'created_at' => $oldComponent->created_at ?? now(),
                    'updated_at' => $oldComponent->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['product_components'] = $migrated;
        $this->line("Migrated {$migrated} product components");
    }

    protected function migratePrinterProducts(): void
    {
        $oldPrinterProducts = DB::connection($this->oldConnection)
            ->table('printer_product')
            ->get();

        $migrated = 0;
        $skipped = 0;
        foreach ($oldPrinterProducts as $oldPrinterProduct) {
            $printerExists = DB::table('printers')->where('id', $oldPrinterProduct->printer_id)->exists();
            $productExists = DB::table('products')->where('id', $oldPrinterProduct->product_id)->exists();
            if (!$printerExists || !$productExists) {
                $skipped++;
                continue;
            }
            if (!$this->isDryRun) {
                DB::table('printer_product')->insert([
                    'printer_id' => $oldPrinterProduct->printer_id,
                    'product_id' => $oldPrinterProduct->product_id,
                ]);
            }
            $migrated++;
        }

        $this->migratedData['printer_products'] = $migrated;
        $this->line("Migrated {$migrated} printer products");
        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} printer_product records due to missing printer or product.");
        }
    }

    protected function migrateDrivers(): void
    {
        $oldDrivers = DB::connection($this->oldConnection)
            ->table('drivers')
            ->get();

        $migrated = 0;
        foreach ($oldDrivers as $oldDriver) {
            if (!$this->isDryRun) {
                Driver::insert([
                    'id' => $oldDriver->id,
                    'name' => $oldDriver->name,
                    'phone' => $oldDriver->phone ?? '',
                    'created_at' => $oldDriver->created_at ?? now(),
                    'updated_at' => $oldDriver->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['drivers'] = $migrated;
        $this->line("Migrated {$migrated} drivers");
    }


    protected function migrateExpenseTypes(): void
    {
        $oldExpenseTypes = DB::connection($this->oldConnection)
            ->table('expense_types')
            ->get();

        $migrated = 0;
        foreach ($oldExpenseTypes as $oldExpenseType) {
            if (!$this->isDryRun) {
                ExpenceType::insert([
                    'id' => $oldExpenseType->id,
                    'name' => $oldExpenseType->name,
                    'created_at' => $oldExpenseType->created_at ?? now(),
                    'updated_at' => $oldExpenseType->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['expense_types'] = $migrated;
        $this->line("Migrated {$migrated} expense types");
    }

    protected function migrateSettings(): void
    {
        $oldSettings = DB::connection($this->oldConnection)
            ->table('settings')
            ->get();

        $migrated = 0;
        foreach ($oldSettings as $oldSetting) {
            if (!$this->isDryRun) {
                Setting::insert([
                    'id' => $oldSetting->id,
                    'key' => $oldSetting->key,
                    'value' => $oldSetting->value,
                    'created_at' => $oldSetting->created_at ?? now(),
                    'updated_at' => $oldSetting->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['settings'] = $migrated;
        $this->line("Migrated {$migrated} settings");
    }

    protected function migrateUsers(): void
    {
        $oldUsers = DB::connection($this->oldConnection)
            ->table('users')
            ->get();

        $migrated = 0;
        foreach ($oldUsers as $oldUser) {
            if (!$this->isDryRun) {
                User::insert([
                    'id' => $oldUser->id,
                    'name' => $oldUser->name ?? 'Unknown',
                    'email' => $oldUser->email ?? "user{$oldUser->id}@example.com",
                    'email_verified_at' => $oldUser->email_verified_at ?? now(),
                    'password' => $oldUser->password ?? bcrypt('password'),
                    'remember_token' => $oldUser->remember_token ?? null,
                    'created_at' => $oldUser->created_at ?? now(),
                    'updated_at' => $oldUser->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['users'] = $migrated;
        $this->line("Migrated {$migrated} users");
    }

    protected function migrateShifts(): void
    {
        $oldShifts = DB::connection($this->oldConnection)
            ->table('shifts')
            ->get();

        $migrated = 0;
        foreach ($oldShifts as $oldShift) {
            if (!$this->isDryRun) {
                Shift::insert([
                    'id' => $oldShift->id,
                    'start_at' => $oldShift->start_at ?? now(),
                    'end_at' => $oldShift->end_at ? Carbon::parse($oldShift->end_at)->format('Y-m-d H:i:s') : null,
                    'user_id' => $oldShift->user_id ?? 1, // Default to first user
                    'start_cash' => $oldShift->start_cash ?? 0,
                    'end_cash' => $oldShift->end_cash,
                    'losses_amount' => $oldShift->losses_amount,
                    'real_cash' => $oldShift->real_cash,
                    'has_deficit' => $oldShift->has_deficit ?? false,
                    'closed' => $oldShift->closed ?? false,
                    'created_at' =>  Carbon::parse($oldShift->end_at)->format('Y-m-d H:i:s'),
                    'updated_at' => $oldShift->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['shifts'] = $migrated;
        $this->line("Migrated {$migrated} shifts");
    }

    protected function migrateOrders(): void
    {
        $oldOrders = DB::connection($this->oldConnection)
            ->table('orders')
            ->get();

        $migrated = 0;
        $user = User::first();

        foreach ($oldOrders as $oldOrder) {
            if (!$this->isDryRun) {
                $exists = Order::where('shift_id', $oldOrder->shift_id ?? 1)
                    ->where('order_number', $oldOrder->order_number ?? $oldOrder->id)
                    ->exists();
                $orderNumber = $oldOrder->order_number;
                if ($exists) {
                    $orderNumber = 'FIX-' . rand(1000, 9999);
                }
                Order::insert([
                    'id' => $oldOrder->id,
                    'customer_id' => $oldOrder->customer_id,
                    'driver_id' => $oldOrder->driver_id,
                    'user_id' => $user->id,
                    'shift_id' => $oldOrder->shift_id ?? 1, // Default to first shift
                    'status' => $oldOrder->status ?? 'pending',
                    'type' => $oldOrder->type ?? 'dine_in',
                    'sub_total' => $oldOrder->sub_total ?? 0,
                    'tax' => $oldOrder->tax ?? 0,
                    'service' => $oldOrder->service ?? 0,
                    'discount' => $oldOrder->discount ?? 0,
                    'temp_discount_percent' => $oldOrder->temp_discount_percent ?? 0,
                    'total' => $oldOrder->total ?? 0,
                    'profit' => $oldOrder->profit ?? 0,
                    'payment_status' => $oldOrder->payment_status ?? 'unpaid',
                    'dine_table_number' => $oldOrder->dine_table_number,
                    'kitchen_notes' => $oldOrder->kitchen_notes,
                    'order_notes' => $oldOrder->order_notes,
                    'order_number' => $orderNumber,
                    'created_at' => Carbon::parse($oldOrder->created_at),
                    'updated_at' => $oldOrder->updated_at,
                ]);
            }
            $migrated++;
        }

        $this->migratedData['orders'] = $migrated;
        $this->line("Migrated {$migrated} orders");
    }

    protected function migrateOrderItems(): void
    {
        $oldOrderItems = DB::connection($this->oldConnection)
            ->table('order_items')
            ->get();

        $migrated = 0;
        foreach ($oldOrderItems as $oldOrderItem) {
            if (!$this->isDryRun) {
                OrderItem::insert([
                    'id' => $oldOrderItem->id,
                    'order_id' => $oldOrderItem->order_id,
                    'product_id' => $oldOrderItem->product_id,
                    'quantity' => $oldOrderItem->quantity ?? 1,
                    'cost' => $oldOrderItem->cost ?? 0,
                    'price' => $oldOrderItem->price ?? 0,
                    'total' => $oldOrderItem->total ?? 0,
                    'notes' => $oldOrderItem->notes,
                    'created_at' => $oldOrderItem->created_at ?? now(),
                    'updated_at' => $oldOrderItem->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['order_items'] = $migrated;
        $this->line("Migrated {$migrated} order items");
    }

    protected function migratePayments(): void
    {
        $oldPayments = DB::connection($this->oldConnection)
            ->table('payments')
            ->get();

        $migrated = 0;
        foreach ($oldPayments as $oldPayment) {
            if (!$this->isDryRun) {
                Payment::insert([
                    'id' => $oldPayment->id,
                    'order_id' => $oldPayment->order_id,
                    'amount' => $oldPayment->paid ?? 0,
                    'method' => $oldPayment->method ?? 'cash',
                    'shift_id' => $oldPayment->shift_id ?? 1, // Default to first shift
                    'created_at' => $oldPayment->created_at ?? now(),
                    'updated_at' => $oldPayment->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['payments'] = $migrated;
        $this->line("Migrated {$migrated} payments");
    }

    protected function migrateInventoryItems(): void
    {
        $oldInventoryItems = DB::connection($this->oldConnection)
            ->table('inventory_items')
            ->get();

        $migrated = 0;
        foreach ($oldInventoryItems as $oldInventoryItem) {
            if (!$this->isDryRun) {
                InventoryItem::insert([
                    'id' => $oldInventoryItem->id,
                    'product_id' => $oldInventoryItem->product_id,
                    'quantity' => $oldInventoryItem->quantity ?? 0,
                    'created_at' => $oldInventoryItem->created_at ?? now(),
                    'updated_at' => $oldInventoryItem->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['inventory_items'] = $migrated;
        $this->line("Migrated {$migrated} inventory items");
    }

    protected function migrateExpenses(): void
    {
        $oldExpenses = DB::connection($this->oldConnection)
            ->table('expenses')
            ->get();

        $migrated = 0;
        foreach ($oldExpenses as $oldExpense) {
            if (!$this->isDryRun) {
                Expense::insert([
                    'id' => $oldExpense->id,
                    'expence_type_id' => $oldExpense->expense_type_id ?? $oldExpense->expence_type_id,
                    'shift_id' => $oldExpense->shift_id ?? 1,
                    'amount' => $oldExpense->amount ?? 0,
                    'notes' => $oldExpense->description,
                    'created_at' => $oldExpense->created_at ?? now(),
                    'updated_at' => $oldExpense->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['expenses'] = $migrated;
        $this->line("Migrated {$migrated} expenses");
    }

    protected function migratePurchaseInvoices(): void
    {
        $oldInvoices = DB::connection($this->oldConnection)
            ->table('purchase_invoices')
            ->get();

        $migrated = 0;
        foreach ($oldInvoices as $oldInvoice) {
            if (!$this->isDryRun) {
                PurchaseInvoice::insert([
                    'id' => $oldInvoice->id,
                    'supplier_id' => $oldInvoice->supplier_id,
                    'total' => $oldInvoice->total ?? 0,
                    'notes' => $oldInvoice->notes ?? '',
                    'user_id' => $oldInvoice->user_id ?? 1, // Default to first user
                    'closed_at' => $oldInvoice->closed ? now() : null,
                    'created_at' => $oldInvoice->created_at ?? now(),
                    'updated_at' => $oldInvoice->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['purchase_invoices'] = $migrated;
        $this->line("Migrated {$migrated} purchase invoices");
    }

    protected function migratePurchaseInvoiceItems(): void
    {
        $oldItems = DB::connection($this->oldConnection)
            ->table('purchase_invoice_items')
            ->get();

        $migrated = 0;
        foreach ($oldItems as $oldItem) {
            if (!$this->isDryRun) {
                PurchaseInvoiceItem::insert([
                    'id' => $oldItem->id,
                    'purchase_invoice_id' => $oldItem->purchase_invoice_id,
                    'product_id' => $oldItem->product_id,
                    'quantity' => $oldItem->quantity ?? 1,
                    'price' => $oldItem->cost ?? 0,
                    'total' => $oldItem->total ?? 0,
                    'created_at' => $oldItem->created_at ?? now(),
                    'updated_at' => $oldItem->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['purchase_invoice_items'] = $migrated;
        $this->line("Migrated {$migrated} purchase invoice items");
    }

    protected function migrateReturnPurchaseInvoices(): void
    {
        $oldInvoices = DB::connection($this->oldConnection)
            ->table('return_purchase_invoices')
            ->get();

        $migrated = 0;
        foreach ($oldInvoices as $oldInvoice) {
            if (!$this->isDryRun) {
                ReturnPurchaseInvoice::insert([
                    'id' => $oldInvoice->id,
                    'supplier_id' => $oldInvoice->supplier_id,
                    'total' => $oldInvoice->total ?? 0,
                    'notes' => $oldInvoice->notes ?? '',
                    'user_id' => $oldInvoice->user_id ?? 1, // Default to first user
                    'closed_at' => $oldInvoice->closed ? now() : null,
                    'created_at' => $oldInvoice->created_at ?? now(),
                    'updated_at' => $oldInvoice->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['return_purchase_invoices'] = $migrated;
        $this->line("Migrated {$migrated} return purchase invoices");
    }

    protected function migrateReturnPurchaseInvoiceItems(): void
    {
        $oldItems = DB::connection($this->oldConnection)
            ->table('return_purchase_invoice_items')
            ->get();

        $migrated = 0;
        foreach ($oldItems as $oldItem) {
            if (!$this->isDryRun) {
                ReturnPurchaseInvoiceItem::insert([
                    'id' => $oldItem->id,
                    'return_purchase_invoice_id' => $oldItem->return_purchase_invoice_id,
                    'product_id' => $oldItem->product_id,
                    'quantity' => $oldItem->quantity ?? 1,
                    'price' => $oldItem->cost ?? 0,
                    'total' => $oldItem->total ?? 0,
                    'created_at' => $oldItem->created_at ?? now(),
                    'updated_at' => $oldItem->updated_at ?? now(),
                ]);
            }
            $migrated++;
        }

        $this->migratedData['return_purchase_invoice_items'] = $migrated;
        $this->line("Migrated {$migrated} return purchase invoice items");
    }

    // protected function migrateStocktakings(): void
    // {
    //     $oldStocktakings = DB::connection($this->oldConnection)
    //         ->table('stocktakings')
    //         ->get();

    //     $migrated = 0;
    //     foreach ($oldStocktakings as $oldStocktaking) {
    //         if (!$this->isDryRun) {
    //             Stocktaking::insert([
    //                 'id' => $oldStocktaking->id,
    //                 'user_id' => $oldStocktaking->user_id ?? 1,
    //                 'notes' => $oldStocktaking->notes ?? '',
    //                 'total' => $oldStocktaking->total,
    //                 'closed_at' => $oldStocktaking->closed ? now() : null,
    //                 'created_at' => $oldStocktaking->created_at ?? now(),
    //                 'updated_at' => $oldStocktaking->updated_at ?? now(),
    //             ]);
    //         }
    //         $migrated++;
    //     }

    //     $this->migratedData['stocktakings'] = $migrated;
    //     $this->line("Migrated {$migrated} stocktakings");
    // }

    // protected function migrateStocktakingItems(): void
    // {
    //     $oldItems = DB::connection($this->oldConnection)
    //         ->table('stocktaking_items')
    //         ->get();

    //     $migrated = 0;
    //     foreach ($oldItems as $oldItem) {
    //         if (!$this->isDryRun) {
    //             StocktakingItem::insert([
    //                 'id' => $oldItem->id,
    //                 'stocktaking_id' => $oldItem->stocktaking_id,
    //                 'inventory_item_id' => $oldItem->inventory_item_id,
    //                 'system_quantity' => $oldItem->system_quantity ?? 0,
    //                 'actual_quantity' => $oldItem->actual_quantity ?? 0,
    //                 'difference' => $oldItem->difference ?? 0,
    //                 'created_at' => $oldItem->created_at ?? now(),
    //                 'updated_at' => $oldItem->updated_at ?? now(),
    //             ]);
    //         }
    //         $migrated++;
    //     }

    //     $this->migratedData['stocktaking_items'] = $migrated;
    //     $this->line("Migrated {$migrated} stocktaking items");
    // }

    // protected function migrateWastes(): void
    // {
    //     $oldWastes = DB::connection($this->oldConnection)
    //         ->table('wastes')
    //         ->get();

    //     $migrated = 0;
    //     foreach ($oldWastes as $oldWaste) {
    //         if (!$this->isDryRun) {
    //             Waste::insert([
    //                 'id' => $oldWaste->id,
    //                 'shift_id' => $oldWaste->shift_id ?? 1,
    //                 'reason' => $oldWaste->reason ?? 'Unknown',
    //                 'notes' => $oldWaste->notes,
    //                 'created_at' => $oldWaste->created_at ?? now(),
    //                 'updated_at' => $oldWaste->updated_at ?? now(),
    //             ]);
    //         }
    //         $migrated++;
    //     }

    //     $this->migratedData['wastes'] = $migrated;
    //     $this->line("Migrated {$migrated} wastes");
    // }

    // protected function migrateWastedItems(): void
    // {
    //     $oldItems = DB::connection($this->oldConnection)
    //         ->table('wasted_items')
    //         ->get();

    //     $migrated = 0;
    //     foreach ($oldItems as $oldItem) {
    //         if (!$this->isDryRun) {
    //             WastedItem::insert([
    //                 'id' => $oldItem->id,
    //                 'waste_id' => $oldItem->waste_id,
    //                 'inventory_item_id' => $oldItem->inventory_item_id,
    //                 'quantity' => $oldItem->quantity ?? 1,
    //                 'created_at' => $oldItem->created_at ?? now(),
    //                 'updated_at' => $oldItem->updated_at ?? now(),
    //             ]);
    //         }
    //         $migrated++;
    //     }

    //     $this->migratedData['wasted_items'] = $migrated;
    //     $this->line("Migrated {$migrated} wasted items");
    // }

    // protected function migrateDailySnapshots(): void
    // {
    //     $oldSnapshots = DB::connection($this->oldConnection)
    //         ->table('daily_snapshots')
    //         ->get();

    //     $migrated = 0;
    //     foreach ($oldSnapshots as $oldSnapshot) {
    //         if (!$this->isDryRun) {
    //             DailySnapshot::insert([
    //                 'id' => $oldSnapshot->id,
    //                 'date' => $oldSnapshot->date ?? now(),
    //                 'total_sales' => $oldSnapshot->total_sales ?? 0,
    //                 'total_orders' => $oldSnapshot->total_orders ?? 0,
    //                 'total_expenses' => $oldSnapshot->total_expenses ?? 0,
    //                 'net_profit' => $oldSnapshot->net_profit ?? 0,
    //                 'created_at' => $oldSnapshot->created_at ?? now(),
    //                 'updated_at' => $oldSnapshot->updated_at ?? now(),
    //             ]);
    //         }
    //         $migrated++;
    //     }

    //     $this->migratedData['daily_snapshots'] = $migrated;
    //     $this->line("Migrated {$migrated} daily snapshots");
    // }

    protected function showMigrationSummary(): void
    {
        $this->info("\n=== Migration Summary ===");
        $total = 0;
        foreach ($this->migratedData as $table => $count) {
            $this->line("{$table}: {$count} records");
            $total += $count;
        }
        $this->info("Total records migrated: {$total}");

        if ($this->isDryRun) {
            $this->warn("This was a dry run - no data was actually migrated");
        }
    }

    protected function prepareDatabaseForMigration(): void
    {
        $this->info('Preparing database for migration...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Disable auto-increment for identity insert
        $this->line('Disabling foreign key checks and preparing for ID preservation...');
    }

    protected function finalizeDatabaseAfterMigration(): void
    {
        $this->info('Finalizing database after migration...');

        // Get all tables with auto-increment and update their auto-increment values
        $tables = [
            'categories',
            'regions',
            'customers',
            'suppliers',
            'printers',
            'products',
            'product_components',
            'drivers',
            'expense_types',
            'settings',
            'users',
            'shifts',
            'orders',
            'order_items',
            'payments',
            'inventory_items',
            'expenses',
            'purchase_invoices',
            'purchase_invoice_items',
            'return_purchase_invoices',
            'return_purchase_invoice_items',
            'stocktakings',
            'stocktaking_items',
            'wastes',
            'wasted_items',
            'daily_snapshots'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    // Get the maximum ID from the table
                    $maxId = DB::table($table)->max('id');
                    if ($maxId) {
                        $nextAutoIncrement = $maxId + 1;
                        DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$nextAutoIncrement}");
                        $this->line("Set {$table} auto_increment to {$nextAutoIncrement}");
                    }
                } catch (Exception $e) {
                    $this->warn("Could not update auto_increment for {$table}: " . $e->getMessage());
                }
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->line('Re-enabled foreign key checks');
    }

    protected function insertWithId(string $table, array $data): void
    {
        DB::table($table)->insert($data);
    }
}
