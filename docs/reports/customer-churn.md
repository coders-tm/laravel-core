# Customer Churn Report

**Report Type:** `customer-churn`  
**Category:** Retention & Customer Health  
**Data Source:** Subscriptions + Users

---

## Overview

Track the percentage of customers who cancel or fail to renew subscriptions over time. Churn is the opposite of retention—monitoring it helps identify problems and prioritize retention strategies.

**You'll quickly spot:**
- Churn rate trends (improving or worsening)
- Churn spikes (seasonal or event-driven)
- At-risk periods
- Impact of retention initiatives

---

## How to Use This Report

### Monitor Retention Health
1. Track monthly churn rate to identify trends
2. Set churn rate targets based on industry benchmarks
3. Alert teams when churn exceeds thresholds

### Identify Root Causes
1. Correlate churn spikes with product changes or seasonality
2. Segment churn by plan, cohort, or acquisition channel
3. Analyze churned customers for common patterns

### Improve Retention
1. Target high-churn segments with retention campaigns
2. Measure impact of onboarding improvements on churn
3. Test retention offers (discounts, features, support)

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket

### Starting Customers
**Type:** Number  
**Description:** Active subscription count at period start

### Churned Customers
**Type:** Number  
**Description:** Subscriptions canceled during the period  
**How it's calculated:** Count of subscriptions that moved from active to canceled/expired

### Ending Customers
**Type:** Number  
**Description:** Active subscription count at period end

### Churn Rate
**Type:** Percentage  
**Description:** Percentage of customers lost  
**How it's calculated:** (Churned Customers ÷ Starting Customers) × 100

---

## Benchmarks & Targets

### Churn Rate (SaaS)
- **Excellent:** < 3% monthly churn (< 30% annual)
- **Good:** 3–5% monthly churn (30–45% annual)
- **Fair:** 5–7% monthly churn (45–60% annual)
- **High:** > 7% monthly churn (> 60% annual—unsustainable)

### Industry Variations
- **Low-Touch SaaS:** 5–7% monthly churn
- **Mid-Market SaaS:** 3–5% monthly churn
- **Enterprise SaaS:** 1–2% monthly churn

---

## Actionable Insights

### If Churn is Rising
**Likely Causes:** Product issues, poor onboarding, price increases, competitive pressure, seasonal factors  
**Actions:** Survey churned customers, improve onboarding, offer retention incentives, fix product gaps

### If Churn is Stable but High
**Likely Causes:** Wrong target market, product-market fit issues, lack of value delivery  
**Actions:** Refine ICP (Ideal Customer Profile), improve product value, enhance customer success

### If Churn is Low
**Likely Causes:** Strong product-market fit, effective onboarding, high switching costs  
**Actions:** Maintain retention programs, invest in growth, share retention playbook across teams

---

## Example

| Period   | Starting Customers | Churned Customers | Ending Customers | Churn Rate |
|----------|--------------------|--------------------|------------------|------------|
| Jan 2025 | 1,000              | 50                 | 950              | 5.0%       |
| Feb 2025 | 950                | 40                 | 910              | 4.2%       |
| Mar 2025 | 910                | 35                 | 875              | 3.8%       |

---

## Filters & Views

### Date Range & Granularity
- **Monthly:** Standard for churn tracking
- **Quarterly:** Strategic planning

### Segment by Plan
- Compare churn rates across subscription tiers

### Cohort Analysis
- Track churn by signup month to identify early vs late churn

---

## Related Reports

- **CLV:** Understand revenue impact of churn
- **Subscription Lifecycle:** See where churn occurs in lifecycle
- **Renewal Forecast:** Predict future churn

---

## FAQs

**Q: What's the difference between churn rate and retention rate?**  
A: Retention Rate = 100% - Churn Rate (e.g., 95% retention = 5% churn)

**Q: Should voluntary and involuntary churn be tracked separately?**  
A: Yes—voluntary churn (customer cancels) vs involuntary churn (payment failures) require different solutions.

**Q: How do I calculate annual churn from monthly churn?**  
A: Annual Churn ≈ 1 - (1 - Monthly Churn)^12 (compound formula)

**Q: What's an acceptable churn rate?**  
A: Varies by business model and price point—lower prices typically have higher churn.

---

## Best Practices

1. **Track churn monthly** to catch trends early
2. **Segment by cohort** to identify onboarding issues
3. **Survey churned customers** to understand root causes
4. **Set churn reduction goals** and measure progress
5. **Invest in retention** when churn > 5% monthly
6. **Monitor leading indicators** (engagement drops, support tickets) before churn occurs

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
