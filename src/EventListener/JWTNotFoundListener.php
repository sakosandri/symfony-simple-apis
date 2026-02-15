<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class JWTNotFoundListener
{
    public function onJWTNotFound(JWTNotFoundEvent $event): void
    {
        $response = new JsonResponse([
            'success' => false,
            'message' => 'Token not found',
            'data' => null,
            'errors' => [
                'token' => 'Authentication token is required'
            ]
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}