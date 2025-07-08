<?php

namespace App\Controller\Api;

use App\DTO\Message\CreateMessageRequest;
use App\Entity\Message;
use App\Entity\Property;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\PropertyRepository;
use App\Repository\UserRepository;
use App\Service\Message\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messages')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    public function __construct(
        private MessageService $messageService,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private PropertyRepository $propertyRepository
    ) {}

    #[Route('', name: 'api_messages_list', methods: ['GET'])]
    public function list(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        $offset = ($page - 1) * $limit;

        $conversations = $this->messageService->getUserConversations($user, $limit, $offset);
        $unreadCount = $this->messageService->countUnreadMessages($user);

        return $this->json([
            'conversations' => $conversations,
            'unreadCount' => $unreadCount,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    #[Route('/conversation/{userId}', name: 'api_messages_conversation', methods: ['GET'])]
    public function conversation(
        int $userId,
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $otherUser = $this->userRepository->find($userId);
        if (!$otherUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $propertyId = $request->query->get('property');
        $property = null;

        if ($propertyId) {
            $property = $this->propertyRepository->find($propertyId);
        }

        $messages = $this->messageService->getConversation($user, $otherUser, $property);

        return $this->json([
            'messages' => $messages,
            'otherUser' => [
                'id' => $otherUser->getId(),
                'firstName' => $otherUser->getFirstName(),
                'lastName' => $otherUser->getLastName(),
                'avatar' => $otherUser->getAvatar(),
                'trustScore' => $otherUser->getTrustScore()
            ]
        ]);
    }

    #[Route('/send', name: 'api_messages_send', methods: ['POST'])]
    public function send(
        #[MapRequestPayload] CreateMessageRequest $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        // Vérifier le destinataire
        $recipient = $this->userRepository->find($request->recipientId);
        if (!$recipient) {
            return $this->json(['error' => 'Destinataire non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier la propriété si fournie
        $property = null;
        if ($request->propertyId) {
            $property = $this->propertyRepository->find($request->propertyId);
            if (!$property) {
                return $this->json(['error' => 'Propriété non trouvée'], Response::HTTP_NOT_FOUND);
            }
        }

        // Vérifier le message parent si fourni
        $parentMessage = null;
        if ($request->parentMessageId) {
            $parentMessage = $this->messageRepository->find($request->parentMessageId);
            if (!$parentMessage) {
                return $this->json(['error' => 'Message parent non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier que l'utilisateur fait partie de la conversation
            if ($parentMessage->getSender()->getId() !== $user->getId() &&
                $parentMessage->getRecipient()->getId() !== $user->getId()) {
                throw new AccessDeniedException('Vous ne pouvez pas répondre à ce message');
            }
        }

        // Vérifier si l'utilisateur peut envoyer le message
        $canSend = $this->messageService->canSendMessage($user, $recipient);
        if (!$canSend['can_send']) {
            return $this->json([
                'error' => 'Impossible d\'envoyer le message',
                'reasons' => $canSend['errors']
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $message = $this->messageService->createMessage(
                $user,
                $recipient,
                $request->content,
                $property,
                $parentMessage
            );

            if ($request->subject) {
                $message->setSubject($request->subject);
            }

            return $this->json([
                'message' => 'Message envoyé avec succès',
                'messageData' => [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'subject' => $message->getSubject(),
                    'createdAt' => $message->getCreatedAt()->format('c'),
                    'needsModeration' => $message->getNeedsModeration()
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de l\'envoi du message',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/read', name: 'api_messages_mark_read', methods: ['POST'])]
    public function markAsRead(
        Message $message,
        #[CurrentUser] User $user
    ): JsonResponse {
        try {
            $this->messageService->markAsRead($message, $user);
            return $this->json(['message' => 'Message marqué comme lu']);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/mark-all-read', name: 'api_messages_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $messageIds = $data['messageIds'] ?? [];

        if (empty($messageIds)) {
            return $this->json(['error' => 'Aucun message spécifié'], Response::HTTP_BAD_REQUEST);
        }

        $count = $this->messageService->markMultipleAsRead($messageIds, $user);

        return $this->json([
            'message' => sprintf('%d messages marqués comme lus', $count),
            'count' => $count
        ]);
    }

    #[Route('/{id}', name: 'api_messages_delete', methods: ['DELETE'])]
    public function delete(
        Message $message,
        #[CurrentUser] User $user
    ): JsonResponse {
        try {
            $this->messageService->deleteMessage($message, $user);
            return $this->json(['message' => 'Message supprimé']);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/search', name: 'api_messages_search', methods: ['GET'])]
    public function search(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $query = $request->query->get('q', '');
        $filters = [
            'isRead' => $request->query->get('isRead'),
            'property' => $request->query->get('property'),
            'dateFrom' => $request->query->get('dateFrom'),
            'dateTo' => $request->query->get('dateTo')
        ];

        // Nettoyer les filtres null
        $filters = array_filter($filters, fn($value) => $value !== null);

        $messages = $this->messageService->searchMessages($user, $query, $filters);

        return $this->json([
            'messages' => $messages,
            'query' => $query,
            'filters' => $filters,
            'count' => count($messages)
        ]);
    }

    #[Route('/stats', name: 'api_messages_stats', methods: ['GET'])]
    public function stats(#[CurrentUser] User $user): JsonResponse
    {
        $stats = $this->messageService->getMessagingStats($user);

        return $this->json(['stats' => $stats]);
    }

    #[Route('/archive/{userId}', name: 'api_messages_archive_conversation', methods: ['POST'])]
    public function archiveConversation(
        int $userId,
        #[CurrentUser] User $user
    ): JsonResponse {
        $otherUser = $this->userRepository->find($userId);
        if (!$otherUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->messageService->archiveConversation($user, $otherUser);

        return $this->json(['message' => 'Conversation archivée']);
    }

    #[Route('/unread', name: 'api_messages_unread', methods: ['GET'])]
    public function unread(#[CurrentUser] User $user): JsonResponse
    {
        $unreadMessages = $this->messageRepository->findUnreadByUser($user);

        return $this->json([
            'messages' => $unreadMessages,
            'count' => count($unreadMessages)
        ]);
    }
}
