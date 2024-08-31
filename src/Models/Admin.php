<?php

namespace Coderstm\Models;

use Coderstm\Models\Log;
use Coderstm\Traits\Core;
use League\ISO3166\ISO3166;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\Addressable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Coderstm\Traits\HasPermissionGroup;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Coderstm\Database\Factories\AdminFactory;
use Coderstm\Exceptions\ImportFailedException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Coderstm\Exceptions\ImportSkippedException;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use Core, Notifiable, HasPermissionGroup, HasApiTokens, Fileable, Addressable;

    protected $guard = "admins";

    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'email',
        'password',
        'phone_number',
        'is_supper_admin',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_supper_admin' => 'boolean',
    ];

    protected $appends = [
        'name',
        'guard',
    ];

    protected $with = [
        'avatar',
        'address',
    ];

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getGuardAttribute()
    {
        return $this->guard;
    }

    public function lastLogin(): MorphOne
    {
        return $this->morphOne(Log::class, 'logable')
            ->where('type', 'login')
            ->orderBy('created_at', 'desc');
    }

    public function createdBy(): MorphOne
    {
        return $this->morphOne(Log::class, 'logable')->whereType('created');
    }

    public function is_active()
    {
        return $this->is_active;
    }

    public function scopeWhereName($query, $filter)
    {
        return $query->where(DB::raw("CONCAT(`first_name`,`last_name`)"), 'like', "%{$filter}%");
    }

    public function scopeExcludeCurrent($query)
    {
        return $query->where('id', '<>', user()->id);
    }


    public function scopeSortBy($query, $column = 'CREATED_AT_ASC', $direction = 'asc'): Builder
    {
        switch ($column) {
            case 'last_login':
                $query->orderByRaw('(SELECT MAX(created_at) FROM logs WHERE logs.logable_id = admins.id AND logs.logable_type = ? AND logs.type = ?) ' . ($direction ?? 'asc'), [$this->getMorphClass(), 'login']);
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

    public function toLoginResponse()
    {
        $response = $this->append('modules')->toArray();

        $response['permissions'] = $this->getScopes();

        return $response;
    }

    public function getShortCodes(): array
    {
        return [
            '{{ADMIN_NAME}}' => $this->name,
            '{{ADMIN_ID}}' => $this->id,
            '{{ADMIN_FIRST_NAME}}' => $this->first_name,
            '{{ADMIN_LAST_NAME}}' => $this->last_name,
            '{{ADMIN_EMAIL}}' => $this->email,
            '{{ADMIN_PHONE_NUMBER}}' => $this->phone_number,
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
            "Password" => 'password',
            "Created At" => 'created_at',
            "Address Line1" => 'line1',
            "Address Line2" => 'line2',
            "Country" => 'country',
            "State" => 'state',
            "State Code" => 'state_code',
            "City" => 'city',
            "Postcode/Zip" => 'postal_code',
        ];
    }

    public static function createFromCsv(array $attributes = [], array $options = [])
    {
        $replaceByEmail = isset($options['email_overwrite']) && $options['email_overwrite'];
        $user = static::where('email', $attributes['email'])->first();

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

        if (isset($attributes['created_at']) && !empty($attributes['created_at'])) {
            $user->created_at = $attributes['created_at'];
        }

        $user->save();

        $user->updateOrCreateAddress($attributes);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return AdminFactory::new();
    }
}
