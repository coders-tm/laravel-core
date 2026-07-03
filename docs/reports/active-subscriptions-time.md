# Active Subscriptions Over Time Report

**Report Type:** `active-subscriptions-time`  
**Category:** Subscription Growth & Retention  
**Data Source:** Subscriptions

---

## Overview

Track subscription base growth over time. See active subscriber counts, new sign-ups, cancellations, net change, and growth rate per period.

**You'll quickly spot:**
- Growth momentum (positive/negative net change)
- Subscription base expansion or contraction
- Cancellation trends and retention health
- Period-over-period growth rates

---

## How to Use This Report

### Monitor Growth Momentum
1. Watch net change (new − canceled) to assess overall health
2. Track growth rate trends to forecast future capacity
3. Compare periods to identify seasonal patterns

### Improve Retention
1. Investigate periods with high cancellations
2. Align retention campaigns to reduce churn
3. Celebrate periods with strong net adds

### Forecast Business Needs
1. Use growth rate to project subscriber counts
2. Plan infrastructure and support capacity
3. Adjust marketing spend based on acquisition efficiency

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket (day, week, month, year)

### Active Subscriptions
**Type:** Number  
**Description:** Count of active subscriptions at period end

### New Subscriptions
**Type:** Number  
**Description:** Subscriptions created during the period

### Canceled Subscriptions
**Type:** Number  
**Description:** Subscriptions canceled during the period

### Net Change
**Type:** Number  
**Description:** Net subscriber growth  
**How it's calculated:** New Subscriptions − Canceled Subscriptions

### Growth Rate
**Type:** Percentage  
**Description:** Period-over-period growth  
**How it's calculated:** ((Active Subscriptions − Previous Active) ÷ Previous Active) × 100

---

## Benchmarks & Targets

### Growth Rate
- **Excellent:** > 10% monthly
- **Good:** 5–10% monthly
- **Average:** 2–5% monthly
- **Needs Attention:** < 2% monthly or negative

### Net Change
- **Healthy:** Positive net adds every period
- **Watch:** Flat or negative net adds

---

## Actionable Insights

### If Growth Rate Declines
**Likely Causes:** Slowing acquisition, rising churn, market saturation  
**Actions:** Increase marketing, improve onboarding, analyze churn reasons, add referral incentives

### If Cancellations Spike
**Likely Causes:** Price changes, product issues, competitive pressure, seasonal trends  
**Actions:** Survey churned users, offer win-back campaigns, improve product-market fit, test pricing

### If Net Change Goes Negative
**Likely Causes:** High churn outpacing acquisition  
**Actions:** Pause price increases, focus on retention, improve customer success, analyze feature usage

---

## Example

| Period   | Active | New | Canceled | Net Change | Growth Rate |
|----------|--------|-----|----------|------------|-------------|
| Jan 2025 | 1,000  | 150 | 50       | +100       | 11.1%       |
| Feb 2025 | 1,100  | 120 | 20       | +100       | 10.0%       |
| Mar 2025 | 1,200  | 130 | 30       | +100       | 9.1%        |

---

## Filters & Views

### Date Range & Granularity
- **Daily:** Monitor short-term trends and campaigns
- **Weekly:** Operational review
- **Monthly:** Strategic planning and forecasting

---

## Related Reports

- **Subscription Lifecycle:** Stage-based transitions
- **Trial Conversion:** New subscriber sources
- **Customer Churn:** Churn root causes

---

## FAQs

**Q: What's a healthy growth rate?**  
A: 5–10% monthly is strong for most SaaS businesses; adjust based on stage and market.

**Q: Should I worry about seasonal dips?**  
A: Track year-over-year to separate seasonal trends from structural issues.

**Q: How do I calculate compound growth?**  
A: Use (Ending Active ÷ Starting Active) ^ (1 / Periods) − 1 for CAGR.

---

## Best Practices

1. **Track trends** over time, not just snapshots
2. **Segment by plan** to identify growth drivers
3. **Forecast conservatively** using trailing growth rates
4. **Communicate wins** to align teams on momentum
5. **Act on churn** before it impacts net change

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
