<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/settings', name: 'admin_settings_')]
class SettingsController extends AbstractController
{
    #[Route('/general', name: 'general', methods: ['GET', 'POST'])]
    public function general(): Response
    {
        // Un vrai formulaire serait ici avec paramétrage de l'app
        return $this->render('admin/settings/settings_general.html.twig');
    }

    #[Route('/email', name: 'email', methods: ['GET', 'POST'])]
    public function email(): Response
    {
        // Un vrai formulaire serait ici avec paramétrage SMTP, sender, etc.
        return $this->render('admin/settings/settings_email.html.twig');
    }
}
