# Orders Export Report

**Report Type:** `orders`  
**Category:** Data Export  
**Data Source:** Orders + Payments + Line Items

---

## Overview

Export complete order records with financial breakdowns and status tracking. Essential for accounting, operations, reconciliation, and fulfillment workflows.

**You'll get:**
- Complete order history
- Financial details (subtotal, tax, discounts, refunds)
- Order status (payment, fulfillment)
- Completion timestamps

---

## How to Use This Report

### Accounting & Finance
1. Export for accounting system reconciliation
2. Track revenue, tax, discounts, and refunds
3. Monthly/quarterly financial reporting

### Operations & Fulfillment
1. Export pending orders for fulfillment
2. Track order completion rates
3. Identify stuck or problematic orders

### Analysis
1. Export for BI tools or spreadsheet analysis
2. Analyze order trends by status
3. Track payment success rates

---

## Column Definitions

### Order ID
**Type:** Number  
**Description:** Unique order identifier

### Customer Email
**Type:** Text  
**Description:** Customer's email address

### Order Status
**Type:** Text  
**Description:** Overall order state (draft, pending, completed, canceled)

### Payment Status
**Type:** Text  
**Description:** Payment state (pending, processing, paid, failed, refunded, partially_refunded)

### Fulfillment Status
**Type:** Text  
**Description:** Fulfillment state (pending, processing, shipped, delivered, cancelled)

### Grand Total
**Type:** Currency  
**Description:** Final order total (after tax, discounts, shipping)

### Subtotal
**Type:** Currency  
**Description:** Sum of line items before adjustments

### Tax Total
**Type:** Currency  
**Description:** Total tax collected

### Discount Total
**Type:** Currency  
**Description:** Total discounts applied

### Shipping Total
**Type:** Currency  
**Description:** Shipping charges

### Refund Total
**Type:** Currency  
**Description:** Total amount refunded

### Paid Total
**Type:** Currency  
**Description:** Amount actually paid (Grand Total - Refunds)

### Created At
**Type:** Date  
**Description:** Order creation timestamp

### Completed At
**Type:** Date  
**Description:** Order completion timestamp

---

## Filters & Views

### Filter by Status
- Completed orders only
- Pending/Draft orders
- Canceled orders
- Failed payments

### Filter by Payment Status
- Paid orders
- Refunded orders
- Failed payments

### Date Range
- Orders created or completed within range

---

## Export Formats

- **CSV:** Standard spreadsheet format
- **Excel:** Formatted workbook with financial formulas

---

## Related Reports

- **Sales Summary:** Revenue metrics and trends
- **Refund Analysis:** Refund patterns and insights
- **Tax Summary:** Tax compliance reporting

---

## FAQs

**Q: What's the difference between Grand Total and Paid Total?**  
A: Grand Total = original order total; Paid Total = Grand Total - Refunds.

**Q: Why are some orders "completed" but payment is "pending"?**  
A: Order workflow completion is separate from payment processing (e.g., invoicing).

**Q: Can I export orders with line items?**  
A: This export shows order-level data; use separate line items export for product details.

---

## Best Practices

1. **Export monthly** for accounting reconciliation
2. **Use filters** to focus on specific statuses or date ranges
3. **Validate totals** against payment processor reports
4. **Secure exports** containing customer data
5. **Track refunds separately** for quality analysis

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
