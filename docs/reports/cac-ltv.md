# CAC vs LTV Report

**Report Type:** `cac-ltv`  
**Category:** Unit Economics & Profitability  
**Data Source:** Marketing Spend + Users + Orders + Subscriptions

---

## Overview

Compare Customer Acquisition Cost (CAC) to Lifetime Value (LTV) to measure acquisition profitability. This ratio is the ultimate test of sustainable growth—if you're spending more to acquire customers than they generate, growth isn't profitable.

**You'll quickly spot:**
- Acquisition profitability
- Channel efficiency
- LTV:CAC ratio trends
- Payback period

---

## How to Use This Report

### Validate Growth Strategy
1. Ensure LTV:CAC ratio > 3:1 for sustainable growth
2. Track trends to catch degradation early
3. Compare across acquisition channels

### Optimize Marketing Spend
1. Allocate budget to channels with highest LTV:CAC
2. Reduce spend on channels with LTV:CAC < 3:1
3. Test new channels and measure LTV:CAC impact

### Plan for Profitability
1. Calculate payback period (CAC ÷ Monthly Revenue)
2. Ensure payback < 12 months for healthy cash flow
3. Model LTV:CAC scenarios for pricing or retention changes

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket

### Total Customers Acquired
**Type:** Number  
**Description:** New customers in the period

### Total Marketing Spend
**Type:** Currency  
**Description:** Sum of marketing and sales expenses

### CAC (Customer Acquisition Cost)
**Type:** Currency  
**Description:** Cost to acquire one customer  
**How it's calculated:** Total Marketing Spend ÷ Total Customers Acquired

### Average LTV
**Type:** Currency  
**Description:** Average customer lifetime value  
**How it's calculated:** Average Monthly Revenue × Average Lifespan (typically 24 months for SaaS)

### LTV:CAC Ratio
**Type:** Number  
**Description:** Return on acquisition investment  
**How it's calculated:** Average LTV ÷ CAC

### Payback Period (Months)
**Type:** Number  
**Description:** Months to recover acquisition cost  
**How it's calculated:** CAC ÷ Average Monthly Revenue per Customer

---

## Benchmarks & Targets

### LTV:CAC Ratio
- **Excellent:** > 4:1 (highly profitable growth)
- **Good:** 3:1–4:1 (sustainable growth)
- **Break-Even:** 1:1–3:1 (marginal profitability)
- **Unsustainable:** < 1:1 (losing money on acquisition)

### Payback Period
- **Excellent:** < 6 months
- **Good:** 6–12 months
- **Concerning:** 12–18 months
- **Unsustainable:** > 18 months

---

## Actionable Insights

### If LTV:CAC < 3:1
**Likely Causes:** High CAC (inefficient marketing), low LTV (churn or low ARPU)  
**Actions:**
- **Reduce CAC:** Optimize conversion rates, focus on organic channels, improve targeting
- **Increase LTV:** Reduce churn, upsell existing customers, raise prices

### If Payback Period > 12 Months
**Likely Causes:** High upfront CAC, low monthly revenue  
**Actions:** Improve onboarding for faster engagement, introduce annual plans, upsell earlier in lifecycle

### If LTV:CAC is Declining
**Likely Causes:** Rising CAC (competition), falling LTV (churn increase)  
**Actions:** Audit marketing channels, improve retention, test pricing changes

---

## Example

| Period   | Customers Acquired | Marketing Spend | CAC     | Avg LTV  | LTV:CAC | Payback (Months) |
|----------|--------------------|-----------------|---------|----------|---------|------------------|
| Jan 2025 | 100                | $10,000         | $100.00 | $600.00  | 6:1     | 4.0              |
| Feb 2025 | 120                | $13,200         | $110.00 | $600.00  | 5.5:1   | 4.4              |
| Mar 2025 | 150                | $18,000         | $120.00 | $600.00  | 5:1     | 4.8              |

---

## Filters & Views

### Segment by Acquisition Channel
- Compare LTV:CAC across paid search, social, organic, referrals

### Cohort Analysis
- Track LTV:CAC by signup month to identify trends

---

## Related Reports

- **CLV:** Deep dive into lifetime value
- **ARPU:** Revenue per user context
- **Customer Churn:** Retention impact on LTV

---

## FAQs

**Q: What's a healthy LTV:CAC ratio?**  
A: Target > 3:1 for sustainable growth; > 4:1 is excellent.

**Q: Should CAC include salaries?**  
A: Yes—include all marketing and sales costs (ads, tools, salaries, commissions).

**Q: How do I calculate LTV if customers are new?**  
A: Use cohort analysis from similar customers or industry benchmarks until actual data matures.

**Q: Why does my LTV:CAC improve over time?**  
A: LTV increases as customers stay longer; early cohorts may show low LTV if lifespan is short.

**Q: What if LTV:CAC is too high (> 10:1)?**  
A: Opportunity to invest more in acquisition—you're under-spending on growth.

---

## Best Practices

1. **Track LTV:CAC monthly** to catch trends early
2. **Segment by channel** to optimize marketing spend
3. **Include all costs in CAC** (ads, tools, salaries)
4. **Use actual data for LTV** when available; adjust projections as cohorts mature
5. **Balance LTV:CAC with payback period** for cash flow health
6. **Test pricing changes** and measure LTV:CAC impact
7. **Monitor retention** as the primary LTV lever

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
