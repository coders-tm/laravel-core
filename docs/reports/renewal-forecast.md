# Renewal Forecast Report

**Report Type:** `renewal-forecast`  
**Category:** Revenue Forecasting & Risk Management  
**Data Source:** Subscriptions

---

## Overview

See upcoming subscription renewals, expected revenue, and at-risk subscriptions. Forecast cash flow and target retention efforts where they matter most.

**You'll quickly spot:**
- Renewal volume and revenue by date
- At-risk subscriptions (past due, incomplete payments)
- Expected MRR from renewals
- Opportunities to improve payment readiness

---

## How to Use This Report

### Forecast Revenue
1. Sum expected revenue across date range for cash flow planning
2. Track expected MRR for recurring revenue forecasts
3. Compare to historical renewal rates for accuracy

### Reduce At-Risk Revenue
1. Identify at-risk subscriptions before renewal dates
2. Update payment methods proactively
3. Deploy dunning campaigns early

### Optimize Retention
1. Focus high-value renewals (large expected revenue)
2. Offer discounts or upgrades to at-risk subscribers
3. Monitor renewal success rates post-intervention

---

## Column Definitions

### Renewal Date
**Type:** Date (YYYY-MM-DD)  
**Description:** The date renewals are expected

### Renewals Count
**Type:** Number  
**Description:** Number of subscriptions renewing on the date

### Expected Revenue
**Type:** Currency  
**Description:** Total revenue expected from renewals  
**How it's calculated:** Sum of (price × quantity) for renewals

### Expected MRR
**Type:** Currency  
**Description:** Monthly-normalized expected revenue  
**How it's calculated:**  
- Yearly: price ÷ 12
- Weekly: price × 4
- Daily: price × 30
- Monthly: price

### At-Risk Count
**Type:** Number  
**Description:** Subscriptions with payment issues  
**How it's calculated:** COUNT(status IN ('past_due', 'incomplete'))

### At-Risk Revenue
**Type:** Currency  
**Description:** Revenue at risk from problem subscriptions  
**How it's calculated:** Sum of (price × quantity) for at-risk subscriptions

---

## Benchmarks & Targets

### At-Risk Percentage
- **Excellent:** < 2% of renewals
- **Good:** 2–5% of renewals
- **Watch:** 5–10% of renewals
- **Action Needed:** > 10% of renewals

### Renewal Success Rate (after interventions)
- **Excellent:** > 98%
- **Good:** 95–98%
- **Needs Improvement:** < 95%

---

## Actionable Insights

### If At-Risk Count is High
**Likely Causes:** Expired cards, insufficient funds, payment method issues  
**Actions:** Send payment update reminders, offer payment plan options, enable alternative payment methods

### If Expected Revenue Fluctuates
**Likely Causes:** Seasonal patterns, annual renewals clustering, churn spikes  
**Actions:** Smooth cash flow with quarterly plans, offer prepayment discounts, forecast conservatively

### If Renewal Counts Decline
**Likely Causes:** Churn, downgrades, cancellations outpacing new sign-ups  
**Actions:** Analyze churn reasons, improve retention campaigns, adjust pricing/packaging

---

## Example

| Renewal Date | Renewals | Expected Revenue | Expected MRR | At-Risk Count | At-Risk Revenue |
|--------------|----------|------------------|--------------|---------------|-----------------|
| 2025-12-15   | 120      | $12,000          | $12,000      | 5             | $500            |
| 2025-12-16   | 95       | $9,500           | $9,500       | 3             | $300            |
| 2025-12-17   | 110      | $11,000          | $11,000      | 8             | $800            |

---

## Filters & Views

### Date Range
- **7 days ahead:** Immediate action on at-risk
- **30 days ahead:** Payment readiness campaigns
- **90 days ahead:** Strategic retention planning

### Segment by Plan
- Prioritize high-value or high-churn plans

---

## Related Reports

- **MRR Movement:** Track actual MRR changes post-renewal
- **Subscription Lifecycle:** Grace period and churn context
- **Payment Performance:** Payment success rates

---

## FAQs

**Q: How far ahead should I forecast?**  
A: 30–90 days for operational planning; 12 months for strategic budgeting.

**Q: What's the best way to reduce at-risk renewals?**  
A: Proactive payment method updates, dunning emails, and offering flexible payment options.

**Q: Should I offer discounts to at-risk subscribers?**  
A: Test selectively—discounts can save revenue but may train users to expect them.

---

## Best Practices

1. **Update payment methods early** (14–30 days before renewal)
2. **Segment at-risk by value** to prioritize high-revenue saves
3. **Test dunning timing** to find optimal reminder cadence
4. **Monitor forecasts weekly** to adjust retention efforts
5. **Celebrate successful renewals** to reinforce retention wins

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
