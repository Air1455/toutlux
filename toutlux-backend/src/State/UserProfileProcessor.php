<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\UserProfile;
use App\Service\User\TrustScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private TrustScoreCalculator $trustScoreCalculator
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserProfile
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new \RuntimeException('User must be authenticated');
        }

        $profile = $user->getProfile();

        if (!$profile) {
            $profile = new UserProfile();
            $user->setProfile($profile);
        }

        // Update profile fields from data
        if (isset($data->firstName)) {
            $profile->setFirstName($data->firstName);
        }
        if (isset($data->lastName)) {
            $profile->setLastName($data->lastName);
        }
        if (isset($data->phoneNumber)) {
            $profile->setPhoneNumber($data->phoneNumber);
        }
        if (isset($data->termsAccepted)) {
            $profile->setTermsAccepted($data->termsAccepted);
        }

        // Check if personal info is complete
        if ($profile->isPersonalInfoComplete() && !$profile->isPersonalInfoValidated()) {
            $profile->setPersonalInfoValidated(true);
        }

        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        // Update trust score
        $this->trustScoreCalculator->updateUserTrustScore($user);

        return $profile;
    }
}
