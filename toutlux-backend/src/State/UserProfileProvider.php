<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\UserProfile;
use Symfony\Bundle\SecurityBundle\Security;

class UserProfileProvider implements ProviderInterface
{
    public function __construct(
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?UserProfile
    {
        $user = $this->security->getUser();

        if (!$user) {
            return null;
        }

        return $user->getProfile();
    }
}
