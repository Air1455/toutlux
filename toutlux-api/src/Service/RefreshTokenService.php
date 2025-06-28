<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

class RefreshTokenService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
        private ?LoggerInterface $logger = null
    ) {
    }

    public function createRefreshToken(User $user, Request $request = null): RefreshToken
    {
        try {
            $this->logger?->info('🔄 Creating refresh token', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail()
            ]);

            // ✅ ÉTAPE 1: Supprimer les anciens tokens de cet utilisateur
            $this->revokeUserTokens($user);

            // ✅ ÉTAPE 2: Créer nouveau token
            $refreshToken = new RefreshToken();
            $refreshToken->setUser($user);

            if ($request) {
                $refreshToken->setIpAddress($request->getClientIp());
                $refreshToken->setUserAgent($request->headers->get('User-Agent'));

                $this->logger?->info('📍 Request info added to refresh token', [
                    'ip' => $request->getClientIp(),
                    'user_agent' => substr($request->headers->get('User-Agent', ''), 0, 100)
                ]);
            }

            // ✅ ÉTAPE 3: Persister
            $this->entityManager->persist($refreshToken);
            $this->entityManager->flush();

            $this->logger?->info('✅ Refresh token created successfully', [
                'token_id' => $refreshToken->getId(),
                'token_preview' => substr($refreshToken->getToken(), 0, 10) . '...',
                'expires_at' => $refreshToken->getExpiresAt()->format('Y-m-d H:i:s')
            ]);

            return $refreshToken;

        } catch (\Exception $e) {
            $this->logger?->error('❌ Failed to create refresh token: ' . $e->getMessage(), [
                'user_id' => $user->getId(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to create refresh token: ' . $e->getMessage(), 0, $e);
        }
    }

    public function refreshToken(string $refreshTokenString): ?array
    {
        try {
            $this->logger?->info('🔄 Attempting to refresh token', [
                'token_preview' => substr($refreshTokenString, 0, 10) . '...'
            ]);

            $refreshToken = $this->entityManager
                ->getRepository(RefreshToken::class)
                ->findOneBy(['token' => $refreshTokenString]);

            if (!$refreshToken) {
                $this->logger?->warning('❌ Refresh token not found in database');
                return null;
            }

            if ($refreshToken->isExpired()) {
                $this->logger?->warning('❌ Refresh token expired', [
                    'expired_at' => $refreshToken->getExpiresAt()->format('Y-m-d H:i:s'),
                    'current_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
                ]);

                // Supprimer le token expiré
                $this->entityManager->remove($refreshToken);
                $this->entityManager->flush();
                return null;
            }

            $user = $refreshToken->getUser();

            // ✅ Générer nouveau JWT
            $newJwtToken = $this->jwtManager->create($user);

            // ✅ Créer nouveau refresh token (rotation)
            $newRefreshToken = $this->createRefreshToken($user);

            $this->logger?->info('✅ Token refreshed successfully', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'new_jwt_preview' => substr($newJwtToken, 0, 20) . '...',
                'new_refresh_preview' => substr($newRefreshToken->getToken(), 0, 10) . '...'
            ]);

            return [
                'token' => $newJwtToken,
                'refresh_token' => $newRefreshToken->getToken(),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'profilePicture' => $user->getProfilePicture(),
                    'isProfileComplete' => $user->isProfileComplete(),
                    'completionPercentage' => $user->getCompletionPercentage(),
                ]
            ];

        } catch (\Exception $e) {
            $this->logger?->error('❌ Token refresh failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function revokeToken(string $refreshTokenString): bool
    {
        try {
            $refreshToken = $this->entityManager
                ->getRepository(RefreshToken::class)
                ->findOneBy(['token' => $refreshTokenString]);

            if ($refreshToken) {
                $this->entityManager->remove($refreshToken);
                $this->entityManager->flush();

                $this->logger?->info('🗑️ Refresh token revoked', [
                    'token_preview' => substr($refreshTokenString, 0, 10) . '...'
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger?->error('❌ Failed to revoke token: ' . $e->getMessage());
            return false;
        }
    }

    public function revokeUserTokens(User $user): int
    {
        try {
            $tokens = $this->entityManager
                ->getRepository(RefreshToken::class)
                ->findBy(['user' => $user]);

            $count = count($tokens);

            foreach ($tokens as $token) {
                $this->entityManager->remove($token);
            }

            if ($count > 0) {
                $this->entityManager->flush();
                $this->logger?->info("🗑️ Revoked {$count} existing tokens for user", [
                    'user_id' => $user->getId()
                ]);
            }

            return $count;

        } catch (\Exception $e) {
            $this->logger?->error('❌ Failed to revoke user tokens: ' . $e->getMessage(), [
                'user_id' => $user->getId()
            ]);
            return 0;
        }
    }

    // ✅ AJOUT: Méthode pour récupérer les tokens d'un utilisateur
    public function getUserTokens(User $user): array
    {
        try {
            return $this->entityManager
                ->getRepository(RefreshToken::class)
                ->findBy(['user' => $user], ['createdAt' => 'DESC']);
        } catch (\Exception $e) {
            $this->logger?->error('❌ Failed to get user tokens: ' . $e->getMessage(), [
                'user_id' => $user->getId()
            ]);
            return [];
        }
    }

    public function cleanExpiredTokens(): int
    {
        try {
            $qb = $this->entityManager->createQueryBuilder();

            $deletedCount = $qb->delete(RefreshToken::class, 'rt')
                ->where('rt.expiresAt < :now')
                ->setParameter('now', new \DateTimeImmutable())
                ->getQuery()
                ->execute();

            $this->logger?->info("🧹 Cleaned {$deletedCount} expired tokens");

            return $deletedCount;

        } catch (\Exception $e) {
            $this->logger?->error('❌ Failed to clean expired tokens: ' . $e->getMessage());
            return 0;
        }
    }
}
