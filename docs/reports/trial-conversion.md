# Trial Conversion Report

**Report Type:** `trial-conversion`  
**Category:** Subscription Analytics  
**Data Source:** Subscriptions

---

## Overview

The Trial Conversion report shows how well your trials turn into paying customers. It tracks trials started, expired (didn’t convert), and converted (became paid), along with your overall conversion rate and the average trial duration.

**Key insights:**
- Trial-to-paid conversion rate
- How many trials started vs expired in each period
- Average trial duration and behavior patterns
- Impact of onboarding and trial length on conversion

---

## What You’ll Learn

### Conversion Performance
- **Trials Started**: Volume entering the funnel
- **Trials Converted**: How many became paying customers
- **Conversion Rate**: Effectiveness of onboarding and product value

### Trial Experience
- **Trials Expired**: Trials that ended without converting
- **Avg Trial Duration**: Typical time between trial start and end

### Optimization Opportunities
- Short vs long trial performance
- Onboarding email effectiveness
- Product activation and feature discovery

---

## How to Use This Report

### Improve Trial-to-Paid Conversion
1. Track conversion rate over time (aim for consistent improvement)
2. Compare trials started vs converted each period
3. Identify dips and correlate with onboarding changes

**Example:** If conversion rate falls from 40% to 25% after changing onboarding emails, revert the change or A/B test new messaging.

### Reduce Trial Expiration
1. Monitor trials expired as a % of trials started
2. Investigate periods with high expiry
3. Add nudges before trial end (emails, in-app prompts)

**Example:** If expiry spikes near weekends, schedule reminder emails on Fridays and Mondays.

### Optimize Trial Length
1. Compare conversion rates and avg duration across periods
2. If longer trials don’t improve conversions, test shorter trials
3. If short trials underperform, add guided onboarding to accelerate value discovery

**Example:** A 14-day trial converts at 35% and a 30-day trial at 37%. If added cost is high, keep 14 days and enhance onboarding.

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The time period for the data (daily, weekly, monthly, or yearly depending on granularity setting)

### Trials Started
**Type:** Number  
**Description:** Number of trials that began during the period  
**How it’s calculated:** Counts subscriptions that started a trial in the period  
**Use Case:** Measure top-of-funnel trial volume

### Trials Expired
**Type:** Number  
**Description:** Trials that ended without converting to paid during the period  
**How it’s calculated:** Counts trials where cancellation occurred before the trial end  
**Use Case:** Identify friction points causing non-conversion

### Trials Converted
**Type:** Number  
**Description:** Trials that became paid subscriptions  
**How it’s calculated:** Counts trials that ended and remained active (not cancelled at/ before trial end)  
**Use Case:** Measure onboarding and product value effectiveness

### Conversion Rate
**Type:** Percentage  
**Description:** Percentage of trials that converted to paid  
**How it’s calculated:** (Trials Converted ÷ Trials Started) × 100  
**Use Case:** Primary KPI for trial effectiveness

### Avg Trial Duration
**Type:** Number (days)  
**Description:** Average number of days from trial start to trial end  
**How it’s calculated:** Average of (trial end date − trial start date) in days  
**Use Case:** Understand how long customers typically take to evaluate

---

## Report Calculations & Formulas

### Conversion Rate
```
Conversion Rate (%) = (Trials Converted ÷ Trials Started) × 100
```

### Average Trial Duration
```
Avg Trial Duration (days) = Average of (trial end − trial start)
```

### Trial Expiration Logic
- **Expired Trial**: Trial ended and subscription was cancelled at or before trial end
- **Converted Trial**: Trial ended and subscription remained active beyond trial end

---

## Benchmarks & Industry Standards

### Trial Conversion Rate
- **Excellent:** > 50%
- **Good:** 30–50%
- **Average:** 15–30%
- **Needs Improvement:** < 15%

### Avg Trial Duration
- **Short trial (7–14 days):** Works best with strong onboarding
- **Standard trial (14–21 days):** Balanced evaluation time
- **Long trial (30+ days):** Consider if product requires deep setup/value discovery

---

## Actionable Insights

### If Conversion Rate is Low
**Potential Causes:**
- Weak onboarding and product activation
- Value not discovered early enough
- Trial length misaligned with evaluation needs

**Actions to Take:**
1. Add guided onboarding and checklists
2. Trigger emails when key features are unused
3. Add in-app prompts for feature discovery
4. Test trial lengths: 14 vs 21 vs 30 days
5. Offer live demos or personal onboarding for high-value leads

### If Trials Expired are High
**Potential Causes:**
- Customers don’t see value before trial ends
- No reminders or CTAs near trial end
- Complex setup blocks activation

**Actions to Take:**
1. Send reminders 3 days and 1 day before trial end
2. Offer limited-time discount at trial end
3. Add quick-start templates
4. Provide chat or human onboarding help
5. Surface ROI/value metrics inside the app

### If Avg Trial Duration is Very Short
**Potential Causes:**
- Wrong audience or poor product-market fit
- Pricing sticker shock
- Setup or UX issues cause early exits

**Actions to Take:**
1. Improve targeting and messaging
2. Clarify pricing and value upfront
3. Reduce setup friction and improve UX
4. Offer quick wins within first session

---

## Summary Metrics

The summary shows:
- **Total Trials Started** across the selected range
- **Total Trials Converted**
- **Overall Conversion Rate** for the period

Use it to quickly assess performance and set targets.

---

## Filters & Customization

### Date Range
- **Last 30 days**: Monitor weekly changes
- **Last 3 months**: Monthly trends and seasonality
- **Custom range**: Any specific period

### Granularity
- **Daily**: Fine-grained analysis for experiments
- **Weekly**: Trend monitoring
- **Monthly**: Strategic planning (recommended)

---

## Related Reports

- **MRR Movement**: See revenue impact of conversions
- **Active Subscriptions Over Time**: Track subscription count growth
- **Subscription Lifecycle**: Understand trial → active → cancelled flows

---

## Frequently Asked Questions

**Q: What’s a healthy trial conversion rate?**  
A: Aim for 30–50% depending on product complexity. Higher-touch products may need longer trials and more onboarding.

**Q: Should I use a 14-day or 30-day trial?**  
A: If value is discoverable quickly with good onboarding, 14 days is sufficient. If setup is complex or value takes longer, consider 21–30 days.

**Q: How do I increase conversions without changing trial length?**  
A: Focus on onboarding, nudges, and surfacing value. Use email triggers, in-app tours, and checklists.

**Q: Why do conversions vary by period?**  
A: Seasonality, product updates, onboarding changes, and marketing targeting all impact conversion.

---

## Best Practices

1. **Nudge cadence:** Day 1, Day 3, Day 7, and 3 days before trial end
2. **Activation checklist:** Guide users to first value in < 10 minutes
3. **In-app tours:** Highlight key features based on persona
4. **Email triggers:** Send tips when usage is low
5. **Value surfacing:** Show ROI metrics prominently
6. **Short feedback loop:** Ask for feedback at trial end
7. **Offer help:** Provide chat/live demo for high-value leads

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
