# Checkout Recovery Report

**Report Type:** `checkout-recovery`  
**Category:** Conversion Optimization  
**Data Source:** Checkouts + Recovery Emails

---

## Overview

Track abandoned cart recovery performance. See recovery rates, revenue recovered, and email effectiveness to optimize your automated recovery campaigns.

**You'll quickly spot:**
- Recovery rate (% of abandoned carts recovered)
- Revenue saved from abandonment
- Best-performing recovery emails
- Timing effectiveness

---

## How to Use This Report

### Recovery Optimization
1. Identify low recovery rates (< 10%)
2. Test email timing (1 hour vs 24 hours)
3. Optimize offer strategy (discount vs urgency)

### Revenue Recovery
1. Track total revenue recovered
2. Calculate ROI of recovery campaigns
3. Prioritize high-value abandoned carts

### Email Performance
1. Compare recovery rates by email sequence
2. Test subject lines and content
3. Optimize send timing

---

## Column Definitions

### Period
**Type:** Text  
**Description:** Date period (day, week, month)

### Abandoned Checkouts
**Type:** Number  
**Description:** Checkouts started but not completed  
**How it's calculated:** Checkouts with status = "abandoned" created in period

### Recovery Emails Sent
**Type:** Number  
**Description:** Automated recovery emails sent

### Recovered Checkouts
**Type:** Number  
**Description:** Abandoned checkouts that later completed  
**How it's calculated:** Checkouts recovered within 7 days of abandonment

### Recovery Rate
**Type:** Percentage  
**Description:** Success rate of recovery efforts  
**How it's calculated:** (Recovered Checkouts ÷ Abandoned Checkouts) × 100

### Revenue Recovered
**Type:** Currency  
**Description:** Total value of recovered checkouts  
**How it's calculated:** Sum of grand_total for recovered checkouts

### Average Recovery Value
**Type:** Currency  
**Description:** Average value per recovered checkout  
**How it's calculated:** Revenue Recovered ÷ Recovered Checkouts

### Recovery Within 1 Hour
**Type:** Number  
**Description:** Checkouts recovered within 1 hour of abandonment

### Recovery Within 24 Hours
**Type:** Number  
**Description:** Checkouts recovered within 24 hours

### Recovery After 24 Hours
**Type:** Number  
**Description:** Checkouts recovered after 24 hours

---

## Benchmarks & Targets

### Recovery Rate
- **Excellent:** > 15% recovery rate
- **Good:** 10–15% recovery rate
- **Fair:** 5–10% recovery rate
- **Poor:** < 5% recovery rate (optimize emails)

### Email Timing
- **1-Hour Email:** 20–30% of recoveries (urgency-focused)
- **24-Hour Email:** 40–50% of recoveries (reminder-focused)
- **48-Hour+ Email:** 20–30% of recoveries (last-chance offers)

### Average Recovery Value
- **High-Value:** > $200 per recovery
- **Medium-Value:** $50–$200 per recovery
- **Low-Value:** < $50 per recovery

---

## Actionable Insights

### If Recovery Rate < 10%
**Likely Causes:** Poor email content, wrong timing, no incentive  
**Actions:** Test discount offers (5–10%), improve subject lines, send within 1 hour

### If Most Recoveries After 24 Hours
**Likely Causes:** Delayed email sequence, weak early emails  
**Actions:** Send first email within 1 hour, add urgency messaging, test time-limited offers

### If Recovery Rate High but Revenue Low
**Likely Causes:** Recovering low-value carts, discount erosion  
**Actions:** Prioritize high-value carts, reduce discount amounts, segment by cart value

### If Revenue Recovered Declining
**Likely Causes:** Email fatigue, discount dependency, poor targeting  
**Actions:** Refresh email content, test non-discount incentives (free shipping), segment audience

---

## Example

| Period   | Abandoned | Emails Sent | Recovered | Recovery Rate | Revenue Recovered | Avg Value | < 1 Hour | < 24 Hours | > 24 Hours |
|----------|-----------|-------------|-----------|---------------|-------------------|-----------|----------|------------|------------|
| Jan 2025 | 500       | 1,200       | 75        | 15.0%         | $18,750           | $250      | 20       | 35         | 20         |
| Feb 2025 | 550       | 1,300       | 66        | 12.0%         | $16,500           | $250      | 15       | 30         | 21         |
| Mar 2025 | 600       | 1,400       | 48        | 8.0%          | $12,000           | $250      | 10       | 20         | 18         |

**Insight:** Recovery rate declining from 15% to 8%—test new email content and earlier send times.

---

## Filters & Views

### Date Range & Granularity
- **Weekly:** Tactical email optimization
- **Monthly:** Standard performance tracking

### Filter by Cart Value
- High-value (> $200)
- Medium-value ($50–$200)
- Low-value (< $50)

### Filter by Recovery Email
- Email 1 (1 hour)
- Email 2 (24 hours)
- Email 3 (48 hours)

---

## Related Reports

- **Abandoned Cart Detail:** Full abandonment analysis
- **Checkout Funnel:** Stage-by-stage conversion
- **Coupon Performance:** Discount offer effectiveness

---

## FAQs

**Q: What's a good recovery rate?**  
A: > 15% is excellent, 10–15% is good. Below 10% needs optimization.

**Q: When should I send the first recovery email?**  
A: Within 1 hour for urgency-driven recoveries. Test 1-hour vs 3-hour timing.

**Q: Should I always offer a discount?**  
A: No—test free shipping, urgency messaging, or no incentive first. Avoid training customers to abandon for discounts.

**Q: How long should I wait before giving up?**  
A: Most recoveries happen within 72 hours. Stop sending after 7 days to avoid spam.

**Q: How do I calculate recovery email ROI?**  
A: (Revenue Recovered - Email Costs - Discount Costs) ÷ Email Costs × 100

---

## Best Practices

1. **Send first email within 1 hour** for maximum urgency impact
2. **Test 3-email sequences**: 1 hour, 24 hours, 48 hours
3. **Segment by cart value**: Different offers for high-value vs low-value carts
4. **A/B test subject lines**: "You left something behind" vs "Complete your order"
5. **Use dynamic content**: Show actual cart items in email
6. **Track incremental revenue**: Only count revenue that wouldn't have happened otherwise
7. **Refresh email content quarterly** to avoid fatigue

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
