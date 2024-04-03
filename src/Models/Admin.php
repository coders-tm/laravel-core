<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Coderstm\Models\Log;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\Addressable;
use Laravel\Sanctum\HasApiTokens;
use Coderstm\Traits\HasPermissionGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class Admin extends Authenticatable
{
    use Core, Notifiable, HasPermissionGroup, HasApiTokens, Fileable, Addressable;

    protected $guard = "admins";

    protected $fillable = [
        'first_name',
        'last_name',
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

    public function lastLogin()
    {
        return $this->morphOne(Log::class, 'logable')->where('type', 'login')->latestOfMany();
    }

    public function createdBy()
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

    public function scopeSortBy($query, $column = 'CREATED_AT_ASC', $direction = 'asc')
    {
        switch ($column) {
            case 'last_login':
                $query->select("{$this->getTable()}.*")
                    ->leftJoin('logs', function ($join) {
                        $join->on('logs.logable_id', '=', "{$this->getTable()}.id")
                            ->where('logs.logable_type', '=', $this->getMorphClass());
                    })
                    ->addSelect(DB::raw('logs.created_at AS last_login'))
                    ->groupBy("{$this->getTable()}.id")
                    ->orderBy('last_login', $direction ?? 'asc');
                break;

            case 'email':
                $query->orderBy('email', $direction ?? 'asc');
                break;

            case 'name':
                $query->select("{$this->getTable()}.*")
                    ->addSelect(DB::raw("CONCAT(`first_name`, `first_name`) AS name"))
                    ->orderBy('name', $direction ?? 'asc');
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
}
