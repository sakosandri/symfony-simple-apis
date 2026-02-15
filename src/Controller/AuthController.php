<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\RefreshToken;
use App\Response\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    ): ApiResponse {
        $data = json_decode($request->getContent(), true);

        // Check required fields
        if (!isset($data['email'], $data['password'])) {
            return ApiResponse::validationError(
                errors: [
                    'email' => !isset($data['email']) ? 'Email is required' : null,
                    'password' => !isset($data['password']) ? 'Password is required' : null,
                ],
                message: 'Email and password are required'
            );
        }

        $email = trim($data['email']);
        $password = trim($data['password']);

        // Validate email
        $emailErrors = $validator->validate($email, [new Assert\NotBlank(), new Assert\Email()]);
        if (count($emailErrors) > 0) {
            return ApiResponse::validationError(
                errors: ['email' => 'Invalid email format'],
                message: 'Validation failed'
            );
        }

        // Validate password
        $passwordErrors = $validator->validate($password, [new Assert\NotBlank(), new Assert\Length(['min' => 8])]);
        if (count($passwordErrors) > 0) {
            return ApiResponse::validationError(
                errors: ['password' => 'Password must be at least 8 characters'],
                message: 'Validation failed'
            );
        }

        // Check existing user
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return ApiResponse::error(
                message: 'Registration failed',
                errors: ['email' => 'Email already in use'],
                statusCode: 409
            );
        }

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
        $refreshToken->setRefreshToken(bin2hex(random_bytes(64))); // secure random token
        $refreshToken->setExpiresAt(new \DateTime('+30 days'));
        $em->persist($refreshToken);
        $em->flush();

        return ApiResponse::created(
            data: [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken->getRefreshToken(),
                'expires_in' => 900, // 15 min
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                ]
            ],
            message: 'User registered successfully'
        );
    }

#[Route('/api/login', name: 'api_login', methods: ['POST'])]
public function login(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher,
    JWTTokenManagerInterface $jwtManager
): ApiResponse {
    $data = json_decode($request->getContent(), true);

    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$email || !$password) {
        return ApiResponse::validationError(
            errors: [
                'email' => !$email ? 'Email is required' : null,
                'password' => !$password ? 'Password is required' : null,
            ],
            message: 'Email and password are required'
        );
    }

    $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
    if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
        return ApiResponse::unauthorized(
            message: 'Invalid credentials',
            errors: ['email' => 'Invalid email or password']
        );
    }

    // Generate JWT access token
    $accessToken = $jwtManager->create($user);

    // Create refresh token
    $refreshToken = new RefreshToken();
    $refreshToken->setUser($user);
    $refreshToken->setRefreshToken(bin2hex(random_bytes(64)));
    $refreshToken->setExpiresAt(new \DateTime('+30 days'));
    $em->persist($refreshToken);
    $em->flush();

    return ApiResponse::success(
        data: [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken->getRefreshToken(),
            'expires_in' => 900,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ],
        message: 'Login successful'
    );
}


    #[Route('/api/refresh', name: 'api_refresh', methods: ['POST'])]
    public function refresh(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): ApiResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['refresh_token'])) {
            return ApiResponse::validationError(
                errors: ['refresh_token' => 'Refresh token is required'],
                message: 'Refresh token required'
            );
        }

        $refreshToken = $em->getRepository(RefreshToken::class)
            ->findOneBy(['token' => $data['refresh_token']]);

        if (!$refreshToken) {
            return ApiResponse::unauthorized(
                message: 'Invalid refresh token',
                errors: ['refresh_token' => 'Refresh token not found']
            );
        }

        if ($refreshToken->getExpiresAt() < new \DateTime()) {
            return ApiResponse::unauthorized(
                message: 'Refresh token expired',
                errors: ['refresh_token' => 'Refresh token has expired']
            );
        }

        $user = $refreshToken->getUser();
        $newAccessToken = $jwtManager->create($user);

        return ApiResponse::success(
            data: [
                'access_token' => $newAccessToken,
                'expires_in' => 900,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ]
            ],
            message: 'Token refreshed successfully'
        );
    }
}