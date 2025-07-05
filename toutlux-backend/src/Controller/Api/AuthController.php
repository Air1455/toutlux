<?php

namespace App\Controller\Api;

use App\DTO\Auth\LoginRequest;
use App\DTO\Auth\RegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Auth\EmailVerificationService;
use App\Service\Auth\JWTService;
use App\Service\Email\WelcomeEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTService $jwtService,
        private EmailVerificationService $emailVerificationService,
        private WelcomeEmailService $welcomeEmailService,
        private ValidatorInterface $validator
    ) {}

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload] RegisterRequest $request,
        UserRepository $userRepository
    ): JsonResponse {
        // Vérifier si l'email existe déjà
        if ($userRepository->findOneBy(['email' => $request->email])) {
            return $this->json([
                'error' => 'Cet email est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($request->email);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $request->password)
        );
        $user->setFirstName($request->firstName);
        $user->setLastName($request->lastName);
        $user->setRoles(['ROLE_USER']);

        // Valider l'entité
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer l'email de bienvenue et de vérification
        $this->welcomeEmailService->sendWelcomeEmail($user);

        // Générer le token JWT
        $tokenData = $this->jwtService->createTokenFromUser($user);

        return $this->json([
            'message' => 'Inscription réussie. Veuillez vérifier votre email.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'isVerified' => $user->isVerified()
            ],
            'token' => $tokenData['token'],
            'tokenType' => $tokenData['type'],
            'expiresIn' => $tokenData['expires_in']
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(
        #[MapRequestPayload] LoginRequest $request,
        UserRepository $userRepository
    ): JsonResponse {
        $user = $userRepository->findOneBy(['email' => $request->email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $request->password)) {
            return $this->json([
                'error' => 'Email ou mot de passe incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Mettre à jour la dernière connexion
        $user->setLastLoginAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        // Générer le token JWT
        $tokenData = $this->jwtService->createTokenFromUser($user);

        return $this->json([
            'message' => 'Connexion réussie',
            'user' => $tokenData['user'],
            'token' => $tokenData['token'],
            'tokenType' => $tokenData['type'],
            'expiresIn' => $tokenData['expires_in']
        ]);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'error' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'avatar' => $user->getAvatar(),
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified(),
                'trustScore' => $user->getTrustScore(),
                'createdAt' => $user->getCreatedAt()->format('c'),
                'profileCompletion' => $this->jwtService->calculateProfileCompletion($user)
            ]
        ]);
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Le JWT est stateless, donc pas de logout côté serveur
        // Le client doit simplement supprimer le token
        return $this->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    #[Route('/verify-email/{token}', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token, Request $request): JsonResponse
    {
        $id = $request->query->get('id');

        if (!$id) {
            return $this->json([
                'error' => 'ID utilisateur manquant'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            // Reconstruire l'URI complet pour la vérification
            $uri = $request->getUri();
            $this->emailVerificationService->verifyUserEmail($uri, $user);

            return $this->json([
                'message' => 'Email vérifié avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/resend-verification', name: 'api_auth_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json([
                'error' => 'Email requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->emailVerificationService->resendVerificationEmail($email);

            if ($result) {
                return $this->json([
                    'message' => 'Email de vérification renvoyé'
                ]);
            } else {
                return $this->json([
                    'error' => 'Utilisateur introuvable'
                ], Response::HTTP_NOT_FOUND);
            }

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json([
                'error' => 'Email requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // TODO: Implémenter la logique de réinitialisation de mot de passe

        return $this->json([
            'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé'
        ]);
    }

    #[Route('/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $password = $data['password'] ?? null;

        if (!$token || !$password) {
            return $this->json([
                'error' => 'Token et mot de passe requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // TODO: Implémenter la logique de réinitialisation avec token

        return $this->json([
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }

    #[Route('/change-password', name: 'api_auth_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json([
                'error' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $currentPassword = $data['currentPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$currentPassword || !$newPassword) {
            return $this->json([
                'error' => 'Mot de passe actuel et nouveau mot de passe requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier le mot de passe actuel
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json([
                'error' => 'Mot de passe actuel incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Mettre à jour le mot de passe
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $newPassword)
        );
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Mot de passe modifié avec succès'
        ]);
    }
}
