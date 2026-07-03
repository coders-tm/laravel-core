# Average Revenue Per User (ARPU) Report

**Report Type:** `arpu`  
**Category:** Unit Economics & Pricing  
**Data Source:** Orders + Subscriptions

---

## Overview

Track average revenue per active user over time. ARPU is a core unit economics metric that measures how much revenue each active subscriber generates per period.

**You'll quickly spot:**
- ARPU trends (growth or decline)
- Impact of pricing changes
- Revenue efficiency per user
- Plan mix shifts

---

## How to Use This Report

### Monitor Unit Economics
1. Track ARPU trends to measure revenue growth per user
2. Compare to CAC (Customer Acquisition Cost) for profitability
3. Segment by plan to identify high-value cohorts

### Optimize Pricing
1. Test price increases and measure ARPU impact
2. Track ARPU after introducing new plans
3. Identify opportunities for upsells or add-ons

### Forecast Revenue
1. Multiply projected active users by current ARPU for revenue forecasts
2. Use ARPU growth rate to model expansion scenarios

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket

### Total Revenue
**Type:** Currency  
**Description:** Sum of paid order totals for the period

### Active Users
**Type:** Number  
**Description:** Count of distinct users with active subscriptions

### ARPU
**Type:** Currency  
**Description:** Average revenue per active user  
**How it's calculated:** Total Revenue ÷ Active Users

---

## Benchmarks & Targets

### ARPU Growth
- **Excellent:** > 5% monthly growth
- **Good:** 2–5% monthly growth
- **Flat:** 0–2% monthly growth
- **Declining:** Negative growth (investigate immediately)

### ARPU by Industry (SaaS)
- **Low-Touch:** $10–$50/month
- **Mid-Market:** $50–$500/month
- **Enterprise:** $500+/month

---

## Actionable Insights

### If ARPU is Declining
**Likely Causes:** Downgrades, churn of high-value users, discount abuse, plan mix shift to lower tiers  
**Actions:** Reduce discounting, improve onboarding, upsell existing users, add premium features

### If ARPU is Flat
**Likely Causes:** Stable plan mix, no pricing changes, balanced churn/upsells  
**Actions:** Test price increases, introduce add-ons, create premium tiers

### If ARPU is Growing
**Likely Causes:** Successful upsells, pricing increases, plan mix shift upward  
**Actions:** Double down on what's working, expand upsell motions, monitor churn for resistance

---

## Example

| Period   | Total Revenue | Active Users | ARPU    |
|----------|---------------|--------------|---------|
| Jan 2025 | $100,000      | 1,000        | $100.00 |
| Feb 2025 | $110,000      | 1,050        | $104.76 |
| Mar 2025 | $120,000      | 1,100        | $109.09 |

---

## Filters & Views

### Date Range & Granularity
- **Monthly:** Standard for ARPU tracking
- **Quarterly:** Strategic planning

### Segment by Plan
- Compare ARPU across plans to identify high-value segments

---

## Related Reports

- **MRR by Plan:** Plan-level revenue contribution
- **CLV:** Lifetime value context
- **CAC-LTV:** Profitability analysis

---

## FAQs

**Q: What's a healthy ARPU?**  
A: Varies by market—compare to competitors and track growth trends over time.

**Q: Should ARPU include one-time revenue?**  
A: Yes, for total ARPU; segment recurring vs one-time for deeper analysis.

**Q: How does ARPU differ from LTV?**  
A: ARPU is per-period revenue; LTV is lifetime revenue per customer.

---

## Best Practices

1. **Track ARPU monthly** to catch trends early
2. **Segment by cohort** to understand plan mix impact
3. **Correlate with pricing changes** to measure elasticity
4. **Monitor churn** alongside ARPU to avoid optimizing for short-term revenue
5. **Benchmark against industry** standards for context

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
