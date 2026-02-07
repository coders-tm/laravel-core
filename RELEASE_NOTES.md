# Payment Method Refactoring - Change Log

## Overview
Refactored payment processing architecture to use explicit `PaymentMethod` model dependency injection instead of automatic detection and ID-only storage.

---

## Core Changes

### 1. Payment Processor Interface (`PaymentProcessorInterface.php`)

**Added Methods:**
```php
public function setPaymentMethod(\Coderstm\Models\PaymentMethod $paymentMethod): self;
public function getPaymentMethod(): ?\Coderstm\Models\PaymentMethod;
```

### 2. Abstract Payment Processor (`AbstractPaymentProcessor.php`)

**Added:**
- Property: `protected $paymentMethod = null;`
- Method: `setPaymentMethod(PaymentMethod $paymentMethod): self` - Returns `$this` for chaining
- Method: `getPaymentMethod(): ?PaymentMethod` - Returns nullable PaymentMethod

**Behavior:**
- Payment method is NO LONGER set automatically in constructors
- Must be set explicitly via `setPaymentMethod()` after instantiation

### 3. All Payment Processors (10 files)

**Files Changed:**
- `StripeProcessor.php`
- `PaypalProcessor.php`
- `RazorpayProcessor.php`
- `MercadoPagoProcessor.php`
- `ManualProcessor.php`
- `WalletProcessor.php`
- `FlutterwaveProcessor.php`
- `PaystackProcessor.php`
- `XenditProcessor.php`
- `KlarnaProcessor.php`

**Changes:**
- ❌ Removed: All constructors (no automatic payment method initialization)
- ✅ Payment method must now be set via `->setPaymentMethod($paymentMethod)`

---

## Payment Mapper Changes

### 4. Abstract Payment Mapper (AbstractPayment.php)

**Property Changed:**
```php
// OLD:
protected int $paymentMethodId;

// NEW:
protected PaymentMethod $paymentMethod;
```

**Methods Changed:**
```php
// NEW method added:
public function getPaymentMethod(): PaymentMethod

// EXISTING method updated:
public function getPaymentMethodId(): int
// Now returns: $this->paymentMethod->id
```

**Removed:**
- ❌ `detectPaymentMethod(string $provider): PaymentMethod` - Static helper for auto-detection
- ❌ `validatePaymentMethod(): void` - Runtime validation (redundant with type hints)

### 5. All Payment Mappers (9 files)

**Files Changed:**
- StripePayment.php
- `FlutterwavePayment.php`
- PayPalPayment.php
- RazorpayPayment.php
- MercadoPagoPayment.php
- PaystackPayment.php
- XenditPayment.php
- KlarnaPayment.php
- ManualPayment.php

**Constructor Signature Changed:**
```php
// OLD:
public function __construct($response, Payable $payable, ?PaymentMethod $paymentMethod = null)

// NEW:
public function __construct($response, Payable $payable, PaymentMethod $paymentMethod)
```

**Property Assignment Changed:**
```php
// OLD:
$this->paymentMethodId = $paymentMethod?->id ?? static::detectPaymentMethod('provider')->id;

// NEW:
$this->paymentMethod = $paymentMethod;
```

**Key Changes:**
- ✅ `PaymentMethod` parameter is now **required** (non-nullable)
- ❌ Removed auto-detection fallback
- ❌ Removed `validatePaymentMethod()` calls (redundant)
- ✅ Store full `PaymentMethod` model instead of just ID

---

## Breaking Changes

### For Payment Processor Usage:

**OLD:**
```php
$processor = new StripeProcessor($config); // Had payment method automatically loaded
```

**NEW:**
```php
$processor = new StripeProcessor($config);
$processor->setPaymentMethod($paymentMethod); // Must set explicitly
```

### For Payment Mapper Usage:

**OLD:**
```php
// Payment method was optional, auto-detected if not provided
$payment = new StripePayment($response, $checkout);
$payment = new StripePayment($response, $checkout, $paymentMethod);
```

**NEW:**
```php
// Payment method is REQUIRED
$payment = new StripePayment($response, $checkout, $paymentMethod);
```

---

## Migration Guide for Tests

### 1. Payment Processor Tests

**Update instantiation:**
```php
// OLD:
$processor = new StripeProcessor($config);

// NEW:
$paymentMethod = PaymentMethod::factory()->create(['provider' => 'stripe']);
$processor = new StripeProcessor($config);
$processor->setPaymentMethod($paymentMethod);
```

### 2. Payment Mapper Tests

**Update constructor calls:**
```php
// OLD:
$mapper = new StripePayment($response, $checkout);
// or
$mapper = new StripePayment($response, $checkout, null);

// NEW:
$paymentMethod = PaymentMethod::factory()->create(['provider' => 'stripe']);
$mapper = new StripePayment($response, $checkout, $paymentMethod);
```

**Update assertions:**
```php
// OLD:
$this->assertEquals($paymentMethodId, $mapper->getPaymentMethodId());

// NEW:
$this->assertEquals($paymentMethodId, $mapper->getPaymentMethodId());
$this->assertInstanceOf(PaymentMethod::class, $mapper->getPaymentMethod());
$this->assertEquals('stripe', $mapper->getPaymentMethod()->provider);
```

### 3. Integration Tests

**Database setup:**
```php
// Ensure PaymentMethod exists before creating payments
$paymentMethod = PaymentMethod::factory()->create([
    'provider' => 'stripe',
    'is_default' => true,
]);

// Use in payment processing
$payment = $processor->process($checkout, $paymentMethod);
```

---

## Test Files That Need Updates

### Processor Tests:
- `tests/Unit/Payment/Processors/StripeProcessorTest.php`
- `tests/Unit/Payment/Processors/PaypalProcessorTest.php`
- `tests/Unit/Payment/Processors/RazorpayProcessorTest.php`
- `tests/Unit/Payment/Processors/MercadoPagoProcessorTest.php`
- `tests/Unit/Payment/Processors/FlutterwaveProcessorTest.php`
- `tests/Unit/Payment/Processors/PaystackProcessorTest.php`
- `tests/Unit/Payment/Processors/XenditProcessorTest.php`
- `tests/Unit/Payment/Processors/KlarnaProcessorTest.php`
- `tests/Unit/Payment/Processors/ManualProcessorTest.php`
- `tests/Unit/Payment/Processors/WalletProcessorTest.php`

### Mapper Tests:
- `tests/Unit/Payment/Mappers/StripePaymentTest.php`
- `tests/Unit/Payment/Mappers/FlutterwavePaymentTest.php`
- `tests/Unit/Payment/Mappers/PayPalPaymentTest.php`
- `tests/Unit/Payment/Mappers/RazorpayPaymentTest.php`
- `tests/Unit/Payment/Mappers/MercadoPagoPaymentTest.php`
- `tests/Unit/Payment/Mappers/PaystackPaymentTest.php`
- `tests/Unit/Payment/Mappers/XenditPaymentTest.php`
- `tests/Unit/Payment/Mappers/KlarnaPaymentTest.php`
- `tests/Unit/Payment/Mappers/ManualPaymentTest.php`

### Integration/Feature Tests:
- `tests/Feature/Payment/CheckoutTest.php`
- `tests/Feature/Payment/SubscriptionPaymentTest.php`
- `tests/Feature/Payment/PaymentProcessingTest.php`
- Any other tests that use payment processors or mappers

---

## Benefits of Changes

1. **Explicit Dependencies**: Payment methods must be provided explicitly
2. **No Database Queries**: Eliminates automatic `PaymentMethod::byProvider()` queries
3. **Better Testability**: Easy to mock/inject payment methods in tests
4. **Type Safety**: PHP type hints ensure valid PaymentMethod objects
5. **Richer Data**: Full PaymentMethod model available instead of just ID
6. **Clear Errors**: Missing payment methods cause immediate type errors, not runtime failures

---

## Validation Checklist for AI Agent

- [ ] All processor tests pass `PaymentMethod` via `setPaymentMethod()`
- [ ] All mapper tests pass `PaymentMethod` as required constructor parameter
- [ ] No tests try to instantiate mappers without payment method
- [ ] Factory/seeder data includes PaymentMethod creation
- [ ] Integration tests set up PaymentMethod before payment processing
- [ ] Assertions check both `getPaymentMethodId()` and `getPaymentMethod()`
- [ ] No references to removed `detectPaymentMethod()` method
- [ ] No references to removed `validatePaymentMethod()` method
