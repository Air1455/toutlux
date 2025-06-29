<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Messaging\EmailService;
use App\Service\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client as GoogleClient;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class GoogleAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenService $refreshTokenService,
        private LoggerInterface $logger,
        private EmailService $emailService
    ) {}

    #[Route('/api/auth/google', name: 'api_auth_google', methods: ['POST'])]
    public function authenticate(Request $request): JsonResponse
    {
        try {
            $this->logger->info('🔍 Google auth request started');

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('❌ JSON parsing error: ' . json_last_error_msg());
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }

            $idToken = $data['id_token'] ?? null;
            $autoRegister = $data['auto_register'] ?? false;

            $this->logger->info('📥 Google auth request data', [
                'has_id_token' => !empty($idToken),
                'auto_register' => $autoRegister
            ]);

            if (!$idToken) {
                $this->logger->warning('❌ Missing id_token in request');
                return new JsonResponse(['error' => 'id_token missing'], 400);
            }

            // Vérification du token Google
            $googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? null;
            if (!$googleClientId) {
                $this->logger->error('❌ GOOGLE_CLIENT_ID not configured');
                return new JsonResponse(['error' => 'Google authentication not configured'], 500);
            }

            // Vérifier le token Google
            $client = new GoogleClient(['client_id' => $googleClientId]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                $this->logger->warning('❌ Google token verification failed');
                return new JsonResponse(['error' => 'Invalid Google token'], 401);
            }

            $this->logger->info('✅ Google token verified successfully');

            // Extraction des données utilisateur
            $googleUserId = $payload['sub'] ?? null;
            $email = $payload['email'] ?? null;
            $lastName = $payload['family_name'] ?? '';
            $firstName = $payload['given_name'] ?? '';
            $picture = $payload['picture'] ?? null;

            if (!$googleUserId || !$email) {
                return new JsonResponse(['error' => 'Missing required user data from Google'], 400);
            }

            // Recherche de l'utilisateur
            $userRepository = $this->entityManager->getRepository(User::class);

            // Chercher par Google ID d'abord
            $user = $userRepository->findOneBy(['googleId' => $googleUserId]);

            if (!$user) {
                // Chercher par email
                $user = $userRepository->findOneBy(['email' => $email]);

                if ($user) {
                    // Utilisateur existant avec cet email, ajouter Google ID
                    $user->setGoogleId($googleUserId);
                    $this->entityManager->flush();
                    $this->logger->info('🔗 Added Google ID to existing user');
                }
            }

            // Si pas d'utilisateur et pas auto_register
            if (!$user && !$autoRegister) {
                $this->logger->info('👤 New Google user needs confirmation', [
                    'email' => $email,
                    'google_id' => $googleUserId,
                    'auto_register' => false
                ]);

                // Retourner les infos Google sans créer de compte
                return new JsonResponse([
                    'requires_registration' => true,
                    'google_data' => [
                        'email' => $email,
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'picture' => $picture,
                        'googleId' => $googleUserId
                    ],
                    'message' => 'Aucun compte trouvé avec cet email Google. Voulez-vous créer un compte ?'
                ], 200);
            }

            // Si auto_register est true ou utilisateur existe, continuer normalement
            $isNewUser = false;

            if (!$user && $autoRegister) {
                // Vérifier d'abord si un compte existe déjà avec cet email
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $this->logger->warning('❌ User already exists with this email', [
                        'email' => $email,
                        'has_google_id' => !empty($existingUser->getGoogleId())
                    ]);

                    return new JsonResponse([
                        'error' => 'User already exists',
                        'code' => 'USER_EXISTS',
                        'message' => 'Un compte existe déjà avec cet email'
                    ], 409);
                }

                $this->logger->info('👤 Creating new user from Google auth with auto_register=true', [
                    'email' => $email,
                    'auto_register' => true
                ]);

                // Créer nouvel utilisateur
                $user = new User();
                $isNewUser = true;

                $tempPassword = bin2hex(random_bytes(16));

                $user->setGoogleId($googleUserId)
                    ->setEmail($email)
                    ->setFirstName($firstName)
                    ->setLastName($lastName)
                    ->setPassword($tempPassword)
                    ->setRoles(['ROLE_USER'])
                    ->setProfilePicture($picture)
                    ->setIsEmailVerified(true) // Gmail vérifié automatiquement
                    ->setEmailVerifiedAt(new \DateTimeImmutable())
                    ->setIsPhoneVerified(false)
                    ->setIsIdentityVerified(false)
                    ->setStatus('active')
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable())
                    ->setProfileViews(0)
                    ->setLanguage('fr')
                    ->setTermsAccepted(false);

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // Envoyer email de bienvenue
                $this->emailService->sendWelcomeEmail($user);

                $this->logger->info('✅ New Google user created', [
                    'user_id' => $user->getId(),
                    'email' => $email
                ]);
            }

            if ($user && !$isNewUser) {
                // Mettre à jour utilisateur existant
                if ($picture && (!$user->getProfilePicture() || $user->getProfilePicture() === 'yes')) {
                    $user->setProfilePicture($picture);
                }

                $user->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->flush();
            }

            // Générer les tokens
            $jwt = $this->jwtManager->create($user);
            $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);

            $response = [
                'token' => $jwt,
                'refresh_token' => $refreshToken->getToken(),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'profilePicture' => $user->getProfilePicture(),
                    'isNewUser' => $isNewUser,
                    'isProfileComplete' => $user->isProfileComplete(),
                    'completionPercentage' => $user->getCompletionPercentage(),
                ]
            ];

            $this->logger->info('🎉 Google authentication completed successfully', [
                'user_id' => $user->getId(),
                'is_new_user' => $isNewUser
            ]);

            return new JsonResponse($response);

        } catch (\Google\Service\Exception $e) {
            $this->logger->error('❌ Google Service Exception: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'errors' => $e->getErrors()
            ]);
            return new JsonResponse(['error' => 'Google service error'], 503);

        } catch (\Exception $e) {
            $this->logger->error('❌ Unexpected error in Google auth: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Authentication error',
                'debug' => $_ENV['APP_ENV'] === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }

    #[Route('/api/auth/google/register', name: 'api_auth_google_register', methods: ['POST'])]
    public function registerWithGoogle(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Réutiliser la même logique mais avec auto_register = true
            $data['auto_register'] = true;

            $newRequest = Request::create(
                '/api/auth/google',
                'POST',
                [],
                [],
                [],
                $request->server->all(),
                json_encode($data)
            );

            // Copier les headers importants
            $newRequest->headers->replace($request->headers->all());

            return $this->authenticate($newRequest);

        } catch (\Exception $e) {
            $this->logger->error('❌ Google registration error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Registration failed'], 500);
        }
    }
}
