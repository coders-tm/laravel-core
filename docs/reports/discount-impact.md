# Discount Impact Report

**Report Type:** `discount-impact`  
**Category:** Financial Analysis  
**Data Source:** Orders + Discounts

---

## Overview

Track discount usage and its impact on revenue and margins. See gross revenue, discount amounts, net revenue, and discount percentage to balance growth with profitability.

**You'll quickly spot:**
- Total discounts given (dollar value)
- Revenue erosion from discounts
- Discount dependency trends
- Profitability impact

---

## How to Use This Report

### Margin Protection
1. Monitor discount percentage of gross revenue
2. Identify unsustainable discount levels (>30%)
3. Set discount caps to protect margins

### Discount Strategy
1. Track discount rate (% of orders with discounts)
2. Evaluate if discounts drive incremental revenue
3. Test reducing discount amounts without losing conversions

### Profitability Analysis
1. Compare net revenue trends to gross revenue
2. Calculate true customer acquisition costs
3. Measure ROI of discount campaigns

---

## Column Definitions

### Period
**Type:** Text  
**Description:** Date period (day, week, month)

### Gross Revenue
**Type:** Currency  
**Description:** Total revenue before discounts  
**How it's calculated:** Sum of all order subtotals (before discounts applied)

### Total Discounts
**Type:** Currency  
**Description:** Total discount amount applied  
**How it's calculated:** Sum of all discount line items

### Net Revenue
**Type:** Currency  
**Description:** Actual revenue after discounts  
**How it's calculated:** Gross Revenue - Total Discounts

### Discount Percentage
**Type:** Percentage  
**Description:** Discounts as percentage of gross revenue  
**How it's calculated:** (Total Discounts ÷ Gross Revenue) × 100

### Discount Rate
**Type:** Percentage  
**Description:** Percentage of orders that used discounts  
**How it's calculated:** (Orders with Discounts ÷ Total Orders) × 100

### Orders with Discounts
**Type:** Number  
**Description:** Count of orders using coupons or discounts

### Total Orders
**Type:** Number  
**Description:** All orders in period

---

## Benchmarks & Targets

### Discount Percentage of Revenue
- **Healthy:** 10–20% of gross revenue
- **Moderate:** 20–30% of gross revenue
- **High Risk:** > 30% of gross revenue (margin erosion)

### Discount Rate (Usage)
- **Controlled:** < 40% of orders use discounts
- **High:** 40–60% of orders use discounts
- **Dependency Risk:** > 60% of orders use discounts

### Net Revenue Growth
- **Excellent:** Net revenue growing despite discounts
- **Warning:** Gross revenue growing, net revenue flat (discount abuse)
- **Critical:** Net revenue declining (unsustainable discounting)

---

## Actionable Insights

### If Discount Percentage > 30%
**Likely Causes:** Aggressive promotions, coupon abuse, pricing too high  
**Actions:** Reduce discount amounts, add minimum purchase requirements, test price optimization

### If Discount Rate > 60%
**Likely Causes:** Customer discount dependency, poor pricing strategy  
**Actions:** Phase out blanket discounts, use targeted offers, improve value communication

### If Net Revenue Flat While Gross Revenue Grows
**Likely Causes:** Discount creep, increasing discount usage  
**Actions:** Audit coupon codes, retire low-ROI coupons, tighten discount controls

### If Discount Percentage Declining and Net Revenue Growing
**Likely Causes:** Successful pricing strategy, reduced discount dependency  
**Actions:** Maintain strategy, reinvest savings in customer experience

---

## Example

| Period   | Gross Revenue | Total Discounts | Net Revenue | Discount % | Discount Rate | Orders w/ Discount | Total Orders |
|----------|---------------|-----------------|-------------|------------|---------------|--------------------|--------------|
| Jan 2025 | $120,000      | $18,000         | $102,000    | 15.0%      | 35%           | 245                | 700          |
| Feb 2025 | $135,000      | $27,000         | $108,000    | 20.0%      | 42%           | 315                | 750          |
| Mar 2025 | $150,000      | $45,000         | $105,000    | 30.0%      | 58%           | 435                | 750          |

**Insight:** Gross revenue growing but net revenue declining—discount percentage jumped from 15% to 30%, eroding profitability.

---

## Filters & Views

### Date Range & Granularity
- **Weekly:** Tactical discount campaign tracking
- **Monthly:** Standard for financial analysis
- **Quarterly:** Strategic profitability assessment

### Filter by Discount Type
- Percentage off vs fixed amount
- Plan discounts vs product discounts
- Auto-apply vs manual entry

---

## Related Reports

- **Coupon Performance:** Specific coupon ROI
- **Sales Summary:** Revenue context
- **Payment Performance:** Net revenue reconciliation

---

## FAQs

**Q: Why is net revenue more important than gross revenue?**  
A: Net revenue reflects actual cash received—gross revenue overstates performance when discounts are high.

**Q: What's a healthy discount percentage?**  
A: 10–20% of gross revenue balances growth with profitability. Above 30% erodes margins.

**Q: Should I stop all discounts?**  
A: No—use targeted discounts for acquisition or reactivation, but avoid blanket promotions.

**Q: How do I reduce discount dependency?**  
A: Phase out discounts gradually, improve value perception, segment offers by customer type.

---

## Best Practices

1. **Set discount caps** at 20% of gross revenue
2. **Track monthly** to catch margin erosion early
3. **Segment by customer cohort** to avoid training customers to wait for discounts
4. **Test discount-free periods** to establish true willingness to pay
5. **Retire low-ROI coupons** that generate sales but hurt profitability
6. **Add minimum purchase requirements** to protect margins
7. **Monitor net revenue growth** as primary KPI, not gross revenue

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
