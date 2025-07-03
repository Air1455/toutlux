<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', name: 'admin_users_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;

        $query = $request->query->get('q');
        $role = $request->query->get('role');

        if ($query) {
            $users = $this->userRepository->searchUsers($query);
        } elseif ($role) {
            $users = $this->userRepository->findByRole($role);
        } else {
            $users = $this->userRepository->findBy([], ['createdAt' => 'DESC'], $limit, ($page - 1) * $limit);
        }

        $totalUsers = $this->userRepository->count([]);

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'page' => $page,
            'pages' => ceil($totalUsers / $limit),
            'query' => $query,
            'role' => $role
        ]);
    }

    #[Route('/new', name: 'admin_users_new')]
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

            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_users_show')]
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

    #[Route('/{id}/edit', name: 'admin_users_edit')]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $plainPassword)
                );
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Empêcher la suppression de son propre compte
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/toggle-status', name: 'admin_users_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user): Response
    {
        $user->setEmailVerified(!$user->isEmailVerified());
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'verified' => $user->isEmailVerified()
        ]);
    }

    #[Route('/{id}/recalculate-trust-score', name: 'admin_users_recalculate_trust_score', methods: ['POST'])]
    public function recalculateTrustScore(User $user): Response
    {
        $user->calculateTrustScore();
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'trustScore' => $user->getTrustScore()
        ]);
    }

    #[Route('/{id}/verify-identity', name: 'admin_users_verify_identity', methods: ['POST'])]
    public function verifyIdentity(User $user): Response
    {
        $user->setIdentityVerified(true);
        $user->calculateTrustScore();
        $this->entityManager->flush();

        $this->addFlash('success', 'Identité vérifiée avec succès.');

        return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/verify-financial', name: 'admin_users_verify_financial', methods: ['POST'])]
    public function verifyFinancial(User $user): Response
    {
        $user->setFinancialVerified(true);
        $user->calculateTrustScore();
        $this->entityManager->flush();

        $this->addFlash('success', 'Documents financiers vérifiés avec succès.');

        return $this->redirectToRoute('admin_users_show', ['id' => $user->getId()]);
    }
}
