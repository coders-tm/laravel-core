# Freeze Usage Report

**Report Type:** `freeze-usage`  
**Category:** Subscription Analytics  
**Data Source:** Subscriptions + Freeze History

---

## Overview

Track subscription pause/freeze behavior. See freeze rates, duration, and release patterns to understand churn risk and improve retention strategies.

**You'll quickly spot:**
- Freeze rate trends (% of active subs freezing)
- Average freeze duration
- Currently frozen subscriptions
- Freeze release patterns

---

## How to Use This Report

### Churn Risk Assessment
1. Monitor freeze rate (target < 5% monthly)
2. Identify long-duration freezes (> 30 days = high churn risk)
3. Track freeze-to-cancel conversion rate

### Retention Optimization
1. Trigger re-engagement campaigns for frozen users
2. Offer incentives to release freezes
3. Improve value messaging to reduce freeze requests

### Policy Evaluation
1. Assess freeze policy effectiveness
2. Compare freeze rates before/after policy changes
3. Optimize freeze duration limits

---

## Column Definitions

### Period
**Type:** Text  
**Description:** Date period (day, week, month)

### Total Freezes
**Type:** Number  
**Description:** New freeze requests in the period  
**How it's calculated:** Count of subscriptions with frozen_at date in period

### Currently Frozen
**Type:** Number  
**Description:** Subscriptions in frozen state at period end  
**How it's calculated:** Count of subscriptions with status = "frozen" at period end

### Average Freeze Duration
**Type:** Number (Days)  
**Description:** Average length of freeze periods  
**How it's calculated:** Average of (release_at - frozen_at) for freezes released in period

### Freezes Released
**Type:** Number  
**Description:** Freezes that ended during the period  
**How it's calculated:** Count of subscriptions with release_at date in period

### Freeze Rate
**Type:** Percentage  
**Description:** Percentage of active subscriptions that froze  
**How it's calculated:** (Total Freezes ÷ Total Active Subscriptions at period start) × 100

---

## Benchmarks & Targets

### Freeze Rate
- **Low Risk:** < 3% monthly freeze rate
- **Moderate Risk:** 3-5% monthly freeze rate
- **High Risk:** 5-10% monthly freeze rate
- **Critical:** > 10% monthly freeze rate (investigate retention issues)

### Average Freeze Duration
- **Short-term:** < 14 days (likely temporary issue)
- **Medium-term:** 14-30 days (neutral)
- **Long-term:** 30-60 days (moderate churn risk)
- **High Churn Risk:** > 60 days (very likely to cancel)

### Release Rate
- **Healthy:** > 70% of freezes released within 30 days
- **Moderate:** 50-70% released within 30 days
- **Poor:** < 50% released within 30 days (high conversion to churn)

---

## Actionable Insights

### If Freeze Rate > 5%
**Likely Causes:** Value perception issues, seasonal factors, competitive threats  
**Actions:** Survey freezing users, improve engagement, test retention offers

### If Average Freeze Duration > 45 Days
**Likely Causes:** Users not returning, poor re-engagement, unclear release process  
**Actions:** Trigger re-engagement emails, simplify release process, offer return incentives

### If Currently Frozen Growing
**Likely Causes:** More freezes than releases, low release rate, retention problem  
**Actions:** Launch win-back campaigns, improve value delivery, contact frozen users

### If Freezes Released Declining
**Likely Causes:** Users forgetting about subscription, poor communication, canceling instead  
**Actions:** Send freeze reminders, add auto-release dates, improve re-activation messaging

---

## Example

| Period   | Total Freezes | Currently Frozen | Avg Freeze Duration | Freezes Released | Freeze Rate |
|----------|---------------|------------------|---------------------|------------------|-------------|
| Jan 2025 | 45            | 120              | 18 days             | 30               | 3.0%        |
| Feb 2025 | 62            | 145              | 22 days             | 37               | 4.1%        |
| Mar 2025 | 78            | 180              | 28 days             | 43               | 5.2%        |

**Insight:** Freeze rate increasing from 3% to 5.2%, and average duration growing from 18 to 28 days—retention issue developing. Launch re-engagement campaign.

---

## Filters & Views

### Date Range & Granularity
- **Weekly:** Early detection of freeze spikes
- **Monthly:** Standard freeze tracking
- **Quarterly:** Strategic retention planning

### Filter by Plan
- Compare freeze rates across plans
- Identify plans with high freeze activity

### Filter by Freeze Duration
- Short freezes (< 14 days)
- Medium freezes (14-30 days)
- Long freezes (> 30 days)

---

## Related Reports

- **Customer Churn:** Overall retention context
- **Subscription Lifecycle:** State transitions including freeze
- **Plan Comparison:** Plan-specific freeze patterns

---

## FAQs

**Q: What's a normal freeze rate?**  
A: < 5% monthly is normal. Above 5% indicates retention issues that need attention.

**Q: Should I allow unlimited freeze duration?**  
A: No—set a maximum (e.g., 60-90 days) to prevent indefinite freezes and encourage decisions.

**Q: How do I reduce freeze rates?**  
A: Improve value delivery, add engagement features, offer flexible plans, communicate benefits regularly.

**Q: Do freezes typically convert to cancellations?**  
A: Freezes > 60 days have high conversion to churn. Short freezes (< 30 days) often resolve successfully.

**Q: Should I charge during freeze periods?**  
A: No—freezes are typically unpaid pauses. Consider offering limited access during freezes instead of full freeze.

---

## Best Practices

1. **Track monthly** to catch freeze rate increases early
2. **Set freeze rate target** of < 5% monthly
3. **Limit freeze duration** to 60-90 days maximum
4. **Send re-engagement emails** at 7, 14, and 30 days
5. **Offer incentives** for releasing freezes (discount, bonus features)
6. **Auto-release freezes** after maximum duration with notification
7. **Survey freezing users** to understand reasons and improve retention

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
