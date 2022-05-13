<?php

namespace App\Service\JobManager\Jobs;

use App\Service\OrdersStorage\OrdersStorage;
use App\Service\Simla\ApiWrapper;
use DateTime;
use DateTimeZone;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProcessOrdersJob implements ProcessOrdersJobInterface
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
        $todayOrders = $this->ordersStorage->readOrders();

        foreach ($todayOrders as $time => $orders) {
            $orderTime = new DateTime('today ' . $time, new DateTimeZone('Europe/Moscow'));
            $diff = $this->now->diff($orderTime);

            if (
                $orderTime > $this->now
                && 0 == $diff->h
                && 5 > $diff->i
            ) {
                foreach ($orders as $orderId) {
                    $this->simla->setStateToOrder($orderId);
                }
            }
        }
    }
}
