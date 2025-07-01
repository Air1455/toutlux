<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Property;
use App\Entity\Message;
use App\Entity\Document;
use App\Service\Document\DocumentValidationService;
use App\Service\Message\MessageModerationService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentValidationService $documentValidationService,
        private MessageModerationService $messageModerationService,
        private ChartBuilderInterface $chartBuilder
    ) {}

    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Get statistics
        $stats = $this->getDashboardStats();

        // Create charts
        $userChart = $this->createUserChart();
        $propertyChart = $this->createPropertyChart();
        $revenueChart = $this->createRevenueChart();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'userChart' => $userChart,
            'propertyChart' => $propertyChart,
            'revenueChart' => $revenueChart,
            'pendingDocuments' => $this->documentValidationService->getPendingDocuments(),
            'pendingMessages' => $this->getPendingMessages()
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('TOUTLUX Admin')
            ->setFaviconPath('favicon.ico')
            ->setTranslationDomain('admin')
            ->generateRelativeUrls()
            ->renderContentMaximized()
            ->renderSidebarMinimized()
            ->disableUrlSignatures();
    }

    public function configureMenuItems(): iterable
    {
        $pendingDocumentsCount = $this->documentValidationService->getPendingDocumentsCount();
        $pendingMessagesCount = $this->messageModerationService->getPendingMessagesCount();

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Users section
        yield MenuItem::section('Users');
        yield MenuItem::linkToCrud('All Users', 'fa fa-users', User::class);
        yield MenuItem::linkToRoute('User Profiles', 'fa fa-id-card', 'admin_user_profiles');

        // Properties section
        yield MenuItem::section('Properties');
        yield MenuItem::linkToCrud('All Properties', 'fa fa-building', Property::class);
        yield MenuItem::linkToRoute('Property Analytics', 'fa fa-chart-line', 'admin_property_analytics');

        // Validation section
        yield MenuItem::section('Validation & Moderation');
        yield MenuItem::linkToRoute('Document Validation', 'fa fa-file-check', 'admin_document_validation')
            ->setBadge($pendingDocumentsCount > 0 ? $pendingDocumentsCount : null, 'danger');
        yield MenuItem::linkToRoute('Message Moderation', 'fa fa-comments', 'admin_message_moderation')
            ->setBadge($pendingMessagesCount > 0 ? $pendingMessagesCount : null, 'warning');

        // Messages section
        yield MenuItem::section('Communications');
        yield MenuItem::linkToCrud('All Messages', 'fa fa-envelope', Message::class);
        yield MenuItem::linkToRoute('Email Templates', 'fa fa-mail-bulk', 'admin_email_templates');

        // Reports section
        yield MenuItem::section('Reports');
        yield MenuItem::linkToRoute('User Reports', 'fa fa-file-alt', 'admin_reports_users');
        yield MenuItem::linkToRoute('Financial Reports', 'fa fa-dollar-sign', 'admin_reports_financial');
        yield MenuItem::linkToRoute('Activity Logs', 'fa fa-history', 'admin_activity_logs');

        // Settings
        yield MenuItem::section('Settings');
        yield MenuItem::linkToRoute('General Settings', 'fa fa-cog', 'admin_settings_general');
        yield MenuItem::linkToRoute('Email Settings', 'fa fa-envelope-square', 'admin_settings_email');
        yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setName($user->getUserIdentifier())
            ->displayUserName(true)
            ->displayUserAvatar(false)
            ->addMenuItems([
                MenuItem::linkToRoute('My Profile', 'fa fa-user', 'admin_profile'),
                MenuItem::linkToRoute('Settings', 'fa fa-cog', 'admin_settings'),
            ]);
    }

    private function getDashboardStats(): array
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $propertyRepo = $this->entityManager->getRepository(Property::class);
        $messageRepo = $this->entityManager->getRepository(Message::class);

        return [
            'users' => [
                'total' => $userRepo->count([]),
                'verified' => $userRepo->count(['isVerified' => true]),
                'new_this_week' => $this->getCountThisWeek(User::class),
                'with_complete_profile' => $this->getCompleteProfileCount()
            ],
            'properties' => [
                'total' => $propertyRepo->count([]),
                'for_sale' => $propertyRepo->count(['type' => Property::TYPE_SALE]),
                'for_rent' => $propertyRepo->count(['type' => Property::TYPE_RENT]),
                'available' => $propertyRepo->count(['status' => Property::STATUS_AVAILABLE])
            ],
            'messages' => [
                'total' => $messageRepo->count([]),
                'pending_moderation' => $messageRepo->count(['status' => Message::STATUS_PENDING]),
                'sent_today' => $this->getCountToday(Message::class)
            ],
            'documents' => $this->documentValidationService->getDocumentValidationStats()
        ];
    }

    private function createUserChart(): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        // Get user registration data for last 30 days
        $data = $this->getUserRegistrationData(30);

        $chart->setData([
            'labels' => array_keys($data),
            'datasets' => [
                [
                    'label' => 'New Users',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'data' => array_values($data),
                    'tension' => 0.3
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ]);

        return $chart;
    }

    private function createPropertyChart(): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $propertyRepo = $this->entityManager->getRepository(Property::class);

        $chart->setData([
            'labels' => ['For Sale', 'For Rent', 'Sold', 'Rented'],
            'datasets' => [
                [
                    'label' => 'Properties',
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(168, 85, 247, 0.8)'
                    ],
                    'data' => [
                        $propertyRepo->count(['type' => Property::TYPE_SALE, 'status' => Property::STATUS_AVAILABLE]),
                        $propertyRepo->count(['type' => Property::TYPE_RENT, 'status' => Property::STATUS_AVAILABLE]),
                        $propertyRepo->count(['status' => Property::STATUS_SOLD]),
                        $propertyRepo->count(['status' => Property::STATUS_RENTED])
                    ],
                ],
            ],
        ]);

        return $chart;
    }

    private function createRevenueChart(): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        // Simulated revenue data - replace with actual data
        $chart->setData([
            'labels' => ['Sales Commission', 'Rental Commission', 'Premium Listings', 'Other'],
            'datasets' => [
                [
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(168, 85, 247, 0.8)'
                    ],
                    'data' => [45000, 32000, 15000, 8000],
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
        ]);

        return $chart;
    }

    private function getUserRegistrationData(int $days): array
    {
        $data = [];
        $startDate = new \DateTime("-{$days} days");

        for ($i = 0; $i < $days; $i++) {
            $date = clone $startDate;
            $date->modify("+{$i} days");
            $dateStr = $date->format('Y-m-d');

            $count = $this->entityManager->createQueryBuilder()
                ->select('COUNT(u.id)')
                ->from(User::class, 'u')
                ->where('DATE(u.createdAt) = :date')
                ->setParameter('date', $dateStr)
                ->getQuery()
                ->getSingleScalarResult();

            $data[$date->format('M d')] = (int) $count;
        }

        return $data;
    }

    private function getCountThisWeek(string $entityClass): int
    {
        $startOfWeek = new \DateTime('monday this week');

        return $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from($entityClass, 'e')
            ->where('e.createdAt >= :start')
            ->setParameter('start', $startOfWeek)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getCountToday(string $entityClass): int
    {
        $today = new \DateTime('today');

        return $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from($entityClass, 'e')
            ->where('e.createdAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getCompleteProfileCount(): int
    {
        return $this->entityManager->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->join('u.profile', 'p')
            ->where('p.personalInfoValidated = true')
            ->andWhere('p.identityValidated = true')
            ->andWhere('p.financialValidated = true')
            ->andWhere('p.termsAccepted = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getPendingMessages(): array
    {
        return $this->entityManager->getRepository(Message::class)
            ->findBy(['status' => Message::STATUS_PENDING], ['createdAt' => 'DESC'], 5);
    }
}
