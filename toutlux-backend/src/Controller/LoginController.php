<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function index(): JsonResponse
    {
        // The security system will intercept this request and handle authentication.
        // If authentication is successful, LexikJWTAuthenticationBundle will return a JWT.
        // If authentication fails, an appropriate error response will be returned.
        throw new \Exception('Should not be reached: authentication is handled by LexikJWTAuthenticationBundle');
    }
}
