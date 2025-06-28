<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTAuthenticationSuccessListener
{
    public function __construct(
        private RefreshTokenService $refreshTokenService,
        private RequestStack $requestStack
    ) {
    }

    /**
     *
     * Appelé après une authentification JWT réussie
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $this->requestStack->getCurrentRequest();

        if (!$user instanceof User) {
            return;
        }

        // ✅ AJOUT: Générer refresh token
        $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);

        // ✅ AJOUT: Ajouter refresh_token à la réponse
        $data = $event->getData();
        $data['refresh_token'] = $refreshToken->getToken();

        // ✅ AJOUT: Ajouter informations utilisateur
        $data['user'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'profilePicture' => $user->getProfilePicture(),
            'isProfileComplete' => $user->isProfileComplete(),
            'completionPercentage' => $user->getCompletionPercentage(),
        ];

        $event->setData($data);
    }
}
