<?php

namespace App\Controller\Admin;

use App\Entity\House;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use App\Repository\HouseRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private UserRepository $userRepository,
        private MessageRepository $messageRepository,
        private HouseRepository $houseRepository,
        private ChartBuilderInterface $chartBuilder,
    ) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $this->getStats(),
            'charts' => $this->getCharts(),
            'pending_users' => $this->userRepository->findPendingValidation(5),
            'recent_messages' => $this->messageRepository->findBy(['isRead' => false], ['createdAt' => 'DESC'], 5),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/logo.png" class="admin-logo"> ToutLux Admin')
            ->setFaviconPath('favicon.ico')
            ->setTranslationDomain('admin')
            ->renderContentMaximized()
            ->generateRelativeUrls();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Tous les utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('En attente', 'fas fa-clock', User::class)
            ->setController(PendingUserCrudController::class);
        yield MenuItem::linkToRoute('Documents', 'fas fa-file-alt', 'admin_documents');

        yield MenuItem::section('Contenu');
        yield MenuItem::linkToCrud('Annonces', 'fas fa-home', House::class);
        yield MenuItem::linkToCrud('Messages', 'fas fa-envelope', Message::class)
            ->setBadge($this->messageRepository->countUnreadForAdmin(), 'danger');

        yield MenuItem::section('Système');
        yield MenuItem::linkToCrud('Logs Email', 'fas fa-paper-plane', EmailLog::class);
        yield MenuItem::linkToRoute('Statistiques', 'fas fa-chart-bar', 'admin_stats');

        yield MenuItem::section();
        yield MenuItem::linkToUrl('Voir le site', 'fas fa-external-link-alt', '/')->setLinkTarget('_blank');
        yield MenuItem::linkToLogout('Déconnexion', 'fas fa-sign-out-alt');
    }

    private function getStats(): array
    {
        return [
            'total_users' => $this->userRepository->count([]),
            'pending_verification' => $this->userRepository->count(['status' => 'pending_verification']),
            'active_houses' => $this->houseRepository->count(['status' => 'active']),
            'unread_messages' => $this->messageRepository->countUnreadForAdmin(),
        ];
    }

    private function getCharts(): array
    {
        // Chart des inscriptions
        $registrationChart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $registrationData = $this->userRepository->getMonthlyRegistrationStats(6);

        $registrationChart->setData([
            'labels' => array_keys($registrationData),
            'datasets' => [
                [
                    'label' => 'Inscriptions',
                    'backgroundColor' => 'rgb(75, 192, 192, 0.2)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'data' => array_values($registrationData),
                ],
            ],
        ]);

        return [
            'registrations' => $registrationChart,
        ];
    }
}
