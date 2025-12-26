# Currency Helpers Migration Prompt

## Objective
Replace all hardcoded currency references (EGP, ج.م, جنيه) with centralized currency helper functions to enable system-wide currency management through settings.

## Available Currency Helper Functions

Located in `app/Helpers.php`:

### 1. `currency_symbol()`
Returns the currency symbol (e.g., 'ج.م', '$', '€')
```php
currency_symbol() // Returns: 'ج.م' (configurable)
```

### 2. `currency_code()`
Returns the currency code (e.g., 'EGP', 'USD', 'EUR')
```php
currency_code() // Returns: 'EGP' (configurable)
```

### 3. `currency_name()`
Returns the currency name in Arabic (e.g., 'جنيه', 'دولار', 'يورو')
```php
currency_name() // Returns: 'جنيه' (configurable)
```

### 4. `currency_decimals()`
Returns the number of decimal places for currency display
```php
currency_decimals() // Returns: 2 (configurable)
```

### 5. `format_money(float $amount, ?int $decimals = null, bool $showSymbol = true)`
Formats a monetary amount with currency symbol
```php
format_money(1500)           // Returns: '1,500.00 ج.م'
format_money(1500, 0)        // Returns: '1,500 ج.م'
format_money(1500, 2, false) // Returns: '1,500.00'
```

## What to Replace

### 1. Hardcoded Currency Codes in Filament Tables
**BEFORE:**
```php
TextColumn::make('total_sales')
    ->money('EGP')
```

**AFTER:**
```php
TextColumn::make('total_sales')
    ->money(currency_code())
```

### 2. Manual Number Formatting with Currency Symbol
**BEFORE:**
```php
number_format($amount, 2) . ' ج.م'
```

**AFTER:**
```php
format_money($amount)
```

### 3. Manual Number Formatting with "جنيه"
**BEFORE:**
```php
number_format($amount, 2) . ' جنيه'
```

**AFTER:**
```php
format_money($amount)
```

### 4. Currency Symbol in Chart Labels
**BEFORE:**
```php
'label' => 'الإيرادات (ج.م)'
```

**AFTER:**
```php
'label' => 'الإيرادات (' . currency_symbol() . ')'
```

### 5. JavaScript/Chart.js Callbacks with Hardcoded Currency
**BEFORE:**
```php
'label' => "function(context) {
    return context.parsed + ' ج.م';
}"
```

**AFTER:**
```php
'label' => "function(context) {
    return context.parsed + ' " . currency_symbol() . "';
}"
```

## Common Patterns to Search For

Use these search patterns to find hardcoded currency references:

1. `'EGP'` - Hardcoded currency code
2. `ج.م` - Arabic currency symbol
3. `جنيه` - Arabic currency name
4. `number_format($` followed by `) . ' ج.م'` or `) . ' جنيه'`
5. `->money('` - Filament money column with hardcoded currency

## Examples from Completed Files

### Example 1: CategoryPerformanceWidget.php
```php
// BEFORE
TextColumn::make('total_sales')
    ->label('إجمالي المبيعات')
    ->money('EGP')
    ->sortable(),

// AFTER
TextColumn::make('total_sales')
    ->label('إجمالي المبيعات')
    ->money(currency_code())
    ->sortable(),
```

### Example 2: CategoryPerformanceWidget.php - Custom Formatting
```php
// BEFORE
TextColumn::make('avg_sales_per_product')
    ->label('متوسط المبيعات')
    ->getStateUsing(function ($record) {
        $avg = $record->total_quantity > 0 ? $record->total_sales / $record->total_quantity : 0;
        return number_format($avg, 2) . ' ج.م';
    }),

// AFTER
TextColumn::make('avg_sales_per_product')
    ->label('متوسط المبيعات')
    ->getStateUsing(function ($record) {
        $avg = $record->total_quantity > 0 ? $record->total_sales / $record->total_quantity : 0;
        return format_money($avg);
    }),
```

### Example 3: ChannelPerformanceStatsWidget.php - Stats Widget
```php
// BEFORE
Stat::make('إجمالي الإيرادات', number_format($metrics['total_revenue'], 2) . ' ج.م')
    ->description('إجمالي إيرادات جميع القنوات')

// AFTER
Stat::make('إجمالي الإيرادات', format_money($metrics['total_revenue']))
    ->description('إجمالي إيرادات جميع القنوات')
```

### Example 4: CurrentShiftDoneOrdersStats.php - Complex Descriptions
```php
// BEFORE
Stat::make('الاوردرات ديليفري', $orderTypeStats['delivery']['count'] . ' اوردر')
    ->description('بقيمة ' . number_format($orderTypeStats['delivery']['value'], 2) . ' جنيه - ربح ' . number_format($orderTypeStats['delivery']['profit'], 2) . ' جنيه' .
        ($orderTypeStats['delivery']['count'] > 0 ? ' - متوسط ' . number_format($orderTypeStats['delivery']['value'] / $orderTypeStats['delivery']['count'], 2) . ' جنيه' : ''))

// AFTER
Stat::make('الاوردرات ديليفري', $orderTypeStats['delivery']['count'] . ' اوردر')
    ->description('بقيمة ' . format_money($orderTypeStats['delivery']['value']) . ' - ربح ' . format_money($orderTypeStats['delivery']['profit']) .
        ($orderTypeStats['delivery']['count'] > 0 ? ' - متوسط ' . format_money($orderTypeStats['delivery']['value'] / $orderTypeStats['delivery']['count']) : ''))
```

### Example 5: ChannelMarketShareWidget.php - Chart Dataset Labels
```php
// BEFORE
'datasets' => [
    [
        'label' => 'الإيرادات (ج.م)',
        'data' => $revenueData,
    ],
],

// AFTER
'datasets' => [
    [
        'label' => 'الإيرادات (' . currency_symbol() . ')',
        'data' => $revenueData,
    ],
],
```

## Files Already Completed ✅

### Widgets
- ✅ `app/Filament/Widgets/CategoryPerformanceWidget.php`
- ✅ `app/Filament/Widgets/ChannelMarketShareWidget.php`
- ✅ `app/Filament/Widgets/ChannelPerformanceStatsWidget.php`
- ✅ `app/Filament/Widgets/ChannelPerformanceTableWidget.php`
- ✅ `app/Filament/Widgets/CurrentShiftDoneOrdersStats.php`
- ✅ `app/Filament/Widgets/CurrentShiftExpensesDetailsTable.php`
- ✅ `app/Filament/Widgets/CurrentShiftExpensesTable.php`
- ✅ `app/Filament/Widgets/CurrentShiftInfoStats.php`
- ✅ `app/Filament/Widgets/PeriodShiftMoneyInfoStats.php`
- ✅ `app/Filament/Widgets/ProductsSalesStatsWidget.php`
- ✅ `app/Filament/Widgets/StockReportTable.php`
- ✅ `app/Filament/Widgets/TopProductsByProfitWidget.php`

### Exporters
- ✅ `app/Filament/Exports/CategoryPerformanceExporter.php`
- ✅ `app/Filament/Exports/CurrentShiftExpensesDetailedExporter.php`
- ✅ `app/Filament/Exports/CurrentShiftExpensesExporter.php`
- ✅ `app/Filament/Exports/CurrentShiftOrdersExporter.php`
- ✅ `app/Filament/Exports/CustomersPerformanceTableExporter.php`
- ✅ `app/Filament/Exports/PeriodShiftExpensesDetailedExporter.php`
- ✅ `app/Filament/Exports/PeriodShiftExpensesExporter.php`
- ✅ `app/Filament/Exports/PeriodShiftOrdersExporter.php`
- ✅ `app/Filament/Exports/ProductsSalesTableExporter.php`
- ✅ `app/Filament/Exports/StockReportExporter.php`

### Components
- ✅ `app/Filament/Components/Forms/ProductSelector.php`
- ✅ `app/Filament/Components/Forms/StocktakingProductSelector.php`

### Pages
- ✅ `app/Filament/Pages/BranchManagement.php`
- ✅ `app/Filament/Pages/Reports/ShiftLogsReport.php`

### Services
- ✅ `app/Services/InvoicePrintService.php`
- ✅ `app/Services/ShiftLoggingService.php`

### Resources - All Files ✅
- ✅ `app/Filament/Resources/Categories/**/*.php`
- ✅ `app/Filament/Resources/ConsumableProducts/**/*.php`
- ✅ `app/Filament/Resources/Customers/**/*.php`
- ✅ `app/Filament/Resources/Drivers/**/*.php`
- ✅ `app/Filament/Resources/Expenses/**/*.php`
- ✅ `app/Filament/Resources/ExpenseTypes/**/*.php`
- ✅ `app/Filament/Resources/InventoryItems/**/*.php`
- ✅ `app/Filament/Resources/ManufacturedProducts/**/*.php`
- ✅ `app/Filament/Resources/Orders/**/*.php`
- ✅ `app/Filament/Resources/Printers/**/*.php`
- ✅ `app/Filament/Resources/PurchaseInvoices/**/*.php`
- ✅ `app/Filament/Resources/RawMaterialProducts/**/*.php`
- ✅ `app/Filament/Resources/Regions/**/*.php`
- ✅ `app/Filament/Resources/ReturnPurchaseInvoices/**/*.php`
- ✅ `app/Filament/Resources/Stocktakings/**/*.php`
- ✅ `app/Filament/Resources/Suppliers/**/*.php`
- ✅ `app/Filament/Resources/Users/**/*.php`
- ✅ `app/Filament/Resources/Wastes/**/*.php`

### Actions
- ✅ `app/Filament/Actions/Forms/LowStockImporterAction.php`
- ✅ `app/Filament/Actions/Forms/ProductComponentsImporterAction.php`
- ✅ `app/Filament/Actions/Forms/ProductImporterAction.php`
- ✅ `app/Filament/Actions/Forms/StocktakingProductImporterAction.php`

### Additional Widgets
- ✅ `app/Filament/Widgets/ProductsSalesStatsWidget.php`
- ✅ `app/Filament/Widgets/TopCustomersByProfitWidget.php`
- ✅ `app/Filament/Widgets/TopProductsByProfitWidget.php`

### Services
- ✅ `app/Services/InvoicePrintService.php`

## Instructions for AI Assistant

When given a file or set of files to migrate:

1. **Search for all hardcoded currency references** using the patterns above
2. **Replace them with appropriate helper functions:**
   - Use `currency_code()` for Filament `->money()` methods
   - Use `format_money()` for manual number formatting with currency
   - Use `currency_symbol()` for labels and descriptions
3. **Use `multi_replace_string_in_file` tool** for efficiency when multiple replacements are needed
4. **Include 3-5 lines of context** before and after each replacement for accuracy
5. **Verify the replacement makes sense** in context (e.g., don't replace if it's a comment or example)
6. **Update this prompt file** to mark completed files with ✅

## Testing After Migration

After migrating files, verify:
1. The UI displays correctly with currency symbols
2. Filament tables show proper currency formatting
3. Charts and graphs display currency correctly
4. No hardcoded currency references remain in the migrated files

## Search Commands

To find remaining files with hardcoded currency:

```bash
# Find files with 'EGP'
grep -r "EGP" app/ resources/ --include="*.php"

# Find files with Arabic currency symbol
grep -r "ج\.م" app/ resources/ --include="*.php"

# Find files with Arabic currency name
grep -r "جنيه" app/ resources/ --include="*.php"

# Find Filament money columns
grep -r "->money(" app/ resources/ --include="*.php"
```
