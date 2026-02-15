<?php

namespace App\EventListener;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class JWTAuthenticationSuccessListener
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        // Create a refresh token
        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setRefreshToken(bin2hex(random_bytes(64)));
        $refreshToken->setExpiresAt(new \DateTime('+30 days'));
        
        $this->em->persist($refreshToken);
        $this->em->flush();

        // Transform to standard response format
        $event->setData([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $data['token'],
                'refresh_token' => $refreshToken->getRefreshToken(),
                'expires_in' => 900,
                'user' => [
                    // 'id' => $user->getId(),
                    'email' => $user->getUserIdentifier(),
                    'roles' => $user->getRoles(),
                ]
            ]
        ]);
    }
}
