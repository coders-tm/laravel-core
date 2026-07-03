# Plan Comparison Report

**Report Type:** `plan-comparison`  
**Category:** Product & Pricing Strategy  
**Data Source:** Plans + Subscriptions

---

## Overview

Compare subscription plan performance side-by-side. Track signups, churn, MRR contribution, and revenue share to optimize pricing strategy and feature distribution.

**You'll quickly spot:**
- Most popular plans (by signups)
- Highest revenue plans (by MRR)
- Highest churn plans (retention issues)
- Plan profitability and market fit

---

## How to Use This Report

### Pricing Optimization
1. Identify underperforming plans (low signups or high churn)
2. Test price adjustments on low-MRR plans
3. Promote high-MRR, low-churn plans

### Product Strategy
1. Sunset plans with poor metrics (low signups + high churn)
2. Invest features in high-revenue plans
3. Balance plan portfolio across price points

### Revenue Mix Analysis
1. Track revenue concentration risk (one plan dominance)
2. Diversify plan options to reduce dependency
3. Identify gaps in pricing tiers

---

## Column Definitions

### Plan ID
**Type:** Number  
**Description:** Unique plan identifier

### Plan Name
**Type:** Text  
**Description:** Plan display name

### Price
**Type:** Currency  
**Description:** Plan price per billing cycle

### Interval
**Type:** Text  
**Description:** Billing frequency (day, week, month, year)

### Active Subscriptions
**Type:** Number  
**Description:** Subscriptions on this plan at period end

### Total Signups
**Type:** Number  
**Description:** New subscriptions to this plan during period

### Churn Count
**Type:** Number  
**Description:** Subscriptions canceled during period

### Churn Rate
**Type:** Percentage  
**Description:** Cancellation rate  
**How it's calculated:** Churn Count ÷ (Active Subscriptions + Churn Count) × 100

### Monthly Recurring Revenue (MRR)
**Type:** Currency  
**Description:** Normalized monthly revenue from this plan  
**How it's calculated:** Price (normalized to monthly) × Active Subscriptions

### Revenue Share
**Type:** Percentage  
**Description:** This plan's share of total MRR  
**How it's calculated:** (Plan MRR ÷ Total MRR) × 100

---

## Benchmarks & Targets

### Churn Rate by Plan
- **Excellent:** < 3% monthly churn
- **Good:** 3–5% monthly churn
- **Fair:** 5–7% monthly churn
- **High:** > 7% monthly churn (investigate or sunset)

### Revenue Concentration
- **Healthy Diversification:** No plan > 40% of MRR
- **Moderate Risk:** One plan 40–60% of MRR
- **High Risk:** One plan > 60% of MRR

### Signup Distribution
- **Balanced:** Plans have similar signup rates relative to pricing tier
- **Imbalanced:** One plan dominates signups (may need more options)

---

## Actionable Insights

### If Churn Rate > 7% on a Plan
**Likely Causes:** Poor value perception, wrong target market, features misaligned with price  
**Actions:** Survey churned users, adjust pricing, improve features, or sunset plan

### If MRR Share > 60% on One Plan
**Likely Causes:** Limited plan options, one size dominates market  
**Actions:** Introduce new plans to diversify, reduce dependency on single offering

### If Signups Low but Churn Low
**Likely Causes:** Niche plan with loyal users, high satisfaction but poor visibility  
**Actions:** Promote more aggressively, bundle with other plans, test price increase

### If Signups High but Churn High
**Likely Causes:** Pricing mismatch, onboarding issues, value delivery gap  
**Actions:** Improve onboarding, adjust pricing, add retention triggers

---

## Example

| Plan Name   | Price   | Interval | Active Subs | Signups | Churn Count | Churn Rate | MRR     | Revenue Share |
|-------------|---------|----------|-------------|---------|-------------|------------|---------|---------------|
| Starter     | $19.00  | month    | 500         | 150     | 25          | 4.8%       | $9,500  | 15%           |
| Professional| $49.00  | month    | 800         | 200     | 20          | 2.4%       | $39,200 | 62%           |
| Enterprise  | $199.00 | month    | 75          | 15      | 2           | 2.6%       | $14,925 | 23%           |

---

## Filters & Views

### Date Range & Granularity
- **Monthly:** Standard for plan performance tracking
- **Quarterly:** Strategic planning

### Filter by Interval
- Compare monthly vs annual plans separately

---

## Related Reports

- **MRR by Plan:** Revenue contribution trends over time
- **Trial Conversion:** Plan-specific trial-to-paid rates
- **Customer Churn:** Overall retention context

---

## FAQs

**Q: Why is churn rate different from customer churn?**  
A: This shows plan-specific churn; customers may switch plans rather than leave entirely.

**Q: Should I sunset low-signup plans?**  
A: Check churn rate and profitability first—low signups with low churn may be valuable niche offerings.

**Q: How do I calculate MRR for annual plans?**  
A: Divide annual price by 12 (e.g., $1,200/year = $100 MRR).

**Q: What's a healthy revenue share distribution?**  
A: No single plan > 40% of total MRR reduces risk.

---

## Best Practices

1. **Track monthly** to catch plan performance degradation early
2. **Segment by cohort** to understand plan evolution over time
3. **Test pricing changes** on low-performing plans
4. **Balance portfolio** across price points to reduce concentration risk
5. **Survey churn** by plan to understand specific pain points
6. **Promote high-MRR, low-churn plans** as flagship offerings

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
