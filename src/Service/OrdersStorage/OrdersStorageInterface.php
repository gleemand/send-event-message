<?php

namespace App\Service\OrdersStorage;

interface OrdersStorageInterface
{
    public function saveOrders(\Generator $orders);

    public function readOrders();
}
