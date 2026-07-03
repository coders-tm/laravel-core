# Member Retention Report

**Report Type:** `member-retention`  
**Category:** Subscription Analytics  
**Data Source:** Subscriptions (Cohort Analysis)

---

## Overview

Track subscriber retention by cohort over time. See how many customers remain active at 1, 2, 3, 6, and 12 months after signup to measure long-term loyalty and product-market fit.

**You'll quickly spot:**
- Retention rates by cohort
- Long-term loyalty trends (12-month retention)
- Cohort performance comparison
- Impact of product changes on retention

---

## How to Use This Report

### Retention Benchmarking
1. Track 12-month retention (target > 40%)
2. Compare cohorts to identify trends
3. Measure impact of product improvements

### Product Validation
1. High retention = strong product-market fit
2. Low retention = value delivery issues
3. Declining retention = competitive threats

### Cohort Comparison
1. Compare pre/post feature launch cohorts
2. Identify seasonal retention patterns
3. Test retention impact of pricing changes

---

## Column Definitions

### Cohort
**Type:** Text (YYYY-MM)  
**Description:** Month when subscribers signed up  
**How it's calculated:** Format of created_at date (e.g., "2025-01")

### Initial Count
**Type:** Number  
**Description:** Total subscribers in the cohort  
**How it's calculated:** Count of subscriptions created in cohort month

### Month 1 Retention
**Type:** Percentage  
**Description:** % of cohort active after 1 month  
**How it's calculated:** (Active at Month 1 ÷ Initial Count) × 100

### Month 2 Retention
**Type:** Percentage  
**Description:** % of cohort active after 2 months  
**How it's calculated:** (Active at Month 2 ÷ Initial Count) × 100

### Month 3 Retention
**Type:** Percentage  
**Description:** % of cohort active after 3 months  
**How it's calculated:** (Active at Month 3 ÷ Initial Count) × 100

### Month 6 Retention
**Type:** Percentage  
**Description:** % of cohort active after 6 months  
**How it's calculated:** (Active at Month 6 ÷ Initial Count) × 100

### Month 12 Retention
**Type:** Percentage  
**Description:** % of cohort active after 12 months  
**How it's calculated:** (Active at Month 12 ÷ Initial Count) × 100

---

## Benchmarks & Targets

### Month 1 Retention
- **Excellent:** > 85% retained
- **Good:** 75-85% retained
- **Fair:** 65-75% retained
- **Poor:** < 65% retained (onboarding issues)

### Month 3 Retention
- **Excellent:** > 70% retained
- **Good:** 60-70% retained
- **Fair:** 50-60% retained
- **Poor:** < 50% retained (value delivery problem)

### Month 6 Retention
- **Excellent:** > 60% retained
- **Good:** 50-60% retained
- **Fair:** 40-50% retained
- **Poor:** < 40% retained (product-market fit issues)

### Month 12 Retention
- **Excellent:** > 50% retained
- **Good:** 40-50% retained
- **Fair:** 30-40% retained
- **Poor:** < 30% retained (long-term viability concern)

---

## Actionable Insights

### If Month 1 Retention < 75%
**Likely Causes:** Poor onboarding, trial friction, wrong customer targeting  
**Actions:** Improve onboarding flow, add activation milestones, qualify leads better

### If Month 3 Retention < 60%
**Likely Causes:** Value delivery gap, feature limitations, better alternatives  
**Actions:** Survey churned users, improve core features, add engagement hooks

### If Month 6 Retention < 50%
**Likely Causes:** Product-market fit issues, pricing problems, competitive pressure  
**Actions:** Deep customer interviews, product roadmap review, pricing adjustment

### If Declining Retention Across Cohorts
**Likely Causes:** Market saturation, product degradation, increased competition  
**Actions:** Refresh product features, improve value delivery, enhance differentiation

---

## Example

| Cohort   | Initial Count | Month 1 | Month 2 | Month 3 | Month 6 | Month 12 |
|----------|---------------|---------|---------|---------|---------|----------|
| 2024-01  | 500           | 82%     | 75%     | 68%     | 58%     | 48%      |
| 2024-02  | 550           | 85%     | 78%     | 72%     | 62%     | 52%      |
| 2024-03  | 600           | 88%     | 82%     | 76%     | 66%     | 56%      |
| 2024-04  | 650           | 86%     | 80%     | 74%     | 64%     | —        |
| 2024-05  | 700           | 84%     | 78%     | 72%     | —       | —        |

**Insight:** Retention improving across cohorts—March cohort has 56% 12-month retention (excellent). Recent cohorts showing strong early retention (> 85% Month 1).

---

## Filters & Views

### Filter by Cohort Period
- Monthly cohorts (standard)
- Quarterly cohorts (strategic view)
- Yearly cohorts (long-term trends)

### Filter by Plan
- Compare retention by plan type
- Identify plans with best long-term retention

### Filter by Channel
- Compare retention by acquisition source
- Optimize marketing spend on high-retention channels

---

## Related Reports

- **Customer Churn:** Overall churn context
- **Trial Conversion:** Trial-to-paid quality
- **Plan Comparison:** Plan-specific retention

---

## FAQs

**Q: What's a good 12-month retention rate?**  
A: > 50% is excellent for most SaaS. Consumer apps may see 30-40%; enterprise SaaS often achieves 70-80%.

**Q: How do I improve Month 1 retention?**  
A: Focus on onboarding—time-to-value, activation events, early wins, personal outreach.

**Q: Should I worry about declining retention after Month 6?**  
A: Some decline is natural, but retention should stabilize. Steep drops after Month 6 indicate product-market fit issues.

**Q: How often should I check cohort retention?**  
A: Monthly for recent cohorts (1-3 months), quarterly for mature cohorts (6-12 months).

**Q: What's the difference between retention rate and churn rate?**  
A: Retention Rate = % still active. Churn Rate = % who left. They're inverse: Retention + Churn = 100%.

---

## Best Practices

1. **Track monthly** for all cohorts to identify trends early
2. **Set retention targets** by milestone (Month 1, 3, 6, 12)
3. **Compare cohorts** pre/post major product changes
4. **Segment by plan** to understand tier-specific retention
5. **Focus on Month 1** for immediate onboarding improvements
6. **Use Month 6-12** for product-market fit validation
7. **Celebrate improving cohorts** and analyze what's working

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
