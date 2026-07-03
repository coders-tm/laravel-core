# Payment Method Performance Report

**Report Type:** `payment-method-performance`  
**Category:** Payments & Checkout  
**Data Source:** Payments grouped by provider/method

---

## Overview

Compare payment providers (Stripe, PayPal, Razorpay, GoCardless, etc.) across reliability, speed, and cost. See which methods deliver the highest success rates, fastest processing, and best net amounts after fees.

**You'll quickly spot:**
- Which providers have the best conversion (success rate)
- Which are slow or unstable (processing time, failures)
- Where fees erode margin (net vs gross)
- Refund patterns by provider

---

## How to Use This Report

### Optimize Checkout Conversion
1. Prioritize providers with consistently high success rates (>95%)
2. Offer alternative methods when a provider underperforms
3. Match payment methods to customer regions and preferences

### Reduce Payment Costs
1. Monitor fees and net amounts per provider
2. Negotiate rates with high-volume providers
3. Route transactions to lower-fee methods where reliability is equal

### Improve Payment Speed
1. Track average processing times per provider
2. Investigate slowdowns and time-of-day patterns
3. Communicate delays proactively if a provider is experiencing latency

---

## Column Definitions

### Provider
**Type:** Text  
**Description:** Payment provider or method name

### Total Attempts
**Type:** Number  
**Description:** Count of all payment attempts for the provider

### Successful
**Type:** Number  
**Description:** Count of successfully completed payments

### Failed
**Type:** Number  
**Description:** Count of failed payment attempts

### Success Rate
**Type:** Percentage  
**Description:** Share of successful payments out of attempts  
**How it's calculated:** (Successful ÷ Total Attempts) × 100

### Total Amount
**Type:** Currency  
**Description:** Gross payment amount processed by the provider

### Average Amount
**Type:** Currency  
**Description:** Average amount per successful payment

### Avg Processing Time
**Type:** Number (hours)  
**Description:** Average time from payment initiation to completion  
**How it's calculated:** Average of (processed time − created time) in hours

---

## Benchmarks & Targets

### Success Rate (per provider)
- **Excellent:** > 98%
- **Good:** 95–98%
- **Average:** 90–95%
- **Needs Attention:** < 90%

### Avg Processing Time
- **Instant:** < 0.1 hours (6 minutes)
- **Fast:** 0.1–1 hour
- **Acceptable:** 1–2 hours
- **Slow:** > 2 hours

---

## Actionable Insights

### If a Provider's Success Rate Drops
**Likely Causes:** Outages, fraud spikes, 3DS challenges, regional issues  
**Actions:** Check provider status, enable backup method, tweak fraud rules, adjust routing by region

### If Processing Time is Slow
**Likely Causes:** Provider latency, network, fraud review queues  
**Actions:** Monitor provider dashboards, prioritize faster methods, inform customers of delays

---

## Example Comparison

| Provider | Attempts | Successful | Failed | Success Rate | Avg Amount | Avg Time |
|----------|----------|------------|--------|--------------|------------|----------|
| Stripe   | 500      | 480        | 20     | 96.0%        | $96.00     | 0.5 h    |
| PayPal   | 300      | 270        | 30     | 90.0%        | $100.00    | 0.8 h    |
| Razorpay | 200      | 195        | 5      | 97.5%        | $100.00    | 0.4 h    |

Use this to identify high-performing providers and cost-effective routing strategies.

---

## Filters & Views

### Date Range & Granularity
- **Daily/Weekly:** Monitor incidents and short-term changes
- **Monthly:** Trend analysis and provider comparisons

### Provider & Region Filters
- Compare providers globally or by region to spot localized issues

---

## Related Reports

- **Payment Performance:** Overall payment health across all methods
- **Sales Summary:** Revenue and order context
- **Refund Analysis:** Deep dive into refunds and root causes

---

## FAQs

**Q: What if two providers have similar success rates?**  
A: Prefer the one with lower fees and faster processing time; keep both enabled for redundancy.

**Q: How do regional preferences affect performance?**  
A: Offer popular local methods (e.g., wallets, BNPL) to improve conversion in specific markets.

**Q: Should I disable a provider after a bad day?**  
A: Investigate first; use backup routing and monitor. Only disable if issues persist and impact conversion.

---

## Best Practices

1. **Monitor daily** for dips in success rate or spikes in failures
2. **Maintain backups** so customers always have alternatives
3. **Negotiate fees** as volume grows
4. **Route smartly** by region, currency, and risk profile
5. **Review authorization** and fraud rules regularly

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
