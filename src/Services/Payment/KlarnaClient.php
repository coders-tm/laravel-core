<?php

namespace Coderstm\Services\Payment;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;

class KlarnaClient
{
    protected $client;

    protected $username;

    protected $password;

    protected $baseUrl;

    public function __construct($options = [])
    {
        $this->username = $options['username'] ?? Config::get('klarna.api_key');
        $this->password = $options['password'] ?? Config::get('klarna.api_secret');
        $testMode = $options['test_mode'] ?? Config::get('klarna.test_mode', false);
        $this->baseUrl = rtrim($options['base_url'] ?? ($testMode ? 'https://api.playground.klarna.com' : 'https://api.klarna.com'), '/');
        if (empty($this->username) || empty($this->password)) {
            throw new \InvalidArgumentException('Klarna API credentials are required.');
        }
        $this->client = new Client(['base_uri' => $this->baseUrl, 'auth' => [$this->username, $this->password], 'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json']]);
    }

    public function createSession(array $sessionData)
    {
        try {
            $response = $this->client->post('/payments/v1/sessions', ['json' => $sessionData]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $msg = $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= ' Response: '.$e->getResponse()->getBody()->getContents();
            }
            throw new \Exception('Failed to create Klarna session: '.$msg, $e->getCode());
        }
    }

    public function updateSession(string $sessionId, array $sessionData)
    {
        try {
            $this->client->post("/payments/v1/sessions/{$sessionId}", ['json' => $sessionData]);
        } catch (RequestException $e) {
            $msg = $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= ' Response: '.$e->getResponse()->getBody()->getContents();
            }
            throw new \Exception('Failed to update Klarna session: '.$msg, $e->getCode());
        }
    }

    public function getSession(string $sessionId)
    {
        try {
            $response = $this->client->get("/payments/v1/sessions/{$sessionId}");

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $msg = $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= ' Response: '.$e->getResponse()->getBody()->getContents();
            }
            throw new \Exception('Failed to get Klarna session: '.$msg, $e->getCode());
        }
    }

    public function createOrder(string $authorizationToken, array $orderData)
    {
        try {
            $response = $this->client->post("/payments/v1/authorizations/{$authorizationToken}/order", ['json' => $orderData]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $msg = $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= ' Response: '.$e->getResponse()->getBody()->getContents();
            }
            throw new \Exception('Failed to create Klarna order: '.$msg, $e->getCode());
        }
    }

    public function cancelAuthorization(string $authorizationToken)
    {
        try {
            $this->client->delete("/payments/v1/authorizations/{$authorizationToken}");
        } catch (RequestException $e) {
            throw new \Exception('Failed to cancel Klarna authorization: '.$e->getMessage(), $e->getCode());
        }
    }

    public function generateCustomerToken(string $authorizationToken, array $tokenData)
    {
        try {
            $response = $this->client->post("/payments/v1/authorizations/{$authorizationToken}/customer-token", ['json' => $tokenData]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to generate Klarna customer token: '.$e->getMessage(), $e->getCode());
        }
    }

    public function getOrder(string $orderId)
    {
        try {
            $response = $this->client->get("/ordermanagement/v1/orders/{$orderId}");

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to get Klarna order: '.$e->getMessage(), $e->getCode());
        }
    }

    public function acknowledgeOrder(string $orderId, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->post("/ordermanagement/v1/orders/{$orderId}/acknowledge", ['headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to acknowledge Klarna order: '.$e->getMessage(), $e->getCode());
        }
    }

    public function updateAuthorization(string $orderId, array $updateData, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->patch("/ordermanagement/v1/orders/{$orderId}/authorization", ['json' => $updateData, 'headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to update Klarna order authorization: '.$e->getMessage(), $e->getCode());
        }
    }

    public function updateCustomerDetails(string $orderId, array $customerData, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->patch("/ordermanagement/v1/orders/{$orderId}/customer-details", ['json' => $customerData, 'headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to update Klarna customer details: '.$e->getMessage(), $e->getCode());
        }
    }

    public function extendAuthorizationTime(string $orderId, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->post("/ordermanagement/v1/orders/{$orderId}/extend-authorization-time", ['headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to extend Klarna authorization time: '.$e->getMessage(), $e->getCode());
        }
    }

    public function updateMerchantReferences(string $orderId, array $references, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->patch("/ordermanagement/v1/orders/{$orderId}/merchant-references", ['json' => $references, 'headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to update Klarna merchant references: '.$e->getMessage(), $e->getCode());
        }
    }

    public function releaseRemainingAuthorization(string $orderId, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->post("/ordermanagement/v1/orders/{$orderId}/release-remaining-authorization", ['headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to release Klarna remaining authorization: '.$e->getMessage(), $e->getCode());
        }
    }

    public function addShippingInfo(string $orderId, array $shippingInfo, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->post("/ordermanagement/v1/orders/{$orderId}/shipping-info", ['json' => $shippingInfo, 'headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to add Klarna shipping info: '.$e->getMessage(), $e->getCode());
        }
    }

    public function cancelOrder(string $orderId, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->post("/ordermanagement/v1/orders/{$orderId}/cancel", ['headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to cancel Klarna order: '.$e->getMessage(), $e->getCode());
        }
    }

    public function captureOrder(string $orderId, array $captureData, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $response = $this->client->post("/ordermanagement/v1/orders/{$orderId}/captures", ['json' => $captureData, 'headers' => $headers]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to capture Klarna order: '.$e->getMessage(), $e->getCode());
        }
    }

    public function getCaptures(string $orderId)
    {
        try {
            $response = $this->client->get("/ordermanagement/v1/orders/{$orderId}/captures");

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to get Klarna captures: '.$e->getMessage(), $e->getCode());
        }
    }

    public function getCapture(string $orderId, string $captureId)
    {
        try {
            $response = $this->client->get("/ordermanagement/v1/orders/{$orderId}/captures/{$captureId}");

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to get Klarna capture: '.$e->getMessage(), $e->getCode());
        }
    }

    public function extendDueDate(string $orderId, string $captureId, int $numberOfDays, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->patch("/ordermanagement/v1/orders/{$orderId}/captures/{$captureId}/extend-due-date", ['json' => ['number_of_days' => $numberOfDays], 'headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to extend Klarna due date: '.$e->getMessage(), $e->getCode());
        }
    }

    public function getExtendDueDateOptions(string $orderId, string $captureId)
    {
        try {
            $response = $this->client->get("/ordermanagement/v1/orders/{$orderId}/captures/{$captureId}/extend-due-date-options");

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to get Klarna extend due date options: '.$e->getMessage(), $e->getCode());
        }
    }

    public function addCaptureShippingInfo(string $orderId, string $captureId, array $shippingInfo, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->post("/ordermanagement/v1/orders/{$orderId}/captures/{$captureId}/shipping-info", ['json' => $shippingInfo, 'headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to add Klarna capture shipping info: '.$e->getMessage(), $e->getCode());
        }
    }

    public function triggerSendOut(string $orderId, string $captureId, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $this->client->post("/ordermanagement/v1/orders/{$orderId}/captures/{$captureId}/trigger-send-out", ['headers' => $headers]);
        } catch (RequestException $e) {
            throw new \Exception('Failed to trigger Klarna send out: '.$e->getMessage(), $e->getCode());
        }
    }

    public function refundOrder(string $orderId, array $refundData, ?string $idempotencyKey = null)
    {
        try {
            $headers = [];
            if ($idempotencyKey) {
                $headers['Klarna-Idempotency-Key'] = $idempotencyKey;
            }
            $response = $this->client->post("/ordermanagement/v1/orders/{$orderId}/refunds", ['json' => $refundData, 'headers' => $headers]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to refund Klarna order: '.$e->getMessage(), $e->getCode());
        }
    }

    public function getRefund(string $orderId, string $refundId)
    {
        try {
            $response = $this->client->get("/ordermanagement/v1/orders/{$orderId}/refunds/{$refundId}");

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to get Klarna refund: '.$e->getMessage(), $e->getCode());
        }
    }

    public function buildSessionData(array $params)
    {
        $defaultData = ['acquiring_channel' => 'ECOMMERCE', 'intent' => 'buy'];
        $required = ['order_amount', 'order_lines', 'purchase_country', 'purchase_currency'];
        foreach ($required as $field) {
            if (! isset($params[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing.");
            }
        }

        return array_merge($defaultData, $params);
    }

    public function buildOrderData(array $params)
    {
        $defaultData = ['auto_capture' => false];
        $required = ['order_amount', 'order_lines', 'purchase_country', 'purchase_currency'];
        foreach ($required as $field) {
            if (! isset($params[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing.");
            }
        }

        return array_merge($defaultData, $params);
    }

    public function buildCaptureData(array $params)
    {
        if (! isset($params['captured_amount'])) {
            throw new \InvalidArgumentException("Required field 'captured_amount' is missing.");
        }

        return $params;
    }

    public function buildRefundData(array $params)
    {
        if (! isset($params['refunded_amount'])) {
            throw new \InvalidArgumentException("Required field 'refunded_amount' is missing.");
        }

        return $params;
    }
}
