<?php

namespace App\Http\Controllers;

use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class McpController extends Controller
{
    public function __construct(private readonly Server $server) {}

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
            foreach ($batch as $req) {
                if (! is_array($req)) {
                    continue;
                }
                [$resp, $shouldReply] = $this->server->dispatch($req);
                if ($shouldReply && $resp !== null) {
                    $out[] = $resp;
                }
            }

            if ($out === []) {
                return response('', 202);
            }

            return response()->json($out);
        }

        $req = json_decode($body, true);
        if (! is_array($req)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => Protocol::rpcError(Protocol::CODE_INVALID_REQUEST, 'invalid request'),
            ]);
        }

        [$resp, $shouldReply] = $this->server->dispatch($req);
        if (! $shouldReply) {
            return response('', 202);
        }

        return response()->json($resp);
    }
}
