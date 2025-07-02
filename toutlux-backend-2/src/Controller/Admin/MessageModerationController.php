<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Service\Message\MessageModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/messages', name: 'admin_message_')]
#[IsGranted('ROLE_ADMIN')]
class MessageModerationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageModerationService $moderationService
    ) {}

    #[Route('/moderation', name: 'moderation')]
    public function index(Request $request): Response
    {
        $filter = $request->query->get('filter', 'pending');
        $page = $request->query->getInt('page', 1);
        $limit = 20;

        $repository = $this->entityManager->getRepository(Message::class);

        $queryBuilder = $repository->createQueryBuilder('m')
            ->join('m.sender', 's')
            ->join('m.recipient', 'r')
            ->leftJoin('m.property', 'p')
            ->orderBy('m.createdAt', 'DESC');

        switch ($filter) {
            case 'pending':
                $queryBuilder->where('m.status = :status')
                    ->setParameter('status', Message::STATUS_PENDING);
                break;
            case 'approved':
                $queryBuilder->where('m.status = :status')
                    ->setParameter('status', Message::STATUS_APPROVED);
                break;
            case 'rejected':
                $queryBuilder->where('m.status = :status')
                    ->setParameter('status', Message::STATUS_REJECTED);
                break;
            case 'all':
                // No filter
                break;
        }

        $paginator = $this->paginate($queryBuilder->getQuery(), $page, $limit);
        $messages = $paginator['items'];
        $totalPages = $paginator['totalPages'];

        return $this->render('admin/message/moderation.html.twig', [
            'messages' => $messages,
            'filter' => $filter,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'pendingCount' => $this->moderationService->getPendingMessagesCount()
        ]);
    }

    #[Route('/{id}/moderate', name: 'moderate', methods: ['GET', 'POST'])]
    public function moderate(Request $request, Message $message): Response
    {
        if ($message->getStatus() !== Message::STATUS_PENDING) {
            $this->addFlash('info', 'This message has already been moderated');
            return $this->redirectToRoute('admin_message_moderation');
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $moderatedContent = $request->request->get('moderated_content');

            if ($action === 'approve') {
                $this->moderationService->moderateMessage(
                    $message,
                    $this->getUser(),
                    true,
                    $moderatedContent ?: null
                );

                $this->addFlash('success', 'Message approved and sent to recipient');
            } elseif ($action === 'reject') {
                $this->moderationService->moderateMessage(
                    $message,
                    $this->getUser(),
                    false
                );

                $this->addFlash('warning', 'Message rejected');
            }

            return $this->redirectToRoute('admin_message_moderation');
        }

        // Get sanitized content suggestion
        $sanitizedContent = $this->moderationService->sanitizeContent($message->getContent());

        return $this->render('admin/message/moderate.html.twig', [
            'message' => $message,
            'sanitizedContent' => $sanitizedContent,
            'conversation' => $this->getConversationHistory($message)
        ]);
    }

    #[Route('/{id}/quick-moderate', name: 'quick_moderate', methods: ['POST'])]
    public function quickModerate(Request $request, Message $message): JsonResponse
    {
        if ($message->getStatus() !== Message::STATUS_PENDING) {
            return $this->json([
                'success' => false,
                'message' => 'Message already moderated'
            ], 400);
        }

        $action = $request->request->get('action');

        try {
            if ($action === 'approve') {
                $this->moderationService->moderateMessage(
                    $message,
                    $this->getUser(),
                    true
                );
                $status = 'approved';
            } elseif ($action === 'reject') {
                $this->moderationService->moderateMessage(
                    $message,
                    $this->getUser(),
                    false
                );
                $status = 'rejected';
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 400);
            }

            return $this->json([
                'success' => true,
                'status' => $status,
                'message' => sprintf('Message %s successfully', $status)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error moderating message: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/batch-moderate', name: 'batch_moderate', methods: ['POST'])]
    public function batchModerate(Request $request): Response
    {
        $action = $request->request->get('action');
        $messageIds = $request->request->all('messages');

        if (empty($messageIds)) {
            $this->addFlash('error', 'No messages selected');
            return $this->redirectToRoute('admin_message_moderation');
        }

        $messages = $this->entityManager->getRepository(Message::class)
            ->findBy(['id' => $messageIds, 'status' => Message::STATUS_PENDING]);

        $count = 0;
        foreach ($messages as $message) {
            if ($action === 'approve') {
                $this->moderationService->moderateMessage(
                    $message,
                    $this->getUser(),
                    true
                );
            } elseif ($action === 'reject') {
                $this->moderationService->moderateMessage(
                    $message,
                    $this->getUser(),
                    false
                );
            }
            $count++;
        }

        $this->addFlash('success', sprintf('%d messages %s', $count, $action === 'approve' ? 'approved' : 'rejected'));

        return $this->redirectToRoute('admin_message_moderation');
    }

    #[Route('/auto-moderate-settings', name: 'auto_moderate_settings', methods: ['GET', 'POST'])]
    public function autoModerateSettings(Request $request): Response
    {
        // Handle auto-moderation settings
        if ($request->isMethod('POST')) {
            // Save settings
            $this->addFlash('success', 'Auto-moderation settings updated');
            return $this->redirectToRoute('admin_message_auto_moderate_settings');
        }

        return $this->render('admin/message/auto_moderate_settings.html.twig', [
            // Pass current settings
        ]);
    }

    private function getConversationHistory(Message $message): array
    {
        $conversation = [];

        // Get parent messages
        $current = $message;
        while ($current->getParent()) {
            $current = $current->getParent();
            array_unshift($conversation, $current);
        }

        // Add current message
        $conversation[] = $message;

        // Get replies
        foreach ($message->getReplies() as $reply) {
            $conversation[] = $reply;
        }

        return $conversation;
    }

    private function paginate($query, int $page, int $limit): array
    {
        $totalItems = count($query->getResult());
        $totalPages = ceil($totalItems / $limit);

        $query->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => $query->getResult(),
            'totalPages' => $totalPages,
            'totalItems' => $totalItems
        ];
    }
}
