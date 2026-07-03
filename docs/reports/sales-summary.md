# Sales Summary Report

**Report Type:** `sales-summary`  
**Category:** Order Analytics  
**Data Source:** Orders

---

## Overview

The Sales Summary report provides a comprehensive overview of your sales performance, tracking key financial metrics including gross merchandise value (GMV), net revenue, discounts, taxes, shipping, and refunds. This report is essential for understanding your overall business health and revenue trends.

**Key insights:**
- Total revenue and net revenue after discounts and refunds
- Order volume and average order value (AOV)
- Impact of discounts and refunds on revenue
- Order completion and cancellation rates
- Revenue breakdown by component (tax, shipping, etc.)

---

## What You'll Learn

### Revenue Performance
- **Gross Merchandise Value (GMV)**: Total value of all orders before any deductions
- **Net Revenue**: Your actual revenue after accounting for discounts and refunds
- **Revenue Composition**: Breakdown of taxes, shipping, and discounts

### Order Metrics
- **Order Volume**: Track how many orders you're receiving over time
- **Average Order Value**: Understand customer spending patterns
- **Completion Rate**: Monitor how many orders are successfully completed

### Business Health Indicators
- **Discount Impact**: See how promotions affect your bottom line
- **Refund Rate**: Track customer satisfaction and product quality issues
- **Cancellation Rate**: Identify potential fulfillment or inventory problems

---

## How to Use This Report

### Track Revenue Trends
1. Monitor the GMV and Net Revenue columns over time
2. Look for seasonal patterns or growth trends
3. Compare period-over-period changes

**Example:** If December shows 40% higher GMV than November, that's expected holiday seasonality. If it's lower, investigate marketing or inventory issues.

### Optimize Pricing & Discounts
1. Compare Discount Total to GMV to calculate discount percentage
2. Track Net Revenue to see actual impact of promotions
3. Calculate ROI of discount campaigns

**Example:** If your discount total is 20% of GMV but order volume only increased 10%, consider reducing discount depth or improving targeting.

### Monitor Order Quality
1. Compare Completed Orders vs Total Orders for completion rate
2. Track Cancelled Orders to identify fulfillment issues
3. Monitor Refund Total for quality problems

**Example:** If cancellation rate jumps from 2% to 8%, check for inventory shortages or shipping delays.

### Analyze AOV Trends
1. Track Average Order Value over time
2. Declining AOV may indicate customers buying cheaper items
3. Increasing AOV suggests successful upselling or premium product adoption

**Example:** If AOV drops from $150 to $100, consider bundling strategies, minimum order thresholds for free shipping, or upsell prompts.

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The time period for the data (daily, weekly, monthly, or yearly depending on granularity setting)  
**Example Values:** "Jan 2025", "Week of Dec 9, 2025", "2025-12-14"

### Total Orders
**Type:** Number  
**Description:** The total number of orders placed during this period (all statuses)  
**How it's calculated:** Counts all orders created in the period  
**Use Case:** Track order volume trends and growth

### Gross Merchandise Value (GMV)
**Type:** Currency  
**Description:** Total value of all orders before any deductions (discounts, refunds, etc.)  
**How it's calculated:** Sum of all order grand totals  
**Use Case:** Headline revenue metric; shows total business activity

### Net Revenue
**Type:** Currency  
**Description:** Actual revenue after subtracting discounts and refunds  
**How it's calculated:** GMV − Refund Total − Discount Total  
**Use Case:** True revenue metric for financial reporting and profitability analysis

### Discount Total
**Type:** Currency  
**Description:** Total value of all discounts applied to orders  
**How it's calculated:** Sum of all discount amounts from orders  
**Use Case:** Track promotional costs; measure discount effectiveness

### Tax Total
**Type:** Currency  
**Description:** Total tax collected on orders  
**How it's calculated:** Sum of all tax amounts from orders  
**Use Case:** Tax reporting and compliance; revenue breakdown

### Shipping Total
**Type:** Currency  
**Description:** Total shipping charges collected  
**How it's calculated:** Sum of all shipping fees from orders  
**Use Case:** Track shipping revenue; analyze shipping strategy effectiveness

### Refund Total
**Type:** Currency  
**Description:** Total value of refunds issued  
**How it's calculated:** Sum of all refund amounts  
**Use Case:** Monitor product quality issues; track customer satisfaction

### Paid Total
**Type:** Currency  
**Description:** Total amount actually paid by customers (completed payments)  
**How it's calculated:** Sum of paid amounts from completed payment transactions  
**Use Case:** Cash flow tracking; accounts receivable reconciliation

### Average Order Value (AOV)
**Type:** Currency  
**Description:** Average value per order  
**How it's calculated:** GMV ÷ Total Orders  
**Use Case:** Track customer spending patterns; measure upsell success

### Completed Orders
**Type:** Number  
**Description:** Number of orders with completed payment status  
**How it's calculated:** Counts orders with payment status "completed"  
**Use Case:** Track successful transactions; calculate completion rate

### Cancelled Orders
**Type:** Number  
**Description:** Number of orders that were cancelled  
**How it's calculated:** Counts orders with status "cancelled" or "canceled"  
**Use Case:** Identify fulfillment or inventory issues; track operational efficiency

---

## Report Calculations & Formulas

### Net Revenue Formula
```
Net Revenue = GMV − Refund Total − Discount Total
```

**Example:**
- GMV: $10,000
- Discount Total: $1,500
- Refund Total: $500
- Net Revenue: $10,000 − $1,500 − $500 = $8,000

### Average Order Value (AOV) Formula
```
AOV = GMV ÷ Total Orders
```

**Example:**
- GMV: $10,000
- Total Orders: 50
- AOV: $10,000 ÷ 50 = $200

### Discount Rate Formula (not shown, but useful)
```
Discount Rate (%) = (Discount Total ÷ GMV) × 100
```

### Completion Rate Formula (not shown, but useful)
```
Completion Rate (%) = (Completed Orders ÷ Total Orders) × 100
```

### Cancellation Rate Formula (not shown, but useful)
```
Cancellation Rate (%) = (Cancelled Orders ÷ Total Orders) × 100
```

---

## Benchmarks & Industry Standards

### Average Order Value (AOV) Benchmarks by Industry
- **Fashion/Apparel:** $50-$100
- **Electronics:** $150-$300
- **Home & Garden:** $75-$150
- **Beauty/Cosmetics:** $30-$75
- **Luxury Goods:** $200-$500

### Completion Rate Benchmarks
- **Excellent:** > 95%
- **Good:** 90-95%
- **Average:** 85-90%
- **Needs Improvement:** < 85%

### Cancellation Rate Benchmarks
- **Excellent:** < 2%
- **Good:** 2-5%
- **Average:** 5-10%
- **High:** > 10% (investigate inventory or fulfillment issues)

### Discount Rate Benchmarks
- **Conservative:** 5-10%
- **Moderate:** 10-20%
- **Aggressive:** 20-30%
- **Unsustainable:** > 30% (may hurt profitability)

### Refund Rate Benchmarks
- **Excellent:** < 5%
- **Good:** 5-10%
- **Average:** 10-15%
- **High:** > 15% (investigate product quality or description accuracy)

---

## Actionable Insights

### If GMV is Declining
**Potential Causes:**
- Decreased traffic or conversion rate
- Seasonal slowdown
- Increased competition
- Product availability issues

**Actions to Take:**
1. Review marketing spend and campaigns
2. Analyze traffic sources and conversion funnels
3. Check inventory levels and product availability
4. Conduct competitive pricing analysis
5. Launch promotional campaigns

### If Net Revenue is Much Lower Than GMV
**Potential Causes:**
- High discount rate eroding margins
- Excessive refunds due to quality issues
- Aggressive promotional strategy

**Actions to Take:**
1. Calculate actual discount rate (Discount Total ÷ GMV)
2. If > 25%, reduce discount depth or improve targeting
3. Analyze refund reasons and address quality issues
4. Consider tiered discounts instead of blanket sales
5. Test minimum purchase thresholds for discounts

### If AOV is Declining
**Potential Causes:**
- Customers buying cheaper items
- Reduced bundling or upsell effectiveness
- Shift to lower-priced product categories
- Economic factors affecting spending

**Actions to Take:**
1. Implement product bundling strategies
2. Add "Frequently Bought Together" recommendations
3. Offer free shipping threshold above current AOV
4. Create upsell prompts at checkout
5. Introduce tiered pricing or volume discounts

### If Completion Rate is Low
**Potential Causes:**
- Payment processing issues
- Shipping costs too high
- Complex checkout process
- Payment method limitations

**Actions to Take:**
1. Review payment gateway performance
2. Offer more payment methods
3. Simplify checkout flow
4. Consider free shipping offers
5. Add abandoned cart recovery emails

### If Cancellation Rate is High
**Potential Causes:**
- Inventory shortages
- Shipping delays
- Payment failures
- Order accuracy issues

**Actions to Take:**
1. Review inventory management processes
2. Improve order fulfillment speed
3. Add automated order status updates
4. Verify payment processing reliability
5. Implement quality control checks

### If Refund Rate is High
**Potential Causes:**
- Product quality issues
- Inaccurate product descriptions
- Size/fit problems
- Shipping damage

**Actions to Take:**
1. Analyze refund reasons by category
2. Improve product photos and descriptions
3. Add size guides and measurement tools
4. Enhance packaging for shipping
5. Consider restocking fees for policy abuse

---

## Summary Metrics

The report summary at the top provides quick totals across the entire selected date range:

- **Total Orders**: Total count of all orders
- **GMV**: Total gross merchandise value (formatted as currency)
- **Net Revenue**: Total net revenue after discounts and refunds (formatted as currency)
- **Discount Total**: Total discounts given (formatted as currency)
- **Refund Total**: Total refunds issued (formatted as currency)
- **AOV**: Average order value across all orders (formatted as currency)

Use these summary metrics to:
- Quickly assess overall performance
- Compare against targets and goals
- Track progress toward revenue objectives
- Calculate key financial ratios

---

## Filters & Customization

### Date Range
Select your reporting period:
- **Last 7 days**: Daily trends and immediate performance
- **Last 30 days**: Weekly trends and monthly comparison
- **Last 3 months**: Monthly trends and quarterly performance
- **Custom range**: Any specific period for analysis

### Granularity
Choose how data is grouped:
- **Daily**: Day-by-day breakdown for detailed analysis
- **Weekly**: Week-over-week trends
- **Monthly**: Month-over-month comparison
- **Yearly**: Year-over-year analysis

### Order Filters (Optional)
- **Payment Status**: Filter by paid, unpaid, pending, failed, or refunded
- **Fulfillment Status**: Filter by pending, processing, shipped, delivered, or cancelled
- **Order Status**: Filter by pending, processing, completed, or cancelled

---

## Related Reports

- **Payment Performance Report**: Detailed payment gateway analysis
- **Refund Analysis Report**: In-depth refund patterns and reasons
- **Tax Summary Report**: Detailed tax reporting by jurisdiction
- **Abandoned Cart Detail Report**: Recover lost sales opportunities

---

## Frequently Asked Questions

**Q: What's the difference between GMV and Net Revenue?**  
A: GMV is the total value before any deductions (what customers added to cart). Net Revenue is what you actually keep after discounts and refunds (your true income).

**Q: Why doesn't Paid Total equal GMV?**  
A: Paid Total only counts completed payments, while GMV includes all orders (even pending or failed ones). Also, discounts reduce the paid amount.

**Q: What's a good AOV to target?**  
A: This varies by industry and product type. Track your baseline AOV, then aim for 10-20% growth through upselling, bundling, and free shipping thresholds.

**Q: Should I be worried if my discount rate is 25%?**  
A: It depends on your margins. If your gross margin is 50%, you still have 25% contribution margin. But if margins are 30%, you're only keeping 5% - that's unsustainable.

**Q: How often should I check this report?**  
A: Check daily for operational monitoring, weekly for trend analysis, and monthly for strategic planning and goal tracking.

**Q: Can I see which specific products are being refunded?**  
A: This report shows aggregate totals. Use the Refund Analysis Report or export order data for product-level refund analysis.

---

## Best Practices

1. **Daily Monitoring**: Check GMV and order count daily to catch issues quickly
2. **Weekly Deep Dive**: Analyze AOV, completion rate, and discount impact weekly
3. **Monthly Planning**: Use monthly data for budgeting and forecasting
4. **Set Baselines**: Establish your normal metrics to identify anomalies
5. **Track Ratios**: Calculate discount rate, refund rate, and completion rate regularly
6. **Compare Periods**: Always compare to previous period and same period last year
7. **Segment Analysis**: Export data to analyze by product category, customer type, or traffic source

---

## Pro Tips

### Maximize AOV
- Set free shipping threshold 20% above current AOV
- Create product bundles with 10-15% discount
- Show "customers also bought" during checkout
- Offer quantity discounts on applicable products

### Optimize Discount Strategy
- Test discount depth: 10% vs 15% vs 20%
- Use minimum purchase thresholds
- Segment discounts by customer type (new vs returning)
- Time-limit offers to create urgency

### Reduce Refunds
- Improve product photography (multiple angles, zoom)
- Add detailed size charts and measurements
- Include customer reviews with photos
- Offer chat support for pre-purchase questions

### Improve Completion Rate
- Simplify checkout to 3 steps or fewer
- Enable guest checkout
- Show trust badges and security seals
- Display all costs upfront (no surprises)

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
