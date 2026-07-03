# New Signups Report

**Report Type:** `new-signups`  
**Category:** Acquisition & Growth  
**Data Source:** Users + Subscriptions + Plans

---

## Overview

Track new user registrations and subscription signups over time. This report measures acquisition activity, trial adoption, and MRR contribution from new customers.

**You'll quickly spot:**
- Acquisition trends (growth or decline)
- Trial vs paid signup mix
- New MRR from signups
- Most popular plans

---

## How to Use This Report

### Monitor Acquisition Health
1. Track new users and subscriptions weekly/monthly
2. Compare trial vs paid signup ratios
3. Identify seasonal patterns or campaign impact

### Optimize Conversion Funnels
1. High new users but low subscriptions = onboarding issue
2. High trial signups but low paid signups = trial conversion problem
3. Low MRR added despite high signups = wrong plan mix

### Plan Performance Insights
1. Track "Top Plan" to see which offerings drive growth
2. Compare plan popularity over time
3. Test new plans and measure signup impact

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket

### New Users
**Type:** Number  
**Description:** Users created in the period

### New Subscriptions
**Type:** Number  
**Description:** Subscriptions started in the period

### Trial Signups
**Type:** Number  
**Description:** New subscriptions that started with a trial  
**How it's calculated:** Count of subscriptions created with `trial_ends_at` set

### Paid Signups
**Type:** Number  
**Description:** New subscriptions that started without a trial  
**How it's calculated:** Count of subscriptions created without `trial_ends_at`

### MRR Added
**Type:** Currency  
**Description:** Monthly recurring revenue from new subscriptions  
**How it's calculated:** Sum of plan prices (normalized to monthly) for active new subscriptions

### Top Plan
**Type:** Text  
**Description:** Plan with the most signups in the period

---

## Benchmarks & Targets

### Trial vs Paid Ratio
- **High Trial (70–90%):** Low-touch or freemium SaaS
- **Balanced (40–60%):** Mid-market SaaS
- **High Paid (60–80%):** Enterprise or high-touch sales

### Signup Growth
- **Excellent:** > 20% month-over-month
- **Good:** 10–20% month-over-month
- **Flat:** 0–10% month-over-month
- **Declining:** Negative growth

---

## Actionable Insights

### If New Users Growing but Subscriptions Flat
**Likely Causes:** Onboarding friction, unclear value prop, pricing issues  
**Actions:** Improve signup flow, add activation triggers, test pricing

### If Trial Signups High but Paid Low
**Likely Causes:** Trial-to-paid conversion problem, trial too long, lack of engagement  
**Actions:** Shorten trial, improve trial onboarding, add conversion triggers

### If MRR Added is Low
**Likely Causes:** Signups on lower-tier plans, heavy discounting, downgrades  
**Actions:** Promote higher-tier plans, reduce discount reliance, improve upsell

---

## Example

| Period   | New Users | New Subscriptions | Trial Signups | Paid Signups | MRR Added | Top Plan   |
|----------|-----------|-------------------|---------------|--------------|-----------|------------|
| Jan 2025 | 450       | 300               | 210           | 90           | $15,000   | Premium    |
| Feb 2025 | 520       | 340               | 238           | 102          | $17,000   | Premium    |
| Mar 2025 | 580       | 380               | 266           | 114          | $19,000   | Professional |

---

## Filters & Views

### Date Range & Granularity
- **Daily:** Campaign tracking
- **Weekly:** Tactical monitoring
- **Monthly:** Strategic planning

### Segment by Source
- Filter by acquisition channel (organic, paid, referral)

---

## Related Reports

- **Trial Conversion:** See how trial signups convert to paid
- **MRR Movement:** Track MRR impact of new signups
- **CLV:** Understand long-term value of new cohorts

---

## FAQs

**Q: Why are new users higher than new subscriptions?**  
A: Not all users subscribe immediately—some browse first or abandon.

**Q: Should I count reactivations as new subscriptions?**  
A: No—this report tracks first-time signups only; reactivations are separate.

**Q: What's a healthy trial-to-paid ratio?**  
A: Varies by business model; SaaS averages 15–30% trial-to-paid conversion.

---

## Best Practices

1. **Track signups weekly** to catch trends early
2. **Segment by acquisition channel** to optimize marketing
3. **Monitor trial-to-paid conversion** alongside raw signups
4. **Compare MRR added to CAC** for profitability
5. **Track top plan** to understand market demand
6. **Correlate with campaigns** to measure ROI

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
