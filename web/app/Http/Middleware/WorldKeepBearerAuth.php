<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorldKeepBearerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim((string) config('worldkeep.api_token', ''));
        if ($token === '') {
            return $next($request);
        }

        $path = '/'.ltrim($request->path(), '/');
        if ($path === 'healthz' || str_starts_with($path, 'mcp')) {
            return $next($request);
        }

        $auth = (string) $request->header('Authorization', '');
        if (! str_starts_with($auth, 'Bearer ')) {
            return $this->forbidden();
        }

        if (trim(substr($auth, 7)) !== $token) {
            return $this->forbidden();
        }

        return $next($request);
    }

    private function forbidden(): Response
    {
        return response()->json([
            'error' => 'forbidden',
            'message' => 'invalid or missing bearer token',
        ], 403);
    }
}
