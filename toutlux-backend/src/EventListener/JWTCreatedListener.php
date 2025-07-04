<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTCreatedListener
{
    /**
     * Ajouter des données personnalisées au JWT
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $payload = $event->getData();

        // Ajouter des informations supplémentaires au JWT
        $payload['id'] = $user->getId();
        $payload['firstName'] = $user->getFirstName();
        $payload['lastName'] = $user->getLastName();
        $payload['trustScore'] = $user->getTrustScore();
        $payload['isVerified'] = $user->isVerified();
        $payload['isProfileCompleted'] = $user->isProfileCompleted();
        $payload['isIdentityVerified'] = $user->isIdentityVerified();
        $payload['isFinancialVerified'] = $user->isFinancialVerified();
        $payload['avatar'] = $user->getAvatar();

        // Ajouter l'expiration du refresh token
        $payload['refresh_token_expiration'] = time() + (30 * 24 * 60 * 60); // 30 jours

        $event->setData($payload);
    }
}
