<?php

namespace Tests\Feature;

use App\Services\WorldKeep\Mcp\ToolDefinitions;
use Tests\TestCase;

class McpOAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://worldkeep.example.test',
            'worldkeep.mcp.public_url' => 'https://worldkeep.example.test/mcp',
            'worldkeep.mcp.cognito_issuer' => 'https://cognito-idp.us-east-1.amazonaws.com/us-east-1_TestPool',
            'cognito.region' => 'us-east-1',
            'cognito.user_pool_id' => 'us-east-1_TestPool',
            'cognito.app_client_id' => 'test-client',
        ]);
    }

    public function test_protected_resource_metadata_at_root_well_known(): void
    {
        $response = $this->get('/.well-known/oauth-protected-resource');

        $response->assertOk();
        $response->assertJson([
            'resource' => 'https://worldkeep.example.test/mcp',
            'authorization_servers' => ['https://cognito-idp.us-east-1.amazonaws.com/us-east-1_TestPool'],
            'scopes_supported' => ['openid', 'email'],
            'bearer_methods_supported' => ['header'],
        ]);
    }

    public function test_protected_resource_metadata_at_path_suffix(): void
    {
        $this->get('/.well-known/oauth-protected-resource/mcp')
            ->assertOk()
            ->assertJsonPath('resource', 'https://worldkeep.example.test/mcp');
    }

    public function test_protected_resource_metadata_at_mcp_prefixed_well_known(): void
    {
        $this->get('/mcp/.well-known/oauth-protected-resource')
            ->assertOk()
            ->assertJsonPath('resource', 'https://worldkeep.example.test/mcp');
    }

    public function test_unauthenticated_tool_call_sets_www_authenticate(): void
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get_campaign_overview',
                'arguments' => [],
            ],
        ]);

        $response->assertOk();
        $response->assertHeader(
            'WWW-Authenticate',
            'Bearer resource_metadata="https://worldkeep.example.test/mcp/.well-known/oauth-protected-resource", error="invalid_token", error_description="Sign in to WorldKeep"',
        );
        $response->assertJsonPath('result.isError', true);
        $response->assertJsonPath('result._meta.mcp/www_authenticate.0', $response->headers->get('WWW-Authenticate'));
    }

    public function test_tools_list_does_not_require_auth(): void
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ]);

        $response->assertOk();
        $response->assertJsonPath('result.tools.0.name', 'get_campaign_dashboard');
        $response->assertHeaderMissing('WWW-Authenticate');
    }

    public function test_every_tool_declares_oauth_security_scheme(): void
    {
        foreach (ToolDefinitions::all() as $tool) {
            $this->assertArrayHasKey('securitySchemes', $tool);
            $this->assertSame('oauth2', $tool['securitySchemes'][0]['type'] ?? null);
            $this->assertSame(['openid', 'email'], $tool['securitySchemes'][0]['scopes'] ?? null);
        }
    }
}
