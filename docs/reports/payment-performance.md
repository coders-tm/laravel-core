# Payment Performance Report

**Report Type:** `payment-performance`  
**Category:** Order & Payments Analytics  
**Data Source:** Payments

---

## Overview

The Payment Performance report tracks payment reliability and efficiency across your store. It shows payment volume, success/failure rates, processing speed, fees, net amounts, and refund activity.

**Key insights:**
- Payment success rate by period
- Average processing time (how fast payments complete)
- Net amounts after fees
- Refund counts and refund amounts
- Early detection of gateway issues

---

## What You’ll Learn

### Reliability
- **Success Rate**: How often payments succeed
- **Failed Payments**: Operational and customer experience risks

### Efficiency
- **Avg Processing Time**: Payment speed and gateway performance
- **Fees vs Net**: Cost of payments and true cash received

### Customer Impact
- **Refund Count/Amount**: Post-purchase issues and customer satisfaction

---

## How to Use This Report

### Monitor Payment Health
1. Track success rate (aim for > 95%)
2. Investigate spikes in failed payments immediately
3. Check processing time for slowdowns

**Example:** If success rate drops from 97% to 92% and processing time rises, check gateway status and error logs.

### Optimize Payment Mix
1. Compare performance across providers (use Payment Method Performance)
2. Prioritize reliable providers with lower fees
3. Offer backup methods during outages

### Reduce Refunds
1. Track refund counts and amounts by period
2. Identify products or campaigns causing refunds
3. Improve product information and post-purchase support

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket

### Total Payments
**Type:** Number  
**Description:** Count of all payments attempted in the period

### Successful Payments
**Type:** Number  
**Description:** Payments that completed successfully

### Failed Payments
**Type:** Number  
**Description:** Payments that failed

### Success Rate
**Type:** Percentage  
**Description:** Share of successful payments out of total  
**How it’s calculated:** (Successful ÷ Total Payments) × 100

### Total Amount
**Type:** Currency  
**Description:** Sum of all payment amounts (gross)

### Total Fees
**Type:** Currency  
**Description:** Sum of payment processing fees

### Net Amount
**Type:** Currency  
**Description:** Amount received after fees  
**How it’s calculated:** Total Amount − Total Fees

### Avg Processing Time
**Type:** Number (hours)  
**Description:** Average time between payment initiation and completion  
**How it’s calculated:** Average of (processed time − created time) in hours

### Refund Count
**Type:** Number  
**Description:** Number of payments that had refunds

### Refund Amount
**Type:** Currency  
**Description:** Total refunded amount

---

## Report Calculations & Formulas

### Success Rate
```
Success Rate (%) = (Successful ÷ Total Payments) × 100
```

### Average Processing Time
```
Avg Processing Time (hours) = Average of (processed_at − created_at) × 24
```

### Net Amount
```
Net Amount = Total Amount − Total Fees
```

---

## Benchmarks & Targets

### Success Rate
- **Excellent:** > 98%
- **Good:** 95–98%
- **Average:** 90–95%
- **Needs Attention:** < 90%

### Avg Processing Time
- **Instant:** < 0.1 hours (6 minutes)
- **Fast:** 0.1–1 hour
- **Acceptable:** 1–2 hours
- **Slow:** > 2 hours (investigate)

### Refund Rate (by count)
- **Excellent:** < 3%
- **Good:** 3–5%
- **Average:** 5–8%
- **High:** > 8%

---

## Actionable Insights

### If Success Rate Drops
**Potential Causes:**
- Gateway outages or errors
- Increased fraud attempts
- Payment method-specific issues

**Actions to Take:**
1. Check provider status and error logs
2. Enable fallback payment method
3. Add fraud rules (AVS/CVV checks)
4. Improve error messages and retry prompts

### If Processing Time Increases
**Potential Causes:**
- Provider latency
- Queue/backlog issues
- Network problems

**Actions to Take:**
1. Monitor provider dashboards
2. Temporarily prioritize faster providers
3. Communicate delays proactively to customers

### If Refunds Spike
**Potential Causes:**
- Product quality or sizing issues
- Misleading product pages
- Shipping delays or damage

**Actions to Take:**
1. Analyze refund reasons by product
2. Improve descriptions and size guides
3. Strengthen packaging and carrier selection
4. Add proactive post-purchase support

---

## Summary Metrics

The summary shows:
- **Total Payments**
- **Success Rate**
- **Net Amount**
- **Refund Amount**

Use it for fast health checks and operational decisions.

---

## Filters & Customization

### Date Range & Granularity
- **Daily/Weekly:** For incident monitoring
- **Monthly:** For trend analysis and provider comparison

### Provider Filters
- Compare performance across gateways/providers (Stripe, PayPal, etc.)

---

## Related Reports

- **Payment Method Performance**: Provider-level breakdowns
- **Sales Summary**: Revenue context
- **Refund Analysis**: Root cause investigation

---

## Frequently Asked Questions

**Q: What is a healthy payment success rate?**  
A: Aim for 95%+ overall. Spikes below 90% should be investigated immediately.

**Q: Why do processing times vary?**  
A: Provider latency, network conditions, and fraud checks can add time.

**Q: How can I reduce failed payments?**  
A: Improve error messages, enable retries, add alternative methods, and implement fraud checks.

---

## Best Practices

1. **Monitor daily:** Catch issues quickly
2. **Fallback methods:** Offer backup options during incidents
3. **Transparent UX:** Clear error states and retry guidance
4. **Fraud controls:** Balance security and conversion
5. **Provider SLAs:** Track performance over time

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
