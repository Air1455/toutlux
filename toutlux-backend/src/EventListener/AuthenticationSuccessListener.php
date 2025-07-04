<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthenticationSuccessListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private LoggerInterface $logger
    ) {}

    /**
     * Ajouter des données supplémentaires à la réponse d'authentification
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        // Mettre à jour la dernière connexion
        $user->setLastLoginAt(new \DateTimeImmutable());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Ajouter des informations utilisateur à la réponse
        $data['user'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'avatar' => $user->getAvatar(),
            'roles' => $user->getRoles(),
            'trustScore' => $user->getTrustScore(),
            'isVerified' => $user->isVerified(),
            'profileCompletion' => $this->calculateProfileCompletion($user)
        ];

        // Ajouter le refresh token si disponible
        if (isset($data['refresh_token'])) {
            $data['refreshToken'] = $data['refresh_token'];
            unset($data['refresh_token']);
        }

        // Logger la connexion
        $this->logger->info('User authentication success', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'ip' => $request ? $request->getClientIp() : null,
            'user_agent' => $request ? $request->headers->get('User-Agent') : null
        ]);

        $event->setData($data);
    }

    private function calculateProfileCompletion(User $user): array
    {
        $sections = [
            'personal' => $this->isPersonalInfoComplete($user),
            'identity' => $user->isIdentityVerified(),
            'financial' => $user->isFinancialVerified(),
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
}
