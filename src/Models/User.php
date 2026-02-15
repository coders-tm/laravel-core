<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Database\Factories\UserFactory;
use Coderstm\Enum\AppRag;
use Coderstm\Enum\AppStatus;
use Coderstm\Exceptions\ImportFailedException;
use Coderstm\Exceptions\ImportSkippedException;
use Coderstm\Traits\Billable;
use Coderstm\Traits\HasBelongsToOne;
use Coderstm\Traits\HasWallet;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use League\ISO3166\ISO3166;

class User extends Admin implements MustVerifyEmail
{
    use Billable, HasBelongsToOne, HasWallet;

    protected $guard = 'users';

    protected $fillable = ['email', 'first_name', 'gender', 'is_active', 'last_name', 'note', 'password', 'phone_number', 'rag', 'is_free_forever', 'trial_ends_at', 'source', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime', 'last_login_at' => 'datetime', 'trial_ends_at' => 'datetime', 'rag' => AppRag::class, 'status' => AppStatus::class, 'is_active' => 'boolean', 'is_free_forever' => 'boolean'];

    protected $appends = ['name', 'member_since', 'guard'];

    protected $with = ['avatar'];

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function routeNotificationForMail($notification): array|string
    {
        return [$this->email => $this->name];
    }

    public function routeNotificationForFcm(): array
    {
        return $this->deviceTokens()->pluck('token')->toArray();
    }

    public function routeNotificationForTwilio()
    {
        return $this->phone_number;
    }

    public function getMemberSinceAttribute()
    {
        return $this->created_at?->format('Y');
    }

    public function notes()
    {
        return $this->morphMany(Log::class, 'logable')->whereNotIn('type', ['login'])->orderBy('created_at', 'desc')->withOnly(['admin']);
    }

    public function lastUpdate()
    {
        return $this->morphOne(Log::class, 'logable')->where('type', 'notes')->orderBy('created_at', 'desc');
    }

    public function updateCancelsAt($dateAt)
    {
        return $this->updateExpiresAt($dateAt);
    }

    public function updateExpiresAt($expiresAt)
    {
        if (! $expiresAt) {
            throw new \InvalidArgumentException('Expires at cannot be empty.');
        }
        if ($this->subscription()) {
            $this->subscription()->update(['expires_at' => $expiresAt]);
        }

        return $this;
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Coderstm::$enquiryModel, 'email', 'email');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function loadUnreadEnquiries()
    {
        return $this->loadCount(['enquiries as unread_enquiries' => function (Builder $query) {
            $query->onlyActive();
        }]);
    }

    public function requestAccountDeletion()
    {
        return $this->morphOne(Log::class, 'logable')->where('type', 'request-account-deletion')->where('created_at', '>', now()->subDays(7))->whereColumn('created_at', 'updated_at')->orderBy('created_at', 'desc');
    }

    public function scopeOnlyActive($query): Builder
    {
        return $query->where(['status' => AppStatus::ACTIVE]);
    }

    public function scopeOnlyEnquiry($query): Builder
    {
        return $query->where('status', '<>', AppStatus::ACTIVE);
    }

    public function scopeOnlyMember($query): Builder
    {
        return $query->where('status', AppStatus::ACTIVE);
    }

    public function scopeOnlyCancelled($query): Builder
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->canceled();
        });
    }

    public function scopeOnlyMonthlyPlan($query): Builder
    {
        return $query->onlyPlan('month');
    }

    public function scopeOnlyYearlyPlan($query): Builder
    {
        return $query->onlyPlan('year');
    }

    public function scopeOnlyPlan($query, string $type = 'month'): Builder
    {
        return $query->whereHas('subscriptions', function ($q) use ($type) {
            $q->active()->whereNull('canceled_at')->whereHas('plan', function ($q) use ($type) {
                $q->whereInterval($type)->where('price', '<>', 0);
            });
        });
    }

    public function scopeOnlyRolling($query): Builder
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->active()->whereNull('canceled_at');
        });
    }

    public function scopeOnlyEnds($query): Builder
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->active()->whereNotNull('canceled_at');
        });
    }

    public function scopeOnlyFree($query): Builder
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->active()->whereHas('plan', function ($q) {
                $q->wherePrice(0);
            });
        });
    }

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

    public function scopeSortBy($query, $column = 'CREATED_AT_ASC', $direction = 'asc'): Builder
    {
        switch ($column) {
            case 'last_login':
                $query->orderByRaw('(SELECT MAX(created_at) FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ?) '.($direction ?? 'asc'), [$this->getMorphClass(), 'login']);
                break;
            case 'last_update':
                $query->orderByRaw('(SELECT MAX(created_at) FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ?) '.($direction ?? 'asc'), [$this->getMorphClass(), 'notes']);
                break;
            case 'created_by':
                $query->orderByRaw('CASE
                        WHEN (SELECT admin_id FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ? ORDER BY created_at DESC LIMIT 1) IS NOT NULL
                        THEN (SELECT first_name FROM admins WHERE admins.id = (SELECT admin_id FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ? ORDER BY created_at DESC LIMIT 1))
                        ELSE JSON_EXTRACT((SELECT options FROM logs WHERE logs.logable_id = users.id AND logs.logable_type = ? AND logs.type = ? ORDER BY created_at DESC LIMIT 1), "$.ref")
                    END '.($direction ?? 'asc'), [$this->getMorphClass(), 'created', $this->getMorphClass(), 'created', $this->getMorphClass(), 'created']);
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
                ) '.($direction ?? 'asc'));
                break;
            case 'name':
                $query->orderBy(DB::raw('CONCAT(`first_name`, `last_name`)'), $direction ?? 'asc');
                break;
            default:
                $query->orderBy($column ?: 'created_at', $direction ?? 'asc');
                break;
        }

        return $query;
    }

    public function scopeWithUnreadEnquiries($query): Builder
    {
        return $query->withCount(['enquiries as unread_enquiries' => function (Builder $query) {
            $query->onlyActive();
        }]);
    }

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
        return $this->loadUnreadEnquiries()->toArray() + ['subscription' => $this->subscription()?->toResponse(['plan', 'invoice', 'usages'])];
    }

    public function getShortCodes(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'first_name' => $this->first_name, 'last_name' => $this->last_name, 'email' => $this->email, 'phone_number' => $this->phone_number];
    }

    public static function getMappedAttributes(): array
    {
        return ['First Name' => 'first_name', 'Surname' => 'last_name', 'Gender' => 'gender', 'Email Address' => 'email', 'Phone Number' => 'phone_number', 'Status' => 'status', 'Deactivates At' => 'deactivates_at', 'Password' => 'password', 'Created At' => 'created_at', 'Plan' => 'plan', 'Trial Ends At' => 'trial_ends_at', 'Address Line1' => 'line1', 'Address Line2' => 'line2', 'Country' => 'country', 'State' => 'state', 'State Code' => 'state_code', 'City' => 'city', 'Postcode/Zip' => 'postal_code', 'Note' => 'note'];
    }

    public static function createFromCsv(array $attributes = [], array $options = [])
    {
        $replaceByEmail = isset($options['email_overwrite']) && $options['email_overwrite'];
        $user = static::where('email', $attributes['email'])->withTrashed()->first();
        if (! $replaceByEmail && $user) {
            throw new ImportFailedException;
        } elseif ($user && ($user->wasRecentlyUpdated || $user->wasRecentlyCreated)) {
            throw new ImportSkippedException;
        }
        if (isset($attributes['password'])) {
            $attributes['password'] = bcrypt($attributes['password']);
        }
        if (isset($attributes['country'])) {
            try {
                $country = (new ISO3166)->name($attributes['country']);
                $attributes['country_code'] = $country['alpha2'];
            } catch (\Throwable $e) {
                $attributes['country_code'] = null;
            }
        }
        $user = static::firstOrNew(['email' => $attributes['email']], $attributes);
        if (isset($attributes['trial_ends_at']) && ! empty($attributes['trial_ends_at'])) {
            $user->trial_ends_at = $attributes['trial_ends_at'];
        }
        if (isset($attributes['created_at']) && ! empty($attributes['created_at'])) {
            $user->created_at = $attributes['created_at'];
        }
        $user->deleted_at = null;
        $user->save();
        $user->updateOrCreateAddress($attributes);
    }

    public function orders()
    {
        return $this->hasMany(\Coderstm\Models\Shop\Order::class, 'customer_id');
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public function addDeviceToken(string $deviceToken)
    {
        if (! $deviceToken) {
            throw new \InvalidArgumentException('Device token cannot be empty.');
        }

        return $this->deviceTokens()->updateOrCreate(['token' => $deviceToken]);
    }

    protected static function booted()
    {
        static::updated(function ($model) {
            Coderstm::$enquiryModel::withoutEvents(function () use ($model) {
                Coderstm::$enquiryModel::where('email', $model->getOriginal('email'))->update(['email' => $model->email]);
            });
        });
        static::addGlobalScope('default', function (Builder $builder) {
            $builder->withMax('lastLogin as last_login_at', 'created_at');
            $builder->withCount(['enquiries as unread_enquiries' => function (Builder $query) {
                $query->onlyActive();
            }]);
        });
    }
}
