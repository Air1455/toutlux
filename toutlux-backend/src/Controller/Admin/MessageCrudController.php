<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\Message\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/messages')]
#[IsGranted('ROLE_ADMIN')]
class MessageCrudController extends AbstractController
{
    public function __construct(
        private MessageRepository $messageRepository,
        private MessageService $messageService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_messages_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;

        $criteria = [];
        $status = $request->query->get('status');
        if ($status) {
            $criteria['status'] = $status;
        }

        $messages = $this->messageRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );

        $totalMessages = $this->messageRepository->count($criteria);

        $stats = $this->messageService->getMessagingStats();

        return $this->render('admin/message/index.html.twig', [
            'messages' => $messages,
            'totalMessages' => $totalMessages,
            'page' => $page,
            'pages' => ceil($totalMessages / $limit),
            'stats' => $stats,
            'statusFilter' => $status
        ]);
    }

    #[Route('/pending', name: 'admin_messages_pending')]
    public function pending(): Response
    {
        $pendingMessages = $this->messageService->getPendingModerationMessages();

        return $this->render('admin/message/pending.html.twig', [
            'messages' => $pendingMessages,
            'count' => count($pendingMessages)
        ]);
    }

    #[Route('/{id}', name: 'admin_messages_show')]
    public function show(Message $message): Response
    {
        return $this->render('admin/message/show.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_messages_approve', methods: ['POST'])]
    public function approve(
        Message $message,
        Request $request,
        #[CurrentUser] User $admin
    ): Response {
        if ($message->getStatus() !== \App\Enum\MessageStatus::PENDING) {
            $this->addFlash('warning', 'Ce message a déjà été traité.');
            return $this->redirectToRoute('admin_messages_pending');
        }

        $data = json_decode($request->getContent(), true);
        $editedContent = $data['content'] ?? null;

        try {
            $this->messageService->approveMessage($message, $admin, $editedContent);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Message approuvé avec succès'
                ]);
            }

            $this->addFlash('success', 'Message approuvé et envoyé.');
            return $this->redirectToRoute('admin_messages_pending');

        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'error' => 'Erreur lors de l\'approbation : ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->addFlash('error', 'Erreur lors de l\'approbation : ' . $e->getMessage());
            return $this->redirectToRoute('admin_messages_show', ['id' => $message->getId()]);
        }
    }

    #[Route('/{id}/reject', name: 'admin_messages_reject', methods: ['POST'])]
    public function reject(
        Message $message,
        Request $request,
        #[CurrentUser] User $admin
    ): Response {
        if ($message->getStatus() !== \App\Enum\MessageStatus::PENDING) {
            return $this->json([
                'error' => 'Ce message a déjà été traité.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Message inapproprié';

        try {
            $this->messageService->rejectMessage($message, $admin, $reason);

            return $this->json([
                'success' => true,
                'message' => 'Message rejeté avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors du rejet : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/delete', name: 'admin_messages_delete', methods: ['POST'])]
    public function delete(Request $request, Message $message): Response
    {
        if ($this->isCsrfTokenValid('delete'.$message->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($message);
            $this->entityManager->flush();

            $this->addFlash('success', 'Message supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_messages_index');
    }

    #[Route('/validate/bulk', name: 'admin_messages_validate_bulk', methods: ['POST'])]
    public function validateBulk(
        Request $request,
        #[CurrentUser] User $admin
    ): Response {
        $data = json_decode($request->getContent(), true);
        $messageIds = $data['messageIds'] ?? [];
        $action = $data['action'] ?? '';

        if (empty($messageIds) || !in_array($action, ['approve', 'reject'])) {
            return $this->json([
                'error' => 'Données invalides'
            ], Response::HTTP_BAD_REQUEST);
        }

        $processed = 0;
        $errors = [];

        foreach ($messageIds as $messageId) {
            $message = $this->messageRepository->find($messageId);
            if (!$message || $message->getStatus() !== \App\Enum\MessageStatus::PENDING) {
                continue;
            }

            try {
                if ($action === 'approve') {
                    $this->messageService->approveMessage($message, $admin);
                } else {
                    $this->messageService->rejectMessage($message, $admin, 'Rejet en masse');
                }
                $processed++;
            } catch (\Exception $e) {
                $errors[] = [
                    'messageId' => $messageId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $this->json([
            'success' => true,
            'processed' => $processed,
            'errors' => $errors
        ]);
    }
}
