<?php

namespace App\Services\Cognito;

use App\Models\User;
use RuntimeException;

class CognitoUserResolver
{
    public function resolveAfterHostedLogin(string $extId, string $name, string $email, ?string $inviteCode = null): User
    {
        $existing = User::query()->where('ext_id', $extId)->first();
        if ($existing !== null) {
            return $existing;
        }

        if ($email === '') {
            throw new RuntimeException('email required to provision new user');
        }

        $shadow = User::query()->where('email', $email)->whereNull('ext_id')->first();
        if ($shadow !== null) {
            $shadow->update([
                'ext_id' => $extId,
                'name' => $name !== '' ? $name : $shadow->name,
                'email_verified_at' => $shadow->email_verified_at ?? now(),
            ]);

            return $shadow->fresh();
        }

        return User::query()->create([
            'ext_id' => $extId,
            'name' => $name !== '' ? $name : $email,
            'email' => $email,
            'email_verified_at' => now(),
        ]);
    }
}
