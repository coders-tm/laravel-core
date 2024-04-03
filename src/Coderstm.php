<?php

namespace Coderstm;

use Laravel\Cashier\Cashier;

class Coderstm
{
    /**
     * The uaer model class name.
     *
     * @var string
     */
    public static $userModel = 'App\\Models\\User';

    /**
     * The default admin model class name.
     *
     * @var string
     */
    public static $adminModel = 'App\\Models\\Admin';

    /**
     * The default enquiry model class name.
     *
     * @var string
     */
    public static $enquiryModel = 'App\\Models\\Enquiry';

    /**
     * The default subscription model class name.
     *
     * @var string
     */
    public static $subscriptionModel = 'Coderstm\\Models\\Cashier\\Subscription';

    /**
     * The default invoice model class name.
     *
     * @var string
     */
    public static $invoiceModel = 'Coderstm\\Models\\Cashier\\Invoice';

    /**
     * The default plan model class name.
     *
     * @var string
     */
    public static $planModel = 'Coderstm\\Models\\Plan';

    /**
     * Indicates if Coderstm's migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Indicates if Coderstm's routes will be register.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    /**
     *  app short codes.
     *
     * @var bool
     */
    public static $appShortCodes = [];

    /**
     * Determine if Coderstm's migrations should be run.
     *
     * @return bool
     */
    public static function shouldRunMigrations()
    {
        return static::$runsMigrations;
    }

    /**
     * Determine if Coderstm's routes will be register.
     *
     * @return bool
     */
    public static function shouldRegistersRoutes()
    {
        return static::$registersRoutes;
    }

    /**
     * Configure Coderstm to not register it's routes.
     *
     * @return bool
     */
    public static function ignoreRoutes()
    {
        static::$registersRoutes = false;

        return new static;
    }

    /**
     * Configure Coderstm to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }

    /**
     * Set the user model class name.
     *
     * @param  string  $userModel
     * @return void
     */
    public static function useUserModel($userModel)
    {
        static::$userModel = $userModel;
        Cashier::useCustomerModel($userModel);
    }

    /**
     * Set the admin model class name.
     *
     * @param  string  $adminModel
     * @return void
     */
    public static function useAdminModel($adminModel)
    {
        static::$adminModel = $adminModel;
    }

    /**
     * Set the enquiry model class name.
     *
     * @param  string  $enquiryModel
     * @return void
     */
    public static function useEnquiryModel($enquiryModel)
    {
        static::$enquiryModel = $enquiryModel;
    }

    /**
     * Set the invoice model class name.
     *
     * @param  string  $invoiceModel
     * @return void
     */
    public static function useInvoiceModel($invoiceModel)
    {
        static::$invoiceModel = $invoiceModel;
    }

    /**
     * Set the subscription model class name.
     *
     * @param  string  $subscriptionModel
     * @return void
     */
    public static function useSubscriptionModel($subscriptionModel)
    {
        static::$subscriptionModel = $subscriptionModel;
        Cashier::useSubscriptionModel($subscriptionModel);
    }

    /**
     * Set the plan model class name.
     *
     * @param  string  $planModel
     * @return void
     */
    public static function usePlanModel($planModel)
    {
        static::$planModel = $planModel;
    }

    /**
     * Set app short codes.
     *
     * @param  array  $appShortCodes
     * @return void
     */
    public static function useAppShortCodes(array $appShortCodes)
    {
        static::$appShortCodes = $appShortCodes;
    }
}
