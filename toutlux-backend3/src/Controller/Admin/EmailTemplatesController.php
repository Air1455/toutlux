<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/email-templates', name: 'admin_email_templates')]
class EmailTemplatesController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        // Liste fictive pour démo
        $templates = [
            ['name' => 'welcome.html.twig', 'subject' => 'Bienvenue chez TOUTLUX'],
            ['name' => 'document_approved.html.twig', 'subject' => 'Document approuvé'],
            ['name' => 'document_rejected.html.twig', 'subject' => 'Document refusé'],
            // ...
        ];

        return $this->render('admin/email/email_templates.html.twig', [
            'templates' => $templates,
        ]);
    }
}
