# Item-Level Discount System Documentation

## Overview

The larament system supports two types of discounts:
1. **Item-Level Discounts** - Applied to individual order items
2. **Order-Level Discounts** - Applied to the entire order

**Important: These are mutually exclusive.** When item-level discounts are applied, order-level discounts are cleared, and vice versa.

---

## Database Structure

### Table: `order_items`

| Column | Type | Description |
|--------|------|-------------|
| `item_discount` | `decimal(10,2)` | The calculated discount amount in EGP (default: 0) |
| `item_discount_type` | `string` (nullable) | Either `'percent'` or `'value'` |
| `item_discount_percent` | `decimal(5,2)` (nullable) | The percentage value when type is `'percent'` |

### Migration Example

```php
$table->decimal('item_discount', 10, 2)->default(0);
$table->string('item_discount_type')->nullable();
$table->decimal('item_discount_percent', 5, 2)->nullable();
```

---

## Discount Types

### 1. Fixed Value Discount (`type: 'value'`)
- The `item_discount` field contains the exact EGP amount to deduct
- `item_discount_percent` is `null`
- Example: 10 EGP off

### 2. Percentage Discount (`type: 'percent'`)
- The `item_discount_percent` field contains the percentage (0-100)
- The `item_discount` field is calculated as: `item_subtotal * (percent / 100)`
- Example: 15% off

---

## Calculation Logic

### Item Subtotal Calculation

```typescript
const itemSubtotal = item.price * item.quantity;
```

### Item Discount Calculation

```typescript
let itemDiscount = 0;

if (item.item_discount_type === 'percent' && item.item_discount_percent) {
    // Percentage discount
    itemDiscount = itemSubtotal * (item.item_discount_percent / 100);
} else {
    // Fixed value discount
    itemDiscount = item.item_discount ?? 0;
}

// Ensure discount doesn't exceed item subtotal
itemDiscount = Math.min(itemDiscount, itemSubtotal);
```

### Total Order Discount (when using item discounts)

```typescript
const totalDiscount = orderItems.reduce((acc, item) => {
    // Calculate each item's discount
    const itemSubtotal = item.price * item.quantity;
    let itemDiscount = 0;

    if (item.item_discount_type === 'percent' && item.item_discount_percent) {
        itemDiscount = itemSubtotal * (item.item_discount_percent / 100);
    } else {
        itemDiscount = item.item_discount ?? 0;
    }

    itemDiscount = Math.min(itemDiscount, itemSubtotal);
    return acc + itemDiscount;
}, 0);
```

---

## Frontend Implementation

### TypeScript Types (`types/index.d.ts`)

```typescript
export interface OrderItemData {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    notes?: string;
    initial_quantity?: number;
    item_discount?: number;
    item_discount_type?: string;      // 'percent' | 'value'
    item_discount_percent?: number;
    product: Product;
}

export type OrderItemAction =
    | { type: 'changeItemDiscount'; id: number; discount: number; discountType: string; discountPercent?: number; user: User }
    // ... other actions
```

### Reducer Action (`utils/orderItemsReducer.ts`)

```typescript
case "changeItemDiscount": {
    return state.map((item) => {
        if (item.product_id !== action.id) return item;
        return {
            ...item,
            item_discount: action.discount,
            item_discount_type: action.discountType,
            item_discount_percent: action.discountPercent,
        };
    });
}
```

### UI Component (`Components/Orders/OrderItem.tsx`)

The OrderItem component displays:
- Discount button (requires `discounts` permission)
- Modal for entering discount type and value
- Display of applied discount with amount and percentage

```tsx
{itemDiscount > 0 && (
    <>
        <Typography.Text>
            الخصم :
            <Tag color="error">
                {formatCurrency(itemDiscount)}
                {orderItem.item_discount_type === 'percent' &&
                    ` (${orderItem.item_discount_percent}%)`
                }
            </Tag>
        </Typography.Text>
        <Typography.Text strong>
            الإجمالي :
            <Tag color="blue">{formatCurrency(itemTotal)}</Tag>
        </Typography.Text>
    </>
)}
```

---

## Backend Implementation

### DTO (`DTOs/OrderItemDTO.php`)

```php
public function __construct(
    public readonly int $productId,
    public readonly int $quantity,
    public readonly float $price,
    public readonly ?string $notes = null,
    public readonly float $itemDiscount = 0,
    public readonly ?string $itemDiscountType = null,
    public readonly ?float $itemDiscountPercent = null,
) {}
```

### Request Validation (`Http/Requests/SaveOrderRequest.php`)

```php
'items.*.item_discount' => 'nullable|numeric|min:0',
'items.*.item_discount_type' => 'nullable|string|in:percent,value',
'items.*.item_discount_percent' => 'nullable|numeric|min:0|max:100',
```

### Order Service (`Services/OrderService.php`)

When updating order items:

```php
// Calculate item discount based on type
if ($itemDiscountType === 'percent' && $itemDiscountPercent) {
    $itemSubtotal = $product->price * $item['quantity'];
    $itemDiscount = $itemSubtotal * ($itemDiscountPercent / 100);
    $itemDiscount = min($itemDiscount, $itemSubtotal);
} elseif (isset($item['item_discount'])) {
    $itemSubtotal = $product->price * $item['quantity'];
    $itemDiscount = min($item['item_discount'], $itemSubtotal);
}

// Update order item
$orderItem->update([
    'item_discount' => $itemDiscount,
    'item_discount_type' => $itemDiscountType,
    'item_discount_percent' => $itemDiscountPercent,
]);
```

### Order Calculation Service (`Services/OrderCalculationService.php`)

The `calculateDiscount()` method:
1. Checks for item-level discounts first
2. If present, sums all item discounts (respecting type)
3. If no item discounts, uses order-level discount

---

## Mutual Exclusivity Rules

### Rule 1: Item Discounts Clear Order Discount

When any item has a discount applied:
- Order's `discount` is set to `0`
- Order's `temp_discount_percent` is set to `0`

```php
// In OrderService when item discounts exist
if ($hasItemDiscounts) {
    $order->update([
        'discount' => 0,
        'temp_discount_percent' => 0,
    ]);
}
```

### Rule 2: Order Discount Clears Item Discounts

When an order-level discount is applied:
- All items' `item_discount` is set to `0`
- All items' `item_discount_type` is set to `null`
- All items' `item_discount_percent` is set to `null`

```php
// In applyDiscount method
$order->items()->update([
    'item_discount' => 0,
    'item_discount_type' => null,
    'item_discount_percent' => null,
]);
```

### Frontend Enforcement

The order discount button is disabled when item discounts exist:

```tsx
const hasItemDiscounts = orderItems.some(item => (item.item_discount ?? 0) > 0);

<LoadingButton
    disabled={disableAllControls || hasItemDiscounts}
    // ...
>
    خصم الطلب
</LoadingButton>
```

---

## User Interface Flow

### Applying Item Discount

1. User clicks on an order item
2. User clicks the discount button (requires `discounts` permission)
3. Modal opens with:
   - Radio/Select to choose discount type (Fixed Value / Percentage)
   - Input field for discount value
4. Validation:
   - Percentage: Max 100%
   - Fixed Value: Max item subtotal
5. On submit, `changeItemDiscount` action is dispatched

### Visual Indicators

- Items with discounts show the discount amount with a red tag
- If percentage, also shows the percentage value
- Order details section shows a tag "خصومات على الأصناف" (Item Discounts) when item discounts exist

---

## Permissions

The `discounts` permission is required to:
- Apply item-level discounts
- Apply order-level discounts

```tsx
<CanAccess permission="discounts">
    {/* Discount UI components */}
</CanAccess>
```

---

## API Endpoint

### Save Order with Items

**Endpoint:** `POST /orders/save-order/{orderId}`

**Payload:**
```json
{
    "items": [
        {
            "product_id": 1,
            "quantity": 2,
            "price": 50.00,
            "notes": null,
            "item_discount": 10.00,
            "item_discount_type": "value",
            "item_discount_percent": null
        },
        {
            "product_id": 2,
            "quantity": 1,
            "price": 100.00,
            "notes": null,
            "item_discount": 15.00,
            "item_discount_type": "percent",
            "item_discount_percent": 15.00
        }
    ]
}
```

---

## Summary Table

| Layer | Component | Responsibility |
|-------|-----------|----------------|
| **Database** | `order_items` table | Store 3 discount fields per item |
| **Model** | `OrderItem.php` | Define fillable fields and casts |
| **DTO** | `OrderItemDTO.php` | Transfer discount data between layers |
| **Validation** | `SaveOrderRequest.php` | Validate discount inputs |
| **Service** | `OrderService.php` | Calculate & save item discounts |
| **Calculation** | `OrderCalculationService.php` | Aggregate discounts for order total |
| **Frontend Types** | `types/index.d.ts` | TypeScript interfaces |
| **Reducer** | `orderItemsReducer.ts` | Handle `changeItemDiscount` action |
| **Calculations** | `orderCalculations.ts` | Calculate totals with discounts |
| **UI** | `OrderItem.tsx` | Display/edit discount modal |

---

## Example Scenarios

### Scenario 1: 10% Off One Item

- Item: Pizza (100 EGP × 2 = 200 EGP subtotal)
- Discount Type: `percent`
- Discount Percent: `10`
- Calculated Discount: `200 × 0.10 = 20 EGP`
- Item Total: `200 - 20 = 180 EGP`

### Scenario 2: Fixed 15 EGP Off

- Item: Burger (50 EGP × 3 = 150 EGP subtotal)
- Discount Type: `value`
- Discount Amount: `15`
- Item Total: `150 - 15 = 135 EGP`

### Scenario 3: Multiple Items with Discounts

| Item | Subtotal | Discount Type | Value | Discount | Final |
|------|----------|---------------|-------|----------|-------|
| Pizza | 200 EGP | percent | 10% | 20 EGP | 180 EGP |
| Burger | 150 EGP | value | 15 EGP | 15 EGP | 135 EGP |
| Drink | 30 EGP | - | - | 0 EGP | 30 EGP |
| **Total** | **380 EGP** | | | **35 EGP** | **345 EGP** |
