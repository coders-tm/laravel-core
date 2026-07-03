# MRR Churn Report

**Report Type:** `mrr-churn`  
**Category:** Revenue Analytics  
**Data Source:** Subscriptions + Orders

---

## Overview

Track monthly recurring revenue (MRR) churn to understand revenue loss from cancellations. See starting MRR, churned MRR, new MRR, and net MRR change to measure revenue health.

**You'll quickly spot:**
- Revenue lost to churn (churned MRR)
- Revenue gained from new subscriptions (new MRR)
- Net MRR growth or decline
- MRR churn rate trends

---

## How to Use This Report

### Revenue Health Monitoring
1. Track MRR churn rate monthly (target < 5%)
2. Compare churned MRR vs new MRR for net growth
3. Identify periods with negative net MRR change

### Churn Reduction
1. Calculate revenue impact of retention improvements
2. Prioritize high-MRR accounts for retention efforts
3. Monitor churn rate trends after product changes

### Growth Planning
1. Forecast future MRR based on churn and new MRR trends
2. Calculate required new MRR to offset churn
3. Set acquisition targets to maintain positive net MRR

---

## Column Definitions

### Period
**Type:** Text  
**Description:** Date period (day, week, month)

### Starting Monthly Recurring Revenue
**Type:** Currency  
**Description:** MRR at the beginning of the period  
**How it's calculated:** Sum of active subscription MRR at period start (normalized to monthly)

### Churned Monthly Recurring Revenue
**Type:** Currency  
**Description:** MRR lost from cancellations  
**How it's calculated:** Sum of MRR from subscriptions canceled during the period

### New Monthly Recurring Revenue
**Type:** Currency  
**Description:** MRR gained from new subscriptions  
**How it's calculated:** Sum of MRR from subscriptions created during the period

### Ending Monthly Recurring Revenue
**Type:** Currency  
**Description:** MRR at the end of the period  
**How it's calculated:** Starting MRR + Net MRR Change

### MRR Churn Rate
**Type:** Percentage  
**Description:** Percentage of starting MRR lost to churn  
**How it's calculated:** (Churned MRR ÷ Starting MRR) × 100

### Net Monthly Recurring Revenue Change
**Type:** Currency  
**Description:** Net MRR growth or decline  
**How it's calculated:** New MRR - Churned MRR

---

## Benchmarks & Targets

### MRR Churn Rate
- **Excellent:** < 3% monthly MRR churn
- **Good:** 3-5% monthly MRR churn
- **Fair:** 5-7% monthly MRR churn
- **High:** > 7% monthly MRR churn (unsustainable)

### Net MRR Growth
- **Excellent:** Positive net MRR growth month-over-month
- **Warning:** Flat net MRR (new MRR = churned MRR)
- **Critical:** Negative net MRR (losing more than gaining)

### New MRR vs Churned MRR Ratio
- **Excellent:** New MRR > 2× Churned MRR
- **Good:** New MRR > 1.5× Churned MRR
- **Fair:** New MRR > Churned MRR
- **Poor:** New MRR < Churned MRR (negative growth)

---

## Actionable Insights

### If MRR Churn Rate > 5%
**Likely Causes:** Poor retention, pricing issues, product-market fit problems  
**Actions:** Launch retention campaigns, survey churned customers, improve onboarding

### If Net MRR Change Negative
**Likely Causes:** Churn outpacing acquisition, high-value customer churn  
**Actions:** Increase acquisition spend, focus on high-MRR retention, improve value delivery

### If Churned MRR Increasing Month-over-Month
**Likely Causes:** Product quality issues, competitive threats, pricing pressure  
**Actions:** Identify churn drivers, improve product features, adjust pricing strategy

### If New MRR Declining
**Likely Causes:** Acquisition challenges, market saturation, seasonal factors  
**Actions:** Boost marketing spend, test new channels, optimize conversion funnel

---

## Example

| Period   | Starting MRR | Churned MRR | New MRR | Ending MRR | MRR Churn Rate | Net MRR Change |
|----------|--------------|-------------|---------|------------|----------------|----------------|
| Jan 2025 | $100,000     | $4,000      | $8,000  | $104,000   | 4.0%           | +$4,000        |
| Feb 2025 | $104,000     | $5,200      | $9,000  | $107,800   | 5.0%           | +$3,800        |
| Mar 2025 | $107,800     | $6,468      | $10,500 | $111,832   | 6.0%           | +$4,032        |

**Insight:** MRR churn rate increasing from 4% to 6%—investigate retention issues. Net MRR still positive but churn rate needs attention.

---

## Filters & Views

### Date Range & Granularity
- **Weekly:** Early warning system for churn spikes
- **Monthly:** Standard for MRR churn tracking
- **Quarterly:** Strategic planning

### Filter by Plan
- Compare churn rates across plans
- Identify plans with high MRR churn

### Filter by Cohort
- Track churn by customer signup month
- Identify cohorts with higher churn

---

## Related Reports

- **MRR Movement:** Overall MRR changes (includes expansion/contraction)
- **Customer Churn:** Customer count churn (vs revenue churn)
- **Plan Comparison:** Plan-specific churn rates

---

## FAQs

**Q: What's the difference between customer churn and MRR churn?**  
A: Customer churn measures % of customers lost; MRR churn measures % of revenue lost. High-value customers leaving can cause high MRR churn even with low customer churn.

**Q: What's a healthy MRR churn rate?**  
A: < 5% monthly is good. < 3% is excellent. Above 7% is unsustainable for most SaaS businesses.

**Q: Should I focus on reducing churn or increasing new MRR?**  
A: Both matter, but reducing churn is usually more cost-effective. Retaining customers is cheaper than acquiring new ones.

**Q: How do I calculate annual MRR churn from monthly?**  
A: Annual MRR churn ≈ (1 - (1 - Monthly Churn Rate)^12) × 100. Example: 5% monthly = ~46% annual.

**Q: Why is my MRR churn rate higher than customer churn rate?**  
A: High-value customers (enterprise plans) are churning at a higher rate than low-value customers.

---

## Best Practices

1. **Track monthly** to catch churn spikes early
2. **Set MRR churn target** of < 5% monthly
3. **Segment by plan tier** to identify high-value churn
4. **Compare to customer churn** to understand revenue impact
5. **Focus on high-MRR accounts** for retention efforts
6. **Calculate payback period** for retention investments
7. **Monitor net MRR growth** as primary growth metric

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
