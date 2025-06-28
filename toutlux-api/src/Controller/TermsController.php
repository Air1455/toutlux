<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[IsGranted('ROLE_USER')]
class TermsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/profile/accept-terms', name: 'api_accept_terms', methods: ['POST'])]
    public function acceptTerms(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }

            // ✅ CORRECTION: Récupérer version comme string
            $version = $data['version'] ?? '1.0';

            // ✅ AJOUT: Validation que version est bien une string
            if (!is_string($version)) {
                $version = '1.0';
                $this->logger->warning('Version is not a string, using default', [
                    'received_version' => $data['version'] ?? 'null',
                    'type' => gettype($data['version'] ?? null)
                ]);
            }

            $this->logger->info('Accepting terms', [
                'user_id' => $user->getId(),
                'version' => $version,
                'version_type' => gettype($version),
                'raw_data' => $data
            ]);

            // ✅ CORRECTION: Passer version comme string
            $user->acceptAllTerms($version);

            // Mise à jour du timestamp
            $user->setUpdatedAt(new \DateTimeImmutable());

            // Sauvegarde
            $this->entityManager->flush();

            $this->logger->info('Terms and conditions accepted successfully', [
                'user_id' => $user->getId(),
                'version' => $version,
                'accepted_at' => $user->getTermsAcceptedAt()?->format('Y-m-d H:i:s')
            ]);

            return new JsonResponse([
                'success' => true,
                'version' => $version,
                'accepted_at' => $user->getTermsAcceptedAt()?->format('c'),
                'user' => [
                    'id' => $user->getId(),
                    'terms_accepted' => $user->isTermsAccepted(),
                    'privacy_accepted' => $user->isPrivacyAccepted(),
                    'is_profile_complete' => $user->isProfileComplete(),
                    'completion_percentage' => $user->getCompletionPercentage()
                ],
                'message' => 'Terms and conditions accepted successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Accept terms error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->getContent()
            ]);

            return new JsonResponse([
                'error' => 'Failed to accept terms',
                'message' => $_ENV['APP_ENV'] === 'dev' ? $e->getMessage() : 'An error occurred while accepting terms and conditions'
            ], 500);
        }
    }


    #[Route('/api/terms', name: 'api_get_terms', methods: ['GET'])]
    public function getTerms(Request $request): JsonResponse
    {
        $language = $request->query->get('lang', 'fr');

        // Ici vous pourriez avoir une entité Terms ou simplement retourner du contenu statique
        $terms = [
            'version' => '1.0',
            'language' => $language,
            'last_updated' => '2024-01-01',
            'sections' => [
                [
                    'id' => 'service',
                    'title' => $language === 'fr' ? 'Utilisation du service' : 'Service Usage',
                    'content' => $language === 'fr'
                        ? 'Notre plateforme immobilière vous permet de rechercher, publier et gérer des biens immobiliers...'
                        : 'Our real estate platform allows you to search, publish and manage real estate properties...'
                ],
                [
                    'id' => 'privacy',
                    'title' => $language === 'fr' ? 'Protection des données' : 'Data Protection',
                    'content' => $language === 'fr'
                        ? 'Nous nous engageons à protéger vos données personnelles conformément au RGPD...'
                        : 'We are committed to protecting your personal data in accordance with GDPR...'
                ],
                // Autres sections...
            ]
        ];

        return new JsonResponse($terms);
    }

    #[Route('/api/me/terms-status', name: 'api_user_terms_status', methods: ['GET'])]
    public function getTermsStatus(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            return new JsonResponse([
                'terms_accepted' => $user->isTermsAccepted(),
                'terms_accepted_at' => $user->getTermsAcceptedAt()?->format('c'),
                'privacy_accepted' => $user->isPrivacyAccepted(),
                'privacy_accepted_at' => $user->getPrivacyAcceptedAt()?->format('c'),
                'marketing_accepted' => $user->isMarketingAccepted(),
                'marketing_accepted_at' => $user->getMarketingAcceptedAt()?->format('c'),
                'current_terms_version' => '1.0',
                'needs_update' => false // Logique à implémenter selon vos besoins
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Get terms status error: " . $e->getMessage());

            return new JsonResponse([
                'error' => 'Failed to get terms status'
            ], 500);
        }
    }
}
