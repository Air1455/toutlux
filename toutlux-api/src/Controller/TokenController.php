<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
            $this->logger->info('🔄 Refresh token request received', [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'content_length' => strlen($request->getContent())
            ]);

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('❌ Invalid JSON in token refresh: ' . json_last_error_msg());
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }

            $refreshTokenString = $data['refresh_token'] ?? null;

            if (!$refreshTokenString) {
                $this->logger->warning('❌ No refresh token provided');
                return new JsonResponse(['error' => 'Refresh token required'], 400);
            }

            if (!is_string($refreshTokenString) || strlen($refreshTokenString) < 10) {
                $this->logger->warning('❌ Invalid refresh token format', [
                    'token_type' => gettype($refreshTokenString),
                    'token_length' => is_string($refreshTokenString) ? strlen($refreshTokenString) : 0,
                    'ip' => $request->getClientIp()
                ]);
                return new JsonResponse(['error' => 'Invalid refresh token format'], 400);
            }

            $this->logger->info('🔍 Processing refresh token', [
                'token_preview' => substr($refreshTokenString, 0, 10) . '...',
                'token_length' => strlen($refreshTokenString),
                'ip' => $request->getClientIp()
            ]);

            $result = $this->refreshTokenService->refreshToken($refreshTokenString);

            if (!$result) {
                $this->logger->warning('❌ Invalid or expired refresh token', [
                    'token_preview' => substr($refreshTokenString, 0, 10) . '...',
                    'ip' => $request->getClientIp()
                ]);

                return new JsonResponse([
                    'error' => 'Invalid or expired refresh token'
                ], 401);
            }

            $this->logger->info('✅ Token refresh successful', [
                'user_id' => $result['user']['id'],
                'user_email' => $result['user']['email'],
                'new_token_preview' => substr($result['token'], 0, 10) . '...',
                'new_refresh_preview' => substr($result['refresh_token'], 0, 10) . '...'
            ]);

            return new JsonResponse($result);

        } catch (\Exception $e) {
            $this->logger->error('❌ Token refresh error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Token refresh failed',
                'debug' => $_ENV['APP_ENV'] === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $refreshToken = null;
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $refreshToken = $data['refresh_token'] ?? null;
            }

            if ($refreshToken && is_string($refreshToken)) {
                $revoked = $this->refreshTokenService->revokeToken($refreshToken);

                $this->logger->info('👋 Logout with token revocation', [
                    'refresh_token_revoked' => $revoked,
                    'token_preview' => substr($refreshToken, 0, 10) . '...',
                    'ip' => $request->getClientIp()
                ]);
            } else {
                $this->logger->info('👋 Logout without refresh token', [
                    'ip' => $request->getClientIp()
                ]);
            }

            return new JsonResponse([
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('❌ Logout error: ' . $e->getMessage());

            return new JsonResponse([
                'message' => 'Logout completed'
            ]);
        }
    }

    #[Route('/api/tokens/revoke-all', name: 'api_tokens_revoke_all', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function revokeAllTokens(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'Unauthorized'], 401);
            }

            $this->refreshTokenService->revokeUserTokens($user);

            $this->logger->info('🗑️ All user tokens revoked', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail()
            ]);

            return new JsonResponse([
                'message' => 'All tokens revoked successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('❌ Revoke all tokens error: ' . $e->getMessage(), [
                'user_id' => $this->getUser()?->getId(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Failed to revoke tokens'
            ], 500);
        }
    }

    // ✅ AJOUT: Endpoint de debug pour les tokens
    #[Route('/api/token/debug', name: 'api_token_debug', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function debugToken(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // ✅ CORRECTION: Utiliser EntityManagerInterface au lieu de getDoctrine()
        $refreshTokens = $this->refreshTokenService->getUserTokens($user);

        $activeTokens = array_filter($refreshTokens, fn($token) => !$token->isExpired());

        return new JsonResponse([
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'total_refresh_tokens' => count($refreshTokens),
            'active_refresh_tokens' => count($activeTokens),
            'tokens_info' => array_map(fn($token) => [
                'id' => $token->getId(),
                'created_at' => $token->getCreatedAt()->format('Y-m-d H:i:s'),
                'expires_at' => $token->getExpiresAt()->format('Y-m-d H:i:s'),
                'is_expired' => $token->isExpired(),
                'ip_address' => $token->getIpAddress(),
            ], $refreshTokens)
        ]);
    }
}
