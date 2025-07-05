<?php

namespace App\Controller\Admin;

use App\Repository\DocumentRepository;
use App\Repository\MessageRepository;
use App\Repository\PropertyRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/analytics')]
#[IsGranted('ROLE_ADMIN')]
class AnalyticsController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private PropertyRepository $propertyRepository,
        private MessageRepository $messageRepository,
        private DocumentRepository $documentRepository
    ) {}

    #[Route('', name: 'admin_analytics_index')]
    public function index(): Response
    {
        return $this->render('admin/analytics/index.html.twig', [
            'userGrowth' => $this->getUserGrowthData(),
            'propertyStats' => $this->getPropertyStats(),
            'documentStats' => $this->getDocumentStats(),
            'messageStats' => $this->getMessageStats(),
            'trustScoreDistribution' => $this->getTrustScoreDistribution()
        ]);
    }

    #[Route('/users', name: 'admin_analytics_users')]
    public function users(Request $request): Response
    {
        $period = $request->query->get('period', '30');

        return $this->render('admin/analytics/users.html.twig', [
            'userGrowth' => $this->getUserGrowthData($period),
            'usersBySource' => $this->getUsersBySource(),
            'userActivity' => $this->getUserActivityData($period),
            'topUsers' => $this->getTopUsers(),
            'period' => $period
        ]);
    }

    #[Route('/properties', name: 'admin_analytics_properties')]
    public function properties(Request $request): Response
    {
        $period = $request->query->get('period', '30');

        return $this->render('admin/analytics/properties.html.twig', [
            'propertyGrowth' => $this->getPropertyGrowthData($period),
            'propertyByType' => $this->getPropertyByType(),
            'propertyByCity' => $this->getPropertyByCity(),
            'priceDistribution' => $this->getPriceDistribution(),
            'period' => $period
        ]);
    }

    #[Route('/engagement', name: 'admin_analytics_engagement')]
    public function engagement(Request $request): Response
    {
        $period = $request->query->get('period', '30');

        return $this->render('admin/analytics/engagement.html.twig', [
            'messageVolume' => $this->getMessageVolumeData($period),
            'documentSubmissions' => $this->getDocumentSubmissionData($period),
            'conversionFunnel' => $this->getConversionFunnelData(),
            'period' => $period
        ]);
    }

    private function getUserGrowthData(string $period = '30'): array
    {
        $endDate = new \DateTimeImmutable();
        $startDate = $endDate->modify("-{$period} days");

        // TODO: Implémenter la requête SQL pour obtenir les données de croissance
        // Pour l'instant, données fictives
        $data = [];
        for ($i = 0; $i < intval($period); $i++) {
            $date = $startDate->modify("+{$i} days");
            $data['labels'][] = $date->format('d/m');
            $data['values'][] = rand(5, 20);
        }

        return $data;
    }

    private function getPropertyStats(): array
    {
        $stats = $this->propertyRepository->getStatistics();

        return [
            'total' => $stats['total_properties'] ?? 0,
            'available' => $stats['available_properties'] ?? 0,
            'forSale' => $stats['for_sale'] ?? 0,
            'forRent' => $stats['for_rent'] ?? 0,
            'avgPrice' => $stats['avg_price'] ?? 0,
            'avgSurface' => $stats['avg_surface'] ?? 0,
            'totalViews' => $stats['total_views'] ?? 0,
            'verified' => $stats['verified_properties'] ?? 0,
            'featured' => $stats['featured_properties'] ?? 0
        ];
    }

    private function getDocumentStats(): array
    {
        $stats = $this->documentRepository->getStatsByTypeAndStatus();

        $processed = [];
        foreach ($stats as $stat) {
            $type = $stat['type']->value;
            $status = $stat['status']->value;
            $count = $stat['count'];

            if (!isset($processed[$type])) {
                $processed[$type] = [
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0
                ];
            }

            $processed[$type][$status] = $count;
        }

        return $processed;
    }

    private function getMessageStats(): array
    {
        return [
            'total' => $this->messageRepository->count([]),
            'pending' => $this->messageRepository->countByStatus(\App\Enum\MessageStatus::PENDING),
            'approved' => $this->messageRepository->countByStatus(\App\Enum\MessageStatus::APPROVED),
            'rejected' => $this->messageRepository->countByStatus(\App\Enum\MessageStatus::REJECTED),
            'withProperty' => $this->messageRepository->countMessagesWithProperty()
        ];
    }

    private function getTrustScoreDistribution(): array
    {
        // TODO: Implémenter la requête pour obtenir la distribution des scores
        return [
            'labels' => ['0-1', '1-2', '2-3', '3-4', '4-5'],
            'values' => [15, 25, 35, 20, 5]
        ];
    }

    private function getUsersBySource(): array
    {
        $emailUsers = $this->userRepository->count(['googleId' => null]);
        $googleUsers = $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.googleId IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'email' => $emailUsers,
            'google' => $googleUsers
        ];
    }

    private function getUserActivityData(string $period): array
    {
        // TODO: Implémenter les requêtes pour l'activité des utilisateurs
        return [
            'logins' => [],
            'messagesPerDay' => [],
            'documentsPerDay' => []
        ];
    }

    private function getTopUsers(): array
    {
        // Utilisateurs avec le plus de propriétés
        $topPropertyOwners = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.properties', 'p')
            ->groupBy('u.id')
            ->orderBy('COUNT(p.id)', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $topPropertyOwners;
    }

    private function getPropertyGrowthData(string $period): array
    {
        $endDate = new \DateTimeImmutable();
        $startDate = $endDate->modify("-{$period} days");

        // TODO: Implémenter la requête SQL
        $data = [];
        for ($i = 0; $i < intval($period); $i++) {
            $date = $startDate->modify("+{$i} days");
            $data['labels'][] = $date->format('d/m');
            $data['values'][] = rand(2, 10);
        }

        return $data;
    }

    private function getPropertyByType(): array
    {
        // TODO: Utiliser une vraie requête
        return [
            'sale' => $this->propertyRepository->count(['type' => 'sale']),
            'rent' => $this->propertyRepository->count(['type' => 'rent'])
        ];
    }

    private function getPropertyByCity(): array
    {
        $cities = $this->propertyRepository->createQueryBuilder('p')
            ->select('p.city, COUNT(p.id) as count')
            ->groupBy('p.city')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $cities;
    }

    private function getPriceDistribution(): array
    {
        // TODO: Implémenter la distribution des prix
        return [
            'ranges' => ['0-50k', '50k-100k', '100k-200k', '200k-500k', '500k+'],
            'counts' => [10, 25, 40, 20, 5]
        ];
    }

    private function getMessageVolumeData(string $period): array
    {
        // TODO: Implémenter le volume de messages par jour
        return [
            'sent' => [],
            'received' => []
        ];
    }

    private function getDocumentSubmissionData(string $period): array
    {
        // TODO: Implémenter les soumissions de documents
        return [
            'identity' => [],
            'financial' => []
        ];
    }

    private function getConversionFunnelData(): array
    {
        $totalUsers = $this->userRepository->count([]);
        $verifiedUsers = $this->userRepository->count(['emailVerified' => true]);
        $profileCompleted = $this->userRepository->count(['profileCompleted' => true]);
        $identityVerified = $this->userRepository->count(['identityVerified' => true]);
        $financialVerified = $this->userRepository->count(['financialVerified' => true]);

        return [
            'registered' => $totalUsers,
            'emailVerified' => $verifiedUsers,
            'profileCompleted' => $profileCompleted,
            'identityVerified' => $identityVerified,
            'financialVerified' => $financialVerified
        ];
    }
}
