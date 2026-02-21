<?php

namespace Coderstm\Traits;

use DateTimeInterface;
use Laravel\Sanctum\HasApiTokens as SanctumHasApiTokens;
use Laravel\Sanctum\NewAccessToken;

trait HasApiTokens
{
    use SanctumHasApiTokens;

    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null)
    {
        $plainTextToken = $this->generateTokenString();
        $token = $this->tokens()->create(['name' => $name, 'token' => hash('sha256', $plainTextToken), 'abilities' => $abilities, 'expires_at' => $expiresAt]);

        return new NewAccessToken($token, $plainTextToken);
    }
}
