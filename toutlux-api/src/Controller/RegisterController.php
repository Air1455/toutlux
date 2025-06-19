<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

class RegisterController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenService $refreshTokenService // ✅ AJOUT
    ) {
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $firstName = trim($data['firstName'] ?? '');
            $lastName = trim($data['lastName'] ?? '');
            dump($email, $password, $firstName, $lastName); // Pour débogage

            // Validation et création utilisateur (votre code existant)...
            if (!$email || !$password) {
                return new JsonResponse([
                    'error' => 'Missing required fields',
                    'message' => 'Email and password are required'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse([
                    'error' => 'Invalid email format'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($userRepository->findOneBy(['email' => $email])) {
                return new JsonResponse([
                    'error' => 'User already exists',
                    'code' => 'USER_EXISTS'
                ], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($email)
                ->setPassword($password)
                ->setRoles(['ROLE_USER'])
                ->setIsEmailVerified(false)
                ->setIsPhoneVerified(false)
                ->setIsIdentityVerified(false)
                ->setStatus('pending_verification')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
                ->setProfileViews(0)
                ->setLanguage('fr')
                ->setTermsAccepted(false);

            if ($firstName) $user->setFirstName($firstName);
            if ($lastName) $user->setLastName($lastName);

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                return new JsonResponse([
                    'error' => 'Validation failed',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $em->persist($user);
            $em->flush();

            // ✅ OPTION: Auto-login après inscription
            $generateTokenDirectly = $data['auto_login'] ?? false;
            $token = null;
            $refreshToken = null;

            if ($generateTokenDirectly) {
                $token = $this->jwtManager->create($user);
                $refreshTokenEntity = $this->refreshTokenService->createRefreshToken($user, $request);
                $refreshToken = $refreshTokenEntity->getToken();
            }

            return new JsonResponse([
                'token' => $token,
                'refresh_token' => $refreshToken, // ✅ AJOUT
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'profilePicture' => $user->getProfilePicture(),
                    'isNewUser' => true,
                    'isProfileComplete' => $user->isProfileComplete(),
                    'completionPercentage' => $user->getCompletionPercentage(),
                ],
                'message' => 'User created successfully'
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $this->logger->error('Registration error: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Registration failed',
                'message' => 'An error occurred during registration'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
