# Refund Analysis Report

**Report Type:** `refund-analysis`  
**Category:** Order Quality & Customer Satisfaction  
**Data Source:** Orders with refunds

---

## Overview

Analyze refund patterns over time. See total refunds, full vs partial, refund amounts, refund rates, and average refund sizes to identify product, billing, or policy issues.

**You'll quickly spot:**
- Refund rate trends (% of orders refunded)
- Full vs partial refund split
- Average refund amount per refunded order
- Periods with spikes in refunds

---

## How to Use This Report

### Reduce Refund Rates
1. Investigate full refunds for product fit or expectation mismatches
2. Analyze partial refunds for pricing or billing errors
3. Track refund rate trends to measure improvement

### Improve Product Quality
1. Correlate refunds with specific products or campaigns
2. Use refund feedback to fix quality issues
3. Adjust product descriptions to set accurate expectations

### Optimize Policies
1. Monitor refund amount to assess policy generosity
2. Test stricter refund windows or restocking fees
3. Balance customer satisfaction with profitability

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket

### Total Refunds
**Type:** Number  
**Description:** Count of orders with any refund

### Full Refunds
**Type:** Number  
**Description:** Orders where refund ≥ order total  
**How it's calculated:** COUNT(refund_total ≥ grand_total)

### Partial Refunds
**Type:** Number  
**Description:** Orders with refunds less than order total  
**How it's calculated:** COUNT(0 < refund_total < grand_total)

### Refund Amount
**Type:** Currency  
**Description:** Total refunded amount  
**How it's calculated:** Sum of refund_total

### Refund Rate
**Type:** Percentage  
**Description:** Share of orders with refunds  
**How it's calculated:** (Orders with Refunds ÷ Total Orders) × 100

### Avg Refund Amount
**Type:** Currency  
**Description:** Average refund per refunded order  
**How it's calculated:** Refund Amount ÷ Orders with Refunds

### Orders with Refunds
**Type:** Number  
**Description:** Count of orders that had refunds

### Total Orders
**Type:** Number  
**Description:** All orders in the period

---

## Benchmarks & Targets

### Refund Rate
- **Excellent:** < 2%
- **Good:** 2–5%
- **Average:** 5–8%
- **Needs Attention:** > 8%

### Full vs Partial Mix
- **Healthy:** < 30% full refunds
- **Watch:** 30–50% full refunds
- **Action Needed:** > 50% full refunds (suggests product fit issues)

---

## Actionable Insights

### If Refund Rate Spikes
**Likely Causes:** Product defects, misleading descriptions, pricing errors, campaign issues  
**Actions:** Review recent product launches, improve descriptions, add product videos, tighten quality control

### If Full Refunds are High
**Likely Causes:** Product doesn't match expectations, sizing issues, poor quality  
**Actions:** Add detailed sizing guides, improve product photography, enhance customer reviews

### If Partial Refunds are High
**Likely Causes:** Billing errors, shipping damage, missing items  
**Actions:** Audit order fulfillment, improve packaging, train support on partial resolution

### If Avg Refund Amount Grows
**Likely Causes:** Refunding high-value orders, generous policies  
**Actions:** Review high-value order quality, test stricter refund terms, offer exchanges instead

---

## Example

| Period   | Total Refunds | Full | Partial | Refund Amount | Refund Rate | Avg Refund | Total Orders |
|----------|---------------|------|---------|---------------|-------------|------------|--------------|
| Jan 2025 | 45            | 30   | 15      | $4,500        | 4.5%        | $100.00    | 1,000        |
| Feb 2025 | 38            | 25   | 13      | $3,800        | 3.8%        | $100.00    | 1,000        |
| Mar 2025 | 52            | 40   | 12      | $5,200        | 5.2%        | $100.00    | 1,000        |

---

## Filters & Views

### Date Range & Granularity
- **Daily:** Monitor post-launch or campaign spikes
- **Weekly:** Operational review
- **Monthly:** Strategic trend analysis

### Segment by Product/Category
- Identify high-refund products for targeted fixes

---

## Related Reports

- **Sales Summary:** Revenue context
- **Payment Performance:** Payment and refund flow
- **Customer Churn:** Refunds as churn indicator

---

## FAQs

**Q: What's a healthy refund rate?**  
A: < 5% is strong for most e-commerce; adjust based on industry and product type.

**Q: Should I reduce refund windows to lower rates?**  
A: Test carefully—stricter policies may hurt conversion and customer trust.

**Q: How do I reduce full refunds?**  
A: Improve product descriptions, add reviews/photos, set accurate expectations.

---

## Best Practices

1. **Track refund reasons** via exit surveys or support tickets
2. **Segment by product** to identify quality issues
3. **Test refund policies** to balance satisfaction and cost
4. **Improve descriptions** to reduce expectation gaps
5. **Monitor trends** over time, not just snapshots

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
