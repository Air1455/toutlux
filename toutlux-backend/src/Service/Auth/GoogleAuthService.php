<?php

namespace App\Service\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Email\WelcomeEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client as GoogleClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GoogleAuthService
{
    private GoogleClient $googleClient;

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private WelcomeEmailService $welcomeEmailService,
        private JWTService $jwtService,
        private string $googleClientId,
        private string $googleClientSecret
    ) {
        $this->googleClient = new GoogleClient();
        $this->googleClient->setClientId($googleClientId);
        $this->googleClient->setClientSecret($googleClientSecret);
    }

    public function authenticate(string $idToken): array
    {
        try {
            // Vérifier le token Google
            $payload = $this->googleClient->verifyIdToken($idToken);

            if (!$payload) {
                throw new AuthenticationException('Invalid Google token');
            }

            $email = $payload['email'];
            $googleId = $payload['sub'];

            // Chercher l'utilisateur existant
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                // Créer un nouvel utilisateur
                $user = $this->createUserFromGoogle($payload);
                $isNewUser = true;
            } else {
                // Mettre à jour les informations Google
                $this->updateUserFromGoogle($user, $payload);
                $isNewUser = false;
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Envoyer email de bienvenue si nouveau
            if ($isNewUser) {
                $this->welcomeEmailService->sendWelcomeEmail($user);
            }

            // Générer le JWT
            $tokenData = $this->jwtService->createTokenFromUser($user);
            $tokenData['isNewUser'] = $isNewUser;

            return $tokenData;

        } catch (\Exception $e) {
            throw new AuthenticationException('Google authentication failed: ' . $e->getMessage());
        }
    }

    private function createUserFromGoogle(array $googleData): User
    {
        $user = new User();
        $user->setEmail($googleData['email']);
        $user->setGoogleId($googleData['sub']);
        $user->setFirstName($googleData['given_name'] ?? '');
        $user->setLastName($googleData['family_name'] ?? '');
        $user->setAvatar($googleData['picture'] ?? null);
        $user->setIsVerified(true); // Email vérifié par Google
        $user->setRoles(['ROLE_USER']);

        // Générer un mot de passe aléatoire (non utilisé pour Google auth)
        $user->setPassword(bin2hex(random_bytes(32)));

        return $user;
    }

    private function updateUserFromGoogle(User $user, array $googleData): void
    {
        if (!$user->getGoogleId()) {
            $user->setGoogleId($googleData['sub']);
        }

        // Mettre à jour les infos si elles sont vides
        if (empty($user->getFirstName()) && isset($googleData['given_name'])) {
            $user->setFirstName($googleData['given_name']);
        }

        if (empty($user->getLastName()) && isset($googleData['family_name'])) {
            $user->setLastName($googleData['family_name']);
        }

        if (empty($user->getAvatar()) && isset($googleData['picture'])) {
            $user->setAvatar($googleData['picture']);
        }

        if (!$user->isVerified()) {
            $user->setIsVerified(true);
        }
    }

    public function validateToken(string $token): bool
    {
        try {
            $payload = $this->googleClient->verifyIdToken($token);
            return $payload !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
