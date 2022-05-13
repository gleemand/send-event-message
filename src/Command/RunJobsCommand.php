<?php

namespace App\Command;

use App\Service\JobManager\JobManagerInterface;
use App\Service\Sync\SyncInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunJobsCommand extends Command
{
    use LockableTrait;

    protected static $defaultName = 'run_jobs';

    protected static $defaultDescription = 'Run jobs';

    private JobManagerInterface $jobManager;

    public function __construct(
        JobManagerInterface $jobManager
    ) {
        $this->jobManager = $jobManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('Command is already running');

            return Command::SUCCESS;
        }

        $this->jobManager->run();

        $this->release();

        return Command::SUCCESS;
    }
}
