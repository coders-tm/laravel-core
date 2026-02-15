<?php

namespace Coderstm\Services\Payment;

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoClient
{
    protected $paymentClient;

    protected $preferenceClient;

    public function __construct(array $options = [])
    {
        $accessToken = $options['access_token'] ?? config('mercadopago.access_token');
        if (! $accessToken) {
            throw new \InvalidArgumentException('MercadoPago access_token is required.');
        }
        MercadoPagoConfig::setAccessToken($accessToken);
        $this->paymentClient = new PaymentClient;
        $this->preferenceClient = new PreferenceClient;
    }

    public function createPaymentIntent(array $params)
    {
        if (isset($params['items'])) {
            return $this->preferenceClient->create($params);
        }

        return $this->paymentClient->create($params);
    }

    public function confirmPayment($paymentId)
    {
        return $this->paymentClient->get($paymentId);
    }
}
