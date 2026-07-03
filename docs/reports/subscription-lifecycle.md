# Subscription Lifecycle Report

**Report Type:** `subscription-lifecycle`  
**Category:** Subscription States & Transitions  
**Data Source:** Subscriptions

---

## Overview

Track subscription states and movements over time. See counts of new, active, trial, canceled, expired, frozen, grace period, and reactivated subscriptions per period.

**You'll quickly spot:**
- State transitions (new → active → canceled → expired)
- Grace period size (cancellations not yet expired)
- Reactivation opportunities
- Trial-to-active conversion flow

---

## How to Use This Report

### Monitor State Health
1. Track active vs trial vs canceled proportions
2. Watch grace period counts for save opportunities
3. Identify expired subscriptions to measure churn

### Improve Retention
1. Target grace period subscribers with win-back offers
2. Analyze why frozen subscriptions pause
3. Measure reactivation campaign effectiveness

### Optimize Lifecycle Flows
1. Compare trial vs active to assess conversion
2. Track canceled → expired lag for intervention windows
3. Monitor frozen → active transitions

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket

### New Subscriptions
**Type:** Number  
**Description:** Subscriptions created during the period

### Active Subscriptions
**Type:** Number  
**Description:** Active subscriptions at period end

### Trial Subscriptions
**Type:** Number  
**Description:** Subscriptions in trial at period end

### Canceled Subscriptions
**Type:** Number  
**Description:** Subscriptions canceled during the period

### Expired Subscriptions
**Type:** Number  
**Description:** Subscriptions that expired (after cancellation) during the period

### Frozen Subscriptions
**Type:** Number  
**Description:** Frozen subscriptions at period end

### Grace Period
**Type:** Number  
**Description:** Canceled but not yet expired at period end  
**How it's calculated:** COUNT(canceled_at IS NOT NULL AND expires_at > period end)

### Reactivations
**Type:** Number  
**Description:** Subscriptions reactivated during the period (if implemented)

---

## Benchmarks & Targets

### Grace Period Size
- **Healthy:** < 5% of active subscriptions
- **Watch:** 5–10% of active subscriptions
- **Action Needed:** > 10% of active subscriptions

### Reactivation Rate
- **Excellent:** > 20% of grace period saved
- **Good:** 10–20% saved
- **Needs Improvement:** < 10% saved

---

## Actionable Insights

### If Grace Period Grows
**Likely Causes:** Cancellation surge, long grace windows  
**Actions:** Deploy save campaigns, offer discounts, improve cancellation flow with retention offers

### If Expired Subscriptions Spike
**Likely Causes:** Failed payment retries, intentional churn  
**Actions:** Improve dunning, update payment methods proactively, exit surveys

### If Frozen Subscriptions Rise
**Likely Causes:** Seasonal pauses, payment issues, user lifecycle  
**Actions:** Add reactivation reminders, offer flexible plans, track frozen → active conversion

### If Reactivations are Low
**Likely Causes:** Weak win-back campaigns, poor targeting  
**Actions:** Personalize offers, test discount levels, improve email copy and timing

---

## Example

| Period   | New | Active | Trial | Canceled | Expired | Frozen | Grace | Reactivations |
|----------|-----|--------|-------|----------|---------|--------|-------|---------------|
| Jan 2025 | 150 | 1,000  | 80    | 50       | 45      | 20     | 30    | 5             |
| Feb 2025 | 120 | 1,100  | 70    | 20       | 25      | 15     | 20    | 8             |
| Mar 2025 | 130 | 1,200  | 75    | 30       | 28      | 18     | 25    | 6             |

---

## Filters & Views

### Date Range & Granularity
- **Daily:** Monitor short-term campaigns
- **Weekly:** Operational cadence
- **Monthly:** Strategic lifecycle analysis

### Segment by Plan
- Compare state distributions across plans to identify retention patterns

---

## Related Reports

- **Active Subscriptions Over Time:** Growth and net change
- **Trial Conversion:** Trial → active conversion
- **Customer Churn:** Churn root causes

---

## FAQs

**Q: What's the difference between canceled and expired?**  
A: Canceled = user initiated cancellation; Expired = subscription actually ended (after grace period).

**Q: How long is a typical grace period?**  
A: Varies by billing interval—monthly plans often have 30 days, annual plans until end of paid period.

**Q: Should I count frozen as active?**  
A: No—frozen means paused, not actively paying or consuming service.

---

## Best Practices

1. **Target grace period** with tailored save offers
2. **Track state ratios** to detect lifecycle bottlenecks
3. **Measure reactivation ROI** to justify retention spend
4. **Analyze frozen reasons** to improve product flexibility
5. **Monitor expired trends** to forecast churn impact

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
