<?php

namespace App\Controller\Admin;

use App\Repository\EmailLogRepository;
use App\Service\Messaging\EmailService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/email-logs')]
#[IsGranted('ROLE_ADMIN')]
class EmailLogsController extends AbstractController
{
    public function __construct(
        private EmailLogRepository $emailLogRepository,
        private EmailService $emailService,
        private PaginatorInterface $paginator
    ) {}

    #[Route('', name: 'admin_email_logs')]
    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->query->get('status'),
            'template' => $request->query->get('template'),
            'search' => $request->query->get('search'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
        ];

        $queryBuilder = $this->emailLogRepository->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->addSelect('u');

        // Application des filtres
        if ($filters['status']) {
            $queryBuilder->andWhere('e.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if ($filters['template']) {
            $queryBuilder->andWhere('e.template = :template')
                ->setParameter('template', $filters['template']);
        }

        if ($filters['search']) {
            $queryBuilder->andWhere('e.toEmail LIKE :search OR e.subject LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if ($filters['date_from']) {
            $queryBuilder->andWhere('e.createdAt >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if ($filters['date_to']) {
            $queryBuilder->andWhere('e.createdAt <= :dateTo')
                ->setParameter('dateTo', new \DateTime($filters['date_to'] . ' 23:59:59'));
        }

        $queryBuilder->orderBy('e.createdAt', 'DESC');

        // Pagination
        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            50
        );

        // Statistiques
        $stats = [
            'total' => $this->emailLogRepository->count([]),
            'sent' => $this->emailLogRepository->count(['status' => 'sent']),
            'failed' => count($this->emailLogRepository->findFailedEmails()),
            'pending' => count($this->emailLogRepository->findPendingEmails()),
        ];

        // Templates disponibles
        $templates = $this->emailLogRepository->createQueryBuilder('e')
            ->select('DISTINCT e.template')
            ->orderBy('e.template')
            ->getQuery()
            ->getSingleColumnResult();

        return $this->render('admin/email_logs/index.html.twig', [
            'pagination' => $pagination,
            'filters' => $filters,
            'stats' => $stats,
            'templates' => $templates,
        ]);
    }

    #[Route('/retry-pending', name: 'admin_email_logs_retry_pending', methods: ['POST'])]
    public function retryPending(): Response
    {
        $this->emailService->processPendingEmails();

        $this->addFlash('success', 'Traitement des emails en attente lancé');
        return $this->redirectToRoute('admin_email_logs');
    }
}
