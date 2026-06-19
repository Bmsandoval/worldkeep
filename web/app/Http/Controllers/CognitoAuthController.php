<?php

namespace App\Http\Controllers;

use App\Services\Cognito\CognitoHosted;
use App\Services\Cognito\CognitoSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CognitoAuthController extends Controller
{
    public function __construct(
        private readonly CognitoHosted $cognito,
        private readonly CognitoSession $session,
    ) {}

    public function authorize(): JsonResponse
    {
        if (! $this->cognito->isConfigured()) {
            return response()->json(['error' => 'cognito is not configured'], 503);
        }

        return response()->json($this->cognito->buildHostedUrls());
    }

    public function cliConfig(): JsonResponse
    {
        if (! $this->cognito->isConfigured()) {
            return response()->json(['error' => 'cognito is not configured'], 503);
        }

        return response()->json($this->cognito->buildCliClientConfig());
    }

    public function cliToken(Request $request): JsonResponse
    {
        if (! $this->cognito->isConfigured()) {
            return response()->json(['error' => 'cognito is not configured'], 503);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return response()->json(['error' => 'missing code query parameter'], 400);
        }

        $redirectUri = $request->query('redirect_uri');
        $redirectUri = is_string($redirectUri) && $redirectUri !== ''
            ? $redirectUri
            : (string) config('cognito.cli_redirect_uri');

        $codeVerifier = $request->query('code_verifier');
        $codeVerifier = is_string($codeVerifier) && $codeVerifier !== '' ? $codeVerifier : null;

        try {
            $tokens = $this->cognito->exchangeAuthorizationCode($code, $redirectUri, $codeVerifier);
            $claims = $this->cognito->validateIdToken($tokens['id_token']);
        } catch (RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 401);
        }

        return response()->json([
            'id_token' => $tokens['id_token'],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'] > 0 ? $tokens['expires_in'] : 3600,
            'email' => $this->cognito->emailFromClaims($claims),
            'sub' => $this->cognito->extIdFromClaims($claims),
        ]);
    }

    public function cliRefresh(Request $request): JsonResponse
    {
        if (! $this->cognito->isConfigured()) {
            return response()->json(['error' => 'cognito is not configured'], 503);
        }

        $refreshToken = (string) ($request->input('refresh_token') ?? '');
        if ($refreshToken === '') {
            return response()->json(['error' => 'missing refresh_token'], 400);
        }

        try {
            $tokens = $this->cognito->refreshAccessToken($refreshToken);
        } catch (RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 401);
        }

        return response()->json([
            'access_token' => $tokens['access_token'],
            'expires_in' => $tokens['expires_in'] > 0 ? $tokens['expires_in'] : 3600,
            'id_token' => $tokens['id_token'] !== '' ? $tokens['id_token'] : null,
            'refresh_token' => $tokens['refresh_token'] !== '' ? $tokens['refresh_token'] : null,
        ]);
    }

    public function token(Request $request): JsonResponse
    {
        if (! $this->cognito->isConfigured()) {
            return response()->json(['error' => 'cognito is not configured'], 503);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return response()->json(['error' => 'missing code query parameter'], 400);
        }

        $redirectUri = $request->query('redirect_uri');
        $redirectUri = is_string($redirectUri) && $redirectUri !== '' ? $redirectUri : null;

        try {
            $payload = $this->session->completeTokenExchange($request, $code, null, $redirectUri);
        } catch (RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 401);
        }

        return response()->json($payload);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return response()->json(['user' => $this->session->userPayload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}
