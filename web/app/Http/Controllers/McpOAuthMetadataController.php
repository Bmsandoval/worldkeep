<?php

namespace App\Http\Controllers;

use App\Services\WorldKeep\Mcp\McpOAuth;
use Illuminate\Http\JsonResponse;

class McpOAuthMetadataController extends Controller
{
    public function __construct(private readonly McpOAuth $oauth) {}

    public function __invoke(): JsonResponse
    {
        return response()->json($this->oauth->protectedResourceMetadata());
    }
}
