# Return Order Implementation - Product Requirements Document

## 1. Overview

### 1.1 Objective
Implement a comprehensive return order system that allows customers to return items from completed orders, automatically adjusts inventory levels, and updates all financial reports to accurately reflect return transactions.

### 1.2 Background
The current cashier system handles regular orders and stock management but lacks the ability to process returns. This functionality is essential for:
- Customer satisfaction and service quality
- Accurate inventory management
- Correct financial reporting
- Compliance with business policies

### 1.3 Scope
This implementation covers:
- Database schema for return orders and return items
- Backend API for processing returns
- Frontend interface for selecting and processing returns
- Inventory adjustment mechanisms
- Report updates to include return calculations
- Validation and business logic for return processing

## 2. Technical Architecture

### 2.1 Technology Stack
- **Backend**: Laravel 11.45.2 with PHP 8.4.8
- **Frontend**: React 18.3.1 with Inertia.js 2.0.5
- **Database**: MySQL
- **UI Framework**: Tailwind CSS 4.1.12

### 2.2 Existing System Integration
The return order system will integrate with:
- Order management system (`Order` and `OrderItem` models)
- Stock management service (`StockService` and `OrderStockConversionService`)
- Reporting services (Products, Customers, Channel, Peak Hours reports)
- Payment processing system
- Filament Admin panel for return order management

## 3. Database Requirements

### 3.1 Return Orders Table (`return_orders`)

```sql
CREATE TABLE return_orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED NOT NULL,
    return_number INT NOT NULL,
    status ENUM('processing', 'completed') NOT NULL DEFAULT 'processing',
    refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_return_number_per_shift (shift_id, return_number),
    INDEX idx_return_orders_order_id (order_id),
    INDEX idx_return_orders_created_at (created_at),
    INDEX idx_return_orders_status (status)
);
```

### 3.2 Return Items Table (`return_items`)

```sql
CREATE TABLE return_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    return_order_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DOUBLE NOT NULL,
    original_price DECIMAL(10,2) NOT NULL,
    original_cost DECIMAL(10,2) NOT NULL,
    return_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (return_order_id) REFERENCES return_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_return_items_return_order_id (return_order_id),
    INDEX idx_return_items_product_id (product_id),
    INDEX idx_return_items_order_item_id (order_item_id)
);
```

### 3.3 Migration Files Required

1. `create_return_orders_table.php`
2. `create_return_items_table.php`

## 4. Backend Requirements

### 4.1 Models

#### 4.1.1 ReturnOrder Model
```php
<?php

namespace App\Models;

use App\Enums\ReturnOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnOrder extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id', 
        'user_id',
        'shift_id',
        'return_number',
        'status',
        'refund_amount',
        'reason',
        'notes'
    ];

    protected $casts = [
        'status' => ReturnOrderStatus::class,
        'refund_amount' => 'decimal:2'
    ];

    public function order(): BelongsTo;
    public function customer(): BelongsTo;
    public function user(): BelongsTo;
    public function shift(): BelongsTo;
    public function items(): HasMany;
}
```

#### 4.1.2 ReturnItem Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_order_id',
        'order_item_id',
        'product_id',
        'quantity',
        'original_price',
        'original_cost',
        'return_price', 
        'total',
        'reason'
    ];

    protected $casts = [
        'quantity' => 'double',
        'original_price' => 'decimal:2',
        'original_cost' => 'decimal:2',
        'return_price' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function returnOrder(): BelongsTo;
    public function orderItem(): BelongsTo;
    public function product(): BelongsTo;
}
```

### 4.2 Enums

#### 4.2.1 ReturnOrderStatus Enum
```php
<?php

namespace App\Enums;

enum ReturnOrderStatus: string
{
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PROCESSING => 'قيد المعالجة',
            self::COMPLETED => 'مكتمل'
        };
    }

    public function canBeModified(): bool
    {
        return $this === self::PROCESSING;
    }
}
```

### 4.3 Services

#### 4.3.1 ReturnOrderService
Responsibilities:
- Create new return orders
- Validate return requests
- Calculate return totals
- Process return completions
- Handle return cancellations (by deleting the record)

Key Methods:
- `createReturnOrder(CreateReturnOrderDTO $dto): ReturnOrder`
- `addReturnItems(int $returnOrderId, array $items): void`
- `completeReturnOrder(int $returnOrderId): bool`
- `deleteReturnOrder(int $returnOrderId): bool` (for cancellation)

#### 4.3.2 ReturnStockService  
Responsibilities:
- Add returned items back to inventory
- Reverse the stock removal from original order
- Log inventory movements for returns

Key Methods:
- `addStockForReturnOrder(ReturnOrder $returnOrder): bool`
- `validateReturnStockImpact(ReturnOrder $returnOrder): array`

### 4.4 Inertia.js Routes and Controllers

#### 4.4.1 Return Order Routes
```php
// In routes/web.php
Route::prefix('return-orders')->middleware(['auth'])->group(function () {
    Route::get('/', [ReturnOrderController::class, 'index'])->name('return-orders.index');
    Route::get('/create', [ReturnOrderController::class, 'create'])->name('return-orders.create');
    Route::post('/', [ReturnOrderController::class, 'store'])->name('return-orders.store');
    Route::get('/{returnOrder}', [ReturnOrderController::class, 'show'])->name('return-orders.show');
    Route::get('/{returnOrder}/edit', [ReturnOrderController::class, 'edit'])->name('return-orders.edit');
    Route::put('/{returnOrder}', [ReturnOrderController::class, 'update'])->name('return-orders.update');
    Route::delete('/{returnOrder}', [ReturnOrderController::class, 'destroy'])->name('return-orders.destroy');
    Route::post('/{returnOrder}/complete', [ReturnOrderController::class, 'complete'])->name('return-orders.complete');
});
```

#### 4.4.2 Required Controller Methods
```php
<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ReturnOrderController extends Controller
{
    public function index(): Response;
    public function create(): Response;
    public function store(StoreReturnOrderRequest $request): RedirectResponse;
    public function show(ReturnOrder $returnOrder): Response;
    public function edit(ReturnOrder $returnOrder): Response;
    public function update(UpdateReturnOrderRequest $request, ReturnOrder $returnOrder): RedirectResponse;
    public function destroy(ReturnOrder $returnOrder): RedirectResponse;
    public function complete(ReturnOrder $returnOrder): RedirectResponse;
}
```

#### 4.4.3 Filament Admin Resource
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnOrderResource\Pages;
use App\Models\ReturnOrder;
use Filament\Resources\Resource;

class ReturnOrderResource extends Resource
{
    protected static ?string $model = ReturnOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationLabel = 'أوامر الإرجاع';
    protected static ?string $modelLabel = 'أمر إرجاع';
    protected static ?string $pluralModelLabel = 'أوامر الإرجاع';
    
    // Tables, Forms, and Pages configuration
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnOrders::route('/'),
            'create' => Pages\CreateReturnOrder::route('/create'),
            'edit' => Pages\EditReturnOrder::route('/{record}/edit'),
            'view' => Pages\ViewReturnOrder::route('/{record}'),
        ];
    }
}
```

### 4.5 Request Validation

#### 4.5.1 StoreReturnOrderRequest
```php
<?php

namespace App\Http\Requests;

class StoreReturnOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.return_price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:255'
        ];
    }
}
```

### 4.6 DTOs

#### 4.6.1 CreateReturnOrderDTO
```php
<?php

namespace App\DTOs;

class CreateReturnOrderDTO
{
    public function __construct(
        public int $orderId,
        public int $userId,
        public ?string $reason = null,
        public ?string $notes = null,
        public array $items = []
    ) {}
}
```

## 5. Frontend Requirements

### 5.1 New Tab Implementation

#### 5.1.1 Location
Add new tab in the main orders interface: `resources/js/Components/Orders/Index/ReturnOrderTab.tsx`

#### 5.1.2 Inertia.js Pages Required

1. **Return Orders Index Page** (`resources/js/Pages/ReturnOrders/Index.tsx`)
   - List all return orders with filtering and search
   - Links to view, edit, and create return orders

2. **Create Return Order Page** (`resources/js/Pages/ReturnOrders/Create.tsx`)
   - Order ID input and validation
   - Order items display and selection
   - Return item configuration

3. **Edit Return Order Page** (`resources/js/Pages/ReturnOrders/Edit.tsx`)  
   - Modify return items (only if status is 'processing')
   - Update return reasons and notes

4. **View Return Order Page** (`resources/js/Pages/ReturnOrders/Show.tsx`)
   - Display return order details
   - Show return items and totals
   - Complete return action button

#### 5.1.3 UI Components Required

1. **Order Lookup Component**
   - Search field for order ID (not order number)
   - Order validation and details display
   - Integration with backend validation

2. **Order Items Display Component**
   - Show all items from the selected order
   - Display: product name, original quantity, price, total
   - Show remaining returnable quantity (considering previous returns)

3. **Return Item Selection Component**
   - Checkboxes for item selection
   - Quantity input with validation
   - Reason selection dropdown
   - Price adjustment capability

4. **Return Summary Component**
   - Total items being returned
   - Total refund amount
   - Return reason and notes
   - Confirmation actions

#### 5.1.4 State Management

```typescript
interface ReturnOrderState {
    selectedOrder: Order | null;
    orderItems: OrderItem[];
    selectedItems: ReturnItem[];
    returnReason: string;
    returnNotes: string;
    totalRefund: number;
    isLoading: boolean;
    errors: Record<string, string>;
}

interface CreateReturnOrderProps extends PageProps {
    orders?: Order[];  // For order lookup
    order?: Order;     // Selected order with items
    returnReasons?: string[];
}
```

#### 5.1.5 Validation Rules (Frontend & Backend)

1. **Order Validation**
   - Order must exist and be completed
   - Order must not be fully returned
   - Order must be within return policy timeframe (if applicable)

2. **Item Validation**  
   - Quantity must not exceed remaining returnable quantity
   - At least one item must be selected
   - Return price cannot exceed original price

3. **Business Rules**
   - Cannot return more than originally ordered
   - Must account for previous returns
   - Return quantities must be positive numbers
   - Validation handled in store/update methods, not separate endpoints

### 5.2 User Interface Flow

1. **Step 1: Navigate to Return Orders**
   - Access via main navigation or orders interface
   - Choose "Create Return Order" action

2. **Step 2: Order Selection**
   - User enters order ID in create form
   - System validates and displays order details
   - Shows order items with returnable quantities

3. **Step 3: Item Selection**
   - User selects items to return
   - Adjusts quantities and reasons
   - System calculates totals in real-time

4. **Step 4: Return Configuration**
   - Add overall return reason/notes
   - Review return details
   - Submit return order (creates with 'processing' status)

5. **Step 5: Processing & Completion**
   - Return order created in 'processing' status
   - Admin can complete via Filament or frontend
   - Cancellation deletes the return order
   - Completion adjusts stock and finalizes return

## 6. Stock Management Integration

### 6.1 Stock Adjustment Process

When a return order is completed:

1. **Inventory Addition**
   - Add returned quantities back to inventory
   - Use `MovementReason::ORDER_RETURN`
   - Reference the return order for tracking

2. **Movement Logging**
   - Create inventory movement records
   - Track return-specific metadata
   - Maintain audit trail

3. **Daily Aggregation Update**
   - Update daily aggregation tables
   - Ensure reporting accuracy

### 6.2 Integration with OrderStockConversionService

Extend the existing service with return-specific methods:

```php
<?php

namespace App\Services\Orders;

class OrderStockConversionService
{
    // Existing methods...
    
    /**
     * Add stock back for return order
     */
    public function addStockForReturnOrder(ReturnOrder $returnOrder): bool;
    
    /**
     * Convert return items to stock items for addition
     */
    public function convertReturnItemsToStockItems(ReturnOrder $returnOrder): array;
    
    /**
     * Validate return stock impact
     */
    public function validateReturnStockImpact(ReturnOrder $returnOrder): array;
}
```

## 7. Reporting Requirements

### 7.1 Report Updates Required

All existing reports must be updated to account for return orders:

#### 7.1.1 ProductsSalesReportService
- Subtract returned quantities from sales figures
- Adjust profit calculations for returns
- Include return metrics in summaries

#### 7.1.2 CustomersPerformanceReportService  
- Deduct return amounts from customer sales totals
- Track return frequency per customer
- Adjust customer lifetime value calculations

#### 7.1.3 ChannelPerformanceReportService
- Account for returns by order type
- Adjust channel revenue figures
- Include return rates in performance metrics

#### 7.1.4 PeakHoursPerformanceReportService
- Factor returns into hourly performance
- Adjust peak hour calculations
- Include return timing analysis

#### 7.1.5 ShiftsReportService
- Include return totals per shift
- Adjust shift revenue calculations
- Track return processing by shift

### 7.2 New Return-Specific Reports

#### 7.2.1 Return Analysis Report
- Return frequency by product
- Return reasons analysis
- Return trends over time
- Customer return patterns

#### 7.2.2 Return Impact Dashboard
- Real-time return metrics
- Financial impact of returns
- Inventory impact tracking
- Return processing efficiency

### 7.3 Database Query Updates

#### 7.3.1 Sales Calculation Updates
```sql
-- Example: Adjusted sales calculation
SELECT 
    SUM(order_items.total) - COALESCE(SUM(return_items.total), 0) as net_sales
FROM order_items
LEFT JOIN return_items ON order_items.id = return_items.order_item_id
WHERE ...
```

#### 7.3.2 Profit Calculation Updates
```sql  
-- Example: Adjusted profit calculation
SELECT 
    (SUM(order_items.total) - COALESCE(SUM(return_items.total), 0)) - 
    (SUM(order_items.cost * order_items.quantity) - COALESCE(SUM(return_items.original_cost * return_items.quantity), 0)) as net_profit
FROM order_items
LEFT JOIN return_items ON order_items.id = return_items.order_item_id
WHERE ...
```

## 8. Business Logic Requirements

### 8.1 Return Policy Rules

1. **Time Limits**
   - Returns must be processed within defined timeframe
   - Configurable return window (e.g., 30 days)

2. **Quantity Limits**
   - Cannot return more than originally ordered
   - Must account for previous returns on same order

3. **Product Eligibility**
   - All product types can be returned unless specifically excluded
   - Special handling for manufactured vs consumable products

4. **Status Requirements**
   - Original order must be completed
   - Order cannot be cancelled or voided

### 8.2 Return Processing Rules

1. **Validation Sequence**
   - Validate order exists and is eligible (in store/index/show methods)
   - Validate item quantities and availability
   - Check business rules and constraints
   - Verify user permissions

2. **Stock Impact**
   - Add items back to inventory when return is completed (not when created)
   - Handle manufactured product returns (add components back)
   - Update all related inventory records

3. **Financial Impact**
   - Calculate refund amounts based on return prices
   - Update financial records only when return is completed
   - Handle partial refunds appropriately

4. **Status Management**
   - Create return orders in 'processing' status
   - Complete returns change status to 'completed' and adjust stock
   - Canceling returns deletes the record entirely

## 9. Security Requirements

### 9.1 Access Control
- Return processing requires appropriate user permissions
- Audit trail for all return operations
- Role-based access to return functionality

### 9.2 Data Validation
- Comprehensive input validation on all return data
- Prevention of duplicate returns
- Protection against manipulation of return amounts

### 9.3 Business Rule Enforcement
- Server-side validation of all business rules
- Prevention of invalid return scenarios
- Automatic rollback on processing failures

## 10. Testing Requirements

### 10.1 Unit Tests
- Model relationships and validations
- Service layer business logic
- Stock calculation methods
- Report calculation accuracy

### 10.2 Feature Tests
- Complete return order processing flow
- API endpoint functionality
- Stock adjustment verification
- Report accuracy after returns

### 10.3 Integration Tests
- Frontend-backend integration
- Database transaction integrity
- Stock service integration
- Report service integration

## 11. Performance Considerations

### 11.1 Database Optimization
- Proper indexing on return tables
- Efficient joins for report queries
- Query optimization for large datasets

### 11.2 Caching Strategy
- Cache returnable items data
- Optimize report calculations
- Implement appropriate cache invalidation

### 11.3 Scalability
- Handle large volumes of return transactions
- Efficient bulk return processing
- Optimized report generation

## 12. Migration and Deployment

### 12.1 Database Migration Strategy
1. Create new tables with foreign key constraints
2. Add indexes for performance
3. Ensure compatibility with existing data

### 12.2 Feature Rollout
1. Backend models, migrations, and services implementation
2. Database schema deployment
3. Filament admin resource for return order management
4. Inertia.js frontend pages and components development
5. Report system updates to include return calculations
6. User training and documentation

### 12.3 Rollback Plan
- Database rollback procedures
- Feature flag implementation
- Data backup and recovery strategy

## 13. Success Criteria

### 13.1 Functional Requirements Met
- ✅ Users can process returns through intuitive interface
- ✅ Stock levels automatically adjust for returns
- ✅ All reports accurately reflect return transactions
- ✅ System maintains data integrity throughout process

### 13.2 Performance Benchmarks
- Return processing completes within 3 seconds
- Report generation includes returns without performance degradation
- System handles concurrent return processing

### 13.3 Business Impact
- Improved customer satisfaction through return capability
- Accurate inventory tracking and reporting
- Better financial visibility and control
- Streamlined return process for staff

## 14. Future Enhancements

### 14.1 Advanced Features
- Automated return reason analysis
- Return policy enforcement automation
- Integration with refund processing systems
- Advanced return analytics and insights

### 14.2 Reporting Enhancements
- Return trend analysis
- Predictive return modeling
- Advanced return dashboards
- Customer return behavior analysis

This PRD provides a comprehensive blueprint for implementing the return order functionality while ensuring integration with existing systems and maintaining data integrity throughout the process.
