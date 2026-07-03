# Subscriptions Export Report

**Report Type:** `subscriptions`  
**Category:** Data Export  
**Data Source:** Subscriptions + Plans + Users

---

## Overview

Export detailed subscription lifecycle data with plan details and timestamps. Essential for compliance, auditing, migration, and financial analysis.

**You'll get:**
- Complete subscription records
- Lifecycle timestamps (trial, cancel, expiry)
- Plan and pricing details
- Subscription quantities

---

## How to Use This Report

### Compliance & Auditing
1. Export for regulatory compliance
2. Audit subscription lifecycle events
3. Track cancellation patterns

### Financial Analysis
1. Export for accounting reconciliation
2. Analyze subscription revenue by plan
3. Track subscription quantities and pricing

### Migration & Backup
1. Export for platform migration
2. Backup subscription data
3. Data portability for customers

---

## Column Definitions

### Subscription ID
**Type:** Number  
**Description:** Unique subscription identifier

### User ID
**Type:** Number  
**Description:** Customer account ID

### Plan Type
**Type:** Text  
**Description:** Subscription plan name

### Subscription Status
**Type:** Text  
**Description:** Current state (active, canceled, expired, trial)

### Price
**Type:** Currency  
**Description:** Subscription price per billing cycle

### Interval
**Type:** Text  
**Description:** Billing frequency (day, week, month, year)

### Quantity
**Type:** Number  
**Description:** Subscription quantity (seats, licenses, units)

### Trial Ends At
**Type:** Date  
**Description:** Trial expiration timestamp

### Expires At
**Type:** Date  
**Description:** Subscription expiration date

### Canceled At
**Type:** Date  
**Description:** Cancellation timestamp

### Created At
**Type:** Date  
**Description:** Subscription start timestamp

### Updated At
**Type:** Date  
**Description:** Last modification timestamp

---

## Filters & Views

### Filter by Status
- Active subscriptions only
- Canceled/Expired subscriptions
- Trial subscriptions

### Filter by Plan
- Export subscriptions for specific plans

### Date Range
- Subscriptions created or modified within range

---

## Export Formats

- **CSV:** Standard spreadsheet format
- **Excel:** Formatted workbook with formulas

---

## Related Reports

- **Users Export:** Customer account data
- **MRR Movement:** Revenue impact of subscriptions
- **Subscription Lifecycle:** State transition analysis

---

## FAQs

**Q: What's the difference between Canceled At and Expires At?**  
A: Canceled At = when user requested cancellation; Expires At = when access actually ends (grace period).

**Q: How do I calculate MRR from this export?**  
A: Normalize Price × Quantity to monthly (e.g., yearly plan ÷ 12).

**Q: Can I export historical subscription changes?**  
A: This export shows current state; use audit logs for full history.

---

## Best Practices

1. **Export monthly** for financial reconciliation
2. **Use filters** to focus on specific states or plans
3. **Validate totals** against MRR reports
4. **Secure exports** containing customer data
5. **Document export purpose** for compliance

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
