<?php

namespace App\Service\Simla;

interface ApiWrapperInterface
{
    public function getOrders(\DateTime $from, \DateTime $to);

    public function setStateToOrder(int $orderId, string $state = 'send');
}