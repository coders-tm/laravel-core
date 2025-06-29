<?php

namespace Coderstm\Http\Controllers\Payment;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Events\GoCardless\FlowCompleted;
use Coderstm\Models\PaymentMethod;
use Coderstm\Services\GatewaySubscriptionFactory;

class GoCardlessController extends Controller
{
    /**
     * Handle the redirect after successful GoCardless flow completion
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function success(Request $request)
    {
        try {
            // Step 1: Validate and extract state data
            $subscription = $this->validateAndGetSubscription($request);
            if (!$subscription) {
                Log::warning('GoCardless flow: Subscription not found');
                return $this->redirectWithError('The payment flow was interrupted. Please try again.');
            }

            $subscription->provider = PaymentMethod::GOCARDLESS;
            // $subscription->save();

            // Step 2: Process the redirect flow
            $flowId = $request->get('redirect_flow_id');
            if (!$flowId) {
                Log::warning('GoCardless flow: Missing redirect_flow_id in callback');
                return $this->redirectWithError('The payment flow was interrupted. Please try again.');
            }

            // Step 3: Complete the flow and set up payments
            $service = GatewaySubscriptionFactory::make($subscription);
            $flow = $service->completeSetup($flowId);

            // Mark the subscription as pending initially - it will be marked active when payment is confirmed
            $subscription->status = SubscriptionStatus::PENDING;
            $subscription->save();

            // Dispatch event with correct parameters - passing flowId as a string
            event(new FlowCompleted($subscription, $flow));

            // Step 4: Return success response
            return $this->redirectWithSuccess('Your Direct Debit mandate has been set up successfully. The payment will be processed shortly.');
        } catch (\Exception $e) {
            Log::error('GoCardless flow error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->redirectWithError('We encountered an error setting up your Direct Debit: ' . $e->getMessage());
        }
    }

    /**
     * Validate state data and retrieve the subscription
     *
     * @param Request $request
     * @return Subscription
     * @throws \Exception
     */
    protected function validateAndGetSubscription(Request $request)
    {
        $state = $request->query('state');

        if (!$state) {
            throw new \Exception('Missing state parameter');
        }

        $subscription = Coderstm::$subscriptionModel::find($state);

        if (!$subscription) {
            throw new \Exception('Subscription not found');
        }

        return $subscription;
    }

    /**
     * Redirect with success message
     *
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectWithSuccess($message)
    {
        return redirect(app_url('/billing', [
            'setup' => 'success',
            'message' => $message,
        ]));
    }

    /**
     * Redirect with error message
     *
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectWithError($message = 'An error occurred')
    {
        return redirect(app_url('/billing', [
            'setup' => 'failed',
            'message' => $message,
        ]));
    }
}
