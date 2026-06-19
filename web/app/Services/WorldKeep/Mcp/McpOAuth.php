<?php

namespace App\Services\WorldKeep\Mcp;

use App\Services\Cognito\CognitoHosted;

/**
 * RFC 9728 protected-resource metadata and ChatGPT account-linking challenges
 * for the in-process Laravel MCP server (Timelord-compatible, no Go sidecar).
 */
final class McpOAuth
{
    public function publicUrl(): string
    {
        $configured = trim((string) config('worldkeep.mcp.public_url', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return rtrim((string) config('app.url'), '/').'/mcp';
    }

    public function cognitoIssuer(): string
    {
        $configured = trim((string) config('worldkeep.mcp.cognito_issuer', ''));
        if ($configured !== '') {
            return $configured;
        }

        $poolId = (string) config('cognito.user_pool_id', '');
        if ($poolId === '') {
            return '';
        }

        return sprintf(
            'https://cognito-idp.%s.amazonaws.com/%s',
            config('cognito.region', 'us-east-1'),
            $poolId,
        );
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        /** @var list<string> $scopes */
        $scopes = config('worldkeep.mcp.oauth_scopes', ['openid', 'email']);

        return $scopes;
    }

    /**
     * @return array<string, mixed>
     */
    public function protectedResourceMetadata(): array
    {
        $servers = array_values(array_filter([$this->cognitoIssuer()]));

        return [
            'resource' => $this->publicUrl(),
            'authorization_servers' => $servers,
            'scopes_supported' => $this->scopes(),
            'bearer_methods_supported' => ['header'],
        ];
    }

    public function wwwAuthenticate(
        string $errorCode = 'invalid_token',
        string $description = 'Sign in to WorldKeep',
    ): string {
        $metaUrl = $this->publicUrl().'/.well-known/oauth-protected-resource';
        $value = sprintf('Bearer resource_metadata="%s"', $metaUrl);
        if ($errorCode !== '') {
            $value .= sprintf(', error="%s"', $errorCode);
        }
        if ($description !== '') {
            $value .= sprintf(', error_description="%s"', $description);
        }

        return $value;
    }

    public function bearerTokenIsValid(?string $token, CognitoHosted $cognito): bool
    {
        if ($token === null || trim($token) === '') {
            return false;
        }

        try {
            $cognito->validateIdToken($token);

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }
}
