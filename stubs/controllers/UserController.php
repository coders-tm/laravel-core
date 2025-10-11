<?php

namespace App\Http\Controllers;

use Coderstm\Coderstm;
use League\Csv\Reader;
use Coderstm\Models\File;
use Coderstm\Models\Import;
use Coderstm\Enum\AppStatus;
use Coderstm\Traits\Helpers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Coderstm\Jobs\ProcessCsvImport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Coderstm\Models\User;

class UserController extends Controller
{
    use Helpers;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [Coderstm::$userModel]);

        $user = Coderstm::$userModel::with($request->includes ?? []);
        $isCancelled = $request->filled('type') && $request->type == 'cancelled';

        if ($request->filled('month') || $request->filled('year')) {
            $column = $isCancelled ? 'ends_at' : 'created_at';
            $user->whereDateColumn([
                'month' => $request->month,
                'year' => $request->year,
                'day' => $request->day
            ], $column);
        }

        if ($request->filled('filter')) {
            if (str($request->filter)->startsWith('email:')) {
                $filter = str($request->filter)->replace('email:', '');
                $user->where('email', 'like', "{$filter}%");
            } else {
                $user->where(DB::raw("CONCAT(first_name,' ',last_name)"), 'like', "%{$request->filter}%");
            }
        }

        if ($request->boolean('isEnquiry')) {
            $user->onlyEnquiry();
            if ($request->filled('status')) {
                $user->where('status', $request->input('status'));
            }
        } else if ($request->boolean('option')) {
            $user->whereIn('status', [AppStatus::ACTIVE, AppStatus::PENDING]);
        } else if ($isCancelled) {
            $user->onlyCancelled();
        } else {
            $user->onlyMember();

            if ($request->boolean('status')) {
                $user->onlyActive();
            } else if ($request->status == 'late-cancellation') {
                $user->onlyLateCancellation();
            } else if ($request->status == 'no-show') {
                $user->onlyNoShow();
            } else if ($request->status == 'blocked') {
                $user->onlyBlocked();
            }

            if ($request->filled('type')) {
                $user->whereTyped($request->input('type'));
            }
        }

        if ($request->filled('rag')) {
            $user->where('rag', $request->rag);
        }

        if ($request->boolean('deleted')) {
            $user->onlyTrashed();
        }

        $users = $user->sortBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->rowsPerPage ?? 15);

        // Load subscription information for each user
        $users->getCollection()->transform(function ($user) {
            return $this->loadSubscriptionInfo($user);
        });

        return new ResourceCollection($users);
    }

    /**
     * Display a options listing of the resource.
     */
    public function options(Request $request)
    {
        $request->merge([
            'option' => true
        ]);

        return $this->index($request);
    }

    /**
     * Display a listing of the resource by ids.
     */
    public function listByIds(Request $request)
    {
        return Coderstm::$userModel::whereIn('id', $request->ids)->get();
    }

    /**
     * Display a enquiry listing of the resource.
     */
    public function enquiry(Request $request)
    {
        $this->authorize('enquiry', [Coderstm::$userModel]);

        $request->merge([
            'isEnquiry' => true,
        ]);

        return $this->index($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Coderstm::$userModel]);

        $rules = [
            'email' => 'required|email|unique:users',
            'first_name' => 'required',
            'last_name' => 'required',
            'password' => 'confirmed',
            'address.line1' => 'required',
            'address.city' => 'required',
            'address.postal_code' => 'required',
            'address.country' => 'required',
        ];

        // Add plan validation if plan is provided
        if ($request->filled('plan')) {
            $rules['plan'] = 'exists:plans,id';
            $rules['payment_method'] = 'required|exists:payment_methods,id';
        }

        // Validate those rules
        $this->validate($request, $rules);

        $request->merge([
            'password' => bcrypt($request->password ?? str()->random(6)),
        ]);

        // create the user
        $user = Coderstm::$userModel::create($request->input());

        // add address to the user
        $user = $user->updateOrCreateAddress($request->input('address'));

        if ($request->filled('avatar')) {
            $user->avatar()->sync([
                $request->input('avatar.id') => [
                    'type' => 'avatar'
                ]
            ]);
        }

        // Create subscription if plan is provided
        if ($request->filled('plan')) {
            $this->createSubscription($user, $request);
        }

        // Load subscription information - refresh user with relationships
        $user = $this->loadSubscriptionInfo($user->fresh(['address', 'notes', 'subscriptions']));

        return response()->json([
            'data' => $user,
            'message' => trans('messages.users.store'),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = Coderstm::$userModel::findOrFail($id);

        $this->authorize('view', [$user]);

        // Load subscription information
        $user = $this->loadSubscriptionInfo($user->load(['notes']));

        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Coderstm::$userModel::findOrFail($id);

        $this->authorize('update', [$user]);

        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => "email|unique:users,email,{$user->id}",
            'password' => 'confirmed',
            'address.line1' => 'required',
            'address.city' => 'required',
            'address.postal_code' => 'required',
            'address.country' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        if ($request->filled('password')) {
            $request->merge([
                'password' => bcrypt($request->password),
            ]);
        }

        $user->update($request->input());

        if ($request->filled('avatar')) {
            $user->avatar()->sync([
                $request->input('avatar.id') => [
                    'type' => 'avatar'
                ]
            ]);
        }

        if ($request->filled('special_note')) {
            $user->notes()->create([
                'type' => 'notes',
                'message' => $request->special_note,
            ]);
        }

        if ($request->filled('expires_at')) {
            $user = $user->updateExpiresAt($request->expires_at);
        }

        $user->updateOrCreateAddress($request->input('address'));

        // Load subscription information
        $user = $this->loadSubscriptionInfo($user->fresh(['address', 'notes']));

        return response()->json([
            'data' => $user,
            'message' => trans('messages.users.updated'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Coderstm::$userModel::findOrFail($id);

        $this->authorize('delete', [$user]);

        $user->delete();

        return response()->json([
            'message' => trans_choice('messages.users.destroy', 1),
        ], 200);
    }

    /**
     * Remove the selected resource from storage.
     */
    public function destroySelected(Request $request)
    {
        $this->authorize('delete', [Coderstm::$userModel]);

        $this->validate($request, [
            'items' => 'required',
        ]);

        Coderstm::$userModel::whereIn('id', $request->items)->each(function ($item) {
            $item->delete();
        });

        return response()->json([
            'message' => trans_choice('messages.users.destroy', 2),
        ], 200);
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore($id)
    {
        $user = Coderstm::$userModel::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', [$user]);

        $user->restore();

        return response()->json([
            'message' => trans_choice('messages.users.restored', 1),
        ], 200);
    }

    /**
     * Remove the selected resource from storage.
     */
    public function restoreSelected(Request $request)
    {
        $this->authorize('restore', [Coderstm::$userModel]);

        $this->validate($request, [
            'items' => 'required',
        ]);

        Coderstm::$userModel::onlyTrashed()
            ->whereIn('id', $request->items)->each(function ($item) {
                $item->restore();
            });

        return response()->json([
            'message' => trans_choice('messages.users.restored', 2),
        ], 200);
    }

    /**
     * Send reset password request to specified resource from storage.
     */
    public function resetPasswordRequest(Request $request, $id)
    {
        $user = Coderstm::$userModel::findOrFail($id);

        $this->authorize('update', [$user]);

        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        return response()->json([
            'status' => $status,
            'message' => trans('messages.users.password'),
        ], 200);
    }

    /**
     * Change active of specified resource from storage.
     */
    public function changeActive(Request $request, $id)
    {
        $user = Coderstm::$userModel::findOrFail($id);

        $this->authorize('update', [$user]);

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $type = !$user->is_active ? 'archived' : 'unarchive';

        return response()->json([
            'message' => trans('messages.users.status', ['type' => trans('messages.attributes.' . $type)]),
        ], 200);
    }

    /**
     * Create notes for specified resource from storage.
     */
    public function notes(Request $request, $id)
    {
        $user = Coderstm::$userModel::findOrFail($id);

        $this->authorize('update', [$user]);

        if ($request->filled('rag')) {
            $user->update($request->only('rag'));
        }

        if ($request->filled('message')) {
            $request->merge([
                'type' => 'notes',
            ]);

            $note = $user->notes()->create($request->input());

            return response()->json([
                'data' => $note->load('admin'),
                'message' => trans('messages.users.note'),
            ], 200);
        } else {
            return response()->json([
                'data' => null,
                'message' => trans('messages.users.note'),
            ], 200);
        }
    }

    public function markAsPaid(Request $request, User $user)
    {
        $request->validate([
            'payment_method' => 'required|exists:payment_methods,id',
        ]);

        try {
            $subscription = $user->subscription();
            $subscription->pay($request->payment_method);
        } catch (\Exception $e) {
            throw $e;
        }

        return response()->json([
            'data' => $user->fresh(),
            'message' => trans('messages.subscription.due_payment')
        ], 200);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => "required|exists:files,id"
        ]);

        $file = File::findOrFail($request->file);
        $path = $file->path(); // file path of csv

        $expectedHeaders = [
            "First Name" => true,
            "Surname" => true,
            "Gender" => true,
            "Email Address" => true,
            "Phone Number" => false,
            "Status" => true,
            "Deactivates At" => false,
            "Password" => true,
            "Created At" => true,
            "Plan" => true,
            "Trial Ends At" => true,
            "Address Line1" => true,
            "Address Line2" => false,
            "Country" => true,
            "State" => true,
            "State Code" => true,
            "City" => true,
            "Postcode/Zip" => false,
            "Note" => false,
        ];

        // Read CSV headers
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(',');

        // Normalize CSV headers to remove newlines
        $csvHeaders = array_map('trim', $csv->getHeader());
        $mappedHeaders = Coderstm::$userModel::getMappedAttributes();

        // Map $headers from $mapped
        $finalHeaders = [];
        foreach ($csvHeaders as $header) {
            if (isset($mappedHeaders[$header])) {
                $finalHeaders[] = $mappedHeaders[$header];
            } else {
                $finalHeaders[] = $header;
            }
        }

        // Validate unwanted fields
        $unwantedFields = array_diff($csvHeaders, array_keys($expectedHeaders));
        if (!empty($unwantedFields)) {
            throw ValidationException::withMessages([
                'unwanted' => ['Unwanted CSV headers: ' . implode(', ', $unwantedFields)],
            ]);
        }

        // Validate required headers
        $requiredHeaders = array_keys(array_filter($expectedHeaders));
        $missingHeaders = array_diff($requiredHeaders, $csvHeaders);
        if (!empty($missingHeaders)) {
            throw ValidationException::withMessages([
                'required' => ['Missing a required header: ' . implode(', ', $missingHeaders)],
            ]);
        }

        $rows = array_values([...$csv->getRecords($finalHeaders)]);

        $this->validate(new Request(['rows' => $rows]), [
            'rows.*.status' => Rule::in(['Pending', 'Active', 'Deactive']),
            'rows.*.email' => 'email',
            'rows.*.created_at' => 'date_format:Y-m-d H:i:s',
            'rows.*.deactivates_at' => 'date_format:Y-m-d H:i:s',
            'rows.*.trial_ends_at' => 'date_format:Y-m-d H:i:s',
            // 'rows.*.plan' => 'exists:plans,id',
        ]);

        $import = Import::create([
            'model' => Coderstm::$userModel,
            'file_id' => $file->id,
            'options' => $request->input(),
        ]);

        ProcessCsvImport::dispatch($import);

        // Return response indicating the import process has started
        return response()->json([
            'message' =>
            'This could take some time to complete. You can close this dialog box while we upload your file. We will email you once the import finishes.'
        ], 200);
    }

    /**
     * Load subscription information for the user.
     * Admin context - uses "User" instead of "You" in messages
     */
    protected function loadSubscriptionInfo($user)
    {
        $subscription = $user->subscription('default');

        if ($user->onGenericTrial()) {
            $trial_end = $user->trial_ends_at->format('M d, Y');
            $user['subscription_message'] = "User is on trial until $trial_end, <strong>but hasn't subscribed to any plan yet</strong>. User needs to subscribe to continue using the application even after the trial ends.";
            $user['upcomingInvoice'] = false;
            return $user;
        } else if ($user->is_free_forever || !$subscription) {
            $user['subscription_message'] = trans('messages.subscription.none');
            $user['upcomingInvoice'] = false;
            return $user;
        }

        $upcomingInvoice = $subscription->upcomingInvoice();

        $subscription->load([
            'nextPlan',
            'planCanceled',
        ]);

        if ($subscription->canceled() && $subscription->onGracePeriod()) {
            if ($subscription->planCanceled) {
                $subscription['message'] = trans('messages.subscription.plan_canceled', [
                    'date' => $subscription->expires_at->format('d M, Y')
                ]);
            } else {
                $subscription['message'] = trans('messages.subscription.canceled', [
                    'date' => $subscription->expires_at->format('d M, Y')
                ]);
            }
        } else if ($subscription->pastDue() || $subscription->hasIncompletePayment()) {
            $invoice = $subscription->latestInvoice;
            $amount = $invoice?->total();
            $subscription['message'] = trans('messages.subscription.past_due', [
                'amount' => $amount
            ]);
            $subscription['invoice'] = [
                'amount' => $amount,
                'key' => $invoice?->key
            ];
            $subscription['hasDue'] = true;
        } else if ($upcomingInvoice) {
            $subscription['upcomingInvoice'] = [
                'amount' => $upcomingInvoice->total(),
                'date' => $upcomingInvoice->due_date->format('d M, Y'),
            ];

            if ($subscription->onTrial()) {
                $trial_end = $subscription->trial_ends_at->format('M d, Y');
                $subscription['message'] = "User is also on trial, until $trial_end. Once it ends, user will be charged for the plan.";
            } else if ($subscription->hasDowngrade()) {
                $subscription['message'] = trans('messages.subscription.downgrade', [
                    'plan' => $subscription->nextPlan?->label,
                    'amount' => $upcomingInvoice->total(),
                    'date' => $subscription['upcomingInvoice']['date']
                ]);
            } else {
                $subscription['message'] = trans('messages.subscription.active', [
                    'amount' => $subscription['upcomingInvoice']['amount'],
                    'date' => $subscription['upcomingInvoice']['date']
                ]);
            }
        }

        $usages = $subscription->usagesToArray();
        $subscription['usages'] = $usages;

        $subscription->unsetRelation('nextPlan')
            ->unsetRelation('planCanceled')
            ->unsetRelation('owner')
            ->unsetRelation('features');

        $subscription['canceled'] = $subscription->canceled();
        $subscription['ended'] = $subscription->ended();
        $subscription['is_valid'] = $subscription->valid() ?? false;

        $user['subscription'] = $subscription;

        return $user;
    }

    /**
     * Create subscription for the user during store operation.
     */
    protected function createSubscription($user, Request $request)
    {
        $plan = \Coderstm\Models\Subscription\Plan::find($request->plan);
        $paymentMethod = \Coderstm\Models\PaymentMethod::find($request->payment_method);
        $provider = $paymentMethod?->provider;
        $trial_end = $user->trial_ends_at;
        $trial_days = $plan->trial_days;

        $subscription = $user->newSubscription('default', $plan->id)
            ->provider($provider);

        if ($request->filled('promotion_code')) {
            $subscription->withCoupon($request->promotion_code);
        }

        if ($trial_end && $trial_end->isFuture()) {
            $subscription->trialUntil($trial_end);
        } else if ($trial_days && !$trial_end) {
            $subscription->trialDays($trial_days);
        }

        // Save subscription and generate invoice (invoice generation will be skipped if status is trialing)
        $subscription->saveAndInvoice();

        // Handle coupon redemption if provided
        if ($request->filled('promotion_code')) {
            $coupon = \Coderstm\Models\Coupon::findByCode($request->promotion_code);
            if ($coupon && $coupon->id) {
                \Coderstm\Models\Redeem::updateOrCreate([
                    'redeemable_type' => get_class($subscription),
                    'redeemable_id' => $subscription->id,
                    'coupon_id' => $coupon->id,
                ], [
                    'user_id' => $user->id,
                    'amount' => $coupon->getAmount($plan->price),
                ]);
            }
        }

        return $subscription;
    }
}
