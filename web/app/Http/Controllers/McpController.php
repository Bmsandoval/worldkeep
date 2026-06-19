<?php

namespace App\Http\Controllers;

use App\Services\Cognito\CognitoHosted;
use App\Services\WorldKeep\Mcp\McpOAuth;
use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class McpController extends Controller
{
    public function __construct(
        private readonly Server $server,
        private readonly McpOAuth $oauth,
        private readonly CognitoHosted $cognito,
    ) {}

    public function __invoke(Request $request): JsonResponse|Response
    {
        if ($request->isMethod('GET')) {
            return response('method not allowed', 405)->header('Allow', 'POST');
        }

        if (! $request->isMethod('POST')) {
            return response('method not allowed', 405)->header('Allow', 'POST');
        }

        $body = $request->getContent();
        if ($body !== '' && str_starts_with(ltrim($body), '[')) {
            $batch = json_decode($body, true);
            if (! is_array($batch)) {
                return response()->json([
                    'jsonrpc' => '2.0',
                    'error' => Protocol::rpcError(Protocol::CODE_PARSE_ERROR, 'parse error'),
                ]);
            }

            $out = [];
            $challenge = null;
            foreach ($batch as $req) {
                if (! is_array($req)) {
                    continue;
                }
                [$resp, $shouldReply, $authChallenge] = $this->dispatchRequest($request, $req);
                if ($authChallenge !== null) {
                    $challenge = $authChallenge;
                }
                if ($shouldReply && $resp !== null) {
                    $out[] = $resp;
                }
            }

            if ($out === []) {
                return response('', 202);
            }

            return $this->jsonResponse($out, $challenge);
        }

        $req = json_decode($body, true);
        if (! is_array($req)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => Protocol::rpcError(Protocol::CODE_INVALID_REQUEST, 'invalid request'),
            ]);
        }

        [$resp, $shouldReply, $challenge] = $this->dispatchRequest($request, $req);
        if (! $shouldReply) {
            return response('', 202);
        }

        return $this->jsonResponse($resp, $challenge);
    }

    /**
     * @param  array<string, mixed>  $req
     * @return array{0: array<string, mixed>|list<array<string, mixed>>|null, 1: bool, 2: string|null}
     */
    private function dispatchRequest(Request $request, array $req): array
    {
        $method = (string) ($req['method'] ?? '');
        if ($method === 'tools/call' && ! $this->oauth->bearerTokenIsValid($request->bearerToken(), $this->cognito)) {
            $id = $req['id'] ?? null;
            if ($id === null) {
                return [null, false, null];
            }

            $challenge = $this->oauth->wwwAuthenticate();

            return [$this->authToolReply($id, $challenge), true, $challenge];
        }

        [$resp, $shouldReply] = $this->server->dispatch($req);

        return [$resp, $shouldReply, null];
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $payload
     */
    private function jsonResponse(array $payload, ?string $wwwAuthenticate): JsonResponse
    {
        $response = response()->json($payload);
        if ($wwwAuthenticate !== null) {
            $response->header('WWW-Authenticate', $wwwAuthenticate);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function authToolReply(mixed $id, string $challenge): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => Protocol::toolAuthError($challenge),
        ];
    }
}
