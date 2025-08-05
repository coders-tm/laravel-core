<?php

namespace Coderstm;

use DateTimeInterface;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Cashier;

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
    public static function gocardless(array $options = [])
    {
        if (static::$gocardlessClient) {
            return static::$gocardlessClient;
        }

        $environment = $options['environment'] ?? config('gocardless.environment', 'sandbox');
        $accessToken = $options['access_token'] ?? config('gocardless.access_token');

        $clientOptions = array_merge([
            'environment' => $environment,
            'access_token' => $accessToken,
        ], $options);

        return static::$gocardlessClient = new \GoCardlessPro\Client($clientOptions);
    }

    /**
     * Get the paypal client instance.
     *
     * @param  array  $options
     * @return \Srmklive\PayPal\Services\PayPal
     */
    public static function paypal(array $options = [])
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
     *
     * @param  array  $options
     * @return \Razorpay\Api\Api
     */
    public static function razorpay(array $options = [])
    {
        if (static::$razorpayClient) {
            return static::$razorpayClient;
        }

        $keyId = $options['key_id'] ?? config('razorpay.key_id');
        $keySecret = $options['key_secret'] ?? config('razorpay.key_secret');

        return static::$razorpayClient = new \Razorpay\Api\Api($keyId, $keySecret);
    }


    /**
     * The cached Stripe client instance.
     * @var \Stripe\StripeClient|null
     */
    protected static $stripeClient;

    /**
     * Get the Stripe client instance.
     *
     * @param  array  $options
     * @return \Stripe\StripeClient
     */
    public static function stripe(array $options = [])
    {
        if (static::$stripeClient) {
            return static::$stripeClient;
        }
        return static::$stripeClient = Cashier::stripe($options);
    }


    /**
     * The cached Klarna client instance.
     * @var \Coderstm\Services\Payment\KlarnaClient|null
     */
    protected static $klarnaClient;

    /**
     * Get the Klarna client instance (custom Guzzle-based client).
     *
     * @param  array  $options
     * @return \Coderstm\Services\Payment\KlarnaClient|null
     */
    public static function klarna(array $options = [])
    {
        if (static::$klarnaClient) {
            return static::$klarnaClient;
        }
        return static::$klarnaClient = new \Coderstm\Services\Payment\KlarnaClient($options);
    }


    /**
     * The cached MercadoPago client instance.
     * @var \Coderstm\Services\Payment\MercadoPagoClient|null
     */
    protected static $mercadopagoClient;

    /**
     * Get the MercadoPago client instance (custom client).
     *
     * @param  array  $options
     * @return \Coderstm\Services\Payment\MercadoPagoClient|null
     */
    public static function mercadopago(array $options = [])
    {
        if (static::$mercadopagoClient) {
            return static::$mercadopagoClient;
        }
        return static::$mercadopagoClient = new \Coderstm\Services\Payment\MercadoPagoClient($options);
    }


    /**
     * The cached Paystack client instance.
     * @var \Yabacon\Paystack|null
     */
    protected static $paystackClient;

    /**
     * Get the Paystack client instance.
     *
     * @param  array  $options
     * @return \Yabacon\Paystack|null
     */
    public static function paystack(array $options = [])
    {
        if (static::$paystackClient) {
            return static::$paystackClient;
        }
        $secretKey = $options['secret_key'] ?? config('paystack.secret_key');
        if ($secretKey) {
            return static::$paystackClient = new \Yabacon\Paystack($secretKey);
        }
        return null;
    }


    /**
     * The cached Xendit client instance.
     * @var \Coderstm\Services\Payment\XenditClient|null
     */
    protected static $xenditClient;

    /**
     * Get the Xendit client instance (custom client).
     *
     * @param  array  $options
     * @return \Coderstm\Services\Payment\XenditClient|null
     */
    public static function xendit(array $options = [])
    {
        if (static::$xenditClient) {
            return static::$xenditClient;
        }
        return static::$xenditClient = new \Coderstm\Services\Payment\XenditClient($options);
    }


    /**
     * The cached Flutterwave client instance.
     * @var \Flutterwave\Flutterwave|null
     */
    protected static $flutterwaveClient;

    /**
     * Get the Flutterwave client instance (official SDK v3).
     * This method initializes the Flutterwave SDK with credentials.
     *
     * @param  array  $options
     * @return \Flutterwave\Flutterwave|null
     */
    public static function flutterwave(array $options = [])
    {
        if (static::$flutterwaveClient !== null) {
            return static::$flutterwaveClient;
        }

        $publicKey = $options['public_key'] ?? config('flutterwave.public_key');
        $secretKey = $options['secret_key'] ?? config('flutterwave.secret_key');
        $encryptionKey = $options['encryption_key'] ?? config('flutterwave.encryption_key');
        $environment = $options['environment'] ?? config('flutterwave.environment', 'sandbox');

        if ($secretKey) {
            // Set environment variables that the SDK expects
            if (!defined('FLW_SECRET_KEY')) {
                define('FLW_SECRET_KEY', $secretKey);
            }
            if ($publicKey && !defined('FLW_PUBLIC_KEY')) {
                define('FLW_PUBLIC_KEY', $publicKey);
            }
            if ($encryptionKey && !defined('FLW_ENCRYPTION_KEY')) {
                define('FLW_ENCRYPTION_KEY', $encryptionKey);
            }
            if (!defined('FLW_ENV')) {
                define('FLW_ENV', $environment);
            }

            // Create and configure the Flutterwave client
            $config = \Flutterwave\Config\PackageConfig::setUp(
                $secretKey,
                $publicKey,
                $encryptionKey,
                $environment
            );

            // Set Laravel logs path for Flutterwave SDK
            if (!defined('FLW_LOGS_PATH')) {
                define('FLW_LOGS_PATH', storage_path('logs'));
            }

            \Flutterwave\Flutterwave::bootstrap($config);

            return static::$flutterwaveClient = new \Flutterwave\Flutterwave();
        }

        return static::$flutterwaveClient = null;
    }

    /**
     * The cached Apple Pay client instance (via Stripe).
     * @var \Stripe\StripeClient|null
     */
    protected static $applePayClient;

    /**
     * Apple Pay is integrated via Stripe. Use the cached stripe() client for Apple Pay operations.
     */
    public static function applePay(array $options = [])
    {
        if (static::$applePayClient) {
            return static::$applePayClient;
        }
        return static::$applePayClient = static::stripe($options);
    }

    /**
     * The cached Google Pay client instance (via Stripe).
     * @var \Stripe\StripeClient|null
     */
    protected static $googlePayClient;

    /**
     * Google Pay is integrated via Stripe. Use the cached stripe() client for Google Pay operations.
     */
    public static function googlePay(array $options = [])
    {
        if (static::$googlePayClient) {
            return static::$googlePayClient;
        }
        return static::$googlePayClient = static::stripe($options);
    }
}
