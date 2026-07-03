# Feature Utilization Report

**Report Type:** `feature-utilization`  
**Category:** Product Analytics  
**Data Source:** Plans + Subscription Features + Usage

---

## Overview

Track how subscribers use plan features. See average usage, utilization rates, and users hitting limits to optimize feature packaging, pricing, and investment priorities.

**You'll quickly spot:**
- Underused features (low utilization)
- Features hitting limits (upgrade opportunities)
- Usage patterns by plan
- Feature value perception

---

## How to Use This Report

### Feature Packaging
1. Identify underused features (< 20% utilization)
2. Consider removing or bundling low-usage features
3. Promote high-usage features in marketing

### Upsell Opportunities
1. Find users at or over limits
2. Trigger upgrade prompts for limit-reaching users
3. Calculate revenue opportunity from upgrades

### Product Investment
1. Prioritize features with high utilization
2. Improve or sunset features with low adoption
3. Adjust limits based on actual usage patterns

---

## Column Definitions

### Plan ID
**Type:** Number  
**Description:** Unique plan identifier

### Plan Name
**Type:** Text  
**Description:** Plan display name

### Feature Name
**Type:** Text  
**Description:** Feature being tracked (e.g., "API Calls", "Storage GB", "Team Members")

### Feature Limit
**Type:** Number  
**Description:** Average configured limit for this feature  
**How it's calculated:** Average of feature limit across active subscriptions on this plan

### Average Usage
**Type:** Number  
**Description:** Average feature usage across subscribers  
**How it's calculated:** Average of current usage value for active subscriptions

### Utilization Rate
**Type:** Percentage  
**Description:** How much of the limit is being used on average  
**How it's calculated:** (Average Usage ÷ Feature Limit) × 100

### Users at Limit
**Type:** Number  
**Description:** Subscribers at exactly their feature limit  
**How it's calculated:** Count of subscriptions where usage = limit

### Users Over Limit
**Type:** Number  
**Description:** Subscribers exceeding their feature limit  
**How it's calculated:** Count of subscriptions where usage > limit

---

## Benchmarks & Targets

### Utilization Rate
- **High Usage:** > 60% average utilization (feature is valuable)
- **Moderate Usage:** 30-60% utilization (normal usage)
- **Low Usage:** < 30% utilization (overprovisioned or undervalued)
- **Very Low:** < 10% utilization (consider removing or rebundling)

### Users at/Over Limit
- **Upsell Opportunity:** > 10% of users at or over limit
- **Comfortable Headroom:** 5-10% at/over limit
- **Underutilized:** < 5% at/over limit

### Feature Value Indicator
- **High Value:** High utilization + many users at limit
- **Right-sized:** Moderate utilization + few at limit
- **Oversized:** Low utilization + almost none at limit

---

## Actionable Insights

### If Utilization Rate < 20%
**Likely Causes:** Limits too high, feature not valuable, poor user education  
**Actions:** Reduce limits to lower plan tier, remove feature, improve onboarding

### If Users Over Limit > 10%
**Likely Causes:** Limits too low, feature highly valuable, upgrade friction  
**Actions:** Trigger upgrade prompts, create overage pricing, increase limits

### If High Utilization but Few at Limit
**Likely Causes:** Limits well-calibrated, feature used but not limiting  
**Actions:** Maintain current limits, use as marketing differentiator

### If Low Utilization and High Users Over Limit
**Likely Causes:** Data anomaly, feature misunderstood, usage spike  
**Actions:** Audit data, investigate feature usage patterns, improve messaging

---

## Example

| Plan Name    | Feature Name  | Feature Limit | Avg Usage | Utilization | At Limit | Over Limit |
|--------------|---------------|---------------|-----------|-------------|----------|------------|
| Starter      | API Calls     | 10,000        | 2,500     | 25.0%       | 5        | 2          |
| Professional | API Calls     | 100,000       | 65,000    | 65.0%       | 45       | 12         |
| Enterprise   | API Calls     | 1,000,000     | 350,000   | 35.0%       | 3        | 0          |
| Starter      | Storage (GB)  | 10            | 1.2       | 12.0%       | 0        | 0          |
| Professional | Storage (GB)  | 100           | 42        | 42.0%       | 8        | 3          |

**Insight:** Professional API Calls at 65% utilization with 12 users over limit—strong upsell opportunity. Starter Storage at 12% utilization—overprovisioned, reduce to 5GB.

---

## Filters & Views

### Filter by Plan
- Compare utilization across plans
- Identify plan-specific usage patterns

### Filter by Feature
- See all plans for one feature
- Identify feature value across tiers

### Filter by Utilization Rate
- High utilization (> 60%)
- Low utilization (< 30%)

---

## Related Reports

- **Plan Comparison:** Overall plan performance
- **Subscription Lifecycle:** Upgrade/downgrade patterns
- **Trial Conversion:** Feature usage during trials

---

## FAQs

**Q: What's a healthy utilization rate?**  
A: 30-60% is ideal. Below 30% suggests overprovisioning; above 60% with many at limit suggests upgrade opportunities.

**Q: Should I reduce limits on underused features?**  
A: Yes, if < 20% utilization and minimal users at limit. Test changes on new customers first.

**Q: How do I increase utilization?**  
A: Improve onboarding, add in-app guidance, showcase feature value in marketing.

**Q: What if users are over limit but not upgrading?**  
A: Add soft limits with graceful degradation, create overage pricing, or improve upgrade messaging.

**Q: Should I remove features with low utilization?**  
A: Consider bundling with other features or making it a premium add-on. Don't remove if it's a key differentiator.

---

## Best Practices

1. **Track monthly** to identify trends and seasonal patterns
2. **Set utilization targets** of 30-60% for optimal value perception
3. **Trigger upgrade prompts** when users hit 80% of limit
4. **Survey low-utilization users** to understand feature value
5. **Test limit changes** on new customers before rolling out broadly
6. **Use high-utilization features** in marketing and sales materials
7. **Create overage pricing** for features with many users over limit

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
