<?php

// ===== 1. GOOGLE AUTH CONTROLLER AVEC DEBUG COMPLET =====

namespace App\Controller;

use App\Entity\User;
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
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/auth/google', name: 'api_auth_google', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->logger->info('🔍 Google auth request started');

            // ✅ ÉTAPE 1: Validation de la requête
            $request->headers->set('Content-Type', 'application/json');
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('❌ JSON parsing error: ' . json_last_error_msg());
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }

            $this->logger->info('📥 Request data received', [
                'has_id_token' => isset($data['id_token']),
                'data_keys' => array_keys($data)
            ]);

            $idToken = $data['id_token'] ?? null;

            if (!$idToken) {
                $this->logger->warning('❌ Missing id_token in request');
                return new JsonResponse(['error' => 'id_token missing'], 400);
            }

            // ✅ ÉTAPE 2: Vérification de la configuration Google
            $googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? null;

            if (!$googleClientId) {
                $this->logger->error('❌ GOOGLE_CLIENT_ID not configured in environment');
                return new JsonResponse(['error' => 'Google authentication not configured'], 500);
            }

            $this->logger->info('🔑 Google Client ID configured', [
                'client_id_prefix' => substr($googleClientId, 0, 10) . '...'
            ]);

            // ✅ ÉTAPE 3: Configuration et vérification du token Google
            try {
                $client = new GoogleClient([
                    'client_id' => $googleClientId
                ]);

                $this->logger->info('🔄 Verifying Google ID token...');
                $payload = $client->verifyIdToken($idToken);

                if (!$payload) {
                    $this->logger->warning('❌ Google token verification failed');
                    return new JsonResponse(['error' => 'Invalid Google token'], 401);
                }

                $this->logger->info('✅ Google token verified successfully');

            } catch (\Google\Service\Exception $e) {
                $this->logger->error('❌ Google Service Exception: ' . $e->getMessage(), [
                    'code' => $e->getCode(),
                    'errors' => $e->getErrors()
                ]);
                return new JsonResponse(['error' => 'Google service error'], 503);

            } catch (\Exception $e) {
                $this->logger->error('❌ Google Client Exception: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return new JsonResponse(['error' => 'Google authentication failed'], 500);
            }

            // ✅ ÉTAPE 4: Extraction des données utilisateur
            $googleUserId = $payload['sub'] ?? null;
            $email = $payload['email'] ?? null;
            $lastName = $payload['family_name'] ?? '';
            $firstName = $payload['given_name'] ?? '';
            $picture = $payload['picture'] ?? null;
            $emailVerified = $payload['email_verified'] ?? false;

            $this->logger->info('👤 Google user data extracted', [
                'google_user_id' => $googleUserId,
                'email' => $email,
                'has_names' => !empty($firstName) && !empty($lastName),
                'email_verified' => $emailVerified,
                'has_picture' => !empty($picture)
            ]);

            if (!$googleUserId || !$email) {
                $this->logger->error('❌ Missing required fields in Google payload', [
                    'has_google_id' => !empty($googleUserId),
                    'has_email' => !empty($email),
                    'payload_keys' => array_keys($payload)
                ]);
                return new JsonResponse(['error' => 'Missing required user data from Google'], 400);
            }

            // ✅ ÉTAPE 5: Recherche/création utilisateur
            try {
                $userRepository = $this->entityManager->getRepository(User::class);

                // Chercher par Google ID d'abord
                $user = $userRepository->findOneBy(['googleId' => $googleUserId]);
                $this->logger->info('🔍 User search by Google ID', [
                    'found' => $user !== null,
                    'google_id' => $googleUserId
                ]);

                if (!$user) {
                    // Chercher par email
                    $user = $userRepository->findOneBy(['email' => $email]);
                    $this->logger->info('🔍 User search by email', [
                        'found' => $user !== null,
                        'email' => $email
                    ]);

                    if ($user) {
                        // Utilisateur existant, ajouter Google ID
                        $user->setGoogleId($googleUserId);
                        $this->logger->info('🔗 Added Google ID to existing user', [
                            'user_id' => $user->getId()
                        ]);
                    }
                }

                $isNewUser = false;

                if (!$user) {
                    // Créer nouvel utilisateur
                    $this->logger->info('👤 Creating new user from Google auth');

                    $user = new User();
                    $isNewUser = true;

                    // Générer mot de passe temporaire
                    $tempPassword = bin2hex(random_bytes(16));

                    $user->setGoogleId($googleUserId)
                        ->setEmail($email)
                        ->setFirstName($firstName)
                        ->setLastName($lastName)
                        ->setPassword($tempPassword)
                        ->setRoles(['ROLE_USER'])
                        ->setProfilePicture($picture)
                        ->setIsEmailVerified($emailVerified)
                        ->setIsPhoneVerified(false)
                        ->setIsIdentityVerified(false)
                        ->setStatus('active')
                        ->setCreatedAt(new \DateTimeImmutable())
                        ->setUpdatedAt(new \DateTimeImmutable())
                        ->setProfileViews(0)
                        ->setLanguage('fr')
                        ->setTermsAccepted(false);

                    $this->logger->info('✅ New user entity created');
                } else {
                    // Mettre à jour utilisateur existant
                    if ($picture && (!$user->getProfilePicture() || $user->getProfilePicture() === 'yes')) {
                        $user->setProfilePicture($picture);
                    }

                    if ($emailVerified && !$user->isEmailVerified()) {
                        $user->setIsEmailVerified(true);
                    }

                    $user->setUpdatedAt(new \DateTimeImmutable());
                    $this->logger->info('🔄 Updated existing user');
                }

                // ✅ ÉTAPE 6: Sauvegarde en base
                $this->logger->info('💾 Persisting user to database');
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $this->logger->info('✅ User saved successfully', [
                    'user_id' => $user->getId()
                ]);

            } catch (\Exception $e) {
                $this->logger->error('❌ Database error during user creation/update: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return new JsonResponse(['error' => 'Database error'], 500);
            }

            // ✅ ÉTAPE 7: Génération des tokens
            try {
                $this->logger->info('🔑 Generating JWT token');
                $jwt = $this->jwtManager->create($user);

                $this->logger->info('🔄 Creating refresh token');
                $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);

                $this->logger->info('✅ Tokens generated successfully');

            } catch (\Exception $e) {
                $this->logger->error('❌ Token generation error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return new JsonResponse(['error' => 'Token generation failed'], 500);
            }

            // ✅ ÉTAPE 8: Construction de la réponse
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
                'is_new_user' => $isNewUser,
                'response_keys' => array_keys($response)
            ]);

            return new JsonResponse($response);

        } catch (\Throwable $e) {
            // ✅ CATCH-ALL pour toute erreur non gérée
            $this->logger->error('❌ Unexpected error in Google auth: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Authentication error',
                'debug' => $_ENV['APP_ENV'] === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }
}
