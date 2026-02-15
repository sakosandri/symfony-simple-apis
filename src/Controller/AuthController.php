<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
        }

        $email = trim($data['email']);
        $password = trim($data['password']);

        // Validate email
        $emailErrors = $validator->validate($email, [new Assert\NotBlank(), new Assert\Email()]);
        if (count($emailErrors) > 0) return new JsonResponse(['error' => 'Invalid email'], 400);

        // Validate password
        $passwordErrors = $validator->validate($password, [new Assert\NotBlank(), new Assert\Length(['min'=>8])]);
        if (count($passwordErrors) > 0) return new JsonResponse(['error' => 'Password too short'], 400);

        // Check existing user
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) return new JsonResponse(['error' => 'Email already in use'], 400);

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        // Generate JWT access token
        $accessToken = $jwtManager->create($user);

        // Create refresh token
        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setToken(bin2hex(random_bytes(64))); // secure random token
        $refreshToken->setExpiresAt(new \DateTime('+30 days'));
        $em->persist($refreshToken);
        $em->flush();

        return new JsonResponse([
            'message' => 'User registered successfully',
            'access_token' => $accessToken,
            'expires_in' => 900, // 15 min
            'refresh_token' => $refreshToken->getToken(),
        ], 201);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): void
    {
        // Handled automatically by LexikJWTAuthenticationBundle
        throw new \Exception('This should never be called directly.');
    }

    #[Route('/api/refresh', name: 'api_refresh', methods: ['POST'])]
    public function refresh(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['refresh_token'])) {
            return new JsonResponse(['error' => 'Refresh token required'], 400);
        }

        $refreshToken = $em->getRepository(RefreshToken::class)
            ->findOneBy(['token' => $data['refresh_token']]);

        if (!$refreshToken || $refreshToken->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['error' => 'Invalid or expired refresh token'], 401);
        }

        $user = $refreshToken->getUser();
        $newAccessToken = $jwtManager->create($user);

        return new JsonResponse([
            'access_token' => $newAccessToken,
            'expires_in' => 900
        ]);
    }
}
