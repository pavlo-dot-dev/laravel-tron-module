<?php

namespace PavloDotDev\LaravelTronModule;

use GuzzleHttp\Client;

class TronGrid
{
    protected readonly Client $client;

    public function __construct(string $apiKey = null)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.trongrid.io',
            'headers' => $apiKey ? [
                'TRON-PRO-API-KEY' => $apiKey
            ] : []
        ]);
    }

    public function getTransactionsByAddress(string $address, array $options = []): array
    {
        $response = $this->client->get('/v1/accounts/' . $address . '/transactions', [
            'query' => $options,
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function getContractTransactionsByAddress(string $address, array $options = []): array
    {
        $response = $this->client->get('/v1/accounts/' . $address . '/transactions/trc20', [
            'query' => $options,
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }
}
