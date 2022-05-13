<?php

namespace App\Service\Simla;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use RetailCrm\Api\Client;
use RetailCrm\Api\Enum\ByIdentifier;
use RetailCrm\Api\Enum\PaginationLimit;
use RetailCrm\Api\Factory\ClientFactory;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Model\Entity\Orders\Order;
use RetailCrm\Api\Model\Filter\Orders\OrderFilter;
use RetailCrm\Api\Model\Request\Orders\OrdersEditRequest;
use RetailCrm\Api\Model\Request\Orders\OrdersRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class ApiWrapper implements ApiWrapperInterface
{
    private Client $client;

    private string $site;

    private string $stateField;

    private string $dateField;

    private string $timeField;

    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $httpClient,
        ContainerBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->site = $params->get('crm.site');
        $this->stateField = $params->get('crm.state_field');
        $this->dateField = $params->get('crm.date_field');
        $this->timeField = $params->get('crm.time_field');
        $this->logger = $logger;

        $apiUrl = $params->get('crm.api_url');
        $apiKey = $params->get('crm.api_key');

        $factory = new ClientFactory();
        $factory->setHttpClient($httpClient);
        $this->client = $factory->createClient($apiUrl, $apiKey);
    }

    public function setStateToOrder(int $orderId, string $state = 'send')
    {
        $this->logger->debug('Order to edit: id#' . $orderId);

        $request                      = new OrdersEditRequest();
        $request->by                  = ByIdentifier::ID;
        $request->site                = $this->site;
        $request->order               = new Order();
        $request->order->customFields = [
            $this->stateField => $state,
        ];

        try {
            $this->client->orders->edit($orderId, $request);
        } catch (ApiExceptionInterface $exception) {
            $this->logger->error(sprintf(
                'Error from RetailCRM API (status code: %d): %s',
                $exception->getStatusCode(),
                $exception->getMessage()
            ));

            return null;
        }

        $this->logger->info('Order edited: id#' . $orderId);
    }

    public function getOrders(\DateTime $from, \DateTime $to)
    {
        $this->logger->info(
            'Getting orders from ' . $from->format('Y-m-d H:i:s') . ' to ' . $to->format('Y-m-d H:i:s')
        );

        $request = new OrdersRequest();
        $request->filter = new OrderFilter();
        $request->filter->customFields = [
            $this->dateField => [
                'min' => $from->format('Y-m-d'),
                'max' => $to->format('Y-m-d'),
            ],
        ];
        $request->limit = PaginationLimit::LIMIT_100;
        $request->page = 1;

        do {
            try {
                $response = $this->client->orders->list($request);
            } catch (ApiExceptionInterface $exception) {
                $this->logger->error(sprintf(
                    'Error from RetailCRM API (status code: %d): %s',
                    $exception->getStatusCode(),
                    $exception->getMessage()
                ));

                return null;
            }

            if (empty($response->orders)) {
                break;
            }

            foreach ($response->orders as $order) {
                if (
                    $from->format('H:i') <= $order->customFields[$this->timeField]
                    && $to->format('H:i') > $order->customFields[$this->timeField]
                ) {
                    $this->logger->debug('Yield order id#' . $order->id);

                    yield $order;
                }
            }

            ++$request->page;
        } while ($response->pagination->currentPage < $response->pagination->totalPageCount);
    }
}