<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Enum\AppRag;
use League\ISO3166\ISO3166;
use Coderstm\Enum\AppStatus;
use Coderstm\Traits\Billable;
use Coderstm\Models\DeviceToken;
use Illuminate\Support\Facades\DB;
use Coderstm\Traits\HasBelongsToOne;
use Illuminate\Database\Eloquent\Builder;
use Coderstm\Database\Factories\UserFactory;
use Coderstm\Exceptions\ImportFailedException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Coderstm\Exceptions\ImportSkippedException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class User extends Admin implements MustVerifyEmail
{
    use HasBelongsToOne, Billable;

    protected $guard = "users";

    protected $fillable = [
        'email',
        'first_name',
        'gender',
        'is_active',
        'last_name',
        'note',
        'password',
        'phone_number',
        'rag',
        'release_at',
        'rfid',
        'qrcode',
        'source',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'release_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'rag' => AppRag::class,
        'status' => AppStatus::class,
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'name',
        'member_since',
        'guard',
    ];

    protected $with = [
        'avatar',
    ];

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return  array<string, string>|string
     */
    public function routeNotificationForMail($notification): array|string
    {
        return [$this->email => $this->name];
    }

    public function getMemberSinceAttribute()
    {
        return $this->created_at->format('Y');
    }

    public function notes()
    {
        return $this->morphMany(Log::class, 'logable')
            ->whereNotIn('type', ['login'])
            ->orderBy('created_at', 'desc')
            ->withOnly(['admin']);
    }

    public function lastUpdate()
    {
        return $this->morphOne(Log::class, 'logable')
            ->where('type', 'notes')
            ->orderBy('created_at', 'desc');
    }

    public function updateEndsAt($endsAt = null)
    {
        if ($this->subscription()) {
            $this->subscription()->update([
                'cancels_at' => $endsAt,
            ]);
        }
        return $this;
    }

    /**
     * Get all of the enquiries for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Coderstm::$enquiryModel, 'email', 'email');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Eager load unread enquiries counts on the User.
     */
    public function loadUnreadEnquiries()
    {
        return $this->loadCount([
            'enquiries as unread_enquiries' => function (Builder $query) {
                $query->onlyActive();
            }
        ]);
    }

    public function requestAccountDeletion()
    {
        return $this->morphOne(Log::class, 'logable')
            ->where('type', 'request-account-deletion')
            ->where('created_at', '>', now()->subDays(7))
            ->whereColumn('created_at', 'updated_at')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to only include onlyActive
     */
    public function scopeOnlyActive($query): Builder
    {
        return $query->where([
            'status' => AppStatus::ACTIVE
        ]);
    }

    /**
     * Scope a query to only include onlyEnquiry
     */
    public function scopeOnlyEnquiry($query): Builder
    {
        return $query->where('status', '<>', AppStatus::ACTIVE);
    }


    /**
     * Scope a query to only include onlyCancelled
     */
    public function scopeOnlyCancelled($query): Builder
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->canceled();
        });
    }

    /**
     * Scope a query to only include onlyMonthlyPlan
     */
    public function scopeOnlyMonthlyPlan($query): Builder
    {
        return $query->onlyPlan('month');
    }

    /**
     * Scope a query to only include onlyYearlyPlan
     */
    public function scopeOnlyYearlyPlan($query): Builder
    {
        return $query->onlyPlan('year');
    }

    /**
     * Scope a query to only include onlyPlan
     *
     * @param   string $type year|month|day
     */
    public function scopeOnlyPlan($query, string $type = 'month'): Builder
    {
        return $query->whereHas('subscriptions', function ($q) use ($type) {
            $q->active()
                ->whereNull('cancels_at')
                ->whereHas('plan', function ($q) use ($type) {
                    $q->whereInterval($type)
                        ->where('price', '<>', 0);
                });
        });
    }

    /**
     * Scope a query to only include onlyRolling
     */
    public function scopeOnlyRolling($query): Builder
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->active()->whereNull('cancels_at');
        });
    }

    /**
     * Scope a query to only include onlyEnds
     */
    public function scopeOnlyEnds($query): Builder
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->active()->whereNotNull('cancels_at');
        });
    }

    /**
     * Scope a query to only include onlyFree
     */
    public function scopeOnlyFree($query): Builder
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->active()->whereHas('plan', function ($q) {
                $q->wherePrice(0);
            });
        });
    }

    /**
     * Scope a query to only include whereTyped
     */
    public function scopeWhereTyped($query, ?string $type = null): Builder
    {
        switch ($type) {
            case 'rolling':
                $query->onlyRolling();
                break;

            case 'ends':
            case 'end_date':
                $query->onlyEnds();
                break;

            case 'month':
            case 'year':
                $query->onlyPlan($type);
                break;

            case 'free':
                $query->onlyFree();
                break;
        }

        return $query;
    }

    /**
     * Scope a query to only include sortBy
     */

    public function scopeSortBy($query, $column = 'CREATED_AT_ASC', $direction = 'asc'): Builder
    {
        switch ($column) {
            case 'last_login':
                $query->orderByRaw('(SELECT MAX(created_at) FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ?) ' . ($direction ?? 'asc'), [$this->getMorphClass(), 'login']);
                break;

            case 'last_update':
                $query->orderByRaw('(SELECT MAX(created_at) FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ?) ' . ($direction ?? 'asc'), [$this->getMorphClass(), 'notes']);
                break;

            case 'created_by':
                $query->orderByRaw(
                    'CASE
                        WHEN (SELECT admin_id FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ? ORDER BY created_at DESC LIMIT 1) IS NOT NULL
                        THEN (SELECT first_name FROM admins WHERE admins.id = (SELECT admin_id FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ? ORDER BY created_at DESC LIMIT 1))
                        ELSE JSON_EXTRACT((SELECT options FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ? ORDER BY created_at DESC LIMIT 1), "$.ref")
                    END ' . ($direction ?? 'asc'),
                    [$this->getMorphClass(), 'created', $this->getMorphClass(), 'created', $this->getMorphClass(), 'created']
                );
                break;

            case 'price':
                $query->orderByRaw('(
                    SELECT label
                    FROM (
                        SELECT label
                        FROM plans
                        WHERE id = (
                            SELECT plan_id
                            FROM subscriptions
                            WHERE user_id = users.id
                            ORDER BY created_at DESC
                            LIMIT 1
                        )
                        LIMIT 1
                    ) AS subquery
                ) ' . ($direction ?? 'asc'));
                break;

            case 'name':
                $query->orderBy(DB::raw("CONCAT(`first_name`, `last_name`)"), $direction ?? 'asc');
                break;

            default:
                $query->orderBy($column ?: 'created_at', $direction ?? 'asc');
                break;
        }

        return $query;
    }

    /**
     * Scope a query to only include withUnreadEnquiries
     */
    public function scopeWithUnreadEnquiries($query): Builder
    {
        return $query->withCount([
            'enquiries as unread_enquiries' => function (Builder $query) {
                $query->onlyActive();
            },
        ]);
    }

    /**
     * Scope a query to only include whereDateColumn
     */
    public function scopeWhereDateColumn($query, $date = [], $column = 'created_at'): Builder
    {
        return $query->whereHas('subscriptions', function ($q) use ($date, $column) {
            if (isset($date['year'])) {
                $q->whereYear($column, $date['year']);
            }
            if (isset($date['month'])) {
                $q->whereMonth($column, $date['month']);
            }
            if (isset($date['day'])) {
                $q->whereDay($column, $date['day']);
            }
        });
    }

    public function toLoginResponse()
    {
        return $this->loadUnreadEnquiries()->toArray();
    }

    public function getShortCodes(): array
    {
        return [
            '{{USER_NAME}}' => $this->name,
            '{{USER_ID}}' => $this->id,
            '{{USER_FIRST_NAME}}' => $this->first_name,
            '{{USER_LAST_NAME}}' => $this->last_name,
            '{{USER_EMAIL}}' => $this->email,
            '{{USER_PHONE_NUMBER}}' => $this->phone_number,
        ];
    }

    public static function getMappedAttributes(): array
    {
        return  [
            "First Name" => 'first_name',
            "Surname" => 'last_name',
            "Gender" => 'gender',
            "Email Address" => 'email',
            "Phone Number" => 'phone_number',
            "Status" => 'status',
            "Deactivates At" => 'deactivates_at',
            "Password" => 'password',
            "Created At" => 'created_at',
            "Plan" => 'plan',
            "Trial Ends At" => 'trial_ends_at',
            "Address Line1" => 'line1',
            "Address Line2" => 'line2',
            "Country" => 'country',
            "State" => 'state',
            "State Code" => 'state_code',
            "City" => 'city',
            "Postcode/Zip" => 'postal_code',
            "Note" => 'note',
        ];
    }

    public static function createFromCsv(array $attributes = [], array $options = [])
    {
        $replaceByEmail = isset($options['email_overwrite']) && $options['email_overwrite'];
        $user = static::where('email', $attributes['email'])->withTrashed()->first();

        if (!$replaceByEmail && $user) {
            throw new ImportFailedException;
        } else if ($user && ($user->wasRecentlyUpdated || $user->wasRecentlyCreated)) {
            throw new ImportSkippedException;
        }

        if (isset($attributes['password'])) {
            $attributes['password'] = bcrypt($attributes['password']);
        }

        if (isset($attributes['country'])) {
            $country = (new ISO3166)->name($attributes['country']);
            $attributes['country_code'] = $country['alpha2'];
        }

        $user = static::firstOrNew([
            'email' => $attributes['email']
        ], $attributes);

        if (isset($attributes['trial_ends_at']) && !empty($attributes['trial_ends_at'])) {
            $user->trial_ends_at = $attributes['trial_ends_at'];
        }

        if (isset($attributes['created_at']) && !empty($attributes['created_at'])) {
            $user->created_at = $attributes['created_at'];
        }

        $user->deleted_at = null;

        $user->save();

        $user->updateOrCreateAddress($attributes);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }

    static public function findByQrcode($qrcode)
    {
        if ($user = static::where('qrcode', $qrcode)->first()) {
            return $user;
        } else {
            throw new ModelNotFoundException('Invalid QR code. Please provide a valid QR code.');
        }
    }

    static public function generateUniqueQRCode()
    {
        $randomInteger = mt_rand(1000000000, 9999999999);

        while (static::where('qrcode', $randomInteger)->exists()) {
            $randomInteger = mt_rand(1000000000, 9999999999);
        }

        return $randomInteger;
    }

    public function addDeviceToken(string $deviceToken)
    {
        if (!$deviceToken) {
            throw new \InvalidArgumentException('Device token cannot be empty.');
        }

        return $this->deviceTokens()->updateOrCreate([
            'token' => $deviceToken
        ]);
    }

    public function canSendPushNotification(): bool
    {
        return $this->deviceTokens->count() > 0 && config('alert.push');
    }

    public function canSendWhatsappNotification(): bool
    {
        return !empty($this->phone_number) && config('alert.whatsapp');
    }

    protected static function booted()
    {
        static::creating(function (self $model) {
            if (empty($model->qrcode)) {
                $model->qrcode = static::generateUniqueQRCode();
            }
        });

        static::updated(function ($model) {
            Coderstm::$enquiryModel::withoutEvents(function () use ($model) {
                Coderstm::$enquiryModel::where('email', $model->getOriginal('email'))->update([
                    'email' => $model->email
                ]);
            });
        });

        static::addGlobalScope('default', function (Builder $builder) {
            $builder->withMax('subscriptions as ends_at', 'cancels_at');
            $builder->withMax('subscriptions as starts_at', 'starts_at');
            $builder->withMax('subscriptions as subscription_status', 'status');
            $builder->withMax('lastLogin as last_login_at', 'created_at');

            $builder->withCount([
                'enquiries as unread_enquiries' => function (Builder $query) {
                    $query->onlyActive();
                },
            ]);
        });
    }
}
