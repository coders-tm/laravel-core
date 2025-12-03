# Order Reports Dashboard - Setup Guide

## Overview

This is a comprehensive Shopify-level analytics dashboard that displays 40+ metrics, charts, and insights for order performance using the reporting APIs we built.

## Setup Instructions

### 1. Seed Test Data

Generate realistic faker data for testing the dashboard:

```bash
# From the laravel-core-source directory
php artisan db:seed --class=Workbench\\Database\\Seeders\\OrderReportsSeeder
```

This will create:

-   **200 test orders** with realistic data
-   **20 products** with variants
-   **8 discount coupons** (SAVE10, SAVE20, FLASH25, etc.)
-   **10 users** (if not already created)
-   Orders across different:
    -   Payment statuses (90% paid, 10% pending)
    -   Fulfillment statuses (delivered, shipped, processing, pending)
    -   Countries (US, Canada, UK, Germany, France, Australia, Japan, Brazil)
    -   Sources (website, mobile app, POS, marketplace, social media)
    -   Carriers (UPS, FedEx, USPS, DHL, Canada Post, Royal Mail)

### 2. Start the Development Server

```bash
composer run serve
```

Or manually:

```bash
php artisan serve
```

### 3. Access the Dashboard

Open your browser and navigate to:

```
http://localhost:8000/reports/dashboard
```

Or access from the workbench home page:

```
http://localhost:8000
```

Click on **"Order Reports Dashboard"**

## Dashboard Features

### Financial KPIs

-   **Gross Sales** - Total revenue before discounts
-   **Net Sales** - Revenue after discounts & refunds
-   **Avg Discount Rate** - Average discount percentage applied
-   **Refund Rate** - Percentage of refunded orders

### Operational Metrics

-   **Avg Fulfillment Time** - Hours from order to shipment
-   **Avg Delivery Time** - Hours from shipment to delivery
-   **Fulfillment Backlog** - Orders pending shipment >48 hours
-   **On-Time Delivery Rate** - Percentage delivered within 7 days

### Charts & Analytics

-   **Top Products by Revenue** - Bar chart of best-selling products
-   **Revenue by Country** - Doughnut chart of geographic distribution
-   **Top Discount Codes** - Horizontal bar chart of most-used coupons
-   **Customer Segments** - Pie chart of first-time vs repeat customers

### Additional Metrics

-   Shipping Revenue
-   Tax Collected
-   Average Items Per Order

### Date Range Filtering

-   Last 7 Days
-   Last 30 Days (default)
-   Last 90 Days
-   Last Year

## API Endpoints Used

The dashboard consumes the following reporting APIs:

```
GET /api/admin/reports/orders/metrics
GET /api/admin/reports/orders/top-products-by-revenue?limit=10
GET /api/admin/reports/orders/top-products-by-units?limit=10
GET /api/admin/reports/orders/top-discount-codes?limit=10
GET /api/admin/reports/orders/revenue-by-country?limit=10
GET /api/admin/reports/orders/revenue-by-region?limit=10
```

## Technologies Used

-   **Chart.js** - Beautiful, responsive charts
-   **Tailwind CSS** - Modern, utility-first styling
-   **Axios** - HTTP client for API calls
-   **Laravel Blade** - Server-side templating

## Customization

### Change Date Range Options

Edit `/workbench/resources/views/reports-dashboard.blade.php`:

```html
<select id="dateRange">
    <option value="7">Last 7 Days</option>
    <option value="30" selected>Last 30 Days</option>
    <option value="90">Last 90 Days</option>
    <option value="365">Last Year</option>
    <!-- Add your custom range -->
</select>
```

### Add More Charts

1. Add a new chart canvas in the HTML:

```html
<div class="bg-white rounded-lg shadow p-6">
    <h3>Your Chart Title</h3>
    <canvas id="yourChartId"></canvas>
</div>
```

2. Create a Chart.js chart in the JavaScript:

```javascript
async function loadYourData() {
    const response = await axios.get(`${API_BASE}/your-endpoint`);
    updateYourChart(response.data);
}

function updateYourChart(data) {
    const ctx = document.getElementById("yourChartId").getContext("2d");
    // ... Chart.js configuration
}
```

3. Add to the refresh function:

```javascript
await Promise.all([
    loadMetrics(),
    loadTopProducts(),
    // ... other loaders
    loadYourData(), // Add here
]);
```

## Troubleshooting

### "No data" showing on charts

**Solution**: Make sure you've run the seeder:

```bash
php artisan db:seed --class=Workbench\\Database\\Seeders\\OrderReportsSeeder
```

### API errors in console

**Solution**: Check that the API routes are registered. Run:

```bash
php artisan route:list | grep reports
```

You should see the reporting endpoints listed.

### Charts not rendering

**Solution**: Check browser console for JavaScript errors. Ensure Chart.js is loading:

```javascript
// Check in browser console
console.log(typeof Chart); // Should output "function"
```

## Sample Data Generated

The seeder creates orders with:

-   **Time range**: Last 90 days
-   **Order distribution**:
    -   90% paid orders
    -   10% pending orders
    -   Various fulfillment stages
-   **Geography**: 8 countries
-   **Discounts**: 30% of orders have coupons applied
-   **Refunds**: 5% of paid orders have refunds
-   **Products**: 1-5 items per order
-   **Shipping**: Realistic tracking numbers and carriers

## Next Steps

1. **Customize metrics** - Add your own KPIs based on business needs
2. **Export functionality** - Add CSV export for reports
3. **Real-time updates** - Implement WebSocket for live metrics
4. **Drill-down views** - Click charts to filter and explore data
5. **Saved reports** - Allow users to save custom report configurations

## Support

For issues or questions:

-   Check the main package documentation: https://laravel-core.netlify.com
-   Review the API tests: `tests/Feature/ReportsApiTest.php`
-   Examine the metrics service: `src/Services/Metrics/OrderMetrics.php`

---

**Built with** ❤️ **using coderstm/laravel-core**
