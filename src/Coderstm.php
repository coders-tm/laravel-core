<?php

namespace Coderstm;

use Coderstm\Services\Payment\KlarnaClient;
use Coderstm\Services\Payment\MercadoPagoClient;
use Coderstm\Services\Payment\XenditClient;
use DateTimeInterface;
use Flutterwave\Config\PackageConfig;
use Flutterwave\Flutterwave;
use GoCardlessPro\Client;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Cashier;
use Razorpay\Api\Api;
use Srmklive\PayPal\Services\PayPal;
use Yabacon\Paystack;
use Yansongda\Pay\Pay;

class Coderstm
{
    public static $dateTimeFormat = DateTimeInterface::ATOM;

    public static $userModel = 'App\\Models\\User';

    public static $subscriptionUserModel = 'App\\Models\\User';

    public static $adminModel = 'App\\Models\\Admin';

    public static $enquiryModel = 'App\\Models\\Enquiry';

    public static $subscriptionModel = 'Coderstm\\Models\\Subscription';

    public static $orderModel = 'Coderstm\\Models\\Shop\\Order';

    public static $planModel = 'Coderstm\\Models\\Subscription\\Plan';

    public static $couponModel = 'Coderstm\\Models\\Coupon';

    public static $runsMigrations = true;

    public static $registersRoutes = true;

    public static $enablesCart = true;

    public static $usesMaskSensitive = false;

    public static $appShortCodes = [];

    protected static $formatCurrencyUsing;

    protected static $gocardlessClient;

    protected static $paypalClient;

    protected static $razorpayClient;

    public static function shouldRunMigrations()
    {
        return static::$runsMigrations;
    }

    public static function shouldRegistersRoutes()
    {
        return static::$registersRoutes;
    }

    public static function shouldEnableCart()
    {
        return static::$enablesCart;
    }

    public static function ignoreRoutes()
    {
        static::$registersRoutes = false;

        return new static;
    }

    public static function useMaskSensitive()
    {
        static::$usesMaskSensitive = true;

        return new static;
    }

    public static function shouldUseMaskSensitive()
    {
        return static::$usesMaskSensitive;
    }

    public static function withoutCart()
    {
        static::$enablesCart = false;

        return new static;
    }

    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }

    public static function useUserModel($userModel)
    {
        static::$userModel = $userModel;
        static::useSubscriptionUserModel($userModel);
        Config::set('auth.providers.users.model', $userModel);
    }

    public static function useSubscriptionUserModel($subscriptionUserModel)
    {
        static::$subscriptionUserModel = $subscriptionUserModel;
    }

    public static function useAdminModel($adminModel)
    {
        static::$adminModel = $adminModel;
        Config::set('auth.providers.admins.model', $adminModel);
    }

    public static function useEnquiryModel($enquiryModel)
    {
        static::$enquiryModel = $enquiryModel;
    }

    public static function useOrderModel($orderModel)
    {
        static::$orderModel = $orderModel;
    }

    public static function useSubscriptionModel($subscriptionModel)
    {
        static::$subscriptionModel = $subscriptionModel;
    }

    public static function usePlanModel($planModel)
    {
        static::$planModel = $planModel;
    }

    public static function useCouponModel($couponModel)
    {
        static::$couponModel = $couponModel;
    }

    public static function useAppShortCodes(array $appShortCodes)
    {
        static::$appShortCodes = $appShortCodes;
    }

    public static function gocardless(array $options = [])
    {
        if (static::$gocardlessClient) {
            return static::$gocardlessClient;
        }
        $environment = $options['environment'] ?? config('gocardless.environment', 'sandbox');
        $accessToken = $options['access_token'] ?? config('gocardless.access_token');
        $clientOptions = array_merge(['environment' => $environment, 'access_token' => $accessToken], $options);

        return static::$gocardlessClient = new Client($clientOptions);
    }

    public static function paypal(array $options = [])
    {
        if (static::$paypalClient) {
            return static::$paypalClient;
        }
        $options = array_merge(config('paypal'), $options);
        $provider = new PayPal;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        return static::$paypalClient = $provider;
    }

    public static function razorpay(array $options = [])
    {
        if (static::$razorpayClient) {
            return static::$razorpayClient;
        }
        $keyId = $options['key_id'] ?? config('razorpay.key_id');
        $keySecret = $options['key_secret'] ?? config('razorpay.key_secret');

        return static::$razorpayClient = new Api($keyId, $keySecret);
    }

    protected static $stripeClient;

    public static function stripe(array $options = [])
    {
        if (static::$stripeClient) {
            return static::$stripeClient;
        }

        return static::$stripeClient = Cashier::stripe($options);
    }

    protected static $klarnaClient;

    public static function klarna(array $options = [])
    {
        if (static::$klarnaClient) {
            return static::$klarnaClient;
        }

        return static::$klarnaClient = new KlarnaClient($options);
    }

    protected static $mercadopagoClient;

    public static function mercadopago(array $options = [])
    {
        if (static::$mercadopagoClient) {
            return static::$mercadopagoClient;
        }

        return static::$mercadopagoClient = new MercadoPagoClient($options);
    }

    protected static $paystackClient;

    public static function paystack(array $options = [])
    {
        if (static::$paystackClient) {
            return static::$paystackClient;
        }
        $secretKey = $options['secret_key'] ?? config('paystack.secret_key');
        if ($secretKey) {
            return static::$paystackClient = new Paystack($secretKey);
        }

        return null;
    }

    protected static $xenditClient;

    public static function xendit(array $options = [])
    {
        if (static::$xenditClient) {
            return static::$xenditClient;
        }

        return static::$xenditClient = new XenditClient($options);
    }

    protected static $flutterwaveClient;

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
            if (! defined('FLW_SECRET_KEY')) {
                define('FLW_SECRET_KEY', $secretKey);
            }
            if ($publicKey && ! defined('FLW_PUBLIC_KEY')) {
                define('FLW_PUBLIC_KEY', $publicKey);
            }
            if ($encryptionKey && ! defined('FLW_ENCRYPTION_KEY')) {
                define('FLW_ENCRYPTION_KEY', $encryptionKey);
            }
            if (! defined('FLW_ENV')) {
                define('FLW_ENV', $environment);
            }
            $config = PackageConfig::setUp($secretKey, $publicKey, $encryptionKey, $environment);
            if (! defined('FLW_LOGS_PATH')) {
                define('FLW_LOGS_PATH', storage_path('logs'));
            }
            Flutterwave::bootstrap($config);

            return static::$flutterwaveClient = new Flutterwave;
        }

        return static::$flutterwaveClient = null;
    }

    protected static $applePayClient;

    public static function applePay(array $options = [])
    {
        if (static::$applePayClient) {
            return static::$applePayClient;
        }

        return static::$applePayClient = static::stripe($options);
    }

    protected static $googlePayClient;

    public static function googlePay(array $options = [])
    {
        if (static::$googlePayClient) {
            return static::$googlePayClient;
        }

        return static::$googlePayClient = static::stripe($options);
    }

    protected static $alipayClient;

    public static function alipay(array $options = [])
    {
        if (static::$alipayClient) {
            return static::$alipayClient;
        }
        $config = config('alipay');
        if ($config && ! empty($config['app_id'])) {
            Pay::config(['alipay' => ['default' => ['app_id' => $config['app_id'], 'ali_public_key' => $config['ali_public_key'], 'private_key' => $config['private_key'], 'notify_url' => $config['webhook_url'], 'mode' => $config['mode'] === 'sandbox' ? Pay::MODE_SANDBOX : Pay::MODE_NORMAL]], 'logger' => ['enable' => true, 'file' => storage_path('logs/alipay.log'), 'level' => 'debug', 'type' => 'daily', 'max_file' => 30]]);

            return static::$alipayClient = Pay::alipay();
        }

        return static::$alipayClient = null;
    }
}
