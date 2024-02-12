<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Enum\AppRag;
use Coderstm\Enum\AppStatus;
use Laravel\Cashier\Cashier;
use Coderstm\Traits\Billable;
use Coderstm\Models\Plan\Price;
use Illuminate\Support\Facades\DB;
use Coderstm\Models\Cashier\Invoice;
use Coderstm\Traits\HasBelongsToOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Stripe\Subscription as StripeSubscription;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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
        'title',
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
        'subscribed',
        'has_cancelled',
    ];

    protected $with = [
        'avatar',
        'address',
        'lastLogin',
        'latestInvoice',
    ];

    public function getNameAttribute()
    {
        return "{$this->title} {$this->first_name} {$this->last_name}";
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
     * Get all of the invoices for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function appInvoices()
    {
        return $this->hasManyThrough(Invoice::class, Cashier::$subscriptionModel);
    }

    /**
     * Get the latest invoices for the User
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function latestInvoice()
    {
        return $this->hasOneThrough(Invoice::class, Cashier::$subscriptionModel)->orderByDesc('created_at');
    }

    /**
     * The price that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function price(): HasOneThrough
    {
        return $this->hasOneThrough(Price::class, Cashier::$subscriptionModel, 'user_id', 'stripe_id', 'id', 'stripe_price')
            ->orderByDesc('created_at');
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
     * Scope a query to only include sumAmount
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  bool $cancelled
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSumAmount($query, $cancelled = false)
    {
        return $query->select('users.id', "plan_prices.amount")->leftJoin('subscriptions', function ($join) use ($cancelled) {
            $join->on('subscriptions.user_id', '=', "users.id")->where('stripe_status', $cancelled ? StripeSubscription::STATUS_CANCELED : StripeSubscription::STATUS_ACTIVE)->limit(1);
        })->leftJoin('plan_prices', function ($join) {
            $join->on('plan_prices.stripe_id', '=', "subscriptions.stripe_price");
        })->sum("plan_prices.amount");
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

    static public function getStatsByMonthAndYear($key, $month = null, $year = null)
    {
        $user = static::onlyActive()->whereDateColumn(['month' => $month, 'year' => $year]);
        $cancelled = static::onlyCancelled()->whereDateColumn(['month' => $month, 'year' => $year], 'ends_at');

        switch ($key) {
            case 'total':
                return $user->count();
                break;

            case 'rolling':
                return $user->onlyRolling()->count();
                break;

            case 'rolling_total':
                return $user->onlyRolling()->sumAmount();
                break;

            case 'end_date':
                return $user->onlyEnds()->count();
                break;

            case 'end_date_total':
                return $user->onlyEnds()->sumAmount();
                break;

            case 'month':
            case 'year':
                return $user->onlyPlan($key)->count();
                break;

            case 'free':
                return $user->onlyFree()->count();
                break;

            case 'cancelled':
                return $cancelled->count();
                break;

            case 'cancelled_total':
                return $cancelled->sumAmount(true);
                break;

            default:
                return 0;
                break;
        }
    }

    static public function getStatsByOptions($key, array $options = [])
    {
        $user = static::whereDateColumn($options);
        $cancelled = static::onlyCancelled()->whereDateColumn($options, 'ends_at');

        switch ($key) {
            case 'total':
                return $user->count();
                break;

            case 'rolling':
                return $user->onlyRolling()->count();
                break;

            case 'rolling_total':
                return $user->onlyRolling()->sumAmount();
                break;

            case 'end_date':
                return $user->onlyEnds()->count();
                break;

            case 'end_date_total':
                return $user->onlyEnds()->sumAmount();
                break;

            case 'month':
            case 'year':
                return $user->onlyPlan($key)->count();
                break;

            case 'free':
                return $user->onlyFree()->count();
                break;

            case 'cancelled':
                return $cancelled->count();
                break;

            case 'cancelled_total':
                return $cancelled->sumAmount(true);
                break;

            default:
                return 0;
                break;
        }
    }

    static public function getStats($key)
    {
        return static::getStatsByMonthAndYear($key);
    }

    public function toLoginResponse()
    {
        return $this->loadUnreadEnquiries()->toArray();
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
