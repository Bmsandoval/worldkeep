<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Cognito\CognitoHosted;
use App\Services\Cognito\CognitoUserResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates an API request via an existing session (web / tests) OR a
 * Cognito access token sent as `Authorization: Bearer <token>` (CLI/desktop/mobile).
 * On an unknown but valid token, provisions the local user from /oauth2/userInfo.
 */
class AuthenticateCognito
{
    public function __construct(
        private readonly CognitoHosted $cognito,
        private readonly CognitoUserResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            return $next($request);
        }

        $token = $request->bearerToken();
        if ($token === null || $token === '') {
            return $this->unauthenticated();
        }

        try {
            $claims = $this->cognito->validateIdToken($token);
            $sub = $this->cognito->extIdFromClaims($claims);
        } catch (RuntimeException $e) {
            return $this->unauthenticated($e->getMessage());
        }

        $user = User::query()->where('ext_id', $sub)->first();

        if ($user === null) {
            try {
                $info = $this->cognito->userInfo($token);
            } catch (RuntimeException $e) {
                return $this->unauthenticated('could not provision user: '.$e->getMessage());
            }

            $user = $this->resolver->resolveAfterHostedLogin(
                $sub,
                (string) ($info['name'] ?? ''),
                (string) ($info['email'] ?? ''),
            );
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthenticated(string $message = 'Unauthenticated.'): Response
    {
        return response()->json(['message' => $message], 401);
    }
}
