# Coupon Performance Report

**Report Type:** `coupon-performance`  
**Category:** Marketing & Discounting  
**Data Source:** Coupons + Orders + Discount Lines

---

## Overview

Track coupon redemption, revenue impact, and return on investment. Identify which promotions drive profitable growth vs those that erode margins.

**You'll quickly spot:**
- High-ROI coupons (revenue generators)
- Low-ROI coupons (margin erosion)
- Underutilized coupons
- Most popular discount codes

---

## How to Use This Report

### Optimize Discounting Strategy
1. Focus on coupons with high ROI (> 300%)
2. Retire or adjust low-ROI coupons
3. Track usage patterns to avoid abuse

### Revenue vs Margin Analysis
1. High revenue but low ROI = margin problem
2. Low usage = poor promotion or targeting
3. Balance revenue growth with profitability

### Campaign Effectiveness
1. Compare coupon performance across campaigns
2. Test discount levels and measure impact
3. Track seasonal vs evergreen coupon performance

---

## Column Definitions

### Coupon ID
**Type:** Number  
**Description:** Unique coupon identifier

### Coupon Code
**Type:** Text  
**Description:** Promotion code used by customers

### Type
**Type:** Text  
**Description:** Coupon type (product, plan, cart)

### Discount Type
**Type:** Text  
**Description:** Discount mechanism (percentage, fixed, override)

### Discount Value
**Type:** Number/Currency  
**Description:** Discount amount or percentage

### Times Used
**Type:** Number  
**Description:** Total redemption count  
**How it's calculated:** Count of discount lines with this coupon

### Total Discount
**Type:** Currency  
**Description:** Sum of all discounts given  
**How it's calculated:** Sum of discount amounts from all redemptions

### Revenue Generated
**Type:** Currency  
**Description:** Total order revenue with this coupon applied  
**How it's calculated:** Sum of order grand totals where coupon was used

### Return on Investment (ROI)
**Type:** Percentage  
**Description:** Revenue efficiency vs discounts given  
**How it's calculated:** ((Revenue Generated - Total Discount) ÷ Total Discount) × 100

### Status
**Type:** Text  
**Description:** Coupon state (active, inactive, expired)

---

## Benchmarks & Targets

### ROI Targets
- **Excellent:** > 400% (every $1 discount generates $5+ revenue)
- **Good:** 300–400% ($4–$5 revenue per $1 discount)
- **Break-Even:** 100–300% ($2–$4 revenue per $1 discount)
- **Margin Erosion:** < 100% (losing money on discounts)

### Redemption Rate
- **High Usage:** > 1,000 redemptions
- **Moderate:** 100–1,000 redemptions
- **Low:** < 100 redemptions

---

## Actionable Insights

### If ROI is Low (< 100%)
**Likely Causes:** Excessive discount, wrong audience, abuse, cart padding  
**Actions:** Reduce discount value, add minimum purchase requirements, limit usage per customer

### If Usage is Low but ROI is High
**Likely Causes:** Poor visibility, limited distribution, targeting issues  
**Actions:** Promote more aggressively, extend expiration, broader distribution

### If Usage is High but ROI is Low
**Likely Causes:** Discount too generous, margin pressure, abuse  
**Actions:** Reduce discount, add restrictions, investigate abuse patterns

---

## Example

| Coupon Code | Type    | Discount Type | Value | Times Used | Total Discount | Revenue Generated | ROI    | Status |
|-------------|---------|---------------|-------|------------|----------------|-------------------|--------|--------|
| SAVE20      | cart    | percentage    | 20%   | 500        | $10,000        | $60,000           | 500%   | Active |
| WELCOME10   | product | percentage    | 10%   | 1,200      | $6,000         | $30,000           | 400%   | Active |
| FLASH50     | cart    | fixed         | $50   | 300        | $15,000        | $20,000           | 33%    | Expired|

---

## Filters & Views

### Filter by Type
- Product coupons
- Plan coupons
- Cart-wide coupons

### Filter by Status
- Active coupons only
- Expired coupons

### Sort by ROI
- Highest ROI first to identify winners
- Lowest ROI first to identify problem coupons

---

## Related Reports

- **Discount Impact:** Revenue vs margin analysis
- **Sales Summary:** Overall revenue trends
- **Orders Export:** Individual order-level coupon usage

---

## FAQs

**Q: Why is ROI negative?**  
A: Discount exceeded revenue (refunds, returns, or very low-value orders).

**Q: Should I retire low-usage coupons?**  
A: Check ROI first—low usage with high ROI might need better promotion, not retirement.

**Q: How do I prevent coupon abuse?**  
A: Add usage limits per customer, minimum order requirements, and track redemption patterns.

**Q: What's a healthy discount percentage?**  
A: 10–20% for most SaaS/e-commerce; higher for customer acquisition, lower for retention.

---

## Best Practices

1. **Track ROI monthly** to catch margin erosion early
2. **Segment by campaign** to measure specific promotions
3. **Set minimum order thresholds** to protect margins
4. **Limit usage per customer** to prevent abuse
5. **Test discount levels** and measure impact on conversion and profitability
6. **Retire low-ROI coupons** that don't drive incremental revenue

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
