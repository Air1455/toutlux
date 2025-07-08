<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Provider personnalisé pour charger les utilisateurs
 * Utilisé par le système de sécurité de Symfony
 */
class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Charger un utilisateur par son identifiant (email)
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findByEmail($identifier);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        // Vérifier si l'utilisateur est actif
        if (!$user->isActive()) {
            throw new UserNotFoundException('User account is disabled.');
        }

        return $user;
    }

    /**
     * Recharger un utilisateur depuis la base de données
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $reloadedUser = $this->userRepository->find($user->getId());

        if (!$reloadedUser) {
            throw new UserNotFoundException(sprintf('User with ID "%s" could not be reloaded.', $user->getId()));
        }

        return $reloadedUser;
    }

    /**
     * Vérifier si la classe User est supportée
     */
    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    /**
     * Mettre à jour le mot de passe hashé dans la base de données
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $this->userRepository->upgradePassword($user, $newHashedPassword);
    }

    /**
     * Charger un utilisateur par son ID Google
     */
    public function loadUserByGoogleId(string $googleId): ?User
    {
        return $this->userRepository->findByGoogleId($googleId);
    }

    /**
     * Charger ou créer un utilisateur depuis les données Google
     */
    public function loadOrCreateUserFromGoogle(array $googleData): User
    {
        // Chercher par Google ID
        $user = $this->loadUserByGoogleId($googleData['sub']);

        if ($user) {
            return $user;
        }

        // Chercher par email
        $user = $this->userRepository->findByEmail($googleData['email']);

        if ($user) {
            // Lier le compte Google existant
            $user->setGoogleId($googleData['sub']);
            $user->setGoogleData($googleData);

            if (!$user->isVerified()) {
                $user->setIsVerified(true);
                $user->setVerifiedAt(new \DateTimeImmutable());
            }

            $this->userRepository->save($user, true);
            return $user;
        }

        // Créer un nouveau compte
        $user = new User();
        $user->setEmail($googleData['email']);
        $user->setGoogleId($googleData['sub']);
        $user->setGoogleData($googleData);
        $user->setIsVerified(true);
        $user->setVerifiedAt(new \DateTimeImmutable());

        if (isset($googleData['given_name'])) {
            $user->setFirstName($googleData['given_name']);
        }

        if (isset($googleData['family_name'])) {
            $user->setLastName($googleData['family_name']);
        }

        if (isset($googleData['picture'])) {
            $user->setAvatar($googleData['picture']);
        }

        // Générer un mot de passe aléatoire (non utilisé pour Google auth)
        $user->setPassword(bin2hex(random_bytes(32)));

        // Définir les rôles par défaut
        $user->setRoles(['ROLE_USER']);

        $this->userRepository->save($user, true);

        return $user;
    }

    /**
     * Vérifier si un email existe déjà
     */
    public function emailExists(string $email): bool
    {
        return $this->userRepository->findByEmail($email) !== null;
    }

    /**
     * Obtenir les utilisateurs par rôle
     */
    public function getUsersByRole(string $role): array
    {
        return $this->userRepository->findByRole($role);
    }

    /**
     * Obtenir tous les administrateurs
     */
    public function getAdmins(): array
    {
        return $this->userRepository->findByRole('ROLE_ADMIN');
    }

    /**
     * Chercher des utilisateurs
     */
    public function searchUsers(string $query): array
    {
        return $this->userRepository->searchUsers($query);
    }

    /**
     * Obtenir les utilisateurs avec des documents en attente
     */
    public function getUsersWithPendingDocuments(): array
    {
        return $this->userRepository->findUsersWithPendingDocuments();
    }

    /**
     * Obtenir les utilisateurs non vérifiés
     */
    public function getUnverifiedUsers(\DateTimeInterface $before = null): array
    {
        return $this->userRepository->findUnverifiedUsers($before);
    }

    /**
     * Obtenir les statistiques des utilisateurs
     */
    public function getUserStatistics(): array
    {
        return $this->userRepository->getUserStatistics();
    }

    /**
     * Nettoyer les utilisateurs non vérifiés anciens
     */
    public function cleanupUnverifiedUsers(int $daysOld = 7): int
    {
        return $this->userRepository->cleanupUnverifiedUsers($daysOld);
    }
}
