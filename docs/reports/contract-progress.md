# Contract Progress Report

**Report Type:** `contract-progress`  
**Category:** Subscription Analytics  
**Data Source:** Subscriptions (Contract-based Plans)

---

## Overview

Track fixed-term contract subscription progress. See how many cycles completed, average progress, and subscribers nearing completion to manage renewal pipeline and prevent churn.

**You'll quickly spot:**
- Contracts nearing completion (renewal opportunity)
- Average contract progress across subscribers
- Completed contracts (renewal/churn decision point)
- Active contract health

---

## How to Use This Report

### Renewal Pipeline Management
1. Identify contracts at ≥ 80% progress
2. Trigger renewal outreach before completion
3. Calculate expected renewals and churn

### Contract Performance
1. Track average progress across plans
2. Monitor contract completion rates
3. Identify early cancellation patterns

### Sales & Finance Forecasting
1. Predict upcoming contract expirations
2. Calculate renewal revenue opportunity
3. Plan inventory and resource allocation

---

## Column Definitions

### Plan Name
**Type:** Text  
**Description:** Plan display name

### Contract Cycles
**Type:** Number  
**Description:** Total billing cycles in contract  
**How it's calculated:** Plan's configured total_cycles value (e.g., 12 for 12-month contract)

### Active Contracts
**Type:** Number  
**Description:** Active subscriptions on this plan (not canceled)  
**How it's calculated:** Count of subscriptions where status = "active" and canceled_at is null

### Average Current Cycle
**Type:** Number  
**Description:** Average cycle number among active contracts  
**How it's calculated:** Average of current_cycle for active subscriptions

### Average Progress
**Type:** Percentage  
**Description:** Average completion percentage across active contracts  
**How it's calculated:** (Average Current Cycle ÷ Contract Cycles) × 100

### Near Completion
**Type:** Number  
**Description:** Contracts at ≥ 80% progress (renewal target)  
**How it's calculated:** Count of active subscriptions where (current_cycle ÷ total_cycles) ≥ 0.8

### Completed Contracts
**Type:** Number  
**Description:** Contracts that have finished all cycles  
**How it's calculated:** Count of subscriptions where current_cycle ≥ total_cycles

---

## Benchmarks & Targets

### Average Progress
- **Early Stage:** < 40% average progress (contracts mostly new)
- **Mid Stage:** 40-70% average progress (balanced portfolio)
- **Late Stage:** > 70% average progress (renewal wave approaching)

### Near Completion Rate
- **Low:** < 15% of active contracts near completion (renewal pipeline healthy)
- **Moderate:** 15-30% near completion (prepare renewal campaigns)
- **High:** > 30% near completion (urgent renewal focus needed)

### Renewal Action Zones
- **80-90% Progress:** Trigger renewal outreach
- **90-95% Progress:** Intensify renewal efforts
- **95-100% Progress:** Final renewal push
- **100%+ Progress:** Expired—retention or churn

---

## Actionable Insights

### If Near Completion > 30% of Active
**Likely Causes:** Contract wave maturing, successful retention, growth slowdown  
**Actions:** Launch renewal campaign, offer incentives, contact personally

### If Average Progress < 20%
**Likely Causes:** Recent contract plan launch, strong new acquisition  
**Actions:** Optimize onboarding for long-term retention, set renewal expectations early

### If Completed Contracts Growing
**Likely Causes:** Contracts expiring, low renewal rate, churn risk  
**Actions:** Analyze renewal rates, improve renewal messaging, offer upgrade incentives

### If Average Current Cycle Declining
**Likely Causes:** New contracts replacing completed ones, strong acquisition  
**Actions:** Maintain acquisition momentum, prepare for future renewal waves

---

## Example

| Plan Name        | Contract Cycles | Active Contracts | Avg Current Cycle | Avg Progress | Near Completion | Completed |
|------------------|-----------------|------------------|-------------------|--------------|-----------------|-----------|
| 6-Month Contract | 6               | 250              | 3.5               | 58.3%        | 45              | 12        |
| Annual Contract  | 12              | 500              | 8.2               | 68.3%        | 125             | 23        |
| 2-Year Contract  | 24              | 150              | 14.5              | 60.4%        | 30              | 5         |

**Insight:** Annual Contract has 125 near completion (25% of active)—launch targeted renewal campaign in next 30 days.

---

## Filters & Views

### Filter by Plan
- Compare contract progress across plans
- Identify plans with high renewal opportunity

### Filter by Progress Range
- 0-40% (early stage)
- 40-80% (mid stage)
- 80-100% (near completion)
- 100%+ (completed/expired)

### Sort by Near Completion
- Prioritize plans with most renewal opportunities

---

## Related Reports

- **Renewal Forecast:** Detailed renewal revenue projections
- **Subscription Lifecycle:** Overall subscription state tracking
- **Plan Comparison:** Plan performance including non-contract plans

---

## FAQs

**Q: What's the difference between contract cycles and billing cycles?**  
A: Contract cycles = total commitment (e.g., 12 months). Billing cycles = payment frequency (monthly, quarterly). A 12-month contract might bill monthly (12 billing cycles) or annually (1 billing cycle).

**Q: When should I start renewal outreach?**  
A: At 80% progress (e.g., month 10 of 12). Gives 2+ cycles for decision-making and prevents last-minute churn.

**Q: What if a contract is > 100% progress?**  
A: Subscription continued beyond contract end without renewal/cancellation. Common for month-to-month continuation after contract.

**Q: How do I improve renewal rates?**  
A: Early engagement (at 80%), clear value delivery, renewal incentives, simplified renewal process, personal outreach for high-value contracts.

**Q: Should I auto-renew contracts?**  
A: Depends on your business model. B2B SaaS often auto-renews with opt-out; consumer products may require opt-in for compliance.

---

## Best Practices

1. **Track weekly** to catch renewal opportunities early
2. **Set renewal triggers** at 80% contract progress
3. **Segment by plan** to tailor renewal messaging
4. **Offer renewal incentives** at 85-90% progress
5. **Personal outreach** for high-value contracts near completion
6. **Monitor completed contracts** and analyze renewal/churn rates
7. **Forecast renewal revenue** using near completion count × plan price

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
