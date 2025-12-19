# Release Notes - Version 6.x

> **Release Date:** October 10, 2025  
> **Type:** Major Release  
> **Upgrade Difficulty:** Medium to High

---

## 📋 Table of Contents

-   [Overview](#overview)
-   [Breaking Changes](#breaking-changes)
-   [New Features](#new-features)
-   [Improvements](#improvements)
-   [Bug Fixes](#bug-fixes)
-   [Upgrade Guide](#upgrade-guide)
-   [Impact Assessment](#impact-assessment)

---

## 🎯 Overview

This major release introduces significant architectural improvements to the subscription system, enhanced e-commerce capabilities, unified payment processing, and workbench restructuring for better testing and development workflows.

**Key Highlights:**

-   🔄 Subscription field restructuring (`ends_at` → `expires_at`)
-   🛍️ Complete e-commerce shop system with checkout, cart, and order management
-   💳 Unified payment processor with multi-gateway support
-   🎟️ Enhanced coupon system with product-level and plan-level coupons
-   🏗️ Workbench namespace reorganization
-   📦 Product subscription support with recurring plans

---

## ⚠️ Breaking Changes

### 1. **CRITICAL: Subscription Database Schema Changes**

**Impact:** HIGH - Direct database column changes

#### Changed Columns:

-   `subscriptions.ends_at` → `subscriptions.expires_at`
-   Added `subscriptions.expires_at` for subscription expiration tracking

**Code Changes Required:**

```php
// Before:
$subscription->ends_at
$subscription->cancel() // sets ends_at

// After:
$subscription->canceled_at // when user canceled
$subscription->expires_at  // when subscription expires
$subscription->cancel()    // sets canceled_at
```

**Affected Methods:**

-   `Subscription::cancel()` - Now sets `expires_at` instead of `ends_at`
-   `Subscription::resume()` - Checks `expires_at` for grace period
-   `Subscription::onGracePeriod()` - Uses `expires_at`

---

### 1.1. **CRITICAL: Additional Subscription Field Cleanup**

**Impact:** HIGH - Removal of obsolete `cancels_at` field

#### Removed Column:

-   **Removed:** `subscriptions.cancels_at` - This field has been completely removed from the codebase
-   The migration `2023_05_06_190730_update_subscriptions_table.php` no longer adds this column
-   Functionality consolidated into `canceled_at` and `expires_at` fields

**Additional Code Changes Required:**

```php
// ❌ OLD - Remove all references to cancels_at
$user->updateCancelsAt($dateAt);
$subscription->cancels_at;
User::whereHas('subscriptions', fn($q) => $q->active()->whereNull('cancels_at'));

// ✅ NEW - Use expires_at and canceled_at instead
$user->updateExpiresAt($expiresAt); // Now requires non-null value
$subscription->expires_at;
$subscription->canceled_at;
User::whereHas('subscriptions', fn($q) => $q->active()->whereNull('canceled_at'));
```

**Additional Affected Methods:**

-   `User::updateCancelsAt()` → **REMOVED** - Use `User::updateExpiresAt()` instead
-   `User::scopeOnlyRolling()` - Now uses `canceled_at` instead of `cancels_at`
-   `User::scopeOnlyEnds()` - Now uses `canceled_at` instead of `cancels_at`
-   `User::scopeOnlyPlan()` - Now uses `canceled_at` instead of `cancels_at`
-   Global scope - Updated to use `canceled_at` instead of `cancels_at`

**Model Fillable/Casts Cleanup:**

```php
// Subscription model - cancels_at removed
// ❌ No longer in fillable
'cancels_at'

// ❌ No longer in casts
'cancels_at' => 'datetime'

// ✅ Existing fields remain
'canceled_at' => 'datetime',
'expires_at' => 'datetime',
```

**Commands Removed:**

-   `Commands\SubscriptionsCancel::class` - Removed from service provider
-   `coderstm:subscriptions-cancel` - Command no longer available
-   Functionality merged into existing subscription management commands

**Tests Removed:**

-   `tests/Feature/SubscriptionsCancelTest.php` - Test file completely removed

---

### 2. **CRITICAL: Workbench Namespace Changes**

**Impact:** HIGH - All workbench models and controllers

**Before:**

```php
namespace App\Models;
namespace App\Http\Controllers;
namespace Database\Seeders;
```

**After:**

```php
namespace Workbench\App\Models;
namespace Workbench\App\Http\Controllers;
namespace Workbench\Database\Seeders;
```

**Files Affected:**

-   `workbench/app/Models/User.php`
-   `workbench/app/Models/Coupon.php`
-   `workbench/app/Providers/WorkbenchServiceProvider.php`
-   All database seeders

**Action Required:**

```bash
# Update all imports in your workbench files
find workbench -type f -name "*.php" -exec sed -i '' 's/namespace App\\/namespace Workbench\\App\\/g' {} +
find workbench -type f -name "*.php" -exec sed -i '' 's/namespace Database\\/namespace Workbench\\Database\\/g' {} +
```

---

### 3. **CRITICAL: Coupon Model Changes**

**Impact:** HIGH - Coupon structure completely redesigned

**New Coupon Types:**

-   `plan` - Applies to subscription plans
-   `product` - Applies to products
-   `cart` - Applies to entire cart (future)

**New Coupon Fields:**

```php
// New columns
'type' => 'plan|product|cart'
'discount_type' => 'percentage|fixed|override'
'value' => 10.50
'auto_apply' => true/false
```

**Removed Factory:**

-   `database/factories/CouponFactory.php` removed from workbench
-   Use `Coderstm\Database\Factories\CouponFactory` from core package

**Migration Required:**

```bash
php artisan migrate
# Runs: 2025_01_30_151000_update_coupon_discount_structure.php
```

---

### 4. **Payment & Order Schema Changes**

**Impact:** MEDIUM - Enhanced payment tracking

**New Payment Fields:**

```php
'currency' => 'USD'
'processed_at' => timestamp
'fees' => 2.50
'net_amount' => 97.50
'refund_amount' => 0.00
'metadata' => json
```

**New Order Fields:**

```php
'status' => 'draft|pending|completed|canceled'
'fulfillment_status' => 'pending|processing|shipped|delivered|cancelled'
'payment_status' => 'pending|processing|paid|failed|refunded|partially_refunded'
'tracking_number' => 'TRACK123'
'tracking_company' => 'FedEx'
'shipped_at' => timestamp
'delivered_at' => timestamp
'cancelled_at' => timestamp
```

---

### 5. **Config & Auth Guard Changes**

**Impact:** MEDIUM - Guard configuration cleanup

**Removed Guards:**

-   `gateclouds` guard removed from `config/auth.php`
-   `gateclouds` guard removed from `config/sanctum.php`

**Updated Model Bindings:**

```php
// Before:
'model' => App\Models\Admin::class

// After:
'model' => Coderstm::$adminModel
```

---

### 6. **New Configuration Options**

**Impact:** LOW - New configuration keys (backward compatible)

**Subscription Configuration:**

New `coderstm.subscription` configuration section added to control subscription behaviors:

```php
// config/coderstm.php
'subscription' => [
    // When true, activating a late payer anchors from the open invoice's intended
    // start date (last unpaid period start) + plan duration; otherwise, uses today.
    'anchor_from_invoice' => (bool) env('SUBSCRIPTION_ANCHOR_FROM_INVOICE', true),

    // Controls when plan downgrades take effect:
    // 'immediate' - Apply downgrade immediately upon request
    // 'next_renewal' - Schedule downgrade for next billing cycle (default)
    'downgrade_timing' => env('SUBSCRIPTION_DOWNGRADE_TIMING', 'next_renewal'),
],
```

**Shop Configuration:**

New `coderstm.shop` configuration section for e-commerce settings:

```php
// config/coderstm.php
'shop' => [
    // Number of hours of inactivity before a cart is considered abandoned
    'abandoned_cart_hours' => (int) env('ABANDONED_CART_HOURS', 2),
],
```

**Database Override Mapping:**

Enhanced `settings_override` configuration to map database settings to Laravel config:

```php
// config/coderstm.php
'settings_override' => [
    'config' => [
        'alias' => 'app',
        'subscription' => 'coderstm.subscription',  // NEW: Maps to subscription config
        'checkout' => 'coderstm.shop',             // NEW: Maps to shop config
        'email' => [
            'coderstm.admin_email',
            'mail.from.address',
        ],
        'name' => ['mail.from.name'],
        'currency' => 'cashier.currency',
        'timezone' => fn($value) => date_default_timezone_set($value),
    ],
],
```

**Environment Variables:**

```env
# Subscription Configuration
SUBSCRIPTION_ANCHOR_FROM_INVOICE=true
SUBSCRIPTION_DOWNGRADE_TIMING=next_renewal

# Shop Configuration
ABANDONED_CART_HOURS=2
```

**Action Required:** None - defaults are backward compatible. Optionally customize via environment variables.

---

## 🚀 New Features

### 1. **Complete E-Commerce Shop System**

**New Controllers:**

-   `Admin/CheckoutController` - Admin checkout management
-   `ProductController` - Product CRUD
-   `OrderController` - Order management
-   `LocationController` - Inventory locations
-   `User/CartController` - Shopping cart
-   `User/CheckoutController` - Checkout process
-   `User/OrderController` - Order history
-   `User/ShopController` - Product catalog

**New Models & Features:**

-   Product subscriptions with recurring plans
-   Inventory tracking per location
-   Product variants with options
-   Product attributes with types (switch, select, button)
-   Collections with conditions
-   Categories, Tags, Vendors

---

### 2. **Unified Payment Processor**

**New Architecture:**

```php
use Coderstm\Payment\Processor;

// Factory pattern for payment processors
$processor = Processor::make('stripe');
$result = $processor->setupPaymentIntent($request, $checkout);

// Supported providers
Processor::isSupported('stripe'); // true
Processor::isSupported('paypal'); // true
```

**Unified Routes:**

```php
// Setup payment intent for any provider
POST /api/shop/{provider}/setup-payment-intent

// Confirm payment for any provider
POST /api/shop/{provider}/confirm-payment

// Success/cancel callbacks
GET /shop/{provider}/success
GET /shop/{provider}/cancel
```

**Supported Payment Methods:**

-   Stripe
-   PayPal
-   Razorpay
-   GoCardless
-   Xendit
-   Flutterwave
-   Mercado Pago

---

### 3. **Enhanced Coupon System**

**Auto-Apply Coupons:**

```php
// Coupons can now auto-apply
$coupon = Coupon::create([
    'type' => 'product',
    'auto_apply' => true,
    'discount_type' => 'percentage',
    'value' => 10
]);

// Product/Plan associations
$coupon->syncProducts([1, 2, 3]);
$coupon->syncPlans([1, 2]);
```

**Coupon Priority:**

```php
// Best coupon automatically selected
$bestCoupon = Coupon::autoApplicable()
    ->where('type', 'product')
    ->get()
    ->filter(fn($c) => $c->canApplyToProduct($productId))
    ->sortByDesc(fn($c) => $c->getDiscountPriority($price))
    ->first();
```

---

### 4. **Product Subscriptions**

**Recurring Plans on Products:**

```php
// Create recurring variant
$variant = Variant::create([
    'recurring' => true,
    'product_id' => $product->id
]);

// Attach plans to variant
$plan = Plan::create([
    'variant_id' => $variant->id,
    'interval' => 'month',
    'interval_count' => 1,
    'price' => 99.00
]);

// Subscribe to product
$subscription = Subscription::create([
    'type' => 'shop',
    'plan_id' => $plan->id,
    'user_id' => $user->id
]);
```

---

### 5. **Cart & Checkout System**

**Cookie-Based Cart:**

```php
// Cart persists via cookies (Shopify-style)
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/add', [CartController::class, 'add']);

// Cart token stored in cookie: 'cart_token'
```

**Checkout Repository:**

```php
use Coderstm\Repository\CheckoutRepository;

$repository = CheckoutRepository::fromRequest($request);
$repository->calculate(); // Smart calculation with caching
$data = $repository->getCheckoutData();
```

**Trial Pricing & Intro Pricing:**

```php
// Trial plans get $0 pricing
if ($plan->hasTrial()) {
    $subscription->trial_ends_at = now()->addDays($plan->trial_period);
    $firstInvoice->total = 0.00;
}

// Intro pricing
if ($plan->hasIntroPricing()) {
    $subscription->intro_price = $plan->intro_price;
    $subscription->intro_ends_at = now()->addPeriod($plan->intro_period);
}
```

**Abandoned Cart Detection:**

```php
// Configurable abandoned cart threshold
// config/coderstm.php
'shop' => [
    'abandoned_cart_hours' => 2, // Default: 2 hours
],

// Command to detect abandoned carts
php artisan shop:process-abandoned-checkouts
```

---

### 6. **Database-Driven Configuration**

**AppSetting Config Sync:**

Settings stored in the database can now automatically override Laravel configuration:

```php
use Coderstm\Models\AppSetting;

// Store settings in database
AppSetting::updateValue('config', [
    'email' => 'admin@example.com',
    'name' => 'My App',
    'currency' => 'EUR',
    'timezone' => 'Europe/London',
    'subscription' => [
        'anchor_from_invoice' => false,
        'downgrade_timing' => 'immediate',
    ],
    'checkout' => [
        'abandoned_cart_hours' => 4,
    ],
]);

// Automatically synced on boot via CoderstmServiceProvider
AppSetting::syncConfig();

// Access via Laravel config
config('coderstm.admin_email'); // admin@example.com
config('mail.from.address');    // admin@example.com
config('mail.from.name');       // My App
config('cashier.currency');     // EUR
config('coderstm.subscription.anchor_from_invoice'); // false
config('coderstm.shop.abandoned_cart_hours');        // 4
```

**Mapping Rules:**

```php
'settings_override' => [
    'config' => [
        'alias' => 'app',                          // Base config key
        'subscription' => 'coderstm.subscription', // Nested config mapping
        'checkout' => 'coderstm.shop',            // Nested config mapping
        'email' => [                              // Multiple config keys
            'coderstm.admin_email',
            'mail.from.address',
        ],
        'name' => ['mail.from.name'],             // Array of config keys
        'currency' => 'cashier.currency',         // Single config key
        'timezone' => fn($value) => date_default_timezone_set($value), // Callable
    ],
],
```

---

## 🔧 Improvements

### Database Schema Enhancements

1. **Line Items:**

    - Added `metadata` JSON field for custom data

2. **Plans:**

    - Added `variant_id` foreign key
    - Renamed `options` → `metadata`
    - Added index on `[variant_id, interval]`
    - Added `grace_period_days` field for plan-specific grace periods (default: 0 - subscriptions expire immediately without grace period)

3. **Variants:**

    - Added `recurring` boolean flag

4. **Attributes:**

    - Added `type` field (switch, select, button)

5. **Payment Methods:**

    - Added `integration_via` for proxy integrations
    - Added `order` for display ordering

6. **Discount Lines:**
    - Added `coupon_id` foreign key
    - Added `coupon_code` tracking

---

### Code Quality Improvements

1. **Middleware:**

    - Cookie encryption exclusion for `cart_token`

2. **Factory Pattern:**

    - Payment processor factory (`Processor::make()`)
    - Gateway factory for subscriptions

3. **Repository Pattern:**

    - `CartRepository` for cart calculations
    - `CheckoutRepository` for checkout processing

4. **Resource Classes:**

    - Product resources with variant data
    - Plan resources with discount info
    - Coupon resources

5. **Configuration Management:**

    - Database-driven configuration with `AppSetting::syncConfig()`
    - Automatic config override mapping
    - Support for nested configuration arrays
    - Callable mapping support for custom logic
    - Efficient cache management per key

---

## 🐛 Bug Fixes

1. **Subscription Cancellation:**

    - Fixed grace period detection using correct fields
    - Properly track cancellation vs expiration

2. **Coupon Import:**

    - Fixed namespace issue: `App\Models\Coupon` → `Coderstm\Models\Coupon`

3. **Test Compatibility:**

    - Fixed subscription test assertions for new field names

4. **Command & Field Cleanup:**
    - Removed obsolete `cancels_at` field from subscription model
    - Removed `SubscriptionsCancel` command (functionality merged)
    - Removed `SubscriptionsCancelTest` test file
    - Cleaned up all `cancels_at` references in User model scopes

---

## 📚 Upgrade Guide

### Step 1: Update Dependencies

```bash
composer update coderstm/laravel-core
```

### Step 2: Run Migrations

```bash
php artisan migrate
```

**Migrations Applied:**

-   `2024_01_15_000006_update_payments_table.php`
-   `2024_01_15_000007_update_orders_table.php`
-   `2025_01_22_000001_add_metadata_to_line_items_table.php`
-   `2025_01_30_151000_update_coupon_discount_structure.php`
-   `2025_06_30_203528_update_products_table.php`
-   `2025_07_21_081214_add_type_columns_to_attributes_table.php`
-   `2025_07_26_084100_update_payment_methods_table.php`
-   `2025_07_29_085729_update_plans_table.php`
-   `2025_07_30_183836_update_discount_lines_table_for_enhanced_coupons.php`
-   `2025_12_15_000001_add_grace_period_days_to_plans_table.php`

### Step 3: Update Subscription Code

**Critical: Update all subscription references**

```php
// ❌ Old Code
if ($subscription->ends_at && $subscription->ends_at->isFuture()) {
    // On grace period
}

// ✅ New Code
if ($subscription->canceled_at && $subscription->expires_at?->isFuture()) {
    // On grace period
}
```

**Update Custom Subscription Queries:**

```php
// ❌ Old
Subscription::whereNotNull('ends_at')->get()

// ✅ New
Subscription::whereNotNull('canceled_at')->get()
```

### Step 4: Update Workbench Namespaces

**If you have custom workbench code:**

```bash
# Update namespaces in all workbench files
cd workbench

# Update App namespace
find . -type f -name "*.php" -exec sed -i '' 's/namespace App\\/namespace Workbench\\App\\/g' {} +
find . -type f -name "*.php" -exec sed -i '' 's/use App\\/use Workbench\\App\\/g' {} +

# Update Database namespace
find . -type f -name "*.php" -exec sed -i '' 's/namespace Database\\/namespace Workbench\\Database\\/g' {} +
find . -type f -name "*.php" -exec sed -i '' 's/use Database\\/use Workbench\\Database\\/g' {} +
```

### Step 5: Update Coupon Usage

**If using coupons, update to new structure:**

```php
// ❌ Old Coupon Creation
Coupon::create([
    'name' => 'Summer Sale',
    'percent_off' => 20,
    'fixed' => false
]);

// ✅ New Coupon Creation
Coupon::create([
    'type' => 'product', // or 'plan', 'cart'
    'name' => 'Summer Sale',
    'discount_type' => 'percentage',
    'value' => 20,
    'auto_apply' => false
]);

// Attach to products/plans
$coupon->syncProducts([1, 2, 3]);
$coupon->syncPlans([1, 2]);
```

### Step 6: Update Auth Configuration

**Review and update `config/auth.php`:**

```php
// ✅ Ensure model bindings use Coderstm models
'providers' => [
    'admins' => [
        'driver' => 'eloquent',
        'model' => Coderstm::$adminModel, // Not hardcoded
    ],
    'users' => [
        'driver' => 'eloquent',
        'model' => Coderstm::$userModel,
    ],
],
```

**Publish and review updated config:**

```bash
# Publish latest config file
php artisan vendor:publish --tag=coderstm-config --force

# Review new configuration options
vim config/coderstm.php
```

**New Configuration Sections:**

```php
// Review and customize these new sections:

// Subscription behavior
'subscription' => [
    'anchor_from_invoice' => true,      // Late payment handling
    'downgrade_timing' => 'next_renewal', // Downgrade timing
],

// Shop settings
'shop' => [
    'abandoned_cart_hours' => 2, // Cart abandonment threshold
],

// Database override mapping
'settings_override' => [
    'config' => [
        'alias' => 'app',
        'subscription' => 'coderstm.subscription',
        'checkout' => 'coderstm.shop',
        // ... other mappings
    ],
],
```

### Step 7: Update Payment Integration

**If using custom payment processing, migrate to Processor:**

```php
// ❌ Old Direct Gateway Usage
$gateway = new StripeGateway();
$result = $gateway->createPaymentIntent($amount);

// ✅ New Unified Processor
use Coderstm\Payment\Processor;

$processor = Processor::make('stripe');
$result = $processor->setupPaymentIntent($request, $checkout);
```

### Step 8: Update Tests

**Update subscription tests:**

```php
// ❌ Old Assertions
$this->assertNotNull($subscription->ends_at);

// ✅ New Assertions
$this->assertNotNull($subscription->canceled_at);
$this->assertNull($subscription->expires_at); // or assertNotNull based on scenario
```

### Step 9: Clear Caches

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Step 10: Test Thoroughly

**Critical Areas to Test:**

1. **Subscriptions:**

    - [ ] Create new subscription
    - [ ] Cancel subscription (check `canceled_at` is set)
    - [ ] Resume subscription (check grace period logic)
    - [ ] Trial subscriptions
    - [ ] Subscription renewals

2. **Coupons:**

    - [ ] Create product coupons
    - [ ] Create plan coupons
    - [ ] Test auto-apply logic
    - [ ] Test coupon priority

3. **Shop/Products:**

    - [ ] Browse products
    - [ ] Add to cart
    - [ ] Checkout flow
    - [ ] Order management
    - [ ] Product subscriptions

4. **Payments:**
    - [ ] Test each payment gateway
    - [ ] Success/cancel callbacks
    - [ ] Refund processing

---

## 📊 Impact Assessment

### Low Impact Changes ✅

**These changes are backward compatible or have minimal impact:**

1. **New Controllers & Routes:**

    - New shop controllers don't affect existing routes
    - Action: None required

2. **New Migrations:**

    - Additive only, no data loss
    - Action: Run migrations

3. **New Models:**

    - Shop models are new additions
    - Action: None required

4. **Enhanced Features:**

    - Auto-apply coupons (opt-in)
    - Product subscriptions (new feature)
    - Action: None required unless using

5. **New Configuration Options:**

    - `coderstm.subscription` config section
    - `coderstm.shop` config section
    - Enhanced `settings_override` mapping
    - All have backward-compatible defaults
    - Action: Optionally customize via `.env`

### Medium Impact Changes ⚠️

**These require review but are manageable:**

1. **Payment Schema:**

    - New fields added to payments/orders
    - Action: Update queries using new fields if needed

2. **Workbench Namespace:**

    - Only affects workbench development
    - Action: Update imports if using workbench

3. **Coupon Structure:**

    - Existing coupons may need migration
    - Action: Review and update coupon creation code

4. **Auth Config:**
    - Model binding changes
    - Action: Update config to use Coderstm models

### High Impact Changes 🔴

**These require immediate attention:**

1. **Subscription Fields:**

    - `ends_at` → `canceled_at`
    - Direct database column change
    - Action: Update ALL code using these fields
    - Search for: `ends_at`, `$subscription->ends`, `->whereNotNull('ends_at')`

2. **Coupon Factory Removal:**

    - `CouponFactory` removed from workbench
    - Action: Use core factory instead

3. **Test Updates:**
    - Subscription tests need field updates
    - Action: Update test assertions

---

## 🔍 Database Migration Guide

### Before Migration Backup

```bash
# Backup database before migrating
php artisan backup:run --only-db

# Or manual backup
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
```

### Migration Validation

**After migration, validate data:**

```sql
-- Check subscription fields
SELECT id, status, canceled_at, expires_at, trial_ends_at
FROM subscriptions
WHERE canceled_at IS NOT NULL;

-- Check coupon types
SELECT id, type, discount_type, value, auto_apply
FROM coupons;

-- Check payment metadata
SELECT id, amount, currency, fees, net_amount, metadata
FROM payments
LIMIT 10;
```

---

## 🛠️ Rollback Plan

**If issues arise, you can rollback:**

### Database Rollback

```bash
# Rollback last batch of migrations
php artisan migrate:rollback

# Rollback specific migration
php artisan migrate:rollback --step=1

# Restore from backup
mysql -u user -p database < backup_20251010.sql
```

### Code Rollback

```bash
# Rollback to previous version
composer require coderstm/laravel-core:^1.0
```

---

## 📞 Support & Resources

### Documentation

-   [Package Documentation](https://github.com/coders-tm/laravel-core)
-   [API Reference](https://docs.coderstm.com/api)
-   [Upgrade Examples](https://docs.coderstm.com/upgrade)

### Getting Help

-   GitHub Issues: https://github.com/coders-tm/laravel-core/issues
-   Email: support@coderstm.com
-   Discord: [Join Community](https://discord.gg/coderstm)

### Breaking Change Summary Table

| Area          | Change                        | Impact    | Action Required                         |
| ------------- | ----------------------------- | --------- | --------------------------------------- |
| Subscriptions | `ends_at` → `canceled_at`     | 🔴 High   | Update all subscription code            |
| Subscriptions | Added `expires_at`            | 🔴 High   | Update grace period logic               |
| Subscriptions | Removed `cancels_at`          | 🔴 High   | Update queries and User methods         |
| Workbench     | Namespace changed             | 🔴 High   | Update all workbench imports            |
| Coupons       | Structure redesigned          | 🔴 High   | Update coupon creation code             |
| Payments      | New fields added              | ⚠️ Medium | Optional - use new fields               |
| Orders        | New status fields             | ⚠️ Medium | Optional - use new fields               |
| Auth          | Model bindings updated        | ⚠️ Medium | Update config file                      |
| Factory       | CouponFactory removed         | ⚠️ Medium | Use core factory                        |
| Config        | New subscription/shop options | ✅ Low    | Optional - customize via `.env`         |
| Config        | Database override mapping     | ✅ Low    | Optional - use AppSetting::syncConfig() |

---

## ✅ Post-Upgrade Checklist

-   [ ] Database backed up
-   [ ] Migrations run successfully
-   [ ] Subscription code updated (`ends_at` → `canceled_at`, `cancels_at` removed)
-   [ ] Workbench namespaces updated
-   [ ] Coupon code updated for new structure
-   [ ] Auth config reviewed and updated
-   [ ] Config file published and reviewed (`php artisan vendor:publish --tag=coderstm-config`)
-   [ ] New config options reviewed (subscription, shop, settings_override)
-   [ ] Environment variables added (if customizing defaults)
-   [ ] Database settings migration (if using AppSetting for config)
-   [ ] Payment processor migrated (if applicable)
-   [ ] Tests updated and passing
-   [ ] Caches cleared
-   [ ] Production deployment tested in staging
-   [ ] Monitoring alerts configured
-   [ ] Rollback plan documented

---

## 🎉 What's Next?

**Future Roadmedia:**

-   GraphQL API support
-   Advanced reporting dashboard
-   Multi-currency support enhancements
-   Subscription analytics
-   AI-powered product recommendations

Stay tuned for updates!

---

**Version:** 2.0.0  
**Released:** October 10, 2025  
**Package:** coderstm/laravel-core
