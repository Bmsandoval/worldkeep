<?php

namespace App\Services\Cognito;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CognitoSession
{
    public function __construct(
        private readonly CognitoHosted $cognito,
        private readonly CognitoUserResolver $users,
    ) {}

    /**
     * @return array{claims: array{user_sub: int, groups: list<string>}, access_token?: string, expires_in?: int}
     */
    public function completeTokenExchange(
        Request $request,
        string $code,
        ?string $inviteCode = null,
        ?string $redirectUri = null,
    ): array {
        $tokens = $this->cognito->exchangeAuthorizationCode($code, $redirectUri);
        $claims = $this->cognito->validateIdToken($tokens['id_token']);
        $extId = $this->cognito->extIdFromClaims($claims);
        $user = $this->users->resolveAfterHostedLogin(
            $extId,
            $this->cognito->nameFromClaims($claims),
            $this->cognito->emailFromClaims($claims),
            $inviteCode,
        );

        if ($tokens['refresh_token'] !== '') {
            $user->update(['cognito_refresh_token' => $tokens['refresh_token']]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('cognito.id_token', $tokens['id_token']);

        return [
            'claims' => [
                'user_sub' => $user->id,
                'groups' => $this->cognito->groupsFromClaims($claims),
            ],
            'access_token' => $tokens['access_token'] !== '' ? $tokens['access_token'] : null,
            'expires_in' => $tokens['expires_in'] > 0 ? $tokens['expires_in'] : 3600,
        ];
    }

    public function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
