# Plan Revenue Breakdown Report

**Report Type:** `plan-revenue-breakdown`  
**Category:** Product & Financial Analysis  
**Data Source:** Plans + Orders + Subscriptions

---

## Overview

Analyze profitability by plan with gross revenue, discounts, refunds, and net revenue. See which plans drive the most profit and optimize your pricing and promotion strategy.

**You'll quickly spot:**
- Most profitable plans (by net revenue)
- Discount impact by plan
- Refund rates by plan
- Revenue per subscriber efficiency

---

## How to Use This Report

### Profitability Analysis
1. Compare net revenue across plans to identify winners
2. Identify plans with high discount erosion
3. Calculate true profit contribution per plan

### Pricing Optimization
1. Track average revenue per subscriber by plan
2. Adjust pricing on low-revenue plans
3. Reduce discounts on high-performing plans

### Promotion Strategy
1. Promote high net revenue, low discount plans
2. Retire or revise plans with negative profitability
3. Balance portfolio for sustainable growth

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

### Gross Revenue
**Type:** Currency  
**Description:** Total revenue before discounts and refunds  
**How it's calculated:** Sum of all paid order totals linked to this plan's subscriptions

### Discounts Applied
**Type:** Currency  
**Description:** Total discount amounts on orders  
**How it's calculated:** Sum of discount_total on all paid orders for this plan

### Refunds
**Type:** Currency  
**Description:** Total refunded amounts  
**How it's calculated:** Sum of refunded order totals for this plan

### Net Revenue
**Type:** Currency  
**Description:** Actual revenue after discounts and refunds  
**How it's calculated:** Gross Revenue - Discounts Applied - Refunds

### Average Revenue per Subscriber
**Type:** Currency  
**Description:** Net revenue divided by subscriber count  
**How it's calculated:** Net Revenue ÷ Active Subscription Count

### Growth Rate
**Type:** Percentage  
**Description:** Revenue growth compared to previous period  
**How it's calculated:** ((Current Period Net Revenue - Previous Period Net Revenue) ÷ Previous Period Net Revenue) × 100

---

## Benchmarks & Targets

### Net Revenue Margin
- **Excellent:** Net Revenue > 85% of Gross Revenue
- **Good:** Net Revenue 75-85% of Gross Revenue
- **Fair:** Net Revenue 65-75% of Gross Revenue
- **Poor:** Net Revenue < 65% of Gross Revenue (high discount/refund erosion)

### Refund Rate
- **Excellent:** < 2% of Gross Revenue
- **Good:** 2-5% of Gross Revenue
- **High:** 5-10% of Gross Revenue
- **Critical:** > 10% of Gross Revenue (quality or targeting issues)

### Discount Rate
- **Healthy:** 10-20% of Gross Revenue
- **Moderate:** 20-30% of Gross Revenue
- **High Risk:** > 30% of Gross Revenue

---

## Actionable Insights

### If Net Revenue < 70% of Gross Revenue
**Likely Causes:** Excessive discounting, high refund rates, poor plan-market fit  
**Actions:** Reduce discount amounts, investigate refund reasons, improve value delivery

### If Refunds > 5% of Gross Revenue
**Likely Causes:** Quality issues, wrong customer targeting, poor onboarding  
**Actions:** Survey refunded customers, improve trial experience, tighten qualification

### If Average Revenue per Subscriber Declining
**Likely Causes:** Increased discounting, customer downgrading, pricing pressure  
**Actions:** Test price increases, reduce promotional discounts, add premium features

### If Growth Rate Negative
**Likely Causes:** Churn increasing, new signups declining, price cuts  
**Actions:** Improve retention, boost acquisition, defend pricing

---

## Example

| Plan Name    | Price   | Gross Revenue | Discounts | Refunds | Net Revenue | Avg Rev/Sub | Growth Rate |
|--------------|---------|---------------|-----------|---------|-------------|-------------|-------------|
| Starter      | $19.00  | $50,000       | $7,500    | $1,500  | $41,000     | $82.00      | +5.2%       |
| Professional | $49.00  | $150,000      | $22,500   | $3,000  | $124,500    | $155.63     | +12.3%      |
| Enterprise   | $199.00 | $80,000       | $8,000    | $800    | $71,200     | $949.33     | +8.7%       |

**Insight:** Professional plan has highest growth and good net revenue margin (83%). Starter has 85% discount/refund erosion—reduce discounts.

---

## Filters & Views

### Date Range & Granularity
- **Monthly:** Standard for profitability tracking
- **Quarterly:** Strategic planning
- **Yearly:** Annual performance review

### Filter by Plan Type
- Subscription vs one-time
- Monthly vs annual billing

### Filter by Status
- Active plans only
- Include retired plans for historical analysis

---

## Related Reports

- **Plan Comparison:** Plan performance with churn rates
- **Discount Impact:** Overall discount strategy
- **MRR by Plan:** Revenue contribution trends

---

## FAQs

**Q: Why is net revenue different from MRR?**  
A: MRR is recurring revenue only; net revenue includes all orders (one-time and recurring) minus discounts and refunds.

**Q: What's a healthy net revenue margin?**  
A: > 80% of gross revenue. Below 70% indicates excessive discounting or refund issues.

**Q: Should I retire plans with negative growth?**  
A: Check profitability first—a plan with declining growth but high net revenue margin may still be valuable.

**Q: How do I improve average revenue per subscriber?**  
A: Reduce discounts, upsell premium features, or increase base pricing.

---

## Best Practices

1. **Track monthly** to catch profitability erosion early
2. **Set net revenue margin targets** of > 80% for each plan
3. **Monitor refund rates** and investigate spikes immediately
4. **Reduce plan-specific discounts** that erode profitability
5. **Compare average revenue per subscriber** across plans to identify underperformers
6. **Use net revenue, not gross revenue** for profitability decisions
7. **Test pricing increases** on plans with high net margins and low churn

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
