# Coupon Redemption Report

**Report Type:** `coupon-redemption`  
**Category:** Marketing & Financial Audit  
**Data Source:** Orders + Coupons

---

## Overview

Track individual coupon redemptions with complete transaction details. See who used which coupons, when, and the discount impact for detailed audit trails and fraud detection.

**You'll quickly spot:**
- Coupon usage patterns by customer
- Discount amounts and final totals
- Suspicious redemption patterns
- Individual transaction details for finance/support

---

## How to Use This Report

### Financial Audit
1. Export for accounting reconciliation
2. Verify discount amounts match expectations
3. Track coupon usage by date range

### Fraud Detection
1. Identify multiple redemptions by same user
2. Spot suspicious redemption patterns
3. Track high-value discount abuse

### Customer Support
1. Lookup specific redemptions by order ID
2. Verify coupon codes used by customers
3. Resolve discount disputes

---

## Column Definitions

### Redemption Date
**Type:** Date  
**Description:** When the coupon was used  
**How it's calculated:** Order created_at timestamp

### Coupon Code
**Type:** Text  
**Description:** The coupon code entered by customer

### User Email
**Type:** Text  
**Description:** Customer email or "Guest" for guest checkouts  
**How it's calculated:** User email from linked user, or "Guest" if no user

### Order ID
**Type:** Number  
**Description:** Unique order identifier

### Order Total
**Type:** Currency  
**Description:** Order grand total before discount  
**How it's calculated:** Order subtotal + tax + shipping (before discount)

### Discount Amount
**Type:** Currency  
**Description:** Total discount applied from this coupon  
**How it's calculated:** Sum of discount_total from order's discount lines for this coupon

### Final Total
**Type:** Currency  
**Description:** Order total after discount  
**How it's calculated:** Order Total - Discount Amount

### Discount Type
**Type:** Text  
**Description:** Type of discount applied  
**Values:** "Percentage", "Fixed Amount", "Override Price"

---

## Benchmarks & Targets

### Redemption Patterns
- **Normal:** < 3 redemptions per customer per month
- **Moderate:** 3-5 redemptions per customer per month
- **High:** > 5 redemptions per customer per month (potential abuse)

### Discount Amounts
- **Normal:** Discount < 30% of order total
- **High:** Discount 30-50% of order total
- **Very High:** Discount > 50% of order total (review for validity)

### Guest vs User Redemptions
- **Healthy:** < 20% guest redemptions (most users create accounts)
- **Moderate:** 20-40% guest redemptions
- **High:** > 40% guest redemptions (may indicate coupon sharing)

---

## Actionable Insights

### If Same User Redeems Multiple Times
**Likely Causes:** Loyal customer, coupon sharing, multi-device usage  
**Actions:** Review if single-use coupons are being bypassed, check for account duplicates

### If High Guest Redemptions
**Likely Causes:** Public coupon codes, coupon sharing, no account requirement  
**Actions:** Require account creation, add minimum purchase requirements

### If Discount Amounts Consistently > 50%
**Likely Causes:** Overly generous coupons, coupon stacking, pricing errors  
**Actions:** Review coupon strategy, add caps, prevent stacking

### If Redemptions Spike on Specific Days
**Likely Causes:** Marketing campaigns, social media sharing, influencer promotion  
**Actions:** Track campaign performance, adjust inventory, monitor abuse

---

## Example

| Date       | Coupon Code | User Email         | Order ID | Order Total | Discount | Final Total | Type       |
|------------|-------------|-------------------|----------|-------------|----------|-------------|------------|
| 2025-01-15 | WINTER20    | john@example.com   | 1001     | $100.00     | $20.00   | $80.00      | Percentage |
| 2025-01-15 | WINTER20    | jane@example.com   | 1002     | $150.00     | $30.00   | $120.00     | Percentage |
| 2025-01-16 | WELCOME50   | Guest              | 1003     | $200.00     | $50.00   | $150.00     | Fixed      |
| 2025-01-16 | WINTER20    | john@example.com   | 1004     | $80.00      | $16.00   | $64.00      | Percentage |

**Insight:** john@example.com used WINTER20 twice in 2 days—check if single-use restriction is working.

---

## Filters & Views

### Date Range
- **Daily:** Detailed tracking
- **Weekly:** Campaign performance
- **Monthly:** Financial reconciliation

### Filter by Coupon Code
- Track specific campaign performance
- Audit single coupon usage

### Filter by User
- Identify frequent coupon users
- Investigate potential abuse

### Filter by Discount Type
- Compare percentage vs fixed discounts
- Analyze discount strategy effectiveness

---

## Related Reports

- **Coupon Performance:** Overall coupon ROI
- **Discount Impact:** Revenue vs margin analysis
- **Orders Export:** Full order details

---

## FAQs

**Q: Why do I see "Guest" for some redemptions?**  
A: Coupon used during guest checkout without account creation. Common for public coupon codes.

**Q: How do I prevent coupon abuse?**  
A: Add single-use restrictions, minimum purchase requirements, account creation requirements, expiration dates.

**Q: Can one order have multiple coupons?**  
A: Depends on your coupon stacking settings. This report shows total discount per order, not per coupon if multiple applied.

**Q: What if discount amount doesn't match expected?**  
A: Check coupon conditions (minimum purchase, specific products), cart subtotal, and coupon type (percentage vs fixed).

**Q: How do I export for accounting?**  
A: Use date range filter for your accounting period, export to CSV, import to accounting software.

---

## Best Practices

1. **Export monthly** for financial reconciliation
2. **Monitor high-value redemptions** for fraud prevention
3. **Track redemptions per user** to identify abuse patterns
4. **Review guest redemptions** and consider account requirements
5. **Use for support** to resolve customer coupon disputes quickly
6. **Set alerts** for unusual redemption spikes
7. **Audit regularly** to ensure single-use coupons work correctly

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
