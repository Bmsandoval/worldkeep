<?php

namespace App\Services\WorldKeep;

use Exception;
use Throwable;

final class ApiError extends Exception
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly string $code,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function badRequest(string $code, string $message): self
    {
        return new self(400, $code, $message);
    }

    public static function forbidden(string $message): self
    {
        return new self(403, 'forbidden', $message);
    }

    public static function notFound(string $message): self
    {
        return new self(404, 'not_found', $message);
    }

    public static function internal(Throwable $err): self
    {
        return new self(500, 'internal', 'internal error: '.$err->getMessage(), $err);
    }
}
