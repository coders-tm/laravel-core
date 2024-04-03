<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Enum\AppRag;
use Coderstm\Enum\AppStatus;
use Coderstm\Traits\Billable;
use Coderstm\Models\DeviceToken;
use Illuminate\Support\Facades\DB;
use Coderstm\Traits\HasBelongsToOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'source',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'release_at' => 'datetime:Y-m-d',
        'email_verified_at' => 'datetime',
        'rag' => AppRag::class,
        'status' => AppStatus::class,
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'name',
        'member_since',
        'guard',
        'is_subscribed',
        'has_cancelled',
    ];

    protected $with = [
        'avatar',
        'address',
        'lastLogin',
        'latestAppInvoice as latest_invoice',
    ];

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
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
        return $this->morphOne(Log::class, 'logable')->where('type', 'notes')
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
     *
     * @return $this
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
            ->latestOfMany();
    }

    /**
     * Scope a query to only include onlyActive
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyActive($query)
    {
        return $query->where([
            'status' => AppStatus::ACTIVE
        ]);
    }

    /**
     * Scope a query to only include onlyEnquiry
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyEnquiry($query)
    {
        return $query->where('status', '<>', AppStatus::ACTIVE);
    }


    /**
     * Scope a query to only include onlyCancelled
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param   int $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyCancelled($query)
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->canceled();
        });
    }

    /**
     * Scope a query to only include onlyMonthlyPlan
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyMonthlyPlan($query)
    {
        return $query->onlyPlan('month');
    }

    /**
     * Scope a query to only include onlyYearlyPlan
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyYearlyPlan($query)
    {
        return $query->onlyPlan('year');
    }

    /**
     * Scope a query to only include onlyPlan
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param   string $type year|month|day
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyPlan($query, string $type = 'month')
    {
        return $query->whereHas('subscriptions', function ($q) use ($type) {
            $q->active()
                ->whereNull('cancels_at')
                ->whereHas('price', function ($q) use ($type) {
                    $q->whereInterval($type)
                        ->where('amount', '<>', 0);
                });
        });
    }

    /**
     * Scope a query to only include onlyRolling
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyRolling($query)
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->active()->whereNull('cancels_at');
        });
    }

    /**
     * Scope a query to only include onlyEnds
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyEnds($query)
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->active()->whereNotNull('cancels_at');
        });
    }

    /**
     * Scope a query to only include onlyFree
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyFree($query)
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->active()->whereHas('price', function ($q) {
                $q->whereAmount(0);
            });
        });
    }

    /**
     * Scope a query to only include whereTyped
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereTyped($query, string $type = null)
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
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortBy($query, $column = 'CREATED_AT_ASC', $direction = 'asc')
    {
        switch ($column) {
            case 'last_login':
                $query->select("users.*")
                    ->leftJoin('logs', function ($join) {
                        $join->on('logs.logable_id', '=', "users.id")
                            ->where('logs.logable_type', '=', $this->getMorphClass())
                            ->where('logs.type', 'login');
                    })
                    ->addSelect(DB::raw('logs.created_at AS last_login_at'))
                    ->groupBy("users.id")
                    ->orderBy('last_login_at', $direction ?? 'asc');
                break;

            case 'last_update':
                $query->select("users.*")
                    ->leftJoin('logs', function ($join) {
                        $join->on('logs.logable_id', '=', "users.id")
                            ->where('logs.logable_type', '=', $this->getMorphClass())
                            ->where('logs.type', 'notes');
                    })
                    ->addSelect(DB::raw('logs.created_at AS last_update_at'))
                    ->groupBy("users.id")
                    ->orderBy('last_update_at', $direction ?? 'asc');
                break;

            case 'created_by':
                $query->select("users.*")
                    ->leftJoin('logs', function ($join) {
                        $join->on('logs.logable_id', '=', "users.id")
                            ->where('logs.logable_type', '=', $this->getMorphClass())
                            ->where('logs.type', 'created');
                    })
                    ->leftJoin('admins', function ($join) {
                        $join->on('logs.admin_id', '=', "admins.id");
                    })
                    ->addSelect(DB::raw('CASE WHEN logs.admin_id IS NOT NULL THEN admins.first_name ELSE JSON_EXTRACT(logs.options, "$.ref") END AS created_by'))
                    ->groupBy("users.id")
                    ->orderBy('created_by', $direction ?? 'asc');
                break;

            case 'price':
                $query->leftJoin('subscriptions', function ($join) {
                    $join->on('subscriptions.user_id', '=', "users.id")->orderByDesc('created_at')->limit(1);
                })->leftJoin('plan_prices', function ($join) {
                    $join->on('plan_prices.stripe_id', '=', "subscriptions.stripe_price");
                })->leftJoin('plans', function ($join) {
                    $join->on('plans.id', '=', "plan_prices.plan_id");
                })->orderBy(DB::raw('plans.label'), $direction ?? 'asc');
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
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithUnreadEnquiries($query)
    {
        return $query->withCount([
            'enquiries as unread_enquiries' => function (Builder $query) {
                $query->onlyActive();
            },
        ]);
    }

    /**
     * Scope a query to only include whereDateColumn
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  array $date
     * @param  string $column
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereDateColumn($query, $date = [], $column = 'created_at')
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

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($model) {
            Coderstm::$enquiryModel::withoutEvents(function () use ($model) {
                Coderstm::$enquiryModel::where('email', $model->getOriginal('email'))->update([
                    'email' => $model->email
                ]);
            });
        });
        static::addGlobalScope('default', function (Builder $builder) {
            $builder->withCount([
                'enquiries as unread_enquiries' => function (Builder $query) {
                    $query->onlyActive();
                },
            ]);
            $builder->withMax('subscriptions as ends_at', 'cancels_at');
            $builder->withMax('subscriptions as starts_at', 'created_at');
        });
    }
}
