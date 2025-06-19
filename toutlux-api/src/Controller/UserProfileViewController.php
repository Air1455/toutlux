<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserProfileViewController extends AbstractController
{
    public function __invoke(User $data, Security $security, EntityManagerInterface $em): User
    {
        $viewer = $security->getUser();

        // Ne pas incrémenter si l'utilisateur voit son propre profil
        if ($viewer !== $data) {
            $data->setProfileViews($data->getProfileViews() + 1);
            $em->flush();
        }

        return $data;
    }
}
