<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * ✅ Provider personnalisé pour l'endpoint /users/me
 *
 * Comment cela fonctionne :
 * 1. API Platform appelle ce provider au lieu de sa logique par défaut
 * 2. On récupère directement l'utilisateur connecté via Security
 * 3. On le retourne sans faire de requête Doctrine complexe
 */
class UserMeProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('User not authenticated');
        }

        // ✅ IMPORTANT: Rafraîchir l'entité pour avoir les dernières données
        // L'utilisateur dans le token JWT peut être "stale"
        $this->entityManager->refresh($user);

        // ✅ Optionnel: Mettre à jour la dernière activité
        $user->setLastActiveAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $user;
    }
}
