<?php

namespace App\Service\OrdersStorage;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class OrdersStorage implements OrdersStorageInterface
{
    private string $timeField;

    private Filesystem $filesystem;

    private string $file;

    public function __construct(
        Filesystem $filesystem,
        ContainerBagInterface $params
    ) {
        $this->timeField = $params->get('crm.time_field');
        $this->filesystem = $filesystem;
        $this->file = $params->get('app.storage_file');
    }

    public function saveOrders(\Generator $orders)
    {
        $data = [];

        foreach ($orders as $order) {
            $data[$order->customFields[$this->timeField]][] = $order->id;
        }

        if (!$this->filesystem->exists($this->file)) {
            $this->filesystem->touch($this->file);
        }

        $this->filesystem->dumpFile($this->file, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function readOrders()
    {
        if ($this->filesystem->exists($this->file)) {
            return json_decode(file_get_contents($this->file), true);
        }

        return [];
    }
}
