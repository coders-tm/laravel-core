<?php

namespace Workbench\App\Providers;

use App\Models\Admin;
use App\Models\User;
use App\Policies\AdminPolicy;
use Coderstm\Coderstm;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Policies\UserPolicy;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Coderstm::useMaskSensitive();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set model classes to the base Coderstm models so Guard::resolveGuardFromModel()
        // can match both base model instances and workbench subclass instances via instanceof.
        Coderstm::useUserModel(\App\Models\User::class);
        Coderstm::useSubscriptionUserModel(\App\Models\User::class);
        Coderstm::useAdminModel(\App\Models\Admin::class);
        Coderstm::useCouponModel(\Workbench\App\Models\Coupon::class);
        Coderstm::useEnquiryModel(\Workbench\App\Models\Enquiry::class);
        Coderstm::usePlanModel(\Workbench\App\Models\Plan::class);
        Coderstm::useSubscriptionModel(\Workbench\App\Models\Subscription::class);
        Coderstm::useOrderModel(\Coderstm\Models\Shop\Order::class);
        Coderstm::useOrderLineItemModel(\Coderstm\Models\Shop\Order\LineItem::class);

        Config::set('cache.default', 'array');
        Config::set('mail.default', 'log');
        Config::set('app.country', 'United States');
        Config::set('app.currency_supported', ['USD', 'INR', 'EUR', 'GBP']);

        // Register policies
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(\Coderstm\Models\User::class, UserPolicy::class);
        Gate::policy(Admin::class, AdminPolicy::class);
        Gate::policy(\Coderstm\Models\Admin::class, AdminPolicy::class);
    }
}
