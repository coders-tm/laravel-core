<?php

namespace Coderstm\Services\Payment;

use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;
use Xendit\PaymentMethod\PaymentMethodApi;
use Xendit\PaymentMethod\PaymentMethodParameters;
use Xendit\PaymentRequest\PaymentRequestApi;
use Xendit\PaymentRequest\PaymentRequestParameters;

class XenditClient
{
    protected $pmApiInstance;

    protected $prApiInstance;

    protected $invoiceApiInstance;

    public function __construct(array $options = [])
    {
        $secretKey = $options['secret_key'] ?? config('xendit.secret_key');
        if (! $secretKey) {
            throw new \InvalidArgumentException('Xendit secret_key is required.');
        }
        Configuration::setXenditKey($secretKey);
        $this->pmApiInstance = new PaymentMethodApi;
        $this->prApiInstance = new PaymentRequestApi;
        $this->invoiceApiInstance = new InvoiceApi;
    }

    public function createPaymentRequest(array $params)
    {
        $paymentRequestParameters = new PaymentRequestParameters($params);

        return $this->prApiInstance->createPaymentRequest(null, null, null, $paymentRequestParameters);
    }

    public function getPaymentRequest($paymentRequestId)
    {
        return $this->prApiInstance->getPaymentRequestByID($paymentRequestId, null);
    }

    public function createPaymentMethod(array $params)
    {
        $paymentMethodParameters = new PaymentMethodParameters($params);

        return $this->pmApiInstance->createPaymentMethod(null, $paymentMethodParameters);
    }

    public function getPaymentMethod($paymentMethodId)
    {
        return $this->pmApiInstance->getPaymentMethodById($paymentMethodId, null);
    }

    public function createInvoice(array $params)
    {
        $invoiceRequest = new CreateInvoiceRequest($params);

        return $this->invoiceApiInstance->createInvoice($invoiceRequest);
    }

    public function getInvoice($invoiceId)
    {
        return $this->invoiceApiInstance->getInvoiceById($invoiceId);
    }
}
