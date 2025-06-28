<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\User\UserWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private UserWorkflowService $userWorkflowService
    ) {}

    #[Route('', name: 'admin_users')]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        $queryBuilder = $this->userRepository->createQueryBuilder('u');

        if ($status) {
            $queryBuilder->andWhere('u.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $users = $queryBuilder->orderBy('u.createdAt', 'DESC')->getQuery()->getResult();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'current_status' => $status,
            'current_search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show')]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            // Utilise tes méthodes existantes !
            'completion_percentage' => $user->getCompletionPercentage(),
            'verification_score' => $user->getVerificationScore(),
            'pending_validations' => $user->getPendingValidationsCount(),
            'documents_status' => $user->getDocumentsStatus(),
            'is_profile_complete' => $user->isProfileComplete(),
        ]);
    }

    #[Route('/{id}/approve-email', name: 'admin_user_approve_email', methods: ['POST'])]
    public function approveEmail(User $user): Response
    {
        // Utilise ta méthode existante setIsEmailVerified qui gère automatiquement la date
        $user->setIsEmailVerified(true);
        $user->setStatus('email_confirmed');
        $this->em->flush();

        // Déclenche le workflow pour l'email de notification
        $this->userWorkflowService->handleEmailConfirmation($user);

        $this->addFlash('success', 'Email validé et utilisateur notifié');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/approve-documents', name: 'admin_user_approve_documents', methods: ['POST'])]
    public function approveDocuments(User $user): Response
    {
        // Utilise tes méthodes existantes qui gèrent automatiquement les dates
        $user->setIsIdentityVerified(true);
        $user->setIsFinancialDocsVerified(true);
        $user->setStatus('documents_approved');
        $this->em->flush();

        // Déclenche le workflow pour l'email de notification
        $this->userWorkflowService->handleDocumentsApproval($user);

        $this->addFlash('success', 'Documents approuvés et utilisateur notifié');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/suspend', name: 'admin_user_suspend', methods: ['POST'])]
    public function suspend(User $user, Request $request): Response
    {
        $reason = $request->request->get('reason', 'Suspension administrative');

        $user->setStatus('suspended');
        $this->em->flush();

        // TODO: Créer un message système avec la raison

        $this->addFlash('warning', 'Utilisateur suspendu');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/activate', name: 'admin_user_activate', methods: ['POST'])]
    public function activate(User $user): Response
    {
        $user->setStatus('active');
        $this->em->flush();

        $this->addFlash('success', 'Utilisateur activé');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }
}
