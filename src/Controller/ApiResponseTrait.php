<?php
// src/Controller/ApiResponseTrait.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

trait ApiResponseTrait
{
    protected function success(array $data = [], string $message = '', ?string $accessToken = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($accessToken !== null) {
            $response['access_token'] = $accessToken;
        }

        return new JsonResponse($response, $status);
    }

    protected function error(string $message, int $status = 400, array $data = []): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
