<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\Document\TrustScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'app:update-trust-scores',
    description: 'Mettre à jour les scores de confiance de tous les utilisateurs',
)]
class UpdateTrustScoresCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private TrustScoreCalculator $trustScoreCalculator,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'Mettre à jour uniquement un utilisateur spécifique')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler sans sauvegarder les changements')
            ->addOption('verbose-output', null, InputOption::VALUE_NONE, 'Afficher les détails pour chaque utilisateur')
            ->setHelp('Cette commande recalcule les scores de confiance de tous les utilisateurs basés sur leurs documents et informations de profil');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $verboseOutput = $input->getOption('verbose-output');
        $userId = $input->getOption('user-id');

        if ($isDryRun) {
            $io->warning('Mode simulation activé - aucune modification ne sera sauvegardée');
        }

        // Récupérer les utilisateurs
        if ($userId) {
            $users = $this->userRepository->findBy(['id' => $userId]);
            if (empty($users)) {
                $io->error(sprintf('Utilisateur avec l\'ID %s introuvable', $userId));
                return Command::FAILURE;
            }
        } else {
            $users = $this->userRepository->findAll();
        }

        $totalUsers = count($users);
        $io->info(sprintf('Mise à jour des scores de confiance pour %d utilisateur(s)', $totalUsers));

        // Créer une barre de progression
        $progressBar = new ProgressBar($output, $totalUsers);
        $progressBar->setFormat('debug');
        $progressBar->start();

        $stats = [
            'updated' => 0,
            'unchanged' => 0,
            'errors' => 0,
            'totalBefore' => 0,
            'totalAfter' => 0
        ];

        foreach ($users as $user) {
            try {
                $oldScore = (float) $user->getTrustScore();
                $stats['totalBefore'] += $oldScore;

                // Calculer le nouveau score
                $newScore = $this->trustScoreCalculator->calculateTrustScore($user);
                $stats['totalAfter'] += $newScore;

                if ($oldScore !== $newScore) {
                    if (!$isDryRun) {
                        $user->setTrustScore($newScore);
                        $this->entityManager->persist($user);
                    }
                    $stats['updated']++;

                    if ($verboseOutput) {
                        $progressBar->clear();
                        $io->info(sprintf(
                            'Utilisateur %s (%s): %.1f → %.1f',
                            $user->getFullName(),
                            $user->getEmail(),
                            $oldScore,
                            $newScore
                        ));
                        $progressBar->display();
                    }
                } else {
                    $stats['unchanged']++;
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $progressBar->clear();
                $io->error(sprintf(
                    'Erreur pour l\'utilisateur %s: %s',
                    $user->getEmail(),
                    $e->getMessage()
                ));
                $progressBar->display();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Sauvegarder les changements
        if (!$isDryRun && $stats['updated'] > 0) {
            try {
                $this->entityManager->flush();
                $io->success('Les scores de confiance ont été mis à jour avec succès.');
            } catch (\Exception $e) {
                $io->error('Erreur lors de la sauvegarde: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Afficher les statistiques
        $io->section('Résumé');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Total utilisateurs', $totalUsers],
                ['Scores mis à jour', $stats['updated']],
                ['Scores inchangés', $stats['unchanged']],
                ['Erreurs', $stats['errors']],
                ['Score moyen avant', $totalUsers > 0 ? round($stats['totalBefore'] / $totalUsers, 2) : 0],
                ['Score moyen après', $totalUsers > 0 ? round($stats['totalAfter'] / $totalUsers, 2) : 0],
            ]
        );

        if ($verboseOutput && $stats['updated'] > 0) {
            // Afficher les détails des scores
            $scoreDetails = $this->getScoreDistribution();
            $io->section('Distribution des scores');
            $io->table(
                ['Plage', 'Nombre d\'utilisateurs', 'Pourcentage'],
                $scoreDetails
            );
        }

        return Command::SUCCESS;
    }

    private function getScoreDistribution(): array
    {
        $ranges = [
            '0-1' => 0,
            '1-2' => 0,
            '2-3' => 0,
            '3-4' => 0,
            '4-5' => 0
        ];

        $users = $this->userRepository->findAll();
        $total = count($users);

        foreach ($users as $user) {
            $score = (float) $user->getTrustScore();
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

        $distribution = [];
        foreach ($ranges as $range => $count) {
            $distribution[] = [
                $range,
                $count,
                $total > 0 ? round(($count / $total) * 100, 2) . '%' : '0%'
            ];
        }

        return $distribution;
    }
}
