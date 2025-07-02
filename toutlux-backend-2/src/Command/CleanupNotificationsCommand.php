<?php

namespace App\Command;

use App\Service\Notification\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-notifications',
    description: 'Clean up old read notifications',
)]
class CleanupNotificationsCommand extends Command
{
    public function __construct(
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Number of days to keep read notifications',
                30
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run without actually deleting notifications'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $dryRun = $input->getOption('dry-run');

        $io->title('Cleaning up old notifications');
        $io->info(sprintf('Removing read notifications older than %d days', $days));

        if ($dryRun) {
            $io->warning('Running in dry-run mode - no notifications will be deleted');
        }

        try {
            if (!$dryRun) {
                $deletedCount = $this->notificationService->cleanupOldNotifications($days);
                $io->success(sprintf('Successfully deleted %d old notifications', $deletedCount));
            } else {
                $io->info('Dry run completed - no notifications were deleted');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error cleaning up notifications: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
