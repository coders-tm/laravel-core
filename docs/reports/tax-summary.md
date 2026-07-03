# Tax Summary Report

**Report Type:** `tax-summary`  
**Category:** Compliance & Financial Reporting  
**Data Source:** Orders

---

## Overview

Track tax collection across periods. See taxable vs tax-exempt orders, total tax collected, taxable amounts, and average effective tax rates for compliance and jurisdictional planning.

**You'll quickly spot:**
- Total tax collected per period
- Effective tax rate trends
- Taxable vs exempt order counts
- Jurisdictional tax patterns

---

## How to Use This Report

### Ensure Compliance
1. Monitor tax collection totals for remittance planning
2. Audit effective tax rates against expected jurisdiction rates
3. Identify tax-exempt orders for review

### Plan Remittances
1. Sum tax totals across periods for tax filing
2. Segment by jurisdiction (if available) for state/regional filing
3. Track collection trends to forecast obligations

### Audit Tax Configuration
1. Watch for outliers in avg tax rate (misconfigurations)
2. Compare taxable vs exempt to ensure proper exemption logic
3. Validate tax calculations against order totals

---

## Column Definitions

### Period
**Type:** Text  
**Description:** The reporting time bucket

### Total Orders
**Type:** Number  
**Description:** All orders in the period

### Taxable Orders
**Type:** Number  
**Description:** Orders with tax collected  
**How it's calculated:** COUNT(tax_total > 0)

### Tax Total
**Type:** Currency  
**Description:** Total tax collected  
**How it's calculated:** Sum of tax_total

### Taxable Amount
**Type:** Currency  
**Description:** Sum of taxable order subtotals  
**How it's calculated:** Sum of sub_total (for taxable orders)

### Avg Tax Rate
**Type:** Percentage  
**Description:** Effective tax rate  
**How it's calculated:** (Tax Total ÷ Taxable Amount) × 100

### Tax-Exempt Orders
**Type:** Number  
**Description:** Orders with no tax collected  
**How it's calculated:** COUNT(tax_total = 0 OR tax_total IS NULL)

---

## Benchmarks & Targets

### Effective Tax Rate
- **Typical Range:** 5–10% (varies by jurisdiction)
- **Watch for:** Rates outside expected range (configuration errors)

### Taxable vs Exempt Ratio
- **Healthy:** Matches business model (e.g., B2B may have high exempt %)
- **Audit:** Unexpected spikes in exempt orders

---

## Actionable Insights

### If Avg Tax Rate is Abnormal
**Likely Causes:** Tax configuration errors, wrong jurisdiction mapping, manual overrides  
**Actions:** Audit tax settings, validate jurisdiction rules, review recent tax rule changes

### If Tax-Exempt Orders Spike
**Likely Causes:** B2B campaign, exemption abuse, configuration errors  
**Actions:** Review exemption certificates, validate customer tax IDs, tighten exemption rules

### If Tax Total Fluctuates
**Likely Causes:** Seasonal order volume, high-value orders, jurisdiction mix changes  
**Actions:** Track period-over-period trends, segment by jurisdiction, forecast conservatively

---

## Example

| Period   | Total Orders | Taxable | Tax Total | Taxable Amount | Avg Tax Rate | Tax-Exempt |
|----------|--------------|---------|-----------|----------------|--------------|------------|
| Jan 2025 | 1,000        | 900     | $9,000    | $100,000       | 9.0%         | 100        |
| Feb 2025 | 1,100        | 990     | $9,900    | $110,000       | 9.0%         | 110        |
| Mar 2025 | 1,200        | 1,080   | $10,800   | $120,000       | 9.0%         | 120        |

---

## Filters & Views

### Date Range & Granularity
- **Daily:** Monitor real-time collection for operational needs
- **Monthly:** Tax filing period alignment
- **Quarterly/Annual:** Compliance and audit preparation

### Segment by Jurisdiction
- Track collection by state/region/country for multi-jurisdictional filing

---

## Related Reports

- **Sales Summary:** Revenue context
- **Orders:** Order-level tax detail
- **Payment Performance:** Cash collection vs tax collection

---

## FAQs

**Q: How often should I review tax summaries?**  
A: Monthly for operational checks; quarterly/annually for compliance and audits.

**Q: What if my effective tax rate is off?**  
A: Audit tax configuration, validate jurisdiction mapping, and review exemption rules.

**Q: Should I track tax by jurisdiction?**  
A: Yes—segment by state/region for accurate multi-jurisdictional filing.

---

## Best Practices

1. **Validate jurisdiction rules** regularly
2. **Audit exemption certificates** for tax-exempt orders
3. **Track effective rates** to catch configuration errors
4. **Segment by jurisdiction** for compliance
5. **Reconcile tax collected** with remittance schedules

---

**Last Updated:** December 14, 2025  
**Report Version:** 1.0
