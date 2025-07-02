<?php

namespace App\Controller\Api;

use App\DTO\Auth\GoogleAuthRequest;
use App\Service\Auth\GoogleAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class GoogleAuthController extends AbstractController
{
    public function __construct(
        private GoogleAuthService $googleAuthService
    ) {}

    #[Route('/google', name: 'api_auth_google', methods: ['POST'])]
    public function googleAuth(
        #[MapRequestPayload] GoogleAuthRequest $request
    ): JsonResponse {
        try {
            $tokenData = $this->googleAuthService->authenticate($request->idToken);

            return $this->json([
                'message' => 'Authentification Google réussie',
                'user' => $tokenData['user'],
                'token' => $tokenData['token'],
                'tokenType' => $tokenData['type'],
                'expiresIn' => $tokenData['expires_in'],
                'isNewUser' => $tokenData['isNewUser']
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Authentification Google échouée',
                'message' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/google/validate', name: 'api_auth_google_validate', methods: ['POST'])]
    public function validateGoogleToken(
        #[MapRequestPayload] GoogleAuthRequest $request
    ): JsonResponse {
        $isValid = $this->googleAuthService->validateToken($request->idToken);

        return $this->json([
            'valid' => $isValid
        ]);
    }
}
