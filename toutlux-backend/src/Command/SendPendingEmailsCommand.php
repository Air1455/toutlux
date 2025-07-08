<?php

namespace App\Command;

use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:send-pending-emails',
    description: 'Envoyer les emails en attente dans la file d\'attente',
)]
class SendPendingEmailsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private EmailService $emailService,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Nombre maximum d\'emails à envoyer', 100)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler sans envoyer')
            ->addOption('retry-failed', null, InputOption::VALUE_NONE, 'Réessayer les emails échoués')
            ->setHelp('Cette commande envoie les emails en attente dans la file d\'attente');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $limit = (int) $input->getOption('limit');
        $retryFailed = $input->getOption('retry-failed');

        if ($isDryRun) {
            $io->warning('Mode simulation activé - aucun email ne sera envoyé');
        }

        $io->info(sprintf('Recherche des emails en attente (limite: %d)...', $limit));

        // Récupérer les emails en attente depuis la base de données
        // Note: Cette partie dépend de votre implémentation spécifique
        // Si vous utilisez Symfony Messenger, les emails sont généralement dans la queue

        try {
            // Exemple d'implémentation basique
            $pendingEmails = $this->getPendingEmails($limit, $retryFailed);
            $count = count($pendingEmails);

            if ($count === 0) {
                $io->success('Aucun email en attente.');
                return Command::SUCCESS;
            }

            $io->progressStart($count);

            $sent = 0;
            $failed = 0;
            $errors = [];

            foreach ($pendingEmails as $email) {
                try {
                    if (!$isDryRun) {
                        $this->sendEmail($email);
                    }
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'email' => $email['to'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];

                    $io->error(sprintf(
                        'Erreur lors de l\'envoi à %s: %s',
                        $email['to'] ?? 'unknown',
                        $e->getMessage()
                    ));
                }

                $io->progressAdvance();
            }

            $io->progressFinish();

            // Afficher le résumé
            $io->section('Résumé');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Emails traités', $count],
                    ['Emails envoyés', $sent],
                    ['Emails échoués', $failed],
                    ['Mode', $isDryRun ? 'Simulation' : 'Production']
                ]
            );

            if ($failed > 0 && !empty($errors)) {
                $io->section('Erreurs');
                $io->table(['Email', 'Erreur'], array_map(function($e) {
                    return [$e['email'], substr($e['error'], 0, 80) . '...'];
                }, array_slice($errors, 0, 10)));

                if (count($errors) > 10) {
                    $io->writeln(sprintf('... et %d autres erreurs', count($errors) - 10));
                }
            }

            return $failed > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur fatale: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Récupérer les emails en attente
     * Note: Cette méthode est un exemple, adaptez selon votre implémentation
     */
    private function getPendingEmails(int $limit, bool $retryFailed): array
    {
        // Si vous utilisez une table pour stocker les emails en attente
        $qb = $this->entityManager->createQueryBuilder();

        // Exemple fictif - remplacez par votre logique
        /*
        $qb->select('e')
            ->from(EmailQueue::class, 'e')
            ->where('e.status = :status')
            ->setParameter('status', $retryFailed ? 'failed' : 'pending')
            ->orderBy('e.priority', 'DESC')
            ->addOrderBy('e.createdAt', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
        */

        // Pour l'instant, retourner un tableau vide
        return [];
    }

    /**
     * Envoyer un email
     */
    private function sendEmail(array $emailData): void
    {
        // Utiliser le service EmailService existant
        $this->emailService->sendEmail(
            $emailData['to'],
            $emailData['subject'],
            $emailData['template'],
            $emailData['context'] ?? [],
            $emailData['replyTo'] ?? null,
            $emailData['attachments'] ?? []
        );

        // Marquer comme envoyé dans la base de données si nécessaire
        if (isset($emailData['id'])) {
            // Mettre à jour le statut
            /*
            $email = $this->entityManager->find(EmailQueue::class, $emailData['id']);
            if ($email) {
                $email->setStatus('sent');
                $email->setSentAt(new \DateTimeImmutable());
                $this->entityManager->flush();
            }
            */
        }
    }

    /**
     * Obtenir les statistiques des emails
     */
    private function getEmailStatistics(): array
    {
        // Exemple de statistiques
        return [
            'pending' => 0,
            'sent_today' => 0,
            'failed' => 0,
            'total' => 0
        ];
    }
}
