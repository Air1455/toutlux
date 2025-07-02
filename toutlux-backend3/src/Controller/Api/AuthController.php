<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\User\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Google_Client;
use Symfony\Component\HttpClient\HttpClient;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRegistrationService $registrationService,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate input
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->json([
                'error' => 'Email already registered'
            ], Response::HTTP_CONFLICT);
        }

        try {
            // Register user
            $user = $this->registrationService->registerUser(
                $data['email'],
                $data['password']
            );

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            return $this->json([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'verified' => $user->isVerified(),
                    'trustScore' => $user->getTrustScore()
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Registration failed: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This is handled by JWT authentication
        // The method is here for API documentation
        return $this->json(['message' => 'Use POST with email and password']);
    }

    #[Route('/google', name: 'google', methods: ['POST'])]
    public function googleAuth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['idToken'])) {
            return $this->json([
                'error' => 'Google ID token is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Verify Google token
            $client = new Google_Client();
            $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);

            $payload = $client->verifyIdToken($data['idToken']);

            if (!$payload) {
                return $this->json([
                    'error' => 'Invalid Google token'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Extract user data from Google
            $googleUserData = [
                'id' => $payload['sub'],
                'email' => $payload['email'],
                'given_name' => $payload['given_name'] ?? null,
                'family_name' => $payload['family_name'] ?? null,
                'picture' => $payload['picture'] ?? null
            ];

            // Register or login user
            $user = $this->registrationService->registerGoogleUser($googleUserData);

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            return $this->json([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'verified' => $user->isVerified(),
                    'trustScore' => $user->getTrustScore(),
                    'profile' => $user->getProfile() ? [
                        'firstName' => $user->getProfile()->getFirstName(),
                        'lastName' => $user->getProfile()->getLastName(),
                        'completionPercentage' => $user->getProfile()->getCompletionPercentage()
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Google authentication failed: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/verify-email', name: 'verify_email', methods: ['POST'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId']) || !isset($data['token'])) {
            return $this->json([
                'error' => 'User ID and token are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->find($data['userId']);

        if (!$user) {
            return $this->json([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($user->isVerified()) {
            return $this->json([
                'message' => 'Email already verified'
            ]);
        }

        // Verify token
        if (!$this->registrationService->isVerificationTokenValid($user, $data['token'])) {
            return $this->json([
                'error' => 'Invalid verification token'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verify email
        $this->registrationService->verifyEmail($user);

        return $this->json([
            'message' => 'Email verified successfully'
        ]);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(): JsonResponse
    {
        // This is handled by the refresh token bundle
        return $this->json(['message' => 'Use POST with refreshToken']);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'verified' => $user->isVerified(),
            'trustScore' => $user->getTrustScore(),
            'roles' => $user->getRoles(),
            'profile' => $user->getProfile() ? [
                'firstName' => $user->getProfile()->getFirstName(),
                'lastName' => $user->getProfile()->getLastName(),
                'phoneNumber' => $user->getProfile()->getPhoneNumber(),
                'profilePictureUrl' => $user->getProfile()->getProfilePictureUrl(),
                'completionPercentage' => $user->getProfile()->getCompletionPercentage(),
                'personalInfoValidated' => $user->getProfile()->isPersonalInfoValidated(),
                'identityValidated' => $user->getProfile()->isIdentityValidated(),
                'financialValidated' => $user->getProfile()->isFinancialValidated(),
                'termsAccepted' => $user->getProfile()->isTermsAccepted()
            ] : null
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // With JWT, logout is handled client-side by removing the token
        return $this->json([
            'message' => 'Logged out successfully'
        ]);
    }

    #[Route('/check-email', name: 'check_email', methods: ['POST'])]
    public function checkEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json([
                'error' => 'Email is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $exists = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $data['email']]) !== null;

        return $this->json([
            'exists' => $exists
        ]);
    }
}
