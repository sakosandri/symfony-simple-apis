<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class JWTInvalidListener
{
    public function onJWTInvalid(JWTInvalidEvent $event): void
    {
        $response = new JsonResponse([
            'success' => false,
            'message' => 'Invalid token',
            'data' => null,
            'errors' => [
                'token' => 'Your token is invalid or has expired'
            ]
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}