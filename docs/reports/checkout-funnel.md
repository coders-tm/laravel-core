# Checkout Funnel Report

**Report Type:** `checkout-funnel`  
**Category:** Conversion Optimization  
**Data Source:** Checkouts

---

## Overview

Track customers through each checkout stage to identify drop-off points. Visualize where users abandon and optimize the weakest steps for higher conversion.

**You'll quickly spot:**
- Where users drop out (biggest conversion gaps)
- Payment success rate
- Overall start-to-complete conversion
- Stage-by-stage progression

---

## How to Use This Report

### Identify Drop-Off Points
1. Find the largest drop between consecutive stages
2. Focus optimization efforts on the weakest step first
3. Track improvements after checkout changes

### Optimize Payment Flow
1. Low payment success rate = payment provider issues
2. High payment attempts but low success = fraud, declines, UX problems
3. Compare payment methods for success rates

### Monitor Checkout Health
1. Track start-to-complete rate over time
2. Set conversion benchmarks and alert on degradation
3. Measure impact of A/B tests and UX changes

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket

### Started
**Type:** Number  
**Description:** Checkouts initiated  
**How it's calculated:** Count of checkout sessions started

### Contact Filled
**Type:** Number  
**Description:** Email/contact information entered  
**How it's calculated:** Count of checkouts with contact info saved

### Billing Added
**Type:** Number  
**Description:** Billing address entered  
**How it's calculated:** Count of checkouts with billing address saved

### Payment Attempted
**Type:** Number  
**Description:** Payment submission initiated  
**How it's calculated:** Count of checkouts with payment status = pending/completed/failed

### Payment Succeeded
**Type:** Number  
**Description:** Payment processed successfully  
**How it's calculated:** Count of checkouts with payment status = completed

### Completed
**Type:** Number  
**Description:** Checkout fully completed  
**How it's calculated:** Count of checkouts with status = completed

### Abandoned
**Type:** Number  
**Description:** Checkout abandoned without completion  
**How it's calculated:** Count of checkouts with status = abandoned

### Start to Complete Rate
**Type:** Percentage  
**Description:** Overall conversion rate  
**How it's calculated:** (Completed ÷ Started) × 100

### Payment Success Rate
**Type:** Percentage  
**Description:** Payment reliability  
**How it's calculated:** (Payment Succeeded ÷ Payment Attempted) × 100

---

## Benchmarks & Targets

### Start to Complete Rate
- **Excellent:** > 60%
- **Good:** 40–60%
- **Fair:** 20–40%
- **Poor:** < 20% (major friction)

### Payment Success Rate
- **Excellent:** > 95%
- **Good:** 90–95%
- **Fair:** 80–90%
- **Poor:** < 80% (payment provider issues)

### Stage Drop-Off (Ideal)
- **Contact Filled:** < 10% drop from Started
- **Billing Added:** < 15% drop from Contact
- **Payment Attempted:** < 20% drop from Billing
- **Payment Succeeded:** < 5% drop from Attempted

---

## Actionable Insights

### If Start to Complete Rate < 40%
**Likely Causes:** Too many steps, unclear pricing, trust issues, payment friction  
**Actions:** Simplify checkout, add trust badges, optimize payment UX, reduce required fields

### If Payment Success Rate < 90%
**Likely Causes:** Payment provider issues, fraud detection too aggressive, expired cards  
**Actions:** Test alternative payment providers, reduce fraud sensitivity, add retry logic

### If Large Drop at Contact Stage
**Likely Causes:** Email required too early, form friction, privacy concerns  
**Actions:** Delay email requirement, simplify form, add privacy reassurance

### If Large Drop at Payment Stage
**Likely Causes:** Unexpected costs, limited payment methods, security concerns  
**Actions:** Show total earlier, add more payment options, improve security messaging

---

## Example

| Period   | Started | Contact | Billing | Pay Attempted | Pay Succeeded | Completed | Abandoned | Start→Complete | Pay Success |
|----------|---------|---------|---------|---------------|---------------|-----------|-----------|----------------|-------------|
| Week 1   | 1,000   | 850     | 720     | 680           | 650           | 630       | 370       | 63%            | 96%         |
| Week 2   | 1,200   | 1,000   | 840     | 800           | 760           | 730       | 470       | 61%            | 95%         |
| Week 3   | 1,500   | 1,275   | 1,080   | 1,020         | 970           | 950       | 550       | 63%            | 95%         |

---

## Filters & Views

### Date Range & Granularity
- **Daily:** A/B test monitoring
- **Weekly:** Tactical optimization
- **Monthly:** Strategic planning

### Segment by Type
- Compare subscription vs product checkouts

---

## Related Reports

- **Checkout Recovery:** Abandoned cart recovery performance
- **Abandoned Cart Detail:** Deep dive into abandonment reasons
- **Payment Performance:** Payment provider reliability

---

## FAQs

**Q: Why are some stages higher than previous ones?**  
A: Returning visitors resuming abandoned checkouts can skip early stages.

**Q: What's a healthy drop-off rate?**  
A: < 30% total drop from start to completion; < 5% at payment stage.

**Q: How do I improve payment success rate?**  
A: Test alternative payment providers, add retry logic, reduce fraud sensitivity.

---

## Best Practices

1. **Track weekly** to catch conversion degradation early
2. **Fix the biggest drop** first for maximum impact
3. **A/B test changes** and measure funnel impact
4. **Segment by device** (mobile vs desktop) for targeted optimization
5. **Monitor payment provider health** separately
6. **Simplify checkout** progressively—remove one field at a time and measure

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
