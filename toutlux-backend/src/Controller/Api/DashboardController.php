<?php

namespace App\Controller\Admin;

use App\Repository\DocumentRepository;
use App\Repository\MessageRepository;
use App\Repository\PropertyRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private PropertyRepository $propertyRepository,
        private MessageRepository $messageRepository,
        private DocumentRepository $documentRepository
    ) {}

    #[Route('', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Statistiques utilisateurs
        $userStats = $this->userRepository->getUserStatistics();

        // Statistiques propriétés
        $propertyStats = $this->propertyRepository->getStatistics();

        // Messages en attente
        $pendingMessages = $this->messageRepository->findPendingModeration(5);
        $messageStats = [
            'pending' => $this->messageRepository->countByStatus(\App\Enum\MessageStatus::PENDING),
            'approved' => $this->messageRepository->countByStatus(\App\Enum\MessageStatus::APPROVED),
            'rejected' => $this->messageRepository->countByStatus(\App\Enum\MessageStatus::REJECTED)
        ];

        // Documents en attente
        $pendingDocuments = $this->documentRepository->findPending(5);
        $documentStats = $this->documentRepository->getStatsByTypeAndStatus();

        // Utilisateurs récents
        $recentUsers = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // Propriétés récentes
        $recentProperties = $this->propertyRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard/index.html.twig', [
            'userStats' => $userStats,
            'propertyStats' => $propertyStats,
            'messageStats' => $messageStats,
            'documentStats' => $documentStats,
            'pendingMessages' => $pendingMessages,
            'pendingDocuments' => $pendingDocuments,
            'recentUsers' => $recentUsers,
            'recentProperties' => $recentProperties
        ]);
    }

    #[Route('/analytics', name: 'admin_analytics')]
    public function analytics(): Response
    {
        // Données pour les graphiques
        $monthlyUsers = $this->getUserGrowthData();
        $propertyDistribution = $this->getPropertyDistributionData();
        $documentValidationRate = $this->getDocumentValidationRate();
        $messageActivity = $this->getMessageActivityData();

        return $this->render('admin/analytics/index.html.twig', [
            'monthlyUsers' => $monthlyUsers,
            'propertyDistribution' => $propertyDistribution,
            'documentValidationRate' => $documentValidationRate,
            'messageActivity' => $messageActivity
        ]);
    }

    private function getUserGrowthData(): array
    {
        // TODO: Implémenter la récupération des données de croissance des utilisateurs
        return [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
            'data' => [10, 25, 40, 55, 80, 120]
        ];
    }

    private function getPropertyDistributionData(): array
    {
        // TODO: Implémenter la récupération de la distribution des propriétés
        return [
            'labels' => ['Vente', 'Location'],
            'data' => [65, 35]
        ];
    }

    private function getDocumentValidationRate(): array
    {
        // TODO: Implémenter le calcul du taux de validation
        return [
            'approved' => 75,
            'rejected' => 15,
            'pending' => 10
        ];
    }

    private function getMessageActivityData(): array
    {
        // TODO: Implémenter la récupération de l'activité des messages
        return [
            'labels' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            'sent' => [45, 52, 38, 65, 71, 42, 35],
            'received' => [38, 48, 42, 58, 65, 38, 30]
        ];
    }
}
