<?php

namespace App\Command;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-expired-tokens',
    description: 'Nettoyer les tokens JWT expirés de la base de données',
)]
class CleanExpiredTokensCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler sans supprimer')
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Supprimer les tokens expirés depuis X jours', 7)
            ->setHelp('Cette commande supprime les refresh tokens JWT expirés de la base de données');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $days = (int) $input->getOption('days');

        if ($isDryRun) {
            $io->warning('Mode simulation activé - aucune suppression ne sera effectuée');
        }

        $io->info(sprintf('Recherche des tokens expirés depuis plus de %d jours...', $days));

        // Calculer la date limite
        $expirationDate = new \DateTime();
        $expirationDate->modify(sprintf('-%d days', $days));

        // Rechercher les tokens expirés
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('rt')
            ->from(RefreshToken::class, 'rt')
            ->where('rt.valid < :date')
            ->setParameter('date', $expirationDate);

        $expiredTokens = $qb->getQuery()->getResult();
        $count = count($expiredTokens);

        if ($count === 0) {
            $io->success('Aucun token expiré trouvé.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('%d token(s) expiré(s) trouvé(s)', $count));

        if (!$isDryRun) {
            $io->progressStart($count);

            foreach ($expiredTokens as $token) {
                $this->entityManager->remove($token);
                $io->progressAdvance();
            }

            $io->progressFinish();

            try {
                $this->entityManager->flush();
                $io->success(sprintf('%d token(s) expiré(s) supprimé(s) avec succès.', $count));
            } catch (\Exception $e) {
                $io->error('Erreur lors de la suppression : ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            // En mode dry-run, afficher quelques exemples
            $io->section('Exemples de tokens qui seraient supprimés :');

            $examples = array_slice($expiredTokens, 0, 5);
            foreach ($examples as $token) {
                $io->writeln(sprintf(
                    '- Token ID: %s, Username: %s, Expiré le: %s',
                    $token->getId(),
                    $token->getUsername(),
                    $token->getValid()->format('Y-m-d H:i:s')
                ));
            }

            if ($count > 5) {
                $io->writeln(sprintf('... et %d autres', $count - 5));
            }
        }

        // Afficher des statistiques
        $this->displayStatistics($io);

        return Command::SUCCESS;
    }

    private function displayStatistics(SymfonyStyle $io): void
    {
        $io->section('Statistiques des refresh tokens');

        $stats = [];

        // Total des tokens
        $totalTokens = $this->entityManager->createQueryBuilder()
            ->select('COUNT(rt.id)')
            ->from(RefreshToken::class, 'rt')
            ->getQuery()
            ->getSingleScalarResult();

        $stats[] = ['Total des tokens', $totalTokens];

        // Tokens valides
        $validTokens = $this->entityManager->createQueryBuilder()
            ->select('COUNT(rt.id)')
            ->from(RefreshToken::class, 'rt')
            ->where('rt.valid > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        $stats[] = ['Tokens valides', $validTokens];

        // Tokens expirés
        $expiredTokens = $totalTokens - $validTokens;
        $stats[] = ['Tokens expirés', $expiredTokens];

        // Token le plus ancien
        $oldestToken = $this->entityManager->createQueryBuilder()
            ->select('rt')
            ->from(RefreshToken::class, 'rt')
            ->orderBy('rt.valid', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($oldestToken) {
            $stats[] = ['Token le plus ancien', $oldestToken->getValid()->format('Y-m-d H:i:s')];
        }

        $io->table(['Métrique', 'Valeur'], $stats);
    }
}
