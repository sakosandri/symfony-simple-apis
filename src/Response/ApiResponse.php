<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse extends JsonResponse
{
    public function __construct(
        bool $success = true,
        mixed $data = null,
        ?string $message = null,
        ?array $errors = null,
        int $statusCode = 200,
        array $headers = []
    ) {
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        parent::__construct($response, $statusCode, $headers);
    }

    public static function success(
        mixed $data = null,
        ?string $message = 'Operation successful',
        int $statusCode = 200
    ): self {
        return new self(
            success: true,
            data: $data,
            message: $message,
            statusCode: $statusCode
        );
    }

    public static function error(
        ?string $message = 'An error occurred',
        ?array $errors = null,
        int $statusCode = 400,
        mixed $data = null
    ): self {
        return new self(
            success: false,
            data: $data,
            message: $message,
            errors: $errors,
            statusCode: $statusCode
        );
    }

    public static function created(
        mixed $data = null,
        ?string $message = 'Resource created successfully'
    ): self {
        return new self(
            success: true,
            data: $data,
            message: $message,
            statusCode: 201
        );
    }

    public static function notFound(
        ?string $message = 'Resource not found',
        ?array $errors = null
    ): self {
        return new self(
            success: false,
            data: null,
            message: $message,
            errors: $errors,
            statusCode: 404
        );
    }

    public static function unauthorized(
        ?string $message = 'Unauthorized',
        ?array $errors = null
    ): self {
        return new self(
            success: false,
            data: null,
            message: $message,
            errors: $errors,
            statusCode: 401
        );
    }

    public static function forbidden(
        ?string $message = 'Forbidden',
        ?array $errors = null
    ): self {
        return new self(
            success: false,
            data: null,
            message: $message,
            errors: $errors,
            statusCode: 403
        );
    }

    public static function validationError(
        array $errors,
        ?string $message = 'Validation failed'
    ): self {
        return new self(
            success: false,
            data: null,
            message: $message,
            errors: $errors,
            statusCode: 422
        );
    }
}