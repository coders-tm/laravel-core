<?php

namespace Coderstm;

use Coderstm\Cashier\Cashier;
use Coderstm\Policies\AdminPolicy;
use Coderstm\Policies\CouponPolicy;
use Coderstm\Policies\EnquiryPolicy;
use Coderstm\Policies\Subscription\PlanPolicy;
use Coderstm\Policies\UserPolicy;
use Coderstm\Services\Payment\FlutterwaveClient;
use Coderstm\Services\Payment\KlarnaClient;
use Coderstm\Services\Payment\MercadoPagoClient;
use Coderstm\Services\Payment\PaypalClient;
use Coderstm\Services\Payment\PayuClient;
use Coderstm\Services\Payment\XenditClient;
use DateTimeInterface;
use GoCardlessPro\Client;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Razorpay\Api\Api;
use Stripe\StripeClient;
use Yabacon\Paystack;

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
     * The default order/invoice model class name.
     *
     * @var string
     */
    public static $orderModel = 'Coderstm\\Models\\Shop\\Order';

    /**
     * The default order line item model class name.
     *
     * @var string
     */
    public static $orderLineItemModel = 'Coderstm\\Models\\Shop\\Order\\LineItem';

    /**
     * The default order discount line model class name.
     *
     * @var string
     */
    public static $orderDiscountLineModel = 'Coderstm\\Models\\Shop\\Order\\DiscountLine';

    /**
     * The default order tax line model class name.
     *
     * @var string
     */
    public static $orderTaxLineModel = 'Coderstm\\Models\\Shop\\Order\\TaxLine';

    /**
     * The default order contact model class name.
     *
     * @var string
     */
    public static $orderContactModel = 'Coderstm\\Models\\Shop\\Order\\Contact';

    /**
     * The default customer model class name.
     *
     * @var string
     */
    public static $customerModel = 'Coderstm\\Models\\Shop\\Order\\Customer';

    /**
     * The default plan model class name.
     *
     * @var string
     */
    public static $planModel = 'Coderstm\\Models\\Subscription\\Plan';

    /**
     * The default coupon model class name.
     *
     * @var string
     */
    public static $couponModel = 'Coderstm\\Models\\Coupon';

    /**
     * The default payment model class name.
     *
     * @var string
     */
    public static $paymentModel = 'Coderstm\\Models\\Payment';

    /**
     * The default refund model class name.
     *
     * @var string
     */
    public static $refundModel = 'Coderstm\\Models\\Refund';

    /**
     * The default payment method model class name.
     *
     * @var string
     */
    public static $paymentMethodModel = 'Coderstm\\Models\\PaymentMethod';

    /**
     * The default address model class name.
     *
     * @var string
     */
    public static $addressModel = 'Coderstm\\Models\\Address';

    /**
     * The default file model class name.
     *
     * @var string
     */
    public static $fileModel = 'Coderstm\\Models\\File';

    /**
     * The default task model class name.
     *
     * @var string
     */
    public static $taskModel = 'Coderstm\\Models\\Task';

    /**
     * The default blog model class name.
     *
     * @var string
     */
    public static $blogModel = 'Coderstm\\Models\\Blog';

    /**
     * The default log model class name.
     *
     * @var string
     */
    public static $logModel = 'Coderstm\\Models\\Log';

    /**
     * The default group model class name.
     *
     * @var string
     */
    public static $groupModel = 'Coderstm\\Models\\Group';

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
     * Indicates if cart functionality should be enabled.
     *
     * @var bool
     */
    public static $enablesCart = true;

    /**
     * Indicates if MaskSensitiveConfig should be used as the global Blade compiler.
     *
     * @var bool
     */
    public static $usesMaskSensitive = false;

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
     * @var Client
     */
    protected static $gocardlessClient;

    /**
     * The cached PaypalClient client instance.
     *
     * @var PaypalClient
     */
    protected static $paypalClient;

    /**
     * The cached PayuClient client instance.
     *
     * @var PayuClient
     */
    protected static $payuClient;

    /**
     * The cached Razorpay client instance.
     *
     * @var Api
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
     * Determine if cart functionality should be enabled.
     *
     * @return bool
     */
    public static function shouldEnableCart()
    {
        return static::$enablesCart;
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
     * Configure Coderstm to use MaskSensitiveConfig as the global Blade compiler.
     *
     * @return static
     */
    public static function useMaskSensitive()
    {
        static::$usesMaskSensitive = true;

        return new static;
    }

    /**
     * Determine if MaskSensitiveConfig should be used as the global Blade compiler.
     *
     * @return bool
     */
    public static function shouldUseMaskSensitive()
    {
        return static::$usesMaskSensitive;
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

        Gate::policy($userModel, UserPolicy::class);

        // Register morph map for user model
        Relation::morphMap([
            'User' => $userModel,
        ]);
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

        Gate::policy($adminModel, AdminPolicy::class);

        // Register morph map for admin model
        Relation::morphMap([
            'Admin' => $adminModel,
        ]);
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

        Gate::policy($enquiryModel, EnquiryPolicy::class);

        // Register morph map for enquiry model
        Relation::morphMap([
            'Enquiry' => $enquiryModel,
        ]);
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

        // Register morph map for order model
        Relation::morphMap([
            'Order' => $orderModel,
        ]);
    }

    /**
     * Set the order line item model class name.
     *
     * @param  string  $orderLineItemModel
     * @return void
     */
    public static function useOrderLineItemModel($orderLineItemModel)
    {
        static::$orderLineItemModel = $orderLineItemModel;

        // Register morph map for line item model
        Relation::morphMap([
            'LineItem' => $orderLineItemModel,
        ]);
    }

    /**
     * Set the order discount line model class name.
     *
     * @param  string  $orderDiscountLineModel
     * @return void
     */
    public static function useOrderDiscountLineModel($orderDiscountLineModel)
    {
        static::$orderDiscountLineModel = $orderDiscountLineModel;

        // Register morph map for discount line model
        Relation::morphMap([
            'DiscountLine' => $orderDiscountLineModel,
        ]);
    }

    /**
     * Set the order tax line model class name.
     *
     * @param  string  $orderTaxLineModel
     * @return void
     */
    public static function useOrderTaxLineModel($orderTaxLineModel)
    {
        static::$orderTaxLineModel = $orderTaxLineModel;

        // Register morph map for tax line model
        Relation::morphMap([
            'TaxLine' => $orderTaxLineModel,
        ]);
    }

    /**
     * Set the order contact model class name.
     *
     * @param  string  $orderContactModel
     * @return void
     */
    public static function useOrderContactModel($orderContactModel)
    {
        static::$orderContactModel = $orderContactModel;

        // Register morph map for contact model
        Relation::morphMap([
            'Contact' => $orderContactModel,
        ]);
    }

    /**
     * Set the customer model class name.
     *
     * @param  string  $customerModel
     * @return void
     */
    public static function useCustomerModel($customerModel)
    {
        static::$customerModel = $customerModel;

        // Register morph map for customer model
        Relation::morphMap([
            'Customer' => $customerModel,
        ]);
    }

    /**
     * Set the order customer model class name (alias).
     *
     * @param  string  $customerModel
     * @return void
     */
    public static function useOrderCustomerModel($customerModel)
    {
        static::useCustomerModel($customerModel);
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

        // Register morph map for subscription model
        Relation::morphMap([
            'Subscription' => $subscriptionModel,
        ]);
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

        Gate::policy($planModel, PlanPolicy::class);

        // Register morph map for plan model
        Relation::morphMap([
            'Plan' => $planModel,
        ]);
    }

    /**
     * Set the coupon model class name.
     *
     * @param  string  $couponModel
     * @return void
     */
    public static function useCouponModel($couponModel)
    {
        static::$couponModel = $couponModel;

        Gate::policy($couponModel, CouponPolicy::class);

        // Register morph map for coupon model
        Relation::morphMap([
            'Coupon' => $couponModel,
        ]);
    }

    /**
     * Set the payment model class name.
     *
     * @param  string  $paymentModel
     * @return void
     */
    public static function usePaymentModel($paymentModel)
    {
        static::$paymentModel = $paymentModel;

        // Register morph map for payment model
        Relation::morphMap([
            'Payment' => $paymentModel,
        ]);
    }

    /**
     * Set the refund model class name.
     *
     * @param  string  $refundModel
     * @return void
     */
    public static function useRefundModel($refundModel)
    {
        static::$refundModel = $refundModel;

        // Register morph map for refund model
        Relation::morphMap([
            'Refund' => $refundModel,
        ]);
    }

    /**
     * Set the payment method model class name.
     *
     * @param  string  $paymentMethodModel
     * @return void
     */
    public static function usePaymentMethodModel($paymentMethodModel)
    {
        static::$paymentMethodModel = $paymentMethodModel;

        // Register morph map for payment method model
        Relation::morphMap([
            'PaymentMethod' => $paymentMethodModel,
        ]);
    }

    /**
     * Set the address model class name.
     *
     * @param  string  $addressModel
     * @return void
     */
    public static function useAddressModel($addressModel)
    {
        static::$addressModel = $addressModel;

        // Register morph map for address model
        Relation::morphMap([
            'Address' => $addressModel,
        ]);
    }

    /**
     * Set the file model class name.
     *
     * @param  string  $fileModel
     * @return void
     */
    public static function useFileModel($fileModel)
    {
        static::$fileModel = $fileModel;

        // Register morph map for file model
        Relation::morphMap([
            'File' => $fileModel,
        ]);
    }

    /**
     * Set the task model class name.
     *
     * @param  string  $taskModel
     * @return void
     */
    public static function useTaskModel($taskModel)
    {
        static::$taskModel = $taskModel;

        // Register morph map for task model
        Relation::morphMap([
            'Task' => $taskModel,
        ]);
    }

    /**
     * Set the blog model class name.
     *
     * @param  string  $blogModel
     * @return void
     */
    public static function useBlogModel($blogModel)
    {
        static::$blogModel = $blogModel;

        // Register morph map for blog model
        Relation::morphMap([
            'Blog' => $blogModel,
        ]);
    }

    /**
     * Set the log model class name.
     *
     * @param  string  $logModel
     * @return void
     */
    public static function useLogModel($logModel)
    {
        static::$logModel = $logModel;

        // Register morph map for log model
        Relation::morphMap([
            'Log' => $logModel,
        ]);
    }

    /**
     * Set the group model class name.
     *
     * @param  string  $groupModel
     * @return void
     */
    public static function useGroupModel($groupModel)
    {
        static::$groupModel = $groupModel;

        // Register morph map for group model
        Relation::morphMap([
            'Group' => $groupModel,
        ]);
    }

    /**
     * Register default morph map for all Coderstm models.
     *
     * @return void
     */
    public static function registerMorphMap()
    {
        Relation::morphMap([
            'User' => static::$userModel,
            'Admin' => static::$adminModel,
            'Enquiry' => static::$enquiryModel,
            'Subscription' => static::$subscriptionModel,
            'Order' => static::$orderModel,
            'LineItem' => static::$orderLineItemModel,
            'DiscountLine' => static::$orderDiscountLineModel,
            'TaxLine' => static::$orderTaxLineModel,
            'Contact' => static::$orderContactModel,
            'Customer' => static::$customerModel,
            'Plan' => static::$planModel,
            'Coupon' => static::$couponModel,
            'Payment' => static::$paymentModel,
            'Refund' => static::$refundModel,
            'PaymentMethod' => static::$paymentMethodModel,
            'Address' => static::$addressModel,
            'File' => static::$fileModel,
            'Task' => static::$taskModel,
            'Blog' => static::$blogModel,
            'Log' => static::$logModel,
            'Group' => static::$groupModel,
        ]);
    }

    /**
     * Set app short codes.
     *
     * @return void
     */
    public static function useAppShortCodes(array $appShortCodes)
    {
        static::$appShortCodes = $appShortCodes;
    }

    /**
     * Get the GoCardless client instance.
     *
     * @return Client
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

        return static::$gocardlessClient = new Client($clientOptions);
    }

    /**
     * Get the paypal client instance.
     *
     * @return PaypalClient
     */
    public static function paypal(array $options = [])
    {
        if (static::$paypalClient) {
            return static::$paypalClient;
        }

        $options = array_merge(config('paypal'), $options);

        $provider = new PaypalClient;
        $provider->setApiCredentials($options);
        $provider->getAccessToken();

        return static::$paypalClient = $provider;
    }

    /**
     * Get the razorpay client instance.
     *
     * @return Api
     */
    public static function razorpay(array $options = [])
    {
        if (static::$razorpayClient) {
            return static::$razorpayClient;
        }

        $keyId = $options['key_id'] ?? config('razorpay.key_id');
        $keySecret = $options['key_secret'] ?? config('razorpay.key_secret');

        return static::$razorpayClient = new Api($keyId, $keySecret);
    }

    /**
     * Get the PayU client instance.
     *
     * @return PayuClient
     */
    public static function payu(array $options = [])
    {
        if (static::$payuClient) {
            return static::$payuClient;
        }

        $options = array_merge(config('payu', []), $options);

        if (empty($options['merchant_key']) || empty($options['merchant_salt'])) {
            throw new \InvalidArgumentException('PayU credentials are not configured.');
        }

        return static::$payuClient = new PayuClient($options);
    }

    /**
     * The cached Stripe client instance.
     *
     * @var StripeClient|null
     */
    protected static $stripeClient;

    /**
     * Get the Stripe client instance.
     *
     * @return StripeClient
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
     *
     * @var KlarnaClient|null
     */
    protected static $klarnaClient;

    /**
     * Get the Klarna client instance (custom Guzzle-based client).
     *
     * @return KlarnaClient|null
     */
    public static function klarna(array $options = [])
    {
        if (static::$klarnaClient) {
            return static::$klarnaClient;
        }

        return static::$klarnaClient = new KlarnaClient($options);
    }

    /**
     * The cached MercadoPago client instance.
     *
     * @var MercadoPagoClient|null
     */
    protected static $mercadopagoClient;

    /**
     * Get the MercadoPago client instance (custom client).
     *
     * @return MercadoPagoClient|null
     */
    public static function mercadopago(array $options = [])
    {
        if (static::$mercadopagoClient) {
            return static::$mercadopagoClient;
        }

        return static::$mercadopagoClient = new MercadoPagoClient($options);
    }

    /**
     * The cached Paystack client instance.
     *
     * @var Paystack|null
     */
    protected static $paystackClient;

    /**
     * Get the Paystack client instance.
     *
     * @return Paystack|null
     */
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

    /**
     * The cached Xendit client instance.
     *
     * @var XenditClient|null
     */
    protected static $xenditClient;

    /**
     * Get the Xendit client instance (custom client).
     *
     * @return XenditClient|null
     */
    public static function xendit(array $options = [])
    {
        if (static::$xenditClient) {
            return static::$xenditClient;
        }

        return static::$xenditClient = new XenditClient($options);
    }

    /**
     * The cached Flutterwave client instance.
     *
     * @var FlutterwaveClient|null
     */
    protected static $flutterwaveClient;

    /**
     * Get the Flutterwave client instance.
     *
     * @return FlutterwaveClient|null
     */
    public static function flutterwave(array $options = [])
    {
        if (static::$flutterwaveClient !== null && empty($options)) {
            return static::$flutterwaveClient;
        }

        $secretKey = $options['secret_key'] ?? config('flutterwave.secret_key');
        if (! $secretKey) {
            return null;
        }

        $client = new FlutterwaveClient($options);

        if (empty($options)) {
            static::$flutterwaveClient = $client;
        }

        return $client;
    }

    /**
     * The cached Apple Pay client instance (via Stripe).
     *
     * @var StripeClient|null
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
     *
     * @var StripeClient|null
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
