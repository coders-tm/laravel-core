<?php

namespace Coderstm\Services\Payment;

use Srmklive\PayPal\Services\PayPal;
use Srmklive\PayPal\Traits\PayPalRequest as PayPalAPIRequest;
use Srmklive\PayPal\Traits\PayPalVerifyIPN;

class PaypalClient extends PayPal
{
    use PayPalAPIRequest;
    use PayPalVerifyIPN;

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->httpBodyParam = 'form_params';
        $this->options = [];
        $this->setRequestHeader('Accept', 'application/json');
    }

    protected function setOptions(array $credentials): void
    {
        $this->config['api_url'] = 'https://api-m.paypal.com';
        $this->config['gateway_url'] = 'https://www.paypal.com';
        $this->config['ipn_url'] = 'https://ipnpb.paypal.com/cgi-bin/webscr';
        if ($this->mode === 'sandbox') {
            $this->config['api_url'] = 'https://api-m.sandbox.paypal.com';
            $this->config['gateway_url'] = 'https://www.sandbox.paypal.com';
            $this->config['ipn_url'] = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
        }
        $this->config['payment_action'] = $credentials['payment_action'];
        $this->config['notify_url'] = $credentials['notify_url'];
        $this->config['locale'] = $credentials['locale'];
        $this->config['timeout'] = (float) ($credentials['timeout'] ?? 30);
        $this->config['connect_timeout'] = (float) ($credentials['connect_timeout'] ?? 10);
        $this->config['max_retries'] = (int) ($credentials['max_retries'] ?? 2);
    }

    public function createBillingAgreementToken(array $data): array
    {
        $this->apiEndPoint = 'v1/billing-agreements/agreement-tokens';
        $this->options['json'] = $data;
        $this->verb = 'post';

        return $this->doPayPalRequest();
    }

    public function executeBillingAgreement(string $tokenId): array
    {
        $this->apiEndPoint = 'v1/billing-agreements/agreements';
        $this->options['json'] = ['token_id' => $tokenId];
        $this->verb = 'post';

        return $this->doPayPalRequest();
    }

    public function createReferenceTransaction(array $data): array
    {
        $this->apiEndPoint = 'v1/payments/payment';
        $this->options['json'] = $data;
        $this->verb = 'post';

        return $this->doPayPalRequest();
    }
}
