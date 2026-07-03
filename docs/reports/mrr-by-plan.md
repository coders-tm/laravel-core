# MRR by Plan Report

**Report Type:** `mrr-by-plan`  
**Category:** Revenue Analytics  
**Data Source:** Subscriptions & Plans

---

## Overview

The MRR by Plan report shows how much monthly recurring revenue each plan contributes, normalized to monthly equivalents. It highlights plan performance, subscription volume, and each plan’s share of total MRR.

**Key insights:**
- Which plans drive the most recurring revenue
- Plan pricing effectiveness and interval normalization
- Subscription volume and quantities by plan
- Share of total MRR per plan (percentage)

---

## What You’ll Learn

### Plan Contribution
- **Monthly Price (normalized)**: Compare plans fairly across intervals
- **Active Subscriptions**: Volume for each plan
- **MRR**: Monthly revenue contribution

### Strategy & Pricing
- **MRR Percentage**: Share of total MRR
- **Plan Mix**: Distribution of customers across tiers
- **Upgrade/Downgrade Targets**: Identify plans for upsell campaigns

---

## How to Use This Report

### Compare Plan Performance
1. Sort by MRR to see top contributors
2. Compare active subscriptions vs monthly price
3. Identify plans with high share but low volume (premium) or high volume but low price (entry)

**Example:** If a mid-tier plan contributes 40% of MRR with 30% of subs, it’s your cash cow—optimize pricing and upsells here.

### Optimize Pricing & Intervals
1. Review monthly-normalized prices (day/week/year are normalized)
2. If weekly plans underperform, test monthly equivalents
3. If yearly plans dominate, consider annual incentives

### Focus Upsell/Downgrade Prevention
1. Target customers on low-performing plans for upsell
2. Watch plans with declining MRR % for churn signals
3. Create upgrade paths with visible value differences

---

## Column Definitions

### Plan Name
**Type:** Text  
**Description:** Human-friendly plan label

### Plan Slug
**Type:** Text  
**Description:** Technical identifier used in URLs and APIs

### Interval
**Type:** Text  
**Description:** Billing interval (day, week, month, year), with count if > 1  
**Use Case:** Understand how pricing maps to monthly value

### Price
**Type:** Currency  
**Description:** Price charged per billing interval

### Monthly Price (Normalized)
**Type:** Currency  
**Description:** Price converted to monthly equivalent for fair comparison  
**How it’s calculated:**
- Year: price ÷ 12
- Week: price × 4.345
- Day: price × 30
- Custom intervals: price ÷ interval_count (if multi-month)

### Active Subscriptions
**Type:** Number  
**Description:** Distinct active subscriptions on the plan  
**Use Case:** Measure plan adoption

### Total Quantity
**Type:** Number  
**Description:** Effective subscription quantity (accounts for unit quantities)  
**How it’s calculated:** If quantity tracked and non-zero, use it; otherwise fallback to active subscription count

### Monthly Recurring Revenue (MRR)
**Type:** Currency  
**Description:** Monthly revenue from the plan  
**How it’s calculated:** Monthly Price × Effective Quantity

### MRR Percentage
**Type:** Percentage  
**Description:** Plan’s share of total MRR  
**How it’s calculated:** (Plan MRR ÷ Total MRR) × 100

---

## Report Calculations & Formulas

### Monthly Price Normalization
```
Monthly Price =
- Year: price ÷ 12
- Week: price × 4.345
- Day: price × 30
- Multi-month intervals: price ÷ interval_count
```

### Effective Quantity
```
Effective Quantity = total_quantity (if > 0) else active_subscriptions
```

### Plan MRR
```
MRR = Monthly Price × Effective Quantity
```

### Plan Share of Total MRR
```
MRR Percentage (%) = (Plan MRR ÷ Σ Plan MRR) × 100
```

---

## Actionable Insights

### If a Plan Dominates MRR
**Potential Actions:**
1. Refine pricing to capture more value
2. Add premium features to create an upgrade path from this tier
3. Run targeted campaigns highlighting this plan’s strengths

### If a Plan Has High Subs but Low MRR
**Potential Actions:**
1. Introduce mid-tier options to move users up
2. Bundle features to increase perceived value
3. Test small price increases with grandfathering

### If a Plan’s MRR % Declines
**Potential Actions:**
1. Investigate churn and downgrade reasons
2. Improve the plan’s value proposition
3. Add limited-time upgrade incentives

---

## Summary Metrics

The summary can show:
- **Total MRR** across all plans (formatted)
- **Top Plan by MRR**
- **Plan Count** and distribution

---

## Filters & Customization

### Date Range & Granularity
- **Monthly (recommended):** Clear plan performance view
- **Weekly:** Fast-moving changes/experiments
- **Quarterly:** Strategic pricing review

---

## Related Reports

- **MRR Movement**: Understand gains/losses driving plan changes
- **Plan Comparison**: Feature and value differences across plans
- **Subscription Lifecycle**: Plan changes over the lifecycle

---

## Frequently Asked Questions

**Q: Why normalize prices to monthly?**  
A: It enables fair comparison across different billing intervals (weekly, annual) and shows true recurring contribution.

**Q: What if quantities differ between plans?**  
A: Use effective quantity to reflect unit-based subscriptions (e.g., seats). If quantity isn’t tracked, use active subscriptions.

**Q: Should I favor annual plans?**  
A: Annual plans improve cash flow and retention. This report still normalizes revenue to monthly to keep comparisons meaningful.

---

## Best Practices

1. **Review monthly:** Watch plan MRR % trends
2. **Price experiments:** Test small changes and monitor impact
3. **Upgrade paths:** Make value differences between tiers clear
4. **Bundle smartly:** Increase perceived value with curated features
5. **Communicate:** Announce pricing changes transparently
6. **Grandfathering:** Protect existing customers during increases

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
