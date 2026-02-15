<?php
namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;

class JWTLoginSuccessHandler
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
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

        $data['refresh_token'] = $refreshToken->getRefreshToken();

        $event->setData($data);
    }
}
