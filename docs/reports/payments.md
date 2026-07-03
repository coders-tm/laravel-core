# Payments Export Report

**Report Type:** `payments`  
**Category:** Data Export & Financial Reconciliation  
**Data Source:** Payments + Payment Methods

---

## Overview

Export detailed payment transaction records with fees, net amounts, refunds, and provider metadata. Essential for reconciling with payment processors and tracking net revenue.

**You'll get:**
- Complete payment transaction history
- Fee breakdowns and net amounts
- Refund tracking
- Payment method and status details
- Provider transaction IDs for reconciliation

---

## How to Use This Report

### Payment Provider Reconciliation
1. Match Transaction IDs with provider dashboards (Stripe, PayPal, etc.)
2. Validate fees charged by each provider
3. Identify discrepancies in payment processing

### Net Revenue Tracking
1. Calculate true net revenue (Amount - Fees - Refunds)
2. Track payment provider cost per transaction
3. Monitor refund rates and patterns

### Financial Reporting
1. Export for accounting system integration
2. Track payment timing for cash flow analysis
3. Audit payment processing compliance

---

## Column Definitions

### Payment ID
**Type:** Number  
**Description:** Unique payment record identifier

### Transaction ID
**Type:** Text  
**Description:** Payment provider's transaction identifier (for reconciliation)

### Amount
**Type:** Currency  
**Description:** Gross payment amount before fees

### Currency
**Type:** Text  
**Description:** Three-letter currency code (USD, EUR, GBP, etc.)

### Fees
**Type:** Currency  
**Description:** Payment processing fees charged by provider

### Net Amount
**Type:** Currency  
**Description:** Amount after fees  
**How it's calculated:** Amount - Fees

### Refund Amount
**Type:** Currency  
**Description:** Total refunded amount

### Status
**Type:** Text  
**Description:** Payment state (pending, processing, completed, failed, refunded)

### Payment Method
**Type:** Text  
**Description:** Payment provider used (Stripe, PayPal, Razorpay, etc.)

### Linked Type
**Type:** Text  
**Description:** Resource type (Order, Subscription, Invoice)

### Linked ID
**Type:** Number  
**Description:** ID of linked resource

### Processed At
**Type:** Date  
**Description:** Timestamp when payment was successfully processed

### Created At
**Type:** Date  
**Description:** Timestamp when payment record was created

### Note
**Type:** Text  
**Description:** Optional payment notes or metadata

---

## Filters & Views

### Filter by Status
- Completed payments only
- Failed payments
- Refunded payments

### Filter by Payment Method
- Stripe payments
- PayPal payments
- Specific provider

### Date Range
- Payments processed or created within range

---

## Export Formats

- **CSV:** Standard spreadsheet format
- **Excel:** Formatted workbook with financial formulas

---

## Related Reports

- **Payment Performance:** Payment timing and reliability
- **Payment Method Performance:** Provider comparison
- **Orders Export:** Order-level financial data

---

## FAQs

**Q: Why is Net Amount different from Amount?**  
A: Net Amount = Amount - Fees (payment processing costs deducted).

**Q: How do I reconcile with Stripe/PayPal?**  
A: Match Transaction IDs with provider dashboards; amounts should align.

**Q: What if a payment shows as "completed" but customer got a refund?**  
A: Check Refund Amount column; status may stay "completed" with refund tracked separately.

**Q: Can I export payment method details (last 4 digits, card type)?**  
A: Additional payment metadata may be in linked orders or separate payment methods export.

---

## Best Practices

1. **Export monthly** for financial reconciliation
2. **Match Transaction IDs** with payment provider reports
3. **Track net revenue** (Amount - Fees - Refunds) for accurate P&L
4. **Monitor failed payments** for provider reliability
5. **Secure exports** containing financial data
6. **Validate fees** against provider agreements

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
