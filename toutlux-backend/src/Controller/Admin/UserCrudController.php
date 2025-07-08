<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users')]
#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', name: 'admin_user_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('q');
        $status = $request->query->get('status');
        $role = $request->query->get('role');
        $trustScore = $request->query->get('trustScore') ? (float) $request->query->get('trustScore') : null;

        $result = $this->userRepository->findUsersWithFilters(
            $search,
            $status,
            $role,
            $trustScore,
            $page
        );

        return $this->render('admin/user/index.html.twig', [
            'users' => $result['users'],
            'totalUsers' => $result['total'],
            'page' => $page,
            'totalPages' => $result['totalPages'],
            'query' => $search,
            'status' => $status,
            'role' => $role,
            'trustScore' => $trustScore
        ]);
    }

    #[Route('/new', name: 'admin_user_new')]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $plainPassword)
                );
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/export', name: 'admin_user_export')]
    public function export(Request $request): Response
    {
        // Handle CSV export based on filters
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $role = $request->query->get('role');
        $trustScore = $request->query->get('trustScore');

        // Build query based on filters
        $users = $this->userRepository->findUsersForExport($search, $status, $role, $trustScore);

        // Créer le contenu CSV en mémoire
        $csvContent = '';

        // Headers CSV
        $headers = [
            'ID', 'Nom complet', 'Email', 'Téléphone', 'Score de confiance',
            'Rôle', 'Statut', 'Date inscription'
        ];

        $csvContent .= '"' . implode('","', $headers) . '"' . "\n";

        // Données
        foreach ($users as $user) {
            $row = [
                $user->getId(),
                str_replace('"', '""', $user->getFullName()),
                str_replace('"', '""', $user->getEmail()),
                str_replace('"', '""', $user->getPhoneNumber() ?? ''),
                $user->getTrustScore() ?? 0,
                str_replace('"', '""', implode(',', $user->getRoles())),
                $user->isActive() ? 'Actif' : 'Inactif',
                $user->getCreatedAt()?->format('d/m/Y H:i') ?? ''
            ];

            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        // Créer la réponse avec le contenu
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    #[Route('/toggle-status', name: 'admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request): JsonResponse
    {
        $userId = $request->request->get('id');
        $action = $request->request->get('action');

        $user = $this->userRepository->find($userId);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }

        if ($action === 'activate') {
            $user->setIsActive(true);
        } elseif ($action === 'deactivate') {
            $user->setIsActive(false);
        }

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/{id}', name: 'admin_user_show', requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
            'documents' => $user->getDocuments(),
            'properties' => $user->getProperties(),
            'sentMessages' => $user->getSentMessages()->slice(0, 10),
            'receivedMessages' => $user->getReceivedMessages()->slice(0, 10)
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password update
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $plainPassword)
                );
            }

            // Handle avatar file upload (if you're using VichUploaderBundle)
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $user->setAvatarFile($avatarFile);
            }

            // The UserSubscriber will automatically:
            // - Check profile completion
            // - Recalculate trust score
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Empêcher la suppression de son propre compte
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/recalculate-trust-score', name: 'admin_user_recalculate_trust_score', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function recalculateTrustScore(User $user): JsonResponse
    {
        $user->calculateTrustScore();
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'trustScore' => $user->getTrustScore()
        ]);
    }

    #[Route('/{id}/verify-identity', name: 'admin_user_verify_identity', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function verifyIdentity(User $user): Response
    {
        $user->setIdentityVerified(true);
        $user->calculateTrustScore();
        $this->entityManager->flush();

        $this->addFlash('success', 'Identité vérifiée avec succès.');

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/verify-financial', name: 'admin_user_verify_financial', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function verifyFinancial(User $user): Response
    {
        $user->setFinancialVerified(true);
        $user->calculateTrustScore();
        $this->entityManager->flush();

        $this->addFlash('success', 'Documents financiers vérifiés avec succès.');

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }
}
