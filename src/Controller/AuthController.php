<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name']) || !isset($data['timezone'])) {
            return $this->json([
                'success' => false,
                'message' => 'Missing required fields',
                'errors' => [
                    'email' => !isset($data['email']) ? 'Email is required' : null,
                    'password' => !isset($data['password']) ? 'Password is required' : null,
                    'name' => !isset($data['name']) ? 'Name is required' : null,
                    'timezone' => !isset($data['timezone']) ? 'Timezone is required' : null,
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = trim($data['email']);
        $password = trim($data['password']);
        $name = trim($data['name']);
        $timezone = trim($data['timezone']);

        // Validate email
        $emailErrors = $this->validator->validate($email, [
            new Assert\NotBlank(),
            new Assert\Email()
        ]);
        
        if (count($emailErrors) > 0) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid email format',
                'errors' => ['email' => 'Invalid email format']
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate password
        if (strlen($password) < 8) {
            return $this->json([
                'success' => false,
                'message' => 'Password must be at least 8 characters',
                'errors' => ['password' => 'Password must be at least 8 characters']
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate timezone
        $validTimezones = [User::TIMEZONE_UK, User::TIMEZONE_MEXICO, User::TIMEZONE_INDIA];
        if (!in_array($timezone, $validTimezones)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid timezone',
                'errors' => ['timezone' => 'Timezone must be UK, MEXICO, or INDIA']
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json([
                'success' => false,
                'message' => 'Email already registered',
                'errors' => ['email' => 'Email already in use']
            ], Response::HTTP_CONFLICT);
        }

        // Create new user
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setName($name);
        $user->setTimezone($timezone);

        $this->em->persist($user);
        $this->em->flush();

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'timezone' => $user->getTimezone()
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json([
                'success' => false,
                'message' => 'Email and password are required',
                'errors' => [
                    'email' => !$email ? 'Email is required' : null,
                    'password' => !$password ? 'Password is required' : null,
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        // Find user by email
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'errors' => ['email' => 'Invalid email or password']
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'timezone' => $user->getTimezone()
                ]
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'timezone' => $user->getTimezone(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ], Response::HTTP_OK);
    }
}