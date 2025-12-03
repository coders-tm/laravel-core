<?php

namespace App\Http\Controllers;

use Coderstm\Coderstm;
use League\Csv\Reader;
use Coderstm\Models\File;
use Coderstm\Models\Import;
use Coderstm\Enum\AppStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Coderstm\Jobs\ProcessCsvImport;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Coderstm\Models\User;

class UserController extends Controller
{
    use \Coderstm\Traits\Helpers;
    use \Coderstm\Traits\HasResourceActions;

    /**
     * Create the controller instance.
     */
    public function __construct()
    {
        $this->useModel(Coderstm::$userModel);
        $this->authorizeResource(Coderstm::$userModel, 'user', [
            'except' => ['show', 'update', 'destroy', 'restore']
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [Coderstm::$userModel]);

        $user = Coderstm::$userModel::with($request->includes ?? []);
        $isCancelled = $request->filled('type') && $request->type == 'cancelled';

        if ($request->filled('month') || $request->filled('year')) {
            $column = $isCancelled ? 'expires_at' : 'created_at';
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
            'message' => __('User account has been created successfully!'),
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
            'message' => __('User account has been updated successfully!'),
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
            'message' => __('User account marked as :type successfully!', ['type' => __($type)]),
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
                'message' => __('Note has been added successfully!'),
            ], 200);
        } else {
            return response()->json([
                'data' => null,
                'message' => __('Note has been added successfully!'),
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
            'message' => __('Due payment has been received.')
        ], 200);
    }

    /**
     * Send a password reset request to the specified user.
     */
    public function resetPasswordRequest(Request $request, $id)
    {
        $user = Coderstm::$userModel::findOrFail($id);

        $this->authorize('update', [$user]);

        // Create password reset token
        $token = \Illuminate\Support\Facades\Password::createToken($user);

        // Build a generic reset URL (frontend may handle it). If frontend URL isn't configured, fallback to app URL
        $baseUrl = config('app.frontend_url') ?: config('app.url');
        $resetUrl = rtrim($baseUrl, '/').'/password/reset?token='.$token.'&email='.urlencode($user->email);

        // Send notification using common template system
        $user->notify(new \Coderstm\Notifications\UserResetPasswordNotification($user, [
            'token' => $token,
            'url' => $resetUrl,
            'expires' => config('auth.passwords.users.expire', 60),
        ]));

        return response()->json([
            'message' => __('Password reset email has been sent.')
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
                'unwanted' => [__('Unwanted CSV headers: :headers', ['headers' => implode(', ', $unwantedFields)])],
            ]);
        }

        // Validate required headers
        $requiredHeaders = array_keys(array_filter($expectedHeaders));
        $missingHeaders = array_diff($requiredHeaders, $csvHeaders);
        if (!empty($missingHeaders)) {
            throw ValidationException::withMessages([
                'required' => [__('Missing a required header: :headers', ['headers' => implode(', ', $missingHeaders)])],
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
            'message' => __('This could take some time to complete. You can close this dialog box while we upload your file. We will email you once the import finishes.')
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
            $user['subscription_message'] = __("User is on trial until :date, <strong>but hasn't subscribed to any plan yet</strong>. User needs to subscribe to continue using the application even after the trial ends.", ['date' => $trial_end]);
            $user['upcomingInvoice'] = false;
            return $user;
        } else if ($user->is_free_forever || !$subscription) {
            $user['subscription_message'] = __('To access exclusive features, please subscribe to a plan. You are not currently subscribed to any plan.');
            $user['upcomingInvoice'] = false;
            return $user;
        }

        $upcomingInvoice = $subscription->upcomingInvoice();

        $subscription->load([
            'nextPlan',
            'planCanceled',
        ]);

        if ($subscription->canceled() && $subscription->canceledOnGracePeriod()) {
            if ($subscription->planCanceled) {
                $subscription['message'] = __('Subscribed plan has been deactivated. Your subscription will end on :date', [
                    'date' => $subscription->expires_at->format('d M, Y')
                ]);
            } else {
                $subscription['message'] = __('You have cancelled your subscription. Your subscription will end on :date', [
                    'date' => $subscription->expires_at->format('d M, Y')
                ]);
            }
        } else if ($subscription->expired() || $subscription->hasIncompletePayment()) {
            $invoice = $subscription->latestInvoice;
            $amount = $invoice?->total();
            $subscription['message'] = __('Your subscription is :status, Please make a payment of :amount to continue enjoying our services.', [
                'status' => $subscription->status,
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
                $subscription['message'] = __("User is also on trial, until :date. Once it ends, user will be charged for the plan.", ['date' => $trial_end]);
            } else if ($subscription->hasDowngrade()) {
                $subscription['message'] = __('New plan :plan with :amount will become effective on :date.', [
                    'plan' => $subscription->nextPlan?->label,
                    'amount' => $upcomingInvoice->total(),
                    'date' => $subscription['upcomingInvoice']['date']
                ]);
            } else {
                $subscription['message'] = __('Next invoice :amount on :date', [
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
