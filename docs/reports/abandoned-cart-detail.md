# Abandoned Cart Detail Report

**Report Type:** `abandoned-cart-detail`  
**Category:** Checkout Analytics  
**Data Source:** `checkouts` table

---

## Overview

The Abandoned Cart Detail report provides comprehensive insights into checkout sessions that were started but not completed. This report helps you understand where customers are dropping off in your checkout process, track recovery efforts, and quantify potential lost revenue.

**Key insights:**
- Total number of abandoned checkouts and their monetary value
- Average cart value of abandoned sessions
- Recovery email performance and conversion rates
- Drop-off analysis by checkout stage (contact, billing, payment)
- Time-to-abandonment patterns

---

## What You'll Learn

### Revenue Impact
- **Lost Revenue Potential**: See the total value of all abandoned carts to understand the opportunity cost
- **Average Cart Value**: Identify whether high-value or low-value carts are being abandoned
- **Recovery Performance**: Track how many abandoned carts are being recovered through email campaigns

### Customer Behavior
- **Checkout Friction Points**: Identify which stage of checkout causes the most drop-offs
- **Time Patterns**: Understand how long customers browse before abandoning
- **Recovery Response**: Measure the effectiveness of your cart recovery emails

### Optimization Opportunities
- **Form Improvements**: If many abandon at contact stage, simplify your forms
- **Pricing Transparency**: High billing-stage abandonment may indicate pricing concerns
- **Payment Issues**: Payment stage drop-offs suggest payment method or security concerns

---

## How to Use This Report

### Identify Checkout Bottlenecks
1. Compare the "Abandoned at Contact", "Abandoned at Billing", and "Abandoned at Payment" columns
2. The stage with the highest count indicates where you're losing the most customers
3. Focus optimization efforts on the problematic stage

**Example:** If 60% of abandonments happen at the billing stage, consider:
- Simplifying billing address forms
- Offering guest checkout
- Clarifying shipping costs earlier in the flow

### Optimize Recovery Campaigns
1. Review the "Recovery Rate" column to measure email effectiveness
2. Compare "Recovery Emails Sent" vs "Recovered After Email"
3. Test different email timing, messaging, and incentives

**Example:** If recovery rate is below 10%, try:
- Sending emails sooner (within 1 hour vs 24 hours)
- Offering a small discount code
- Improving email copy with urgency and clear CTAs

### Analyze Time Patterns
1. Look at "Avg Time to Abandon" to understand browsing behavior
2. Very short times (< 5 minutes) may indicate pricing shock
3. Longer times (> 30 minutes) suggest indecision or comparison shopping

**Example:** If average time is 2 hours, consider:
- Retargeting ads for browsers who left
- Live chat support for hesitant buyers
- Limited-time offers to create urgency

### Monitor Trends Over Time
1. Use the Period column to track improvements over time
2. Compare week-over-week or month-over-month changes
3. Correlate changes with checkout updates or campaigns

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The time period for the data (daily, weekly, monthly, or yearly depending on granularity setting)  
**Example Values:** "Jan 2025", "Week of Dec 9, 2025", "2025-12-14"

### Total Abandoned
**Type:** Number  
**Description:** The total number of checkout sessions that were started but not completed during this period  
**How it's calculated:** Counts all checkouts with "abandoned" status  
**Use Case:** Track the volume of lost sales opportunities

### Abandoned Value
**Type:** Currency  
**Description:** The total monetary value of all abandoned carts in this period  
**How it's calculated:** Adds up the total value of all abandoned carts  
**Use Case:** Quantify potential lost revenue; prioritize recovery efforts

### Avg Cart Value
**Type:** Currency  
**Description:** The average value of abandoned checkout sessions  
**How it's calculated:** Total abandoned value divided by number of abandoned carts  
**Use Case:** Determine if high-value or low-value carts are being abandoned more frequently

### Recovery Email Sent
**Type:** Number  
**Description:** The number of abandoned carts that had a recovery email sent  
**How it's calculated:** Counts abandoned carts where a recovery email was sent  
**Use Case:** Track recovery campaign reach; ensure automated emails are functioning

### Recovered After Email
**Type:** Number  
**Description:** The number of checkouts that were completed after a recovery email was sent  
**How it's calculated:** Counts completed checkouts that received a recovery email  
**Use Case:** Measure direct impact of recovery email campaigns

### Recovery Rate
**Type:** Percentage  
**Description:** The percentage of recovery emails that resulted in completed purchases  
**How it's calculated:** (Recovered After Email ÷ Recovery Email Sent) × 100  
**Use Case:** Evaluate recovery campaign effectiveness; benchmark against industry standards (typically 8-15%)

### Abandoned at Contact
**Type:** Number  
**Description:** Checkouts abandoned after providing email but before entering billing information  
**How it's calculated:** Counts carts with email address but no billing details  
**Use Case:** Identify if contact information collection is causing friction

### Abandoned at Billing
**Type:** Number  
**Description:** Checkouts abandoned after providing billing information  
**How it's calculated:** Counts carts with billing address entered  
**Use Case:** Identify if billing form complexity or shipping costs are deterrents

### Abandoned at Payment
**Type:** Number  
**Description:** Checkouts abandoned at the payment stage (approximation based on billing data)  
**How it's calculated:** Same as "Abandoned at Billing" (proxy metric)  
**Use Case:** Identify payment method issues or security concerns

**Note:** This metric currently mirrors "Abandoned at Billing" as a proxy. In future updates, it will track abandonments specifically after payment method selection.

### Avg Time to Abandon
**Type:** Number (hours, decimal)  
**Description:** The average time elapsed from checkout start to abandonment  
**How it's calculated:** Average time between when customer started checkout and when they abandoned it  
**Use Case:** Understand customer browsing patterns; optimize timing of recovery emails

**Interpretation:**
- **< 5 minutes**: Immediate bounce, possibly due to pricing or trust issues
- **5-30 minutes**: Normal browsing and consideration time
- **30-60 minutes**: Extended comparison shopping or indecision
- **> 60 minutes**: Multi-session abandonment or interruptions

---

## Report Calculations & Formulas

### Recovery Rate Formula
```
Recovery Rate (%) = (Recovered After Email / Recovery Email Sent) × 100
```

**Example:**
- Recovery emails sent: 100
- Recovered after email: 12
- Recovery rate: (12 / 100) × 100 = 12%

### Average Time to Abandon Formula
```
Avg Time to Abandon = AVG((abandoned_at - started_at) in days) × 24 hours
```

**Example:**
- Cart 1: Abandoned 0.125 days (3 hours) after start
- Cart 2: Abandoned 0.5 days (12 hours) after start
- Average: ((0.125 + 0.5) / 2) × 24 = 7.5 hours

### Stage-Based Abandonment Logic
- **Abandoned at Contact**: Customer provided their email address but didn't continue to billing
- **Abandoned at Billing**: Customer entered billing information but didn't complete checkout
- **Abandoned at Payment**: Customer reached the payment stage but didn't complete the purchase (currently tracked via billing stage)

---

## Benchmarks & Industry Standards

### Recovery Rate Benchmarks
- **Excellent:** > 15%
- **Good:** 10-15%
- **Average:** 5-10%
- **Needs Improvement:** < 5%

### Time to Abandon Benchmarks
- **Immediate bounce:** < 1 minute (indicates pricing shock or wrong audience)
- **Quick browse:** 1-10 minutes (normal evaluation)
- **Extended session:** 10-60 minutes (comparison shopping)
- **Multi-session:** > 60 minutes (requires re-engagement strategy)

### Cart Abandonment Rate (Industry Average)
- **Retail average:** 69.8%
- **Mobile commerce:** 85.7%
- **Desktop commerce:** 73.1%

---

## Actionable Insights

### If Total Abandoned is High
**Potential Causes:**
- Complex or lengthy checkout process
- Unexpected shipping costs
- Limited payment options
- Trust or security concerns
- Forced account creation

**Actions to Take:**
1. Enable guest checkout
2. Display shipping costs early
3. Add trust badges and security seals
4. Streamline form fields (remove optional fields)
5. Offer multiple payment methods

### If Recovery Rate is Low
**Potential Causes:**
- Poor email timing (too soon or too late)
- Weak email copy or unclear CTA
- No incentive to complete purchase
- Email going to spam

**Actions to Take:**
1. A/B test email timing (1 hour vs 24 hours)
2. Offer a small discount (5-10%) in recovery email
3. Improve subject lines (e.g., "Your cart is waiting!")
4. Include product images in email
5. Check email deliverability and spam scores

### If Abandoned at Contact is High
**Potential Causes:**
- Too many required fields
- Unclear why email is needed
- Privacy concerns
- Form validation errors

**Actions to Take:**
1. Reduce required fields to email only
2. Add privacy reassurance ("We'll never spam you")
3. Improve form UX (auto-complete, inline validation)
4. Offer social login options

### If Abandoned at Billing is High
**Potential Causes:**
- Shipping costs surprise
- Complex billing form
- International shipping limitations
- Payment method not available

**Actions to Take:**
1. Show estimated shipping earlier
2. Offer free shipping threshold
3. Auto-fill billing from shipping address
4. Add more payment options
5. Clarify international shipping policies

### If Avg Time to Abandon is Very Low (< 5 min)
**Potential Causes:**
- Pricing too high
- Wrong target audience
- Technical issues or errors
- Confusing checkout flow

**Actions to Take:**
1. Review pricing competitiveness
2. Improve product page clarity
3. Test checkout flow for errors
4. Add live chat for immediate support

---

## Summary Metrics

The report summary at the top provides quick totals across the entire selected date range:

- **Total Abandoned**: Total count of all abandoned checkouts
- **Abandoned Value**: Total value of all abandoned carts (formatted as currency)
- **Recovery Sent**: Total recovery emails sent

Use these summary metrics to:
- Quickly assess overall abandonment impact
- Compare against previous periods
- Set recovery campaign targets

---

## Filters & Customization

### Date Range
Select your reporting period:
- **Last 7 days**: Daily trends
- **Last 30 days**: Weekly trends
- **Last 3 months**: Monthly trends
- **Custom range**: Any specific period

### Granularity
Choose how data is grouped:
- **Daily**: Day-by-day breakdown
- **Weekly**: Week-over-week trends
- **Monthly**: Month-over-month comparison
- **Yearly**: Year-over-year analysis

---

## Related Reports

- **Checkout Conversion Report**: See completed vs abandoned checkout rates
- **Sales Summary Report**: Compare recovered revenue to total sales
- **Customer Acquisition Report**: Track new vs returning customer abandonment patterns

---

## Technical Notes

### Data Source
- Analyzes checkout sessions that were started but not completed
- Tracks recovery email performance
- Groups data by your selected time period (daily, weekly, monthly, yearly)

### How Recovery is Tracked
- Recovery emails are tracked when sent to abandoned cart customers
- Recovery success is measured when a customer completes their purchase after receiving an email
- Attribution is based on the most recent recovery email sent

### Current Limitations
- "Abandoned at Payment" currently mirrors "Abandoned at Billing" as we refine payment stage tracking
- Recovery attribution uses a single-email model (most recent email gets credit)
- Time to abandon is only calculated when the abandonment timestamp is recorded

---

## Frequently Asked Questions

**Q: Why is my recovery rate 0% even though I'm sending emails?**  
A: Check that `recovery_email_sent_at` is being set correctly when emails are sent. Also, ensure enough time has passed for customers to respond (usually 24-48 hours).

**Q: What's a good recovery rate to aim for?**  
A: Industry average is 8-15%. Above 15% is excellent. Below 5% indicates room for improvement in email timing, messaging, or incentives.

**Q: Can I see individual abandoned carts?**  
A: This report shows aggregated data. Use the checkout detail view or export feature to see individual cart details.

**Q: Why do some periods show 0 abandoned carts?**  
A: This is normal if your store had no abandoned checkouts during that period. Check your date range and granularity settings.

**Q: How is this different from the overall cart abandonment rate?**  
A: This report shows absolute numbers and stage-specific drop-offs. For an overall abandonment percentage, use the Checkout Conversion Report.

---

## Best Practices

1. **Monitor Weekly**: Check this report weekly to catch sudden changes in abandonment patterns
2. **Set Baselines**: Establish your normal abandonment metrics to identify anomalies
3. **Test Changes**: Use this report to measure impact of checkout improvements
4. **Segment Analysis**: Export data and segment by product type, customer type, or traffic source
5. **Recovery Timing**: Test sending recovery emails at different intervals (1hr, 3hr, 24hr, 48hr)
6. **Mobile Optimization**: Compare mobile vs desktop abandonment if you track device type

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
