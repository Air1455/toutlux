<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * State Provider pour les opérations de lecture sur User
 * Nouvelle architecture API Platform 4.1
 */
final class UserStateProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Pour une collection
        if (!isset($uriVariables['id'])) {
            // Si admin, voir tous les utilisateurs
            if ($this->security->isGranted('ROLE_ADMIN')) {
                return $this->userRepository->findBy([], ['createdAt' => 'DESC']);
            }

            // Sinon, retourner une collection vide ou erreur
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Access denied');
        }

        // Pour un item spécifique
        $user = $this->userRepository->find($uriVariables['id']);

        if (!$user) {
            return null;
        }

        // Vérifier les permissions
        if (!$this->security->isGranted('ROLE_ADMIN') &&
            $this->security->getUser()?->getId() !== $user->getId()) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Access denied');
        }

        return $user;
    }
}
