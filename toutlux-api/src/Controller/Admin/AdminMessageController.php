<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\Messaging\EmailService;
use App\Service\Messaging\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
        private EmailService $emailService,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private PaginatorInterface $paginator
    ) {}

    #[Route('', name: 'admin_messages_index')]
    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->query->get('status', 'all'),
            'type' => $request->query->get('type', 'all'),
            'user' => $request->query->get('user'),
            'search' => $request->query->get('search'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
        ];

        $queryBuilder = $this->messageRepository->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u');

        // Application des filtres
        if ($filters['status'] !== 'all') {
            if ($filters['status'] === 'unread') {
                $queryBuilder->andWhere('m.isRead = false');
            } elseif ($filters['status'] === 'read') {
                $queryBuilder->andWhere('m.isRead = true');
            } elseif ($filters['status'] === 'archived') {
                $queryBuilder->andWhere('m.status = :status')
                    ->setParameter('status', 'archived');
            }
        }

        if ($filters['type'] !== 'all') {
            $queryBuilder->andWhere('m.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if ($filters['user']) {
            $queryBuilder->andWhere('u.id = :userId')
                ->setParameter('userId', $filters['user']);
        }

        if ($filters['search']) {
            $queryBuilder->andWhere('m.subject LIKE :search OR m.content LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if ($filters['date_from']) {
            $queryBuilder->andWhere('m.createdAt >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if ($filters['date_to']) {
            $queryBuilder->andWhere('m.createdAt <= :dateTo')
                ->setParameter('dateTo', new \DateTime($filters['date_to'] . ' 23:59:59'));
        }

        $queryBuilder->orderBy('m.createdAt', 'DESC');

        // Pagination
        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        // Stats
        $stats = [
            'total' => $this->messageRepository->count([]),
            'unread' => count($this->messageRepository->findUnreadForAdmin()),
            'today' => $this->getMessagesTodayCount(),
            'response_rate' => $this->calculateResponseRate(),
        ];

        return $this->render('admin/messages/index.html.twig', [
            'pagination' => $pagination,
            'filters' => $filters,
            'stats' => $stats,
            'unread_count' => $stats['unread'],
        ]);
    }

    #[Route('/{id}', name: 'admin_messages_show', requirements: ['id' => '\d+'])]
    public function show(Message $message): Response
    {
        // Marquer comme lu
        if (!$message->isRead()) {
            $this->messageService->markAsRead($message);
        }

        // Historique de conversation avec cet utilisateur
        $conversationHistory = $this->messageRepository->findBy(
            ['user' => $message->getUser()],
            ['createdAt' => 'DESC'],
            10
        );

        // Messages similaires (même sujet ou contenu similaire)
        $similarMessages = $this->findSimilarMessages($message);

        // Réponses prédéfinies
        $predefinedResponses = $this->getPredefinedResponses();

        return $this->render('admin/messages/show.html.twig', [
            'message' => $message,
            'conversation_history' => $conversationHistory,
            'similar_messages' => $similarMessages,
            'predefined_responses' => $predefinedResponses,
        ]);
    }

    #[Route('/{id}/reply', name: 'admin_messages_reply', methods: ['POST'])]
    public function reply(Message $message, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reply-' . $message->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $replyContent = $request->request->get('content');
        $subject = $request->request->get('subject', 'Re: ' . $message->getSubject());
        $sendEmail = $request->request->get('send_email', true);

        if (empty($replyContent)) {
            $this->addFlash('error', 'Le contenu de la réponse est obligatoire.');
            return $this->redirectToRoute('admin_messages_show', ['id' => $message->getId()]);
        }

        // Créer un nouveau message de réponse
        $replyMessage = $this->messageService->createMessage(
            $message->getUser(),
            $subject,
            $replyContent,
            \App\Enum\MessageType::ADMIN_TO_USER
        );

        // Marquer le message original comme traité
        $message->setStatus('archived');
        $this->em->flush();

        // Envoyer l'email si demandé
        if ($sendEmail) {
            $this->emailService->sendAdminReply($message, $replyContent);
        }

        $this->addFlash('success', 'Votre réponse a été envoyée.');

        return $this->redirectToRoute('admin_messages_show', ['id' => $message->getId()]);
    }

    #[Route('/{id}/mark-processed', name: 'admin_messages_mark_processed', methods: ['POST'])]
    public function markProcessed(Message $message, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('mark-processed-' . $message->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $message->setStatus('archived');

        // Ajouter dans metadata
        $metadata = $message->getMetadata() ?? [];
        $metadata['processed'] = true;
        $metadata['processed_at'] = (new \DateTime())->format('Y-m-d H:i:s');
        $metadata['processed_by'] = $this->getUser()->getEmail();
        $message->setMetadata($metadata);

        $this->em->flush();

        $this->addFlash('success', 'Message marqué comme traité');
        return $this->redirectToRoute('admin_messages_index');
    }

    #[Route('/{id}/delete', name: 'admin_messages_delete', methods: ['POST'])]
    public function delete(Message $message, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-' . $message->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $this->em->remove($message);
        $this->em->flush();

        $this->addFlash('success', 'Message supprimé');
        return $this->redirectToRoute('admin_messages_index');
    }

    #[Route('/send-to-user/{id}', name: 'admin_messages_send_to_user')]
    public function sendToUser(User $user, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('send-message', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token');
            }

            $subject = $request->request->get('subject');
            $content = $request->request->get('content');
            $sendEmail = $request->request->get('send_email', true);

            if (empty($subject) || empty($content)) {
                $this->addFlash('error', 'Le sujet et le contenu sont obligatoires');
                return $this->redirectToRoute('admin_messages_send_to_user', ['id' => $user->getId()]);
            }

            // Créer le message
            $message = $this->messageService->createMessage(
                $user,
                $subject,
                $content,
                \App\Enum\MessageType::ADMIN_TO_USER
            );

            // Envoyer email si demandé
            if ($sendEmail) {
                $this->emailService->sendAdminReply($message, $content);
            }

            $this->addFlash('success', 'Message envoyé à ' . $user->getDisplayName());
            return $this->redirectToRoute('admin_messages_show', ['id' => $message->getId()]);
        }

        // Historique des messages avec cet utilisateur
        $messageHistory = $this->messageRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('admin/messages/send_to_user.html.twig', [
            'user' => $user,
            'message_history' => $messageHistory,
            'predefined_responses' => $this->getPredefinedResponses(),
        ]);
    }

    #[Route('/bulk-action', name: 'admin_messages_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request): Response
    {
        $action = $request->request->get('action');
        $messageIds = $request->request->all('message_ids');

        if (empty($messageIds)) {
            $this->addFlash('error', 'Aucun message sélectionné');
            return $this->redirectToRoute('admin_messages_index');
        }

        $messages = $this->messageRepository->findBy(['id' => $messageIds]);

        switch ($action) {
            case 'mark_read':
                foreach ($messages as $message) {
                    $message->setIsRead(true);
                }
                $this->addFlash('success', sprintf('%d messages marqués comme lus', count($messages)));
                break;

            case 'archive':
                foreach ($messages as $message) {
                    $message->setStatus('archived');
                }
                $this->addFlash('success', sprintf('%d messages archivés', count($messages)));
                break;

            case 'delete':
                foreach ($messages as $message) {
                    $this->em->remove($message);
                }
                $this->addFlash('success', sprintf('%d messages supprimés', count($messages)));
                break;

            default:
                $this->addFlash('error', 'Action non reconnue');
        }

        $this->em->flush();

        return $this->redirectToRoute('admin_messages_index');
    }

    private function getMessagesTodayCount(): int
    {
        $today = new \DateTime('today');

        $qb = $this->messageRepository->createQueryBuilder('m');
        return $qb->select('COUNT(m.id)')
            ->where('m.createdAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function calculateResponseRate(): float
    {
        $totalUserMessages = $this->messageRepository->count(['type' => 'user_to_admin']);
        $repliedMessages = $this->messageRepository->count(['type' => 'admin_to_user']);

        if ($totalUserMessages === 0) {
            return 100.0;
        }

        return round(($repliedMessages / $totalUserMessages) * 100, 1);
    }

    private function findSimilarMessages(Message $message, int $limit = 3): array
    {
        // Recherche simple basée sur des mots-clés du sujet
        $keywords = explode(' ', strtolower($message->getSubject()));
        $keywords = array_filter($keywords, fn($word) => strlen($word) > 3);

        if (empty($keywords)) {
            return [];
        }

        $qb = $this->messageRepository->createQueryBuilder('m');
        $qb->where('m.id != :currentId')
            ->setParameter('currentId', $message->getId());

        $orConditions = [];
        foreach ($keywords as $index => $keyword) {
            $paramName = 'keyword' . $index;
            $orConditions[] = "LOWER(m.subject) LIKE :$paramName";
            $qb->setParameter($paramName, '%' . $keyword . '%');
        }

        if (!empty($orConditions)) {
            $qb->andWhere('(' . implode(' OR ', $orConditions) . ')');
        }

        return $qb->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function getPredefinedResponses(): array
    {
        return [
            'documents_manquants' => [
                'title' => 'Documents manquants',
                'content' => "Bonjour,\n\nNous avons bien reçu votre demande. Cependant, certains documents sont manquants pour finaliser votre dossier.\n\nMerci de nous fournir les éléments suivants :\n- [À compléter]\n\nCordialement,\nL'équipe ToutLux"
            ],
            'verification_reussie' => [
                'title' => 'Vérification réussie',
                'content' => "Bonjour,\n\nNous avons le plaisir de vous informer que votre compte a été vérifié avec succès.\n\nVous pouvez maintenant profiter de toutes les fonctionnalités de notre plateforme.\n\nCordialement,\nL'équipe ToutLux"
            ],
            'assistance_technique' => [
                'title' => 'Assistance technique',
                'content' => "Bonjour,\n\nNous avons bien reçu votre demande d'assistance.\n\n[Réponse personnalisée]\n\nSi vous avez d'autres questions, n'hésitez pas à nous contacter.\n\nCordialement,\nL'équipe ToutLux"
            ],
            'delai_traitement' => [
                'title' => 'Délai de traitement',
                'content' => "Bonjour,\n\nVotre demande est en cours de traitement. Le délai habituel est de 24 à 48 heures.\n\nNous vous tiendrons informé dès que possible.\n\nCordialement,\nL'équipe ToutLux"
            ],
        ];
    }
}
