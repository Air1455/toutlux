<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RefreshTokenService;
use App\Service\Messaging\EmailService;
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
        private RefreshTokenService $refreshTokenService,
        private EmailService $emailService
    ) {}

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des données
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $firstName = trim($data['firstName'] ?? '');
            $lastName = trim($data['lastName'] ?? '');

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

            // Créer l'utilisateur
            $user = new User();
            $user->setEmail($email)
                ->setPassword($password)
                ->setRoles(['ROLE_USER'])
                ->setStatus('pending_verification')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
                ->setProfileViews(0)
                ->setLanguage('fr')
                ->setTermsAccepted(false)
                ->setIsPhoneVerified(false)
                ->setIsIdentityVerified(false)
                ->setIsFinancialDocsVerified(false);

            // Gmail = vérification automatique
            if (str_ends_with(strtolower($email), '@gmail.com')) {
                $user->setIsEmailVerified(true)
                    ->setEmailVerifiedAt(new \DateTimeImmutable());
            } else {
                $user->setIsEmailVerified(false);
            }

            if ($firstName) $user->setFirstName($firstName);
            if ($lastName) $user->setLastName($lastName);

            // Validation
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

            // Envoi des emails
            $emailNotifications = [];

            // Email de bienvenue
            $welcomeSent = $this->emailService->sendWelcomeEmail($user);
            $emailNotifications['welcome_sent'] = $welcomeSent;

            // Email de confirmation si pas Gmail
            if (!$user->isGmailAccount()) {
                $confirmationSent = $this->emailService->sendEmailConfirmation($user);
                $emailNotifications['confirmation_sent'] = $confirmationSent;
            }

            // Auto-login si demandé
            $generateTokenDirectly = $data['auto_login'] ?? false;
            $token = null;
            $refreshToken = null;

            if ($generateTokenDirectly) {
                $token = $this->jwtManager->create($user);
                $refreshTokenEntity = $this->refreshTokenService->createRefreshToken($user, $request);
                $refreshToken = $refreshTokenEntity->getToken();
            }

            $this->logger->info('User registered successfully', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'is_gmail' => $user->isGmailAccount(),
                'email_notifications' => $emailNotifications
            ]);

            return new JsonResponse([
                'token' => $token,
                'refresh_token' => $refreshToken,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'profilePicture' => $user->getProfilePicture(),
                    'isNewUser' => true,
                    'isProfileComplete' => $user->isProfileComplete(),
                    'completionPercentage' => $user->getCompletionPercentage(),
                    'isEmailVerified' => $user->isEmailVerified(),
                    'needsEmailConfirmation' => $user->isEmailConfirmationRequired()
                ],
                'email_notifications' => $emailNotifications,
                'message' => $user->isGmailAccount()
                    ? 'User created successfully with verified Gmail account'
                    : 'User created successfully. Please check your email to confirm your address.'
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
