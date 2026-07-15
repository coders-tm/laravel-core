<?php

namespace Coderstm\Traits;

use Coderstm\Events\DeviceTokenAdded;
use Coderstm\Models\DeviceToken;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDeviceTokens
{
    public function routeNotificationForFcm(): array
    {
        return $this->deviceTokens()->pluck('token')->toArray();
    }

    public function deviceTokens(): MorphMany
    {
        return $this->morphMany(DeviceToken::class, 'tokenable');
    }

    public function addDeviceToken(string $deviceToken, ?string $appId = null)
    {
        if (! $deviceToken) {
            throw new \InvalidArgumentException('Device token cannot be empty.');
        }

        $token = $this->deviceTokens()->updateOrCreate(
            ['token' => $deviceToken],
            ['app_id' => $appId]
        );

        DeviceTokenAdded::dispatch($this, $deviceToken, $appId);

        return $token;
    }
}
