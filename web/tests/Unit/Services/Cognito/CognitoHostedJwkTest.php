<?php

namespace Tests\Unit\Services\Cognito;

use App\Services\Cognito\CognitoHosted;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CognitoHostedJwkTest extends TestCase
{
    public function test_jwks_rsa_public_keys_are_openssl_compatible(): void
    {
        Http::fake([
            'cognito-idp.us-east-1.amazonaws.com/*' => Http::response([
                'keys' => [[
                    'alg' => 'RS256',
                    'e' => 'AQAB',
                    'kid' => 'test-kid',
                    'kty' => 'RSA',
                    'n' => '0LHBxk38UEnl_tE80ycf6lfRmW_7eXvTx3eE0Jrd2IYdGmax6_OKROtBEjaDpkg1K0bfA7kQLJLScTOH4OHKquv-3vmtMIJVeIltDaMSuZ1nBjSonO8mM44aJBlNb1P36MWDywV9SpuxTC753zT8p-Usl1qtxfuKRzjdbSWLN3cMDuEYacpEx7haqAT80NVTLqYDApUyC45_g9ye5ELlg7xcsPVZUWrmtaQk--1UH4PqCGxr24EgPOtEvKlpgptu1kV73gBoNiRMKl5YcKPofeCmfnIe0mnBQiHCILOCkVoIevxOTPFJ90WtZR9y3Q6fBrYxjOPB3N_WSKS3unA3-w',
                    'use' => 'sig',
                ]],
            ]),
        ]);

        config([
            'cognito.region' => 'us-east-1',
            'cognito.user_pool_id' => 'us-east-1_testpool',
            'cognito.app_client_id' => 'client-id',
            'cognito.domain' => 'hub-local',
            'cognito.redirect_uri' => 'http://127.0.0.1:8000/app/auth/redirect',
        ]);

        $header = base64_encode(json_encode(['alg' => 'RS256', 'kid' => 'test-kid', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['sub' => 'x']));
        $token = rtrim(strtr($header, '+/', '-_'), '=').'.'
            .rtrim(strtr($payload, '+/', '-_'), '=').'.'
            .'signature';

        $cognito = app(CognitoHosted::class);

        try {
            $cognito->validateIdToken($token);
            $this->fail('Expected signature validation to fail with dummy token');
        } catch (\RuntimeException $exception) {
            $this->assertNotSame(
                'invalid public key',
                $exception->getMessage(),
                'JWK conversion must produce an OpenSSL-compatible RSA public key',
            );
        }
    }
}
