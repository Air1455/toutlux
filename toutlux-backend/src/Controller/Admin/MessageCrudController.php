<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Repository\PropertyRepository;
use App\Service\Message\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\Message\MessageValidationService;
use Psr\Log\LoggerInterface;

#[Route('/messages')]
#[IsGranted('ROLE_ADMIN')]
class MessageCrudController extends AbstractController
{
    public function __construct(
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private PropertyRepository $propertyRepository,
        private MessageService $messageService,
        private MessageValidationService $validationService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[Route('', name: 'admin_message_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;

        // Récupérer tous les filtres avec valeurs par défaut
        $filters = [
            'search' => $request->query->get('search', ''),
            'status' => $request->query->get('status', ''),
            'type' => $request->query->get('type', ''),
            'sender' => $request->query->get('sender', ''),
            'recipient' => $request->query->get('recipient', ''),
            'property' => $request->query->get('property', ''),
            'date_from' => $request->query->get('date_from', ''),
            'date_to' => $request->query->get('date_to', ''),
            'is_read' => $request->query->get('is_read', ''),
        ];

        // Nettoyer les filtres vides pour la requête
        $activeFilters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        try {
            // Utiliser la méthode de recherche avec filtres avancés
            if (!empty($activeFilters)) {
                $result = $this->messageRepository->findWithAdvancedFilters($activeFilters, $page, $limit);
            } else {
                // Requête simple si aucun filtre
                $messages = $this->messageRepository->findBy(
                    [],
                    ['createdAt' => 'DESC'],
                    $limit,
                    ($page - 1) * $limit
                );
                $totalMessages = $this->messageRepository->count([]);

                $result = [
                    'messages' => $messages,
                    'total' => $totalMessages,
                    'totalPages' => (int) ceil($totalMessages / $limit),
                ];
            }

            $messages = $result['messages'];
            $totalMessages = $result['total'];
            $totalPages = $result['totalPages'];

            // Get unique senders for the filter dropdown
            $senders = $this->userRepository->createQueryBuilder('u')
                ->innerJoin('u.sentMessages', 'sm')
                ->orderBy('u.firstName', 'ASC')
                ->addOrderBy('u.lastName', 'ASC')
                ->getQuery()
                ->getResult();

            // Get unique recipients for the filter dropdown
            $recipients = $this->userRepository->createQueryBuilder('u')
                ->innerJoin('u.receivedMessages', 'rm')
                ->orderBy('u.firstName', 'ASC')
                ->addOrderBy('u.lastName', 'ASC')
                ->getQuery()
                ->getResult();

            // Get properties for the filter dropdown
            $properties = $this->propertyRepository->createQueryBuilder('p')
                ->innerJoin('p.messages', 'pm')
                ->orderBy('p.title', 'ASC')
                ->getQuery()
                ->getResult();

            $stats = $this->messageService->getMessagingStats();

            return $this->render('admin/message/index.html.twig', [
                'messages' => $messages,
                'senders' => $senders,
                'recipients' => $recipients,
                'properties' => $properties,
                'totalMessages' => $totalMessages,
                'page' => $page,
                'totalPages' => $totalPages,
                'stats' => $stats,
                'filters' => $filters, // Toujours passer tous les filtres avec valeurs par défaut
                'hasActiveFilters' => !empty($activeFilters),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du chargement des messages: ' . $e->getMessage(), [
                'filters' => $filters,
                'page' => $page,
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Une erreur est survenue lors du chargement des messages.');

            // Retourner une page vide en cas d'erreur
            return $this->render('admin/message/index.html.twig', [
                'messages' => [],
                'senders' => [],
                'recipients' => [],
                'properties' => [],
                'totalMessages' => 0,
                'page' => 1,
                'totalPages' => 0,
                'stats' => ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0],
                'filters' => $filters,
                'hasActiveFilters' => false,
            ]);
        }
    }

    #[Route('/pending', name: 'admin_message_pending')]
    public function pending(): Response
    {
        $messages = $this->messageService->getPendingModerationMessages();

        // Analyser le contenu de chaque message
        $contentAnalysis = [];
        foreach ($messages as $message) {
            $contentAnalysis[$message->getId()] = $this->analyzeMessageContent($message->getContent());
        }

        return $this->render('admin/message/pending.html.twig', [
            'messages' => $messages,
            'count' => count($messages),
            'contentAnalysis' => $contentAnalysis
        ]);
    }

    #[Route('/{id}', name: 'admin_message_show')]
    public function show(Message $message): Response
    {
        // Analyse du contenu du message
        $contentAnalysis = $this->analyzeMessageContent($message->getContent());

        // Récupérer l'historique de conversation si nécessaire
        $conversationHistory = [];
        if ($message->getRecipient() && $message->getSender()) {
            $conversationHistory = $this->messageRepository->findConversationBetween(
                $message->getSender(),
                $message->getRecipient(),
                $message->getProperty()
            );
        }

        return $this->render('admin/message/show.html.twig', [
            'message' => $message,
            'conversationHistory' => $conversationHistory,
            'contentAnalysis' => $contentAnalysis
        ]);
    }

    /**
     * Analyse le contenu d'un message pour détecter différents éléments
     */
    private function analyzeMessageContent(string $content): array
    {
        // Regex améliorées pour une meilleure détection
        $phonePattern = '/(?:\+33|0)[1-9](?:[.\-\s]?\d{2}){4}/'; // Numéros français
        $emailPattern = '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/';
        $urlPattern = '/https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&=]*)/';

        return [
            'hasPhone' => preg_match($phonePattern, $content) === 1,
            'hasEmail' => preg_match($emailPattern, $content) === 1,
            'hasUrl' => preg_match($urlPattern, $content) === 1,
            'wordCount' => str_word_count($content),
            'charCount' => mb_strlen($content), // Utilise mb_strlen pour les caractères UTF-8
            'hasContactInfo' => preg_match($phonePattern, $content) === 1 ||
                preg_match($emailPattern, $content) === 1 ||
                preg_match($urlPattern, $content) === 1
        ];
    }

    #[Route('/{id}/approve', name: 'admin_message_approve', methods: ['POST'])]
    public function approve(
        Message $message,
        Request $request,
        #[CurrentUser] User $admin
    ): Response {
        if ($message->getStatus() !== \App\Enum\MessageStatus::PENDING) {
            $this->addFlash('warning', 'Ce message a déjà été traité.');
            return $this->redirectToRoute('admin_message_pending');
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
            return $this->redirectToRoute('admin_message_pending');

        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'error' => 'Erreur lors de l\'approbation : ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->addFlash('error', 'Erreur lors de l\'approbation : ' . $e->getMessage());
            return $this->redirectToRoute('admin_message_show', ['id' => $message->getId()]);
        }
    }

    #[Route('/{id}/reject', name: 'admin_message_reject', methods: ['POST'])]
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

    #[Route('/{id}/delete', name: 'admin_message_delete', methods: ['POST'])]
    public function delete(Request $request, Message $message): Response
    {
        if ($this->isCsrfTokenValid('delete_message', $request->request->get('_token'))) {
            $this->entityManager->remove($message);
            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }

            $this->addFlash('success', 'Message supprimé avec succès.');
        } else {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Token CSRF invalide'], Response::HTTP_FORBIDDEN);
            }

            $this->addFlash('error', 'Token CSRF invalide');
        }

        return $this->redirectToRoute('admin_message_index');
    }

    #[Route('/{id}/mark-read', name: 'admin_message_mark_read', methods: ['POST'])]
    public function markAsRead(Message $message, Request $request): Response
    {
        try {
            if (!$message->isRead()) {
                $message->setIsRead(true);
                $message->setReadAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                $this->logger->info('Message marked as read', [
                    'message_id' => $message->getId(),
                    'marked_by_admin' => $this->getUser()->getId()
                ]);
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Message marqué comme lu'
                ]);
            }

            $this->addFlash('success', 'Message marqué comme lu.');
            return $this->redirectToRoute('admin_message_index');

        } catch (\Exception $e) {
            $this->logger->error('Error marking message as read: ' . $e->getMessage(), [
                'message_id' => $message->getId()
            ]);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Erreur lors du marquage'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->addFlash('error', 'Erreur lors du marquage du message.');
            return $this->redirectToRoute('admin_message_index');
        }
    }

    #[Route('/bulk-mark-read', name: 'admin_message_bulk_mark_read', methods: ['POST'])]
    public function bulkMarkAsRead(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $messageIds = $data['messageIds'] ?? [];

            if (empty($messageIds)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Aucun message sélectionné'
                ], Response::HTTP_BAD_REQUEST);
            }

            $count = $this->messageRepository->createQueryBuilder('m')
                ->update()
                ->set('m.isRead', 'true')
                ->set('m.readAt', ':now')
                ->where('m.id IN (:ids)')
                ->andWhere('m.isRead = false')
                ->setParameter('ids', $messageIds)
                ->setParameter('now', new \DateTimeImmutable())
                ->getQuery()
                ->execute();

            $this->logger->info('Bulk mark as read completed', [
                'message_ids' => $messageIds,
                'count' => $count,
                'marked_by_admin' => $this->getUser()->getId()
            ]);

            return $this->json([
                'success' => true,
                'count' => $count,
                'message' => $count . ' message(s) marqué(s) comme lu(s)'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error in bulk mark as read: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors du marquage'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/mark-all-read', name: 'admin_message_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $filters = $data['filters'] ?? [];

            // Construire la même requête que pour l'affichage avec les filtres
            $qb = $this->messageRepository->createQueryBuilder('m')
                ->update()
                ->set('m.isRead', 'true')
                ->set('m.readAt', ':now')
                ->where('m.isRead = false')
                ->setParameter('now', new \DateTimeImmutable());

            // Appliquer les mêmes filtres que pour l'affichage
            if (!empty($filters['status'])) {
                $qb->andWhere('m.status = :status')
                    ->setParameter('status', $filters['status']);
            }

            if (!empty($filters['type'])) {
                if ($filters['type'] === 'property') {
                    $qb->andWhere('m.property IS NOT NULL');
                } elseif ($filters['type'] === 'direct') {
                    $qb->andWhere('m.property IS NULL');
                }
            }

            if (!empty($filters['sender'])) {
                $qb->andWhere('m.sender = :sender')
                    ->setParameter('sender', $filters['sender']);
            }

            if (!empty($filters['recipient'])) {
                $qb->andWhere('m.recipient = :recipient')
                    ->setParameter('recipient', $filters['recipient']);
            }

            if (!empty($filters['property'])) {
                $qb->andWhere('m.property = :property')
                    ->setParameter('property', $filters['property']);
            }

            $count = $qb->getQuery()->execute();

            $this->logger->info('Mark all as read completed', [
                'filters' => $filters,
                'count' => $count,
                'marked_by_admin' => $this->getUser()->getId()
            ]);

            return $this->json([
                'success' => true,
                'count' => $count,
                'message' => $count . ' message(s) marqué(s) comme lu(s)'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error in mark all as read: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors du marquage'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function validateBulk(
        Request $request,
        #[CurrentUser] User $admin
    ): Response {
        if (!$this->isCsrfTokenValid('bulk_action', $request->request->get('_token'))) {
            return $this->json([
                'error' => 'Token CSRF invalide'
            ], Response::HTTP_FORBIDDEN);
        }

        $messageIds = $request->request->get('messageIds', []);
        $action = $request->request->get('action', '');

        if (empty($messageIds) || !in_array($action, ['approve', 'reject', 'delete'])) {
            return $this->json([
                'error' => 'Données invalides'
            ], Response::HTTP_BAD_REQUEST);
        }

        $processed = 0;
        $errors = [];

        foreach ($messageIds as $messageId) {
            $message = $this->messageRepository->find($messageId);
            if (!$message) {
                continue;
            }

            try {
                switch ($action) {
                    case 'approve':
                        if ($message->getStatus() === \App\Enum\MessageStatus::PENDING) {
                            $this->messageService->approveMessage($message, $admin);
                            $processed++;
                        }
                        break;

                    case 'reject':
                        if ($message->getStatus() === \App\Enum\MessageStatus::PENDING) {
                            $this->messageService->rejectMessage($message, $admin, 'Rejet en masse');
                            $processed++;
                        }
                        break;

                    case 'delete':
                        $this->entityManager->remove($message);
                        $processed++;
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'messageId' => $messageId,
                    'error' => $e->getMessage()
                ];
            }
        }

        if ($action === 'delete') {
            $this->entityManager->flush();
        }

        return $this->json([
            'success' => true,
            'processed' => $processed,
            'errors' => $errors
        ]);
    }
}
