# Customers Export Report

**Report Type:** `customers`  
**Category:** Data Export  
**Data Source:** Users + Subscriptions + Orders

---

## Overview

Export comprehensive customer records with subscription history, order counts, revenue totals, and estimated lifetime value. Master dataset for CRM integration, marketing analysis, and BI tools.

**You'll get:**
- Complete customer profiles
- Subscription status and history
- Revenue and order metrics
- Estimated CLV per customer

---

## How to Use This Report

### CRM Integration
1. Export for CRM system synchronization
2. Segment customers by value and status
3. Track customer lifecycle progression

### Marketing Analysis
1. Identify high-value customers for VIP campaigns
2. Segment by plan or subscription status for targeted outreach
3. Track acquisition cohorts and retention

### Financial Analysis
1. Calculate customer concentration risk
2. Track revenue per customer trends
3. Validate CLV estimates against actual performance

---

## Column Definitions

### Email
**Type:** Text  
**Description:** Customer's primary email address

### User ID
**Type:** Number  
**Description:** Unique customer identifier in the system

### Name
**Type:** Text  
**Description:** Customer's full name (first + last)

### First Subscription Date
**Type:** Date  
**Description:** Earliest subscription start date for this customer

### Current Plan
**Type:** Text  
**Description:** Active subscription plan name (if any)

### Subscription Status
**Type:** Text  
**Description:** Current subscription state (active, canceled, expired, trial)

### Total Subscriptions
**Type:** Number  
**Description:** Lifetime count of all subscriptions (active + canceled + expired)

### Total Orders
**Type:** Number  
**Description:** Lifetime count of all orders (paid and completed)

### Total Revenue
**Type:** Currency  
**Description:** Sum of all paid order grand totals  
**How it's calculated:** Sum of order totals where payment_status = paid

### Customer Lifetime Value
**Type:** Currency  
**Description:** Estimated CLV based on average monthly revenue  
**How it's calculated:** Average Monthly Revenue × 24 months (industry standard)

### Created At
**Type:** Date  
**Description:** Customer account creation timestamp

---

## Filters & Views

### Filter by Subscription Status
- Active subscribers only
- Canceled/Expired subscribers
- Trial users

### Filter by Value
- High-value customers (CLV > $1,000)
- Medium-value ($100–$1,000)
- Low-value (< $100)

### Date Range
- Customers created within date range

---

## Export Formats

- **CSV:** Standard spreadsheet format
- **Excel:** Formatted workbook with pivot tables

---

## Related Reports

- **CLV:** Deep dive into lifetime value analysis
- **Users Export:** Additional user account details
- **Subscriptions Export:** Detailed subscription records per customer

---

## FAQs

**Q: Why is CLV different from Total Revenue?**  
A: CLV is a forward-looking estimate (projected 24 months); Total Revenue is historical.

**Q: Can I export customer purchase history?**  
A: This export shows aggregated metrics; use Orders Export for detailed purchase history.

**Q: How do I identify at-risk customers?**  
A: Look for high CLV with canceled or expiring subscriptions.

---

## Best Practices

1. **Export monthly** for CRM synchronization
2. **Segment by value** for targeted campaigns
3. **Track CLV trends** to validate retention efforts
4. **Secure exports** containing PII (personally identifiable information)
5. **Validate totals** against financial reports

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
