# Plan to Remove DINE_IN, COMPANIES, and TALABAT Order Types

## Overview
This document outlines the comprehensive plan to remove the following order types from the cashier system:
- `DINE_IN` (dine_in)
- `COMPANIES` (companies)  
- `TALABAT` (talabat)

## ⚠️ Prerequisites
**Development Environment Notes:**
- This project is in development mode and has never been deployed to production
- No data migration or backup strategies are needed
- Changes will be made directly to existing migration files
- Database can be refreshed/migrated from scratch if needed

## Phase 1: Database Schema Updates

### 1.1 Update Migration Files Directly
Since this is a development environment, we'll modify existing migration files to remove references to the deprecated order types.

**File: `database/migrations/2025_07_07_000009_create_orders_table.php`**
- No changes needed to the migration itself (type is stored as string)
- The enum will handle validation of allowed types

### 1.2 Remove Dine Tables (If Not Needed)
**Check if dine_tables table is still needed:**
- Since DINE_IN is being removed, evaluate if the dine_tables table should be removed
- **File to potentially delete**: `database/migrations/2025_07_07_000022_create_dine_tables_table.php`

### 1.3 Fresh Migration Strategy
If needed, the database can be refreshed using:
```bash
php artisan migrate:fresh --seed
```

## Phase 2: Backend Code Changes

### 2.1 Enum Updates
**File: `app/Enums/OrderType.php`**
- Remove `DINE_IN`, `COMPANIES`, `TALABAT` cases
- Remove corresponding entries in `getLabel()`, `getColor()`, `getIcon()` methods
- Update `requiresTable()` method (currently only returns true for DINE_IN)
- Remove references from `hasDeliveryFee()` and `isWebOrder()` methods

### 2.2 Controllers
**File: `app/Http/Controllers/OrderController.php` (line ~715)**
- Remove cases for 'dine_in', 'companies', 'talabat' in the match statement
- Update validation rules to exclude these types

### 2.3 DTOs
**File: `app/DTOs/Orders/CreateOrderDTO.php`**
- Update table requirement validation logic (currently checks `$type->requiresTable()`)
- May need to update validation message for table requirements

### 2.4 Services

**File: `app/Services/ShiftsReportService.php`**
- Line 246: Remove 'dineIn' from stats array initialization  
- Line 273: Remove `OrderType::DINE_IN => 'dineIn'` mapping
- Remove `OrderType::TALABAT => 'talabat'` mapping
- Remove `OrderType::COMPANIES => 'companies'` mapping

**File: `app/Services/ProductsSalesReportService.php`**
- Line 69+: Remove SQL aggregations for talabat sales/profit
- Remove companies sales/profit aggregations

**File: `app/Services/CustomersPerformanceReportService.php`**
- Line 74: Remove talabat_orders calculation
- Line 79: Remove companies_orders calculation
- Remove related sales and profit calculations

**File: `app/Services/SettingsService.php`**
- Line 49: Remove `getDineInServiceCharge()` method (if not used elsewhere)

### 2.5 Filament Widgets

**File: `app/Filament/Widgets/CurrentShiftDoneOrdersStats.php`**
- Lines 39-47: Remove dineIn stats widget
- Lines 79-89: Remove talabat stats widget
- Remove companies stats widget (if exists)

**File: `app/Filament/Widgets/PeriodShiftDoneOrdersStats.php`**
- Line 38: Remove 'dine_in' mapping

**File: `app/Filament/Resources/Drivers/RelationManagers/OrdersRelationManager.php`**
- Line 137+: Remove filter options for DINE_IN, TALABAT, COMPANIES

## Phase 3: Frontend Code Changes

### 3.1 React Components

**File: `resources/js/Pages/Orders/Index.tsx`**
- Line 5: Remove DineInTab import
- Line 8: Remove TalabatTab import  
- Line 9: Remove CompaniesTab import
- Lines 30-34: Remove dineInOrders filter
- Lines 44-47: Remove talabatOrders filter
- Remove companiesOrders filter (if exists)
- Lines 67, 82: Remove corresponding tab definitions

**File: `resources/js/Components/Orders/Index/DineInTab.tsx`**
- **DELETE ENTIRE FILE**

**File: `resources/js/Components/Orders/Index/TalabatTab.tsx`**
- **DELETE ENTIRE FILE**

**File: `resources/js/Components/Orders/Index/CompaniesTab.tsx`**
- **DELETE ENTIRE FILE**

**File: `resources/js/Components/Orders/Index/ReceiveOrdersPaymentsTab.tsx`**
- Line 2: Remove DineInTab import
- Line 10: Update logic (currently returns DineInTab)

**File: `resources/js/Components/Orders/ChangeOrderTypeModal.tsx`**
- Line 16: Remove isDineIn state
- Lines 19-36: Remove isDineIn related logic
- Lines 38-42: Remove orderTypes entries for dine_in, companies, talabat
- Line 102: Remove dine_in check logic
- Line 125+: Remove dine_in specific form fields (table selection)

**File: `resources/js/Pages/Orders/ManageOrder.tsx`**
- Line 124: Remove isDineIn logic

### 3.2 Print Templates

**File: `resources/js/Components/Print/ReceiptTemplate.tsx`**
- Remove 'dine_in', 'companies', 'talabat' from typeMap

**File: `resources/js/Components/Print/PartialReceiptTemplate.tsx`**
- Remove 'dine_in', 'companies', 'talabat' from typeMap

### 3.3 Cypress Fixtures
**File: `cypress/fixtures/orders.json`**
- Line 13: Remove "dineIn" fixture data

## Phase 4: Database Schema Cleanup

### 4.1 Remove Table-Related Fields
**Since DINE_IN is being removed and no other functionality requires dine_table_number:**
- Modify existing migration to remove `dine_table_number` column from orders table
- **File**: `database/migrations/2025_07_07_000009_create_orders_table.php`

### 4.2 Remove Dine Tables Migration
**Since DINE_IN was the only type using tables:**
- **DELETE FILE**: `database/migrations/2025_07_07_000022_create_dine_tables_table.php`
- Remove any related table types or table management features

## Phase 5: Configuration and Settings

### 5.1 Settings Service
- Remove dine-in specific service charges if they exist
- Update any configuration that references these order types

### 5.2 Validation Rules
- Update form request validation to exclude removed order types
- Update API validation rules

## Phase 6: Documentation and Cleanup

### 6.1 Update Documentation
- Update any user documentation that references these order types
- Update API documentation if these types were exposed

### 6.2 Code Comments
- Remove or update comments that reference removed order types
- Update PHPDoc blocks that mention these types

## Implementation Order

1. **Phase 1**: Database schema cleanup (remove dine tables migration)
2. **Phase 2**: Backend enum and service updates
3. **Phase 3**: Frontend component removal
4. **Phase 4**: Database schema updates (remove dine_table_number)
5. **Phase 5**: Configuration cleanup
6. **Phase 6**: Documentation updates

## Risk Assessment

### Low Risk (Development Environment)
- **No Data Loss Risk**: No production data exists
- **Easy Rollback**: Git version control allows easy reversion
- **Testing Flexibility**: Can refresh database anytime

### Medium Risk
- **Development Workflow**: Other developers need to be aware of changes
- **Frontend Breaking Changes**: Components expecting these types will need updates

## Rollback Plan

1. **Git Revert**: Use git to rollback any changes
2. **Fresh Migration**: Run `php artisan migrate:fresh --seed` if needed
3. **No backup recovery needed**: Development environment

## Verification Steps

1. **Frontend Loads**: All order management pages load correctly
2. **Database Migration**: `php artisan migrate:fresh --seed` works without errors
3. **New Orders**: Can create orders with remaining types (TAKEAWAY, DELIVERY, WEB_DELIVERY, WEB_TAKEAWAY)
4. **Reports Function**: Reports work without referencing removed types
5. **No PHP Errors**: Application runs without fatal errors

---

**Note**: Since this is a development environment, changes can be made directly without complex migration strategies. If issues arise, the database can be refreshed from scratch.