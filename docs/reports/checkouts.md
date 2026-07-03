# Checkouts Export Report

**Report Type:** `checkouts`  
**Category:** Data Export  
**Data Source:** Checkouts + Orders + Users

---

## Overview

Export raw checkout session data including financial totals, status, timing, and recovery information. Essential for operational analysis, support workflows, and conversion optimization.

**You'll get:**
- Complete checkout session records
- Financial breakdowns (subtotal, tax, discounts, shipping)
- Status and timing data
- Recovery campaign tracking

---

## How to Use This Report

### Operations & Support
1. Export for customer support investigations
2. Track checkout issues and failures
3. Identify stuck or problematic sessions

### Conversion Analysis
1. Export for BI tools or spreadsheet analysis
2. Analyze abandonment patterns
3. Track recovery campaign effectiveness

### Financial Reconciliation
1. Match checkouts to completed orders
2. Audit discount and tax calculations
3. Validate payment provider data

---

## Column Definitions

### Checkout ID
**Type:** Number  
**Description:** Unique checkout session identifier

### Checkout Token
**Type:** Text  
**Description:** Secure session token for URL tracking

### Checkout Type
**Type:** Text  
**Description:** Checkout type (subscription, shop, etc.)

### Status
**Type:** Text  
**Description:** Session state (started, pending, completed, abandoned)

### Email
**Type:** Text  
**Description:** Customer email address

### First Name / Last Name
**Type:** Text  
**Description:** Customer name fields

### Phone
**Type:** Text  
**Description:** Customer phone number

### Subtotal
**Type:** Currency  
**Description:** Sum of line items before adjustments

### Discount Total
**Type:** Currency  
**Description:** Total discounts applied

### Tax Total
**Type:** Currency  
**Description:** Tax amount calculated

### Shipping Total
**Type:** Currency  
**Description:** Shipping charges

### Grand Total
**Type:** Currency  
**Description:** Final checkout total

### Coupon Code
**Type:** Text  
**Description:** Applied coupon code (if any)

### Payment Provider
**Type:** Text  
**Description:** Selected payment method (Stripe, PayPal, etc.)

### User ID
**Type:** Number  
**Description:** Linked user account (if logged in)

### Order ID
**Type:** Number  
**Description:** Linked order (if checkout completed)

### Started At
**Type:** Date  
**Description:** Checkout session start timestamp

### Abandoned At
**Type:** Date  
**Description:** Abandonment detection timestamp

### Completed At
**Type:** Date  
**Description:** Checkout completion timestamp

### Recovery Email Sent
**Type:** Boolean  
**Description:** Whether recovery email was sent

---

## Filters & Views

### Filter by Status
- Completed checkouts
- Abandoned checkouts
- Pending sessions

### Filter by Recovery
- Recovery emails sent
- No recovery email

### Date Range
- Checkouts started or completed within range

---

## Export Formats

- **CSV:** Standard spreadsheet format
- **Excel:** Formatted workbook

---

## Related Reports

- **Checkout Funnel:** Stage-by-stage conversion analysis
- **Checkout Recovery:** Abandoned cart recovery performance
- **Abandoned Cart Detail:** Deep dive into abandonment patterns

---

## FAQs

**Q: Why are some checkouts "completed" without an Order ID?**  
A: Technical issue or order creation failure; investigate further.

**Q: What's the difference between Abandoned At and completion timestamp?**  
A: Abandoned At = system detected inactivity; Completed At = payment successful.

**Q: Can I export checkout line items?**  
A: This export shows session-level data; use separate line items export for product details.

---

## Best Practices

1. **Export weekly** for operational reviews
2. **Use filters** to focus on specific statuses or recovery states
3. **Validate completed checkouts** against order records
4. **Track abandonment patterns** by product, coupon, or payment provider
5. **Secure exports** containing customer data

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
