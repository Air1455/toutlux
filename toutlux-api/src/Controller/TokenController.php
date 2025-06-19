<?php

namespace App\Controller;

use App\Service\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class TokenController extends AbstractController
{
    public function __construct(
        private RefreshTokenService $refreshTokenService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $refreshTokenString = $data['refresh_token'] ?? null;

            if (!$refreshTokenString) {
                return new JsonResponse([
                    'error' => 'Refresh token required'
                ], 400);
            }

            $this->logger->info('Attempting token refresh', [
                'refresh_token_length' => strlen($refreshTokenString),
                'ip' => $request->getClientIp()
            ]);

            $result = $this->refreshTokenService->refreshToken($refreshTokenString);

            if (!$result) {
                $this->logger->warning('Invalid or expired refresh token', [
                    'refresh_token' => substr($refreshTokenString, 0, 10) . '...',
                    'ip' => $request->getClientIp()
                ]);

                return new JsonResponse([
                    'error' => 'Invalid or expired refresh token'
                ], 401);
            }

            $this->logger->info('Token refresh successful', [
                'user_id' => $result['user']['id']
            ]);

            return new JsonResponse($result);

        } catch (\Exception $e) {
            $this->logger->error('Token refresh error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Token refresh failed'
            ], 500);
        }
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $refreshToken = $data['refresh_token'] ?? null;

            if ($refreshToken) {
                $revoked = $this->refreshTokenService->revokeToken($refreshToken);

                $this->logger->info('Logout', [
                    'refresh_token_revoked' => $revoked,
                    'ip' => $request->getClientIp()
                ]);
            }

            return new JsonResponse([
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Logout error: ' . $e->getMessage());

            return new JsonResponse([
                'message' => 'Logout completed' // Toujours retourner succès
            ]);
        }
    }

    #[Route('/api/tokens/revoke-all', name: 'api_tokens_revoke_all', methods: ['POST'])]
    public function revokeAllTokens(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $this->refreshTokenService->revokeUserTokens($user);

        $this->logger->info('All tokens revoked', [
            'user_id' => $user->getId()
        ]);

        return new JsonResponse([
            'message' => 'All tokens revoked successfully'
        ]);
    }
}
