<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/activity-logs', name: 'admin_activity_logs')]
class ActivityLogsController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        // TODO : Ajoutez de vrais logs via Monolog ou une entité Log dédiée.
        $logs = [
            ['date' => (new \DateTime())->format('d/m/Y H:i'), 'user' => 'admin@toutlux.com', 'action' => 'Login admin'],
            ['date' => (new \DateTime())->modify('-1 day')->format('d/m/Y H:i'), 'user' => 'alice@example.com', 'action' => 'Ajout document'],
            // ...
        ];

        return $this->render('admin/activity_logs.html.twig', [
            'logs' => $logs,
        ]);
    }
}
