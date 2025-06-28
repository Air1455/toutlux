<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Service\Messaging\EmailService;
use App\Service\Messaging\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/messages')]
#[IsGranted('ROLE_ADMIN')]
class AdminMessageController extends AbstractController
{
    public function __construct(
        private MessageService $messageService,
        private EmailService $emailService
    ) {}

    #[Route('', name: 'admin_messages')]
    public function index(): Response
    {
        $messages = $this->messageService->getAdminMessages();
        $unreadMessages = $this->messageService->getUnreadAdminMessages();

        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages,
            'unread_count' => count($unreadMessages)
        ]);
    }

    #[Route('/{id}', name: 'admin_message_show')]
    public function show(Message $message): Response
    {
        // Marquer comme lu
        if (!$message->isRead()) {
            $this->messageService->markAsRead($message);
        }

        return $this->render('admin/messages/show.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/{id}/reply', name: 'admin_message_reply', methods: ['POST'])]
    public function reply(Message $message, Request $request): Response
    {
        $replyContent = $request->request->get('reply_content');

        if (empty($replyContent)) {
            $this->addFlash('error', 'Le contenu de la réponse est obligatoire.');
            return $this->redirectToRoute('admin_message_show', ['id' => $message->getId()]);
        }

        // Créer un nouveau message de réponse
        $replyMessage = $this->messageService->createMessage(
            $message->getUser(),
            'Re: ' . $message->getSubject(),
            $replyContent,
            \App\Enum\MessageType::ADMIN_TO_USER
        );

        // Envoyer l'email
        $this->emailService->sendAdminReply($message, $replyContent);

        $this->addFlash('success', 'Votre réponse a été envoyée.');

        return $this->redirectToRoute('admin_message_show', ['id' => $message->getId()]);
    }
}
