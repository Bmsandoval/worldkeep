<?php

namespace App\Services\WorldKeep\Mcp;

final class Protocol
{
    public const CODE_PARSE_ERROR = -32700;

    public const CODE_INVALID_REQUEST = -32600;

    public const CODE_METHOD_NOT_FOUND = -32601;

    public const CODE_INVALID_PARAMS = -32602;

    public const CODE_INTERNAL_ERROR = -32603;

    public const DEFAULT_PROTOCOL_VERSION = '2025-06-18';

    /**
     * @return array<string, mixed>
     */
    public static function toolResultText(mixed $value): array
    {
        return [
            'content' => [
                ['type' => 'text', 'text' => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toolResultError(string $message): array
    {
        return [
            'content' => [
                ['type' => 'text', 'text' => $message],
            ],
            'isError' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rpcError(int $code, string $message): array
    {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }
}
