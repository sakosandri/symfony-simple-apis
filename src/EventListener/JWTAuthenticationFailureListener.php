<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class JWTAuthenticationFailureListener
{
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $response = new JsonResponse([
            'success' => false,
            'message' => 'Invalid credentials',
            'data' => null,
            'errors' => [
                'credentials' => 'The email or password is incorrect'
            ]
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}