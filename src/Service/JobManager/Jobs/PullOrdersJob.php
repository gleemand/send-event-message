<?php

namespace App\Service\JobManager\Jobs;

use App\Service\OrdersStorage\OrdersStorage;
use App\Service\Simla\ApiWrapper;
use DateInterval;
use DateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PullOrdersJob implements PullOrdersJobInterface
{
    private ApiWrapper $simla;

    private OrdersStorage $ordersStorage;

    private DateTime $now;

    public function __construct(
        ApiWrapper $simla,
        OrdersStorage $ordersStorage
    ) {
        $this->simla = $simla;
        $this->ordersStorage = $ordersStorage;
        $this->now = new DateTime(getenv('NOW_DATE_TIME'), new \DateTimeZone('Europe/Moscow'));
    }

    public function run()
    {
        $from = $this->now;
        $to = clone $from;
        $to->add(new DateInterval('PT1H'));

        $orders = $this->simla->getOrders($from, $to);

        if ($orders) {
            $this->ordersStorage->saveOrders($orders);
        }
    }
}
