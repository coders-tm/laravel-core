<?php

namespace Coderstm\Services\Payment;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class FlutterwaveClient
{
    protected Client $client;

    protected string $secretKey;

    protected ?string $publicKey;

    protected ?string $encryptionKey;

    protected string $baseUrl = 'https://api.flutterwave.com/v3/';

    public function __construct(array $options = [])
    {
        $this->secretKey = $options['secret_key'] ?? Config::get('flutterwave.secret_key', '');
        $this->publicKey = $options['public_key'] ?? Config::get('flutterwave.public_key');
        $this->encryptionKey = $options['encryption_key'] ?? Config::get('flutterwave.encryption_key');

        if (empty($this->secretKey)) {
            throw new \InvalidArgumentException('Flutterwave secret_key is required.');
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Create a standard hosted payment link.
     */
    public function createPayment(array $payload): array
    {
        return $this->post('payments', $payload);
    }

    /**
     * Verify a transaction by ID or reference.
     */
    public function verifyTransaction(string|int $transactionIdOrRef): array
    {
        if (is_numeric($transactionIdOrRef)) {
            return $this->get("transactions/{$transactionIdOrRef}/verify");
        }

        return $this->get('transactions/verify_by_reference', [
            'tx_ref' => (string) $transactionIdOrRef,
        ]);
    }

    /**
     * Requery / verify transaction (alias for compatibility).
     */
    public function requeryTransaction(string|int $referenceNumber): array
    {
        return $this->verifyTransaction($referenceNumber);
    }

    /**
     * Refund a transaction.
     */
    public function refundTransaction(string|int $transactionId, ?float $amount = null): array
    {
        $payload = [];
        if ($amount !== null && $amount > 0) {
            $payload['amount'] = $amount;
        }

        return $this->post("transactions/{$transactionId}/refund", $payload);
    }

    /**
     * Perform a GET request.
     */
    public function get(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->client->get($endpoint, [
                'query' => $query,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            $this->handleException($e, 'GET', $endpoint);
        }
    }

    /**
     * Perform a POST request.
     */
    public function post(string $endpoint, array $payload = []): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            $this->handleException($e, 'POST', $endpoint);
        }
    }

    /**
     * Handle request exception with detailed error message.
     */
    protected function handleException(RequestException $e, string $method, string $endpoint): void
    {
        $message = $e->getMessage();
        if ($e->hasResponse()) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = $body['message'] ?? $message;
        }

        Log::error("Flutterwave API [{$method} {$endpoint}] Error: {$message}");

        throw new \Exception("Flutterwave API Error: {$message}", $e->getCode(), $e);
    }
}
