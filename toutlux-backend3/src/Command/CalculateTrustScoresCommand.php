<?php

namespace App\Command;

use App\Entity\User;
use App\Service\User\TrustScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'app:calculate-trust-scores',
    description: 'Recalculate trust scores for all users',
)]
class CalculateTrustScoresCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TrustScoreCalculator $trustScoreCalculator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'user-id',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Calculate trust score for a specific user ID'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Number of users to process in each batch',
                100
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getOption('user-id');
        $batchSize = (int) $input->getOption('batch-size');

        $io->title('Calculating Trust Scores');

        try {
            if ($userId) {
                // Process single user
                $user = $this->entityManager->getRepository(User::class)->find($userId);

                if (!$user) {
                    $io->error(sprintf('User with ID %s not found', $userId));
                    return Command::FAILURE;
                }

                $oldScore = $user->getTrustScore();
                $this->trustScoreCalculator->updateUserTrustScore($user);
                $newScore = $user->getTrustScore();

                $io->success(sprintf(
                    'Trust score updated for %s: %.1f → %.1f',
                    $user->getEmail(),
                    $oldScore,
                    $newScore
                ));

                return Command::SUCCESS;
            }

            // Process all users
            $totalUsers = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $io->info(sprintf('Processing %d users...', $totalUsers));

            $progressBar = new ProgressBar($output, $totalUsers);
            $progressBar->start();

            $offset = 0;
            $updated = 0;
            $errors = 0;

            while ($offset < $totalUsers) {
                $users = $this->entityManager->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->leftJoin('u.profile', 'p')
                    ->addSelect('p')
                    ->setFirstResult($offset)
                    ->setMaxResults($batchSize)
                    ->getQuery()
                    ->getResult();

                foreach ($users as $user) {
                    try {
                        $oldScore = $user->getTrustScore();
                        $this->trustScoreCalculator->updateUserTrustScore($user);

                        if ($oldScore !== $user->getTrustScore()) {
                            $updated++;
                        }

                        $progressBar->advance();
                    } catch (\Exception $e) {
                        $errors++;
                        $io->error(sprintf(
                            'Error updating trust score for user %s: %s',
                            $user->getEmail(),
                            $e->getMessage()
                        ));
                    }
                }

                // Clear entity manager to free memory
                $this->entityManager->clear();

                $offset += $batchSize;
            }

            $progressBar->finish();
            $io->newLine(2);

            $io->success(sprintf(
                'Trust score calculation completed: %d users processed, %d scores updated, %d errors',
                $totalUsers,
                $updated,
                $errors
            ));

            // Show statistics
            $stats = $this->getScoreStatistics();
            $io->table(
                ['Score Range', 'User Count', 'Percentage'],
                [
                    ['0 - 1', $stats['0-1'], sprintf('%.1f%%', ($stats['0-1'] / $totalUsers) * 100)],
                    ['1 - 2', $stats['1-2'], sprintf('%.1f%%', ($stats['1-2'] / $totalUsers) * 100)],
                    ['2 - 3', $stats['2-3'], sprintf('%.1f%%', ($stats['2-3'] / $totalUsers) * 100)],
                    ['3 - 4', $stats['3-4'], sprintf('%.1f%%', ($stats['3-4'] / $totalUsers) * 100)],
                    ['4 - 5', $stats['4-5'], sprintf('%.1f%%', ($stats['4-5'] / $totalUsers) * 100)],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error calculating trust scores: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getScoreStatistics(): array
    {
        $ranges = [
            '0-1' => 0,
            '1-2' => 0,
            '2-3' => 0,
            '3-4' => 0,
            '4-5' => 0,
        ];

        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u.trustScore')
            ->getQuery()
            ->getArrayResult();

        foreach ($users as $user) {
            $score = $user['trustScore'];

            if ($score <= 1) {
                $ranges['0-1']++;
            } elseif ($score <= 2) {
                $ranges['1-2']++;
            } elseif ($score <= 3) {
                $ranges['2-3']++;
            } elseif ($score <= 4) {
                $ranges['3-4']++;
            } else {
                $ranges['4-5']++;
            }
        }

        return $ranges;
    }
}
