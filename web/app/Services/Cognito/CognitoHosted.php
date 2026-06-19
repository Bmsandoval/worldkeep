<?php

namespace App\Services\Cognito;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CognitoHosted
{
    /**
     * @return array{login_url: string, signup_url: string, logout_url: string, redirect_uri: string, oauth_authorize_url?: string}
     */
    public function buildHostedUrls(): array
    {
        $this->ensureConfigured();

        $redirect = rawurlencode((string) config('cognito.redirect_uri'));
        $clientId = (string) config('cognito.app_client_id');
        $query = "response_type=code&client_id={$clientId}&redirect_uri={$redirect}";
        $base = $this->tokenHost();

        return [
            'login_url' => "{$base}/login?{$query}",
            'signup_url' => "{$base}/signup?{$query}",
            'logout_url' => $this->logoutUrl(),
            'redirect_uri' => (string) config('cognito.redirect_uri'),
            'oauth_authorize_url' => $this->oauthAuthorizeUrl(),
        ];
    }

    /**
     * Public OAuth settings for CLI/desktop/mobile clients (no client secret).
     *
     * @return array{region: string, domain: string, clientId: string, apiBaseURL: string, redirectURI: string}
     */
    public function buildCliClientConfig(): array
    {
        $this->ensureConfigured();

        return [
            'region' => (string) config('cognito.region'),
            'domain' => (string) config('cognito.domain'),
            'clientId' => (string) config('cognito.app_client_id'),
            'apiBaseURL' => rtrim((string) config('app.url'), '/'),
            'redirectURI' => (string) config('cognito.cli_redirect_uri'),
        ];
    }

    public function loginUrl(?string $state = null): string
    {
        $url = $this->buildHostedUrls()['login_url'];

        return $state ? $url.'&state='.rawurlencode($state) : $url;
    }

    public function signupUrl(?string $state = null): string
    {
        $url = $this->buildHostedUrls()['signup_url'];

        return $state ? $url.'&state='.rawurlencode($state) : $url;
    }

    public function logoutUrl(): string
    {
        $base = $this->tokenHost();
        $clientId = rawurlencode((string) config('cognito.app_client_id'));
        $logoutUri = rawurlencode((string) config('cognito.logout_uri'));

        return "{$base}/logout?client_id={$clientId}&logout_uri={$logoutUri}";
    }

    /**
     * @return array{id_token: string, access_token: string, refresh_token: string, expires_in: int}
     */
    public function exchangeAuthorizationCode(string $code, ?string $redirectUri = null, ?string $codeVerifier = null): array
    {
        $this->ensureConfigured();

        if ($code === '') {
            throw new RuntimeException('authorization code is empty');
        }

        $redirect = $redirectUri ?? (string) config('cognito.redirect_uri');
        if (! $this->isAllowedRedirectUri($redirect)) {
            throw new RuntimeException('redirect_uri is not allowed');
        }

        $payload = [
            'grant_type' => 'authorization_code',
            'client_id' => config('cognito.app_client_id'),
            'code' => $code,
            'redirect_uri' => $redirect,
        ];

        if ($codeVerifier !== null && $codeVerifier !== '') {
            $payload['code_verifier'] = $codeVerifier;
        }

        $secret = config('cognito.app_client_secret');
        if ($secret) {
            $payload['client_secret'] = $secret;
        }

        $response = $this->cognitoHttp()->asForm()
            ->timeout(15)
            ->post($this->tokenHost().'/oauth2/token', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('token exchange failed: '.$response->body());
        }

        $data = $response->json();
        if (! empty($data['error'])) {
            throw new RuntimeException(($data['error'] ?? 'error').': '.($data['error_description'] ?? ''));
        }

        if (empty($data['id_token'])) {
            throw new RuntimeException('no id_token in token response');
        }

        return [
            'id_token' => (string) $data['id_token'],
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => (string) ($data['refresh_token'] ?? ''),
            'expires_in' => (int) ($data['expires_in'] ?? 0),
        ];
    }

    /**
     * @return array{id_token: string, access_token: string, refresh_token: string, expires_in: int}
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $this->ensureConfigured();

        if ($refreshToken === '') {
            throw new RuntimeException('refresh token is empty');
        }

        $payload = [
            'grant_type' => 'refresh_token',
            'client_id' => config('cognito.app_client_id'),
            'refresh_token' => $refreshToken,
        ];

        $secret = config('cognito.app_client_secret');
        if ($secret) {
            $payload['client_secret'] = $secret;
        }

        $response = $this->cognitoHttp()->asForm()
            ->timeout(15)
            ->post($this->tokenHost().'/oauth2/token', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('token refresh failed: '.$response->body());
        }

        $data = $response->json();
        if (! empty($data['error'])) {
            throw new RuntimeException(($data['error'] ?? 'error').': '.($data['error_description'] ?? ''));
        }

        return [
            'id_token' => (string) ($data['id_token'] ?? ''),
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => (string) ($data['refresh_token'] ?? $refreshToken),
            'expires_in' => (int) ($data['expires_in'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validateIdToken(string $idToken): array
    {
        $this->ensureConfigured();

        $header = $this->decodeHeader($idToken);
        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new RuntimeException('missing kid in token header');
        }

        $keys = $this->jwks();
        if (! isset($keys[$kid])) {
            throw new RuntimeException("unknown kid: {$kid}");
        }

        $this->verifyRs256Signature($idToken, $keys[$kid]);
        $claims = $this->decodePayload($idToken);

        $issuer = sprintf(
            'https://cognito-idp.%s.amazonaws.com/%s',
            config('cognito.region'),
            config('cognito.user_pool_id'),
        );

        if (($claims['iss'] ?? '') !== $issuer) {
            throw new RuntimeException('iss claim invalid');
        }

        $tokenUse = $claims['token_use'] ?? '';
        if (! in_array($tokenUse, ['id', 'access'], true)) {
            throw new RuntimeException('token_use should be id or access');
        }

        if (($claims['exp'] ?? 0) <= time()) {
            throw new RuntimeException('token is expired');
        }

        $audience = $claims['aud'] ?? $claims['client_id'] ?? null;
        $clientId = (string) config('cognito.app_client_id');
        $audOk = is_array($audience)
            ? in_array($clientId, $audience, true)
            : $audience === $clientId;
        if (! $audOk) {
            throw new RuntimeException('audience claim invalid');
        }

        return $claims;
    }

    public function extIdFromClaims(array $claims): string
    {
        $sub = $claims['sub'] ?? '';
        if (! is_string($sub) || $sub === '') {
            throw new RuntimeException('missing sub claim');
        }

        return $sub;
    }

    public function emailFromClaims(array $claims): string
    {
        $email = $claims['email'] ?? '';

        return is_string($email) ? $email : '';
    }

    public function nameFromClaims(array $claims): string
    {
        $name = $claims['name'] ?? '';
        if (is_string($name) && $name !== '') {
            return $name;
        }

        $given = $claims['given_name'] ?? '';
        $family = $claims['family_name'] ?? '';
        if (is_string($given) && $given !== '') {
            if (is_string($family) && $family !== '') {
                return trim($given.' '.$family);
            }

            return $given;
        }

        return $this->emailFromClaims($claims);
    }

    /**
     * @return list<string>
     */
    public function groupsFromClaims(array $claims): array
    {
        $raw = $claims['cognito:groups'] ?? [];
        if (! is_array($raw)) {
            return [];
        }

        $groups = [];
        foreach ($raw as $group) {
            if (is_string($group)) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    /**
     * Fetch the OpenID userInfo for an access token (sub, email, name, …).
     * Used to provision a local user on a client's first API call when the
     * access token alone lacks email/name claims.
     *
     * @return array<string, mixed>
     */
    public function userInfo(string $accessToken): array
    {
        $this->ensureConfigured();

        $response = $this->cognitoHttp()
            ->withToken($accessToken)
            ->timeout(10)
            ->get($this->tokenHost().'/oauth2/userInfo');

        if (! $response->successful()) {
            throw new RuntimeException('userInfo request failed: '.$response->status());
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    public function isConfigured(): bool
    {
        return config('cognito.user_pool_id')
            && config('cognito.app_client_id')
            && config('cognito.domain')
            && config('cognito.redirect_uri');
    }

    public function oauthAuthorizeUrl(?string $state = null): string
    {
        $this->ensureConfigured();

        $base = $this->tokenHost();
        $scope = rawurlencode('openid email profile');
        $redirect = rawurlencode((string) config('cognito.redirect_uri'));
        $clientId = (string) config('cognito.app_client_id');
        $url = "{$base}/oauth2/authorize?response_type=code&client_id={$clientId}&redirect_uri={$redirect}&scope={$scope}";
        if ($state) {
            $url .= '&state='.rawurlencode($state);
        }

        return $url;
    }

    public function tokenHost(): string
    {
        $domain = (string) config('cognito.domain');
        $region = (string) config('cognito.region');

        if (! str_contains($domain, '.')) {
            return "https://{$domain}.auth.{$region}.amazoncognito.com";
        }

        if (str_starts_with($domain, 'https://')) {
            return rtrim($domain, '/');
        }

        return 'https://'.rtrim($domain, '/');
    }

    private function isAllowedRedirectUri(string $redirect): bool
    {
        if (in_array($redirect, config('cognito.allowed_redirect_uris', []), true)) {
            return true;
        }

        // Expo Go local dev (exp://…/--/auth/redirect) — not a fixed config value.
        if (config('app.debug') && str_starts_with($redirect, 'exp://') && str_contains($redirect, '/auth/redirect')) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function jwks(): array
    {
        $region = (string) config('cognito.region');
        $poolId = (string) config('cognito.user_pool_id');
        $cacheKey = "cognito.jwks.v2.{$region}.{$poolId}";

        return Cache::remember($cacheKey, 3600, function () use ($region, $poolId): array {
            $url = "https://cognito-idp.{$region}.amazonaws.com/{$poolId}/.well-known/jwks.json";
            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('fetch jwks failed');
            }

            $keys = [];
            foreach ($response->json('keys', []) as $jwk) {
                if (! is_array($jwk) || empty($jwk['kid'])) {
                    continue;
                }
                $keys[$jwk['kid']] = $this->rsaPublicKeyFromJwk(
                    (string) ($jwk['e'] ?? ''),
                    (string) ($jwk['n'] ?? ''),
                );
            }

            return $keys;
        });
    }

    private function rsaPublicKeyFromJwk(string $rawE, string $rawN): string
    {
        $modulus = $this->encodeLengthPrefixedInteger($this->base64UrlDecode($rawN));
        $exponent = $this->encodeLengthPrefixedInteger($this->base64UrlDecode($rawE));
        $sequence = $this->encodeLengthPrefixed($modulus.$exponent, 0x30);

        // PKCS#1 RSAPublicKey — OpenSSL 3 rejects our SPKI encoding on PHP 8.4+.
        return "-----BEGIN RSA PUBLIC KEY-----\n"
            .chunk_split(base64_encode($sequence), 64, "\n")
            ."-----END RSA PUBLIC KEY-----\n";
    }

    private function encodeLengthPrefixedInteger(string $bytes): string
    {
        if (ord($bytes[0]) > 0x7f) {
            $bytes = "\x00".$bytes;
        }

        return $this->encodeLengthPrefixed($bytes, 0x02);
    }

    private function encodeLengthPrefixed(string $bytes, int $tag): string
    {
        $length = strlen($bytes);
        if ($length < 0x80) {
            return chr($tag).chr($length).$bytes;
        }

        $lenBytes = ltrim(pack('N', $length), "\0");
        if ($length > 0xFFFFFF) {
            throw new RuntimeException('integer too large');
        }

        return chr($tag).chr(0x80 | strlen($lenBytes)).$lenBytes.$bytes;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new RuntimeException('invalid base64');
        }

        return $decoded;
    }

    private function verifyRs256Signature(string $jwt, string $publicKeyPem): void
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new RuntimeException('invalid jwt');
        }

        $signed = $parts[0].'.'.$parts[1];
        $signature = $this->base64UrlDecode($parts[2]);
        $key = openssl_pkey_get_public($publicKeyPem);
        if ($key === false) {
            throw new RuntimeException('invalid public key');
        }

        $valid = openssl_verify($signed, $signature, $key, OPENSSL_ALGO_SHA256);
        if ($valid !== 1) {
            throw new RuntimeException('invalid token signature');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            throw new RuntimeException('invalid jwt');
        }

        $json = $this->base64UrlDecode($parts[1]);
        $payload = json_decode($json, true);

        return is_array($payload) ? $payload : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeHeader(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            throw new RuntimeException('invalid jwt');
        }

        $json = $this->base64UrlDecode($parts[0]);
        $header = json_decode($json, true);

        return is_array($header) ? $header : [];
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('cognito is not configured');
        }
    }

    /**
     * HTTP client for Cognito OAuth host. In local dev, falls back to public DNS
     * when the system resolver has not yet picked up new *.amazoncognito.com records.
     */
    private function cognitoHttp(): \Illuminate\Http\Client\PendingRequest
    {
        $host = parse_url($this->tokenHost(), PHP_URL_HOST);
        $options = $this->resolveHostOptions(is_string($host) ? $host : null);

        return Http::withOptions($options);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveHostOptions(?string $host): array
    {
        if (! is_string($host) || $host === '' || ! app()->isLocal()) {
            return [];
        }

        if ($this->hostResolves($host)) {
            return [];
        }

        $ip = $this->lookupHostViaPublicDns($host);
        if ($ip === null) {
            return [];
        }

        return ['curl' => [CURLOPT_RESOLVE => ["{$host}:443:{$ip}"]]];
    }

    private function hostResolves(string $host): bool
    {
        if (function_exists('checkdnsrr') && checkdnsrr($host, 'A')) {
            return true;
        }

        $records = dns_get_record($host, DNS_A);

        return is_array($records) && $records !== [];
    }

    private function lookupHostViaPublicDns(string $host): ?string
    {
        try {
            $response = Http::timeout(5)->get('https://dns.google/resolve', [
                'name' => $host,
                'type' => 'A',
            ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        foreach ($response->json('Answer', []) as $answer) {
            if (is_array($answer) && ($answer['type'] ?? null) === 1 && ! empty($answer['data'])) {
                return (string) $answer['data'];
            }
        }

        return null;
    }
}
