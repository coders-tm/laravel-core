<?php

namespace Coderstm;

use DateTimeInterface;
use Illuminate\Support\Facades\Config;

class Coderstm
{
    /**
     * The format used for serializing DateTime instances.
     * This format is applied when converting DateTime objects to strings,
     * particularly during array/JSON serialization.
     *
     * @var string
     */
    public static $dateTimeFormat = DateTimeInterface::ATOM;

    /**
     * The user model class name.
     *
     * @var string
     */
    public static $userModel = 'App\\Models\\User';

    /**
     * The subscription user model class name.
     *
     * @var string
     */
    public static $subscriptionUserModel = 'App\\Models\\User';

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
    public static $subscriptionModel = 'Coderstm\\Models\\Subscription';

    /**
     * The default invoice model class name.
     *
     * @var string
     */
    public static $orderModel = 'Coderstm\\Models\\Shop\\Order';

    /**
     * The default plan model class name.
     *
     * @var string
     */
    public static $planModel = 'Coderstm\\Models\\Subscription\\Plan';

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
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * The cached GoCardless client instance.
     *
     * @var \GoCardlessPro\Client
     */
    protected static $gocardlessClient;

    /**
     * The cached PayPal client instance.
     *  @var \Srmklive\PayPal\Services\PayPal
     */
    protected static $paypalClient;

    /**
     * The cached Razorpay client instance.
     *  @var \Razorpay\Api\Api
     */
    protected static $razorpayClient;

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

        static::useSubscriptionUserModel($userModel);

        Config::set('auth.providers.users.model', $userModel);
    }

    /**
     * Set the subscription user model class name.
     *
     * @param  string  $subscriptionUserModel
     * @return void
     */
    public static function useSubscriptionUserModel($subscriptionUserModel)
    {
        static::$subscriptionUserModel = $subscriptionUserModel;
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

        Config::set('auth.providers.admins.model', $adminModel);
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
     * Set the order model class name.
     *
     * @param  string  $orderModel
     * @return void
     */
    public static function useOrderModel($orderModel)
    {
        static::$orderModel = $orderModel;
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

    /**
     * Get the GoCardless client instance.
     *
     * @param  array  $options
     * @return \GoCardlessPro\Client
     */
    public static function gocardless(array $options = []): \GoCardlessPro\Client
    {
        if (static::$gocardlessClient) {
            return static::$gocardlessClient;
        }

        $environment = $options['environment'] ?? config('gocardless.environment', 'sandbox');
        $accessToken = $options['access_token'] ?? config('gocardless.access_token');

        if (! $accessToken) {
            throw new \Exception('GoCardless access token not set. Please configure your GoCardless payment method.');
        }

        $clientOptions = array_merge([
            'environment' => $environment,
            'access_token' => $accessToken,
        ], $options);

        return static::$gocardlessClient = new \GoCardlessPro\Client($clientOptions);
    }

    /**
     * Get the paypal client instance.
     *  @param  array  $options
     * @return \Srmklive\PayPal\Services\PayPal
     */
    public static function paypal(array $options = []): \Srmklive\PayPal\Services\PayPal
    {
        if (static::$paypalClient) {
            return static::$paypalClient;
        }

        $options = array_merge(config('paypal'), $options);

        $provider = new \Srmklive\PayPal\Services\PayPal;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        return static::$paypalClient = $provider;
    }

    /**
     * Get the razorpay client instance.
     *  @param  array  $options
     * @return \Razorpay\Api\Api
     */
    public static function razorpay(array $options = []): \Razorpay\Api\Api
    {
        if (static::$razorpayClient) {
            return static::$razorpayClient;
        }

        $keyId = $options['key_id'] ?? config('razorpay.key_id');
        $keySecret = $options['key_secret'] ?? config('razorpay.key_secret');

        if (empty($keyId) || empty($keySecret)) {
            throw new \Exception('Razorpay key ID and secret are required. Please configure your Razorpay payment method.');
        }

        return static::$razorpayClient = new \Razorpay\Api\Api($keyId, $keySecret);
    }
}
