# MRR Movement Report

**Report Type:** `mrr-movement`  
**Category:** Revenue Analytics  
**Data Source:** Subscriptions

---

## Overview

The MRR (Monthly Recurring Revenue) Movement report provides a detailed breakdown of how your subscription revenue changes over time. This report is essential for understanding the health and growth of your subscription business by showing exactly where revenue is gained and lost.

**Key insights:**
- Starting and ending MRR for each period
- New revenue from new subscriptions
- Revenue lost from cancellations (churn)
- Revenue gained from upgrades (expansion)
- Revenue lost from downgrades (contraction)
- Net MRR growth rate

---

## What You'll Learn

### Revenue Growth Drivers
- **New MRR**: Revenue from customers starting new subscriptions
- **Expansion MRR**: Revenue from existing customers upgrading plans
- **Net Growth**: Overall MRR increase or decrease

### Revenue Loss Factors
- **Churned MRR**: Revenue lost from cancelled subscriptions
- **Contraction MRR**: Revenue lost from customers downgrading plans
- **Growth Rate**: Percentage change in MRR period-over-period

### Business Health Indicators
- **MRR Stability**: How much revenue is recurring vs new
- **Customer Value Trends**: Whether customers are upgrading or downgrading
- **Churn Impact**: How cancellations affect overall revenue

---

## How to Use This Report

### Track MRR Growth
1. Monitor the MRR Growth Rate column for overall health
2. Look for consistent positive growth (aim for 5-10% monthly)
3. Identify periods of decline and investigate causes

**Example:** If MRR growth rate drops from 8% to 2%, check if churn increased or new sign-ups decreased.

### Identify Revenue Leaks
1. Compare Churned MRR to New MRR
2. If churned exceeds new, you're losing ground
3. High churn requires immediate retention focus

**Example:** If New MRR is $5,000 but Churned MRR is $6,000, your net MRR is declining by $1,000 despite new customers.

### Optimize Expansion Revenue
1. Track Expansion MRR to measure upsell success
2. Low expansion suggests limited upgrade paths
3. High expansion indicates strong value delivery

**Example:** If Expansion MRR is consistently $0, consider adding premium tiers or usage-based pricing.

### Monitor Contraction
1. High Contraction MRR indicates customers finding less value
2. May signal pricing misalignment or competitive pressure
3. Should be addressed with value demonstration

**Example:** If Contraction MRR jumps from $500 to $2,000, survey downgrading customers to understand why.

### Calculate Quick Health Ratios
1. **New MRR : Churned MRR** - Should be > 1 (gaining more than losing)
2. **Expansion : Contraction** - Positive expansion indicates satisfied customers
3. **Net MRR Change** - Should be consistently positive for healthy growth

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The time period for the data (daily, weekly, monthly, or yearly depending on granularity setting)  
**Example Values:** "Jan 2025", "Week of Dec 9, 2025", "2025-12-14"

### Starting MRR
**Type:** Currency  
**Description:** Monthly recurring revenue at the beginning of this period
**How it's calculated:** MRR from all active subscriptions at the start of the period, normalized to monthly value
**Use Case:** Baseline for measuring growth; shows revenue before period's changes

**Note:** All subscription plans are normalized to monthly equivalents regardless of billing interval.

### New MRR
**Type:** Currency  
**Description:** New monthly recurring revenue from subscriptions created during this period
**How it's calculated:** Sum of monthly-normalized MRR from all new subscriptions in the period
**Use Case:** Track acquisition success; measure new customer revenue

**Example:** 10 new subscriptions at $50/month = $500 New MRR

### Expansion MRR
**Type:** Currency  
**Description:** Additional monthly recurring revenue from existing customers upgrading their plans
**How it's calculated:** Increase in MRR when customers upgrade to higher-priced plans
**Use Case:** Measure upsell effectiveness; track customer value growth

**Current Implementation:** Simplified to $0 (expansion tracking enhancement planned)

### Churned MRR
**Type:** Currency  
**Description:** Monthly recurring revenue lost from subscriptions cancelled during this period
**How it's calculated:** Sum of monthly-normalized MRR from all subscriptions cancelled in the period
**Use Case:** Quantify revenue loss from churn; prioritize retention efforts

**Example:** 5 customers cancelled at $50/month each = $250 Churned MRR

### Contraction MRR
**Type:** Currency  
**Description:** Monthly recurring revenue lost from existing customers downgrading their plans
**How it's calculated:** Decrease in MRR when customers move to lower-priced plans
**Use Case:** Identify customers finding less value; prevent further churn

**Current Implementation:** Simplified to $0 (contraction tracking enhancement planned)

### Net MRR Change
**Type:** Currency  
**Description:** Total change in MRR during this period (positive or negative)
**How it's calculated:** New MRR + Expansion MRR − Churned MRR − Contraction MRR
**Use Case:** Overall growth metric; should be positive for healthy business

**Example:** $5,000 new + $1,000 expansion − $2,000 churned − $500 contraction = $3,500 net change

### Ending MRR
**Type:** Currency  
**Description:** Monthly recurring revenue at the end of this period
**How it's calculated:** Starting MRR + Net MRR Change
**Use Case:** Current revenue baseline; becomes next period's starting MRR

### MRR Growth Rate
**Type:** Percentage  
**Description:** Percentage change in MRR during this period
**How it's calculated:** ((Ending MRR − Starting MRR) ÷ Starting MRR) × 100
**Use Case:** Normalize growth across different MRR levels; benchmark against targets

**Example:** Started at $10,000, ended at $10,500: ($500 / $10,000) × 100 = 5% growth

---

## Report Calculations & Formulas

### Net MRR Change Formula
```
Net MRR Change = New MRR + Expansion MRR − Churned MRR − Contraction MRR
```

### Ending MRR Formula
```
Ending MRR = Starting MRR + Net MRR Change
```

### MRR Growth Rate Formula
```
MRR Growth Rate (%) = ((Ending MRR − Starting MRR) ÷ Starting MRR) × 100
```

### MRR Normalization
All subscription plans are converted to monthly equivalents:
- **Daily:** Price × 30 days
- **Weekly:** Price × 4.345 weeks
- **Monthly:** Price × 1
- **Yearly:** Price ÷ 12 months
- **Custom:** Price × interval / months in interval

---

## Benchmarks & Industry Standards

### MRR Growth Rate Benchmarks
- **Hyper-Growth:** > 15% monthly
- **Healthy Growth:** 5-15% monthly
- **Steady State:** 2-5% monthly
- **Declining:** < 2% monthly or negative

### New-to-Churned MRR Ratio
- **Excellent:** > 3:1 (gaining 3× what you lose)
- **Good:** 2:1 to 3:1
- **Sustainable:** 1.5:1 to 2:1
- **Warning:** 1:1 (break even)
- **Critical:** < 1:1 (losing ground)

### Expansion MRR as % of Starting MRR
- **Best-in-Class:** > 4% monthly
- **Strong:** 2-4% monthly
- **Average:** 1-2% monthly
- **Needs Improvement:** < 1% monthly

### Quick Ratio (New + Expansion) ÷ (Churned + Contraction)
- **Excellent:** > 4 (growing 4× faster than shrinking)
- **Good:** 2-4
- **Acceptable:** 1-2
- **Warning:** < 1 (shrinking faster than growing)

---

## Actionable Insights

### If MRR Growth Rate is Declining
**Potential Causes:**
- Increased churn rate
- Decreased new sign-ups
- Slower acquisition velocity
- Market saturation

**Actions to Take:**
1. Analyze churn reasons (survey exit customers)
2. Review pricing and value proposition
3. Increase marketing spend or improve conversion
4. Add new product features to reduce churn
5. Implement win-back campaigns

### If New MRR < Churned MRR
**Potential Causes:**
- High churn outpacing acquisition
- Onboarding issues causing early cancellations
- Product-market fit problems
- Competitive pressure

**Actions to Take:**
1. **Immediate:** Focus on retention before acquisition
2. Improve onboarding experience
3. Add customer success touchpoints
4. Survey churning customers to understand issues
5. Consider pricing adjustments or plan changes

### If Expansion MRR is Low or Zero
**Potential Causes:**
- No clear upgrade path
- Single-tier pricing
- Customers not discovering premium features
- Value ceiling reached

**Actions to Take:**
1. Create tiered pricing with clear upgrade benefits
2. Implement usage-based pricing elements
3. Add premium features to higher tiers
4. Proactively suggest upgrades to power users
5. Launch feature adoption campaigns

### If Contraction MRR is High
**Potential Causes:**
- Customers finding less value over time
- Pricing too high for value received
- Competitors offering better prices
- Economic downturn affecting budgets

**Actions to Take:**
1. Interview downgrading customers
2. Review feature usage before downgrades
3. Add value at current tier before they downgrade
4. Consider annual contract incentives
5. Create "value realization" email campaigns

### If MRR is Flat (Low Growth)
**Potential Causes:**
- New MRR ≈ Churned MRR (break-even)
- Limited expansion opportunities
- Market maturity
- Seasonal factors

**Actions to Take:**
1. Calculate customer acquisition cost (CAC)
2. Improve customer lifetime value (LTV)
3. Launch referral programs
4. Expand to new markets or segments
5. Add new product lines or features

---

## Summary Metrics

The report summary provides the current MRR:

- **Current MRR**: Your total monthly recurring revenue as of today (formatted as currency)

Use this metric to:
- Quickly see your current revenue baseline
- Track progress toward revenue goals
- Calculate annual recurring revenue (ARR = MRR × 12)
- Forecast future revenue based on growth trends

---

## Filters & Customization

### Date Range
Select your reporting period:
- **Last 3 months**: Monthly trends (recommended for MRR)
- **Last 6 months**: Quarterly patterns
- **Last 12 months**: Year-over-year comparison
- **Custom range**: Any specific period for analysis

### Granularity
Choose how data is grouped:
- **Monthly**: Standard for MRR tracking (recommended)
- **Weekly**: Detailed trend analysis for fast-growing businesses
- **Quarterly**: High-level strategic view
- **Yearly**: Long-term growth trends

**Note:** MRR is typically tracked monthly. Weekly granularity is useful for rapidly scaling businesses.

---

## Related Reports

- **MRR by Plan Report**: See which plans contribute most to MRR
- **MRR Churn Report**: Deep dive into churn patterns and rates
- **Active Subscriptions Over Time**: Track subscription count trends
- **Trial Conversion Report**: Measure new subscription quality

---

## Understanding MRR Movement Waterfall

Think of MRR Movement as a waterfall chart:

```
Starting MRR: $10,000
  + New MRR: +$3,000
  + Expansion: +$500
  - Churned: -$1,500
  - Contraction: -$200
  = Net Change: +$1,800
Ending MRR: $11,800
```

Each component shows where your revenue is coming from and going to.

---

## Frequently Asked Questions

**Q: Why is MRR different from actual monthly revenue?**  
A: MRR normalizes all subscriptions to monthly values, even if they're billed annually or weekly. It shows predictable recurring revenue.

**Q: What's a healthy MRR growth rate?**  
A: 5-10% monthly is healthy for established SaaS. Early-stage companies often see 15%+ as they scale from a smaller base.

**Q: Should I focus on new MRR or reducing churned MRR?**  
A: Both matter, but reducing churn is typically more cost-effective than acquisition. Aim for churned MRR < 50% of new MRR.

**Q: Why is Expansion MRR showing $0?**  
A: The current implementation simplifies expansion tracking. Future enhancements will track plan upgrades explicitly.

**Q: How is MRR calculated for annual plans?**  
A: Annual plan price ÷ 12 = Monthly MRR. A $1,200/year plan contributes $100/month to MRR.

**Q: What if I have usage-based pricing?**  
A: This report tracks base subscription MRR. Usage overage revenue would be tracked separately in order reports.

---

## Best Practices

1. **Track Monthly**: MRR is best monitored with monthly granularity
2. **Set Growth Targets**: Aim for 5-10% monthly growth in early stages
3. **Monitor Churn**: Keep churned MRR below 50% of new MRR
4. **Focus on Expansion**: Build upgrade paths to increase expansion MRR
5. **Quick Ratio**: Calculate (New + Expansion) ÷ (Churned + Contraction) monthly
6. **Trend Analysis**: Compare to same month last year for seasonal businesses
7. **Cohort Correlation**: Cross-reference with customer cohort retention data

---

## Pro Tips

### Maximize New MRR
- Optimize trial-to-paid conversion
- Improve onboarding to reduce early churn
- Launch targeted acquisition campaigns
- Add annual billing options with discounts
- Create referral incentives

### Reduce Churned MRR
- Identify at-risk customers before they cancel
- Implement automated win-back campaigns
- Add pause/downgrade options before cancellation
- Survey all cancellations to understand reasons
- Improve feature adoption and engagement

### Increase Expansion MRR
- Create clear feature-based plan tiers
- Proactively suggest upgrades to power users
- Use in-app messaging for upgrade prompts
- Offer temporary premium access trials
- Track feature usage to identify upgrade candidates

### Minimize Contraction MRR
- Demonstrate value consistently via email
- Provide quarterly business reviews for enterprise
- Add features to mid-tier plans before customers downgrade
- Offer discounts before accepting downgrades
- Use exit surveys to understand contraction reasons

---

## Advanced Analysis

### Calculate Quick Ratio
```
Quick Ratio = (New MRR + Expansion MRR) ÷ (Churned MRR + Contraction MRR)
```

A ratio above 4 indicates very healthy growth. Below 1 means you're shrinking.

### Project Future MRR
```
Next Month MRR = Current MRR × (1 + (MRR Growth Rate ÷ 100))
```

Use average growth rate from past 3-6 months for forecasting.

### Calculate MRR Churn Rate
```
MRR Churn Rate = (Churned MRR ÷ Starting MRR) × 100
```

Target: < 5% monthly for healthy SaaS businesses.

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
