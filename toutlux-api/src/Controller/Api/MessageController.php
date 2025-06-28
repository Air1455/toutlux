<?php

namespace App\Controller\Api;

use App\Entity\Message;
use App\Service\Messaging\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/messages')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    public function __construct(
        private MessageService $messageService
    ) {}

    #[Route('', methods: ['GET'])]
    public function getUserMessages(): JsonResponse
    {
        $messages = $this->messageService->getUserMessages($this->getUser());

        return $this->json($messages, 200, [], ['groups' => ['message:read']]);
    }

    #[Route('/unread-count', methods: ['GET'])]
    public function getUnreadCount(): JsonResponse
    {
        $count = $this->messageService->getUnreadCount($this->getUser());

        return $this->json(['count' => $count]);
    }

    #[Route('/{id}/read', methods: ['PUT'])]
    public function markAsRead(Message $message): JsonResponse
    {
        if ($message->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $this->messageService->markAsRead($message);

        return $this->json(['status' => 'success']);
    }

    #[Route('', methods: ['POST'])]
    public function createMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['subject']) || !isset($data['content'])) {
            return $this->json(['error' => 'Subject and content are required'], 400);
        }

        $message = $this->messageService->createMessage(
            $this->getUser(),
            $data['subject'],
            $data['content']
        );

        return $this->json($message, 201, [], ['groups' => ['message:read']]);
    }
}
