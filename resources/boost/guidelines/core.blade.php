# Laravel Core (coderstm/laravel-core)

- Enterprise SaaS/e-commerce Laravel package providing subscription billing, multi-currency shopping, 20+ payment gateways, RBAC, reporting, blog, and wallet — all via service providers, facades (Blog, Shop, Currency), and 39 model traits.
- IMPORTANT: Always use the `search-docs` tool for detailed laravel-core patterns, API usage, payment processor integration, and subscription lifecycle documentation.
- IMPORTANT: Activate the `laravel-core` skill when working with Subscription, Plan, Feature, Coupon, Shop, Cart, Checkout, Order, Payment, Refund, WalletBalance, Blog, Admin, Currency, AppSetting, PaymentMethod, ManagesSubscriptions, Billable, HasWallet, HasFeature, HasPermission, Coderstm facade, auth guards, or any artisan command prefixed with `check:`, `subscriptions:`, `reset:`, or `update:exchange-rates`.
- IMPORTANT: Use `Coderstm::useUserModel()` and related binding methods in the host app's service provider — never reference package model classes directly in application code.
- IMPORTANT: Use `Currency::format()`, `format_currency()`, `user()`, `is_admin()`, and `app_url()` helpers rather than raw Laravel equivalents — they handle multi-guard and multi-currency context correctly.
