<?php

namespace PavloDotDev\LaravelTronModule\Api;

use GuzzleHttp\Client;
use PavloDotDev\LaravelTronModule\Api\Enums\HttpMethod;
use Psr\Http\Message\StreamInterface;

class HttpProvider
{
    protected Client $client;

    public function __construct(
        public readonly string     $baseUri,
        protected readonly array   $headers = [],
        protected readonly ?string $user = null,
        protected readonly ?string $password = null,
        public readonly int        $timeout = 30000,
        protected string           $statusPage = '/'
    )
    {
        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout' => $timeout,
            'auth' => $this->user && [$this->user, $this->password]
        ]);
    }

    public function setStatusPage(string $page = '/'): void
    {
        $this->statusPage = $page;
    }

    public function isConnected(): bool
    {
        $response = $this->request($this->statusPage);

        if (array_key_exists('blockID', $response)) {
            return true;
        } elseif (array_key_exists('status', $response)) {
            return true;
        } elseif (isset($response['database']['block'])) {
            return true;
        } elseif ($response['ok'] ?? false) {
            return true;
        }

        return false;
    }

    public function request(string $path, array $payload = [], HttpMethod $method = HttpMethod::GET): array
    {
        $response = $this->client->request($method->name, $path, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                ...$this->headers
            ],
            'json' => $payload,
        ]);

        return $this->decodeBody(
            $response->getBody(),
            $response->getStatusCode()
        );
    }

    protected function decodeBody(StreamInterface $stream, int $status): array
    {
        $decodedBody = json_decode($stream->getContents(), true);

        if ((string)$stream == 'OK') {
            $decodedBody = [
                'status' => 1
            ];
        } elseif ($decodedBody == null or !is_array($decodedBody)) {
            $decodedBody = [];
        }

        if ($status == 404) {
            throw new \Exception('Page not found');
        }

        if( isset( $decodedBody['Error'] ) ) {
            throw new \Exception($decodedBody['Error']);
        }

        return $decodedBody;
    }
}
