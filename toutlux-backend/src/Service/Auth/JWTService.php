<?php

namespace App\Service\Auth;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTService
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private string $jwtTTL
    ) {}

    public function createToken(UserInterface $user): string
    {
        return $this->jwtManager->create($user);
    }

    public function createTokenFromUser(User $user): array
    {
        $token = $this->createToken($user);

        return [
            'token' => $token,
            'type' => 'Bearer',
            'expires_in' => $this->jwtTTL,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'trustScore' => $user->getTrustScore(),
                'isVerified' => $user->isVerified(),
                'profileCompleted' => $this->calculateProfileCompletion($user)
            ]
        ];
    }

    public function decode(string $token): ?array
    {
        try {
            return $this->jwtManager->parse($token);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function calculateProfileCompletion(User $user): array
    {
        $sections = [
            'personal' => $this->isPersonalInfoComplete($user),
            'identity' => $this->isIdentityComplete($user),
            'financial' => $this->isFinancialComplete($user),
            'terms' => $user->isTermsAccepted()
        ];

        $completed = count(array_filter($sections));
        $total = count($sections);

        return [
            'percentage' => ($completed / $total) * 100,
            'sections' => $sections,
            'completed' => $completed,
            'total' => $total
        ];
    }

    private function isPersonalInfoComplete(User $user): bool
    {
        return !empty($user->getFirstName())
            && !empty($user->getLastName())
            && !empty($user->getPhone())
            && !empty($user->getAvatar());
    }

    private function isIdentityComplete(User $user): bool
    {
        $identityDocs = $user->getDocuments()->filter(function($doc) {
            return $doc->getType() === 'identity' && $doc->getStatus() === 'validated';
        });

        return $identityDocs->count() >= 2; // ID + Selfie
    }

    private function isFinancialComplete(User $user): bool
    {
        $financialDocs = $user->getDocuments()->filter(function($doc) {
            return $doc->getType() === 'financial' && $doc->getStatus() === 'validated';
        });

        return $financialDocs->count() > 0;
    }
}
