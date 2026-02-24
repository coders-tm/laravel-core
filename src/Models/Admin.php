<?php

namespace Coderstm\Models;

use Coderstm\Database\Factories\AdminFactory;
use Coderstm\Exceptions\ImportFailedException;
use Coderstm\Exceptions\ImportSkippedException;
use Coderstm\Traits\Addressable;
use Coderstm\Traits\Core;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\HasApiTokens;
use Coderstm\Traits\HasPermissionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use League\ISO3166\ISO3166;

class Admin extends Authenticatable
{
    use Addressable, Core, Fileable, HasApiTokens, HasPermissionGroup, Notifiable;

    protected $guard = 'admins';

    protected $fillable = ['first_name', 'last_name', 'gender', 'email', 'password', 'phone_number', 'is_supper_admin', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime', 'last_login_at' => 'datetime', 'is_active' => 'boolean', 'is_supper_admin' => 'boolean'];

    protected $appends = ['name', 'guard'];

    protected $with = ['avatar'];

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function routeNotificationForMail($notification): array|string
    {
        return [$this->email => $this->name];
    }

    public function lastLogin(): MorphOne
    {
        return $this->morphOne(Log::class, 'logable')->where('type', 'login')->orderBy('created_at', 'desc');
    }

    public function createdBy(): MorphOne
    {
        return $this->morphOne(Log::class, 'logable')->whereType('created');
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function scopeWhereName($query, $filter)
    {
        return $query->where(DB::raw('CONCAT(`first_name`,`last_name`)'), 'like', "%{$filter}%");
    }

    public function scopeExcludeCurrent($query)
    {
        return $query->where('id', '<>', user()->id);
    }

    public function scopeSortBy($query, $column = 'CREATED_AT_ASC', $direction = 'asc'): Builder
    {
        switch ($column) {
            case 'last_login':
                $query->orderByRaw('(SELECT MAX(created_at) FROM logs WHERE logs.logable_id = admins.id AND logs.logable_type = ? AND logs.type = ?) '.($direction ?? 'asc'), [$this->getMorphClass(), 'login']);
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

    public function toLoginResponse()
    {
        $response = $this->append('modules')->toArray();
        $response['permissions'] = $this->getScopes();

        return $response;
    }

    public function getShortCodes(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'first_name' => $this->first_name, 'last_name' => $this->last_name, 'email' => $this->email, 'phone_number' => $this->phone_number];
    }

    public static function getMappedAttributes(): array
    {
        return ['First Name' => 'first_name', 'Surname' => 'last_name', 'Gender' => 'gender', 'Email Address' => 'email', 'Phone Number' => 'phone_number', 'Status' => 'status', 'Password' => 'password', 'Created At' => 'created_at', 'Address Line1' => 'line1', 'Address Line2' => 'line2', 'Country' => 'country', 'State' => 'state', 'State Code' => 'state_code', 'City' => 'city', 'Postcode/Zip' => 'postal_code'];
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
            $country = (new ISO3166)->name($attributes['country']);
            $attributes['country_code'] = $country['alpha2'];
        }
        $user = static::firstOrNew(['email' => $attributes['email']], $attributes);
        if (isset($attributes['created_at']) && ! empty($attributes['created_at'])) {
            $user->created_at = $attributes['created_at'];
        }
        $user->deleted_at = null;
        $user->save();
        $user->updateOrCreateAddress($attributes);
    }

    public function getGuardAttribute()
    {
        return $this->guard;
    }

    protected static function newFactory()
    {
        return AdminFactory::new();
    }

    protected static function booted()
    {
        static::addGlobalScope('default', function (Builder $builder) {
            $builder->withMax('lastLogin as last_login_at', 'created_at');
        });
    }
}
