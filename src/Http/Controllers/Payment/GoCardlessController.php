<?php

namespace Coderstm\Http\Controllers\Payment;

use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Events\GoCardless\FlowCompleted;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\PaymentMethod;
use Coderstm\Services\GatewaySubscriptionFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoCardlessController extends Controller
{
    public function success(Request $request)
    {
        try {
            $subscription = $this->validateAndGetSubscription($request);
            if (! $subscription) {
                Log::warning('GoCardless flow: Subscription not found');

                return $this->redirectWithError('The payment flow was interrupted. Please try again.');
            }
            $subscription->provider = PaymentMethod::GOCARDLESS;
            $flowId = $request->get('redirect_flow_id');
            if (! $flowId) {
                Log::warning('GoCardless flow: Missing redirect_flow_id in callback');

                return $this->redirectWithError('The payment flow was interrupted. Please try again.');
            }
            $service = GatewaySubscriptionFactory::make($subscription);
            $flow = $service->completeSetup($flowId);
            $subscription->status = SubscriptionStatus::PENDING;
            $subscription->save();
            event(new FlowCompleted($subscription, $flow));

            return $this->redirectWithSuccess('Your Direct Debit mandate has been set up successfully. The payment will be processed shortly.');
        } catch (\Throwable $e) {
            Log::error('GoCardless flow error: '.$e->getMessage(), ['trace' => $e->getTraceAsString(), 'request' => $request->all()]);

            return $this->redirectWithError('We encountered an error setting up your Direct Debit: '.$e->getMessage());
        }
    }

    protected function validateAndGetSubscription(Request $request)
    {
        $state = $request->query('state');
        if (! $state) {
            throw new \Exception('Missing state parameter');
        }
        $subscription = Coderstm::$subscriptionModel::find($state);
        if (! $subscription) {
            throw new \Exception('Subscription not found');
        }

        return $subscription;
    }

    protected function redirectWithSuccess($message)
    {
        return redirect(app_url('/billing', ['setup' => 'success', 'message' => $message]));
    }

    protected function redirectWithError($message = 'An error occurred')
    {
        return redirect(app_url('/billing', ['setup' => 'failed', 'message' => $message]));
    }
}
