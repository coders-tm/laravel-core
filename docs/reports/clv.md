# Customer Lifetime Value (CLV) Report

**Report Type:** `clv`  
**Category:** Customer Analytics & Unit Economics  
**Data Source:** Users + Orders + Subscriptions

---

## Overview

Measure the total revenue each customer generates over their lifetime. CLV is critical for acquisition spending, retention priorities, and long-term profitability.

**You'll quickly spot:**
- Highest-value customers
- CLV trends by cohort
- Revenue concentration
- Acquisition ROI benchmarks

---

## How to Use This Report

### Optimize Acquisition Spending
1. Compare CLV to CAC (Customer Acquisition Cost)
2. Target CLV:CAC ratio > 3:1 for profitability
3. Allocate marketing budget based on segment CLV

### Prioritize Retention
1. Identify high-CLV customers for VIP treatment
2. Focus retention efforts on segments with highest lifetime value
3. Reduce churn in high-CLV cohorts first

### Forecast Revenue
1. Multiply new customer count by average CLV for revenue projections
2. Track CLV trends to predict long-term revenue growth

---

## Column Definitions

### User ID
**Type:** Number  
**Description:** Unique customer identifier

### Email
**Type:** Text  
**Description:** Customer email address

### Total Revenue
**Type:** Currency  
**Description:** Sum of all paid orders from this customer  
**How it's calculated:** Sum of completed order totals for the user

### Months Active
**Type:** Number  
**Description:** Number of months between first and last order  
**How it's calculated:** Time difference from first order to most recent order (or today if subscribed)

### Average Monthly Revenue
**Type:** Currency  
**Description:** Revenue per month over active period  
**How it's calculated:** Total Revenue ÷ Months Active

### Estimated CLV
**Type:** Currency  
**Description:** Projected lifetime value  
**How it's calculated:** Average Monthly Revenue × 24 months (industry standard for subscription businesses)

---

## Benchmarks & Targets

### CLV:CAC Ratio
- **Excellent:** > 4:1
- **Good:** 3:1–4:1
- **Break-Even:** 1:1–3:1
- **Unsustainable:** < 1:1 (spending more to acquire than customer generates)

### CLV by Segment (SaaS)
- **Low-Touch:** $200–$1,200 lifetime
- **Mid-Market:** $1,200–$12,000 lifetime
- **Enterprise:** $12,000+ lifetime

---

## Actionable Insights

### If CLV is Low
**Likely Causes:** High churn, low ARPU, short customer lifespans, discount dependency  
**Actions:** Improve onboarding, increase engagement, upsell existing customers, extend contract lengths

### If CLV:CAC < 3:1
**Likely Causes:** Over-spending on acquisition, targeting wrong segments, poor product-market fit  
**Actions:** Reduce CAC through organic channels, improve conversion rates, focus on higher-value segments

### If CLV is Growing
**Likely Causes:** Successful retention, upsells, longer customer lifespans  
**Actions:** Invest in acquisition to capitalize on unit economics, expand retention programs

---

## Example

| User ID | Email               | Total Revenue | Months Active | Avg Monthly Revenue | Estimated CLV |
|---------|---------------------|---------------|---------------|---------------------|---------------|
| 1234    | john@example.com    | $2,400        | 12            | $200.00             | $4,800        |
| 5678    | jane@example.com    | $6,000        | 24            | $250.00             | $6,000        |
| 9012    | bob@example.com     | $1,200        | 6             | $200.00             | $4,800        |

---

## Filters & Views

### Segment by Plan
- Compare CLV across subscription tiers to identify most valuable plans

### Cohort Analysis
- Group by signup month to track CLV trends over time

### Top Performers
- Sort by Total Revenue or Estimated CLV to identify VIP customers

---

## Related Reports

- **ARPU:** Revenue per user per period
- **Customer Churn:** Retention impact on CLV
- **CAC-LTV:** Acquisition profitability analysis

---

## FAQs

**Q: Why use 24 months for CLV estimation?**  
A: Industry standard for subscription businesses; adjust based on your average customer lifespan.

**Q: Should CLV include one-time purchases?**  
A: Yes, for total CLV; segment recurring vs one-time for deeper analysis.

**Q: How often should CLV be recalculated?**  
A: Monthly for trending; quarterly for strategic planning.

**Q: What's the difference between CLV and LTV?**  
A: Same metric—Customer Lifetime Value and Lifetime Value are interchangeable.

---

## Best Practices

1. **Track CLV by cohort** to identify trends over time
2. **Compare CLV to CAC** to ensure sustainable growth
3. **Segment by acquisition channel** to optimize marketing spend
4. **Monitor CLV alongside churn** to understand retention impact
5. **Update projections regularly** as customer behavior evolves
6. **Use CLV to prioritize** product features and customer support

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
