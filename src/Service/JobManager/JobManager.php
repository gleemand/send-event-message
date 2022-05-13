<?php

namespace App\Service\JobManager;

use App\Service\JobManager\Jobs\ProcessOrdersJobInterface;
use App\Service\JobManager\Jobs\PullOrdersJobInterface;
use DateTime;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JobManager implements JobManagerInterface
{
    private PullOrdersJobInterface $pullOrdersJob;

    private ProcessOrdersJobInterface $processOrdersJob;

    private DateTime $now;

    public function __construct(
        PullOrdersJobInterface $pullOrdersJob,
        ProcessOrdersJobInterface $processOrdersJob
    ) {
        $this->processOrdersJob = $processOrdersJob;
        $this->pullOrdersJob = $pullOrdersJob;

        $now = (new DateTime('now'))->format(DateTimeInterface::RFC3339_EXTENDED);
        $this->now = new DateTime($now, new \DateTimeZone('Europe/Moscow'));
        putenv('NOW_DATE_TIME=' . $now);
    }

    public function run()
    {
        if (50 > $this->now->format('i')) {
            $this->pullOrdersJob->run();
        }

        $this->processOrdersJob->run();
    }
}
