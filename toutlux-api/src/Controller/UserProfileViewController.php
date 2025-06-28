<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UserProfileViewController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(User $data, Security $security, EntityManagerInterface $em): User
    {
        try {
            $viewer = $security->getUser();

            // ✅ AMÉLIORATION: Vérifications plus robustes
            if (!$viewer instanceof User) {
                // Utilisateur non connecté - pas d'incrémentation
                $this->logger->info('Profile view by anonymous user', [
                    'viewed_user_id' => $data->getId()
                ]);
                return $data;
            }

            // Ne pas incrémenter si l'utilisateur voit son propre profil
            if ($viewer->getId() === $data->getId()) {
                $this->logger->debug('User viewing own profile', [
                    'user_id' => $viewer->getId()
                ]);
                return $data;
            }

            // ✅ AMÉLIORATION: Gestion défensive des vues
            $currentViews = $data->getProfileViews() ?? 0;
            $newViews = $currentViews + 1;

            $data->setProfileViews($newViews);
            $em->flush();

            $this->logger->info('Profile view incremented', [
                'viewed_user_id' => $data->getId(),
                'viewer_user_id' => $viewer->getId(),
                'new_view_count' => $newViews
            ]);

        } catch (\Exception $e) {
            // ✅ AMÉLIORATION: Ne pas faire échouer la requête si l'incrémentation échoue
            $this->logger->error('Failed to increment profile views', [
                'viewed_user_id' => $data->getId(),
                'viewer_user_id' => $viewer?->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return $data;
    }
}
