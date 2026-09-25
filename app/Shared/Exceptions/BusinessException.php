<?php

namespace App\Shared\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Functional error shown as-is to the user by the mobile app.
 * Rendered as `{ "message": "...", "code": "..." }`.
 */
class BusinessException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'BUSINESS_ERROR',
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
        ], $this->status);
    }
}
