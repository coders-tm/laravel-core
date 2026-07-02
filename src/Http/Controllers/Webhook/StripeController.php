<?php

namespace Coderstm\Http\Controllers\Webhook;

use Coderstm\Cashier\Events\WebhookHandled;
use Coderstm\Cashier\Events\WebhookReceived;
use Coderstm\Cashier\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Response;

class StripeController extends Controller
{
    public function __construct()
    {
        if (config('stripe.webhook.secret')) {
            $this->middleware(VerifyWebhookSignature::class);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $method = 'handle'.Str::studly(str_replace('.', '_', $payload['type']));
        WebhookReceived::dispatch($payload);
        if (method_exists($this, $method)) {
            $this->setMaxNetworkRetries();
            $response = $this->{$method}($payload);
            WebhookHandled::dispatch($payload);

            return $response;
        }

        return $this->missingMethod($payload);
    }

    protected function successMethod($parameters = [])
    {
        return new Response('Webhook Handled', 200);
    }

    protected function missingMethod($parameters = [])
    {
        return new Response;
    }

    protected function setMaxNetworkRetries($retries = 3)
    {
        Stripe::setMaxNetworkRetries($retries);
    }
}
