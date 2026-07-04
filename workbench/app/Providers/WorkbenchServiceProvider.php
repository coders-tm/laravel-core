<?php

namespace Workbench\App\Providers;

use App\Models\Admin;
use App\Models\User;
use App\Policies\AdminPolicy;
use Coderstm\Coderstm;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Shop\Order\LineItem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Models\Coupon;
use Workbench\App\Models\Enquiry;
use Workbench\App\Models\Plan;
use Workbench\App\Models\Subscription;
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
        Coderstm::useUserModel(User::class);
        Coderstm::useSubscriptionUserModel(User::class);
        Coderstm::useAdminModel(Admin::class);
        Coderstm::useCouponModel(Coupon::class);
        Coderstm::useEnquiryModel(Enquiry::class);
        Coderstm::usePlanModel(Plan::class);
        Coderstm::useSubscriptionModel(Subscription::class);
        Coderstm::useOrderModel(Order::class);
        Coderstm::useOrderLineItemModel(LineItem::class);

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
