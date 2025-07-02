<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/profile', name: 'admin_profile')]
#[IsGranted('ROLE_ADMIN')]
class AdminProfileController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $admin = $this->getUser();

        return $this->render('admin/admin_profile.html.twig', [
            'admin' => $admin,
        ]);
    }
}
