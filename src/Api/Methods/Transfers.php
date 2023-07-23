<?php

namespace PavloDotDev\LaravelTronModule\Api\Methods;

use Illuminate\Support\Collection;
use PavloDotDev\LaravelTronModule\Api\ApiManager;
use PavloDotDev\LaravelTronModule\Api\DTO\TransferDTO;
use PavloDotDev\LaravelTronModule\Api\Enums\Confirmation;
use PavloDotDev\LaravelTronModule\Api\Enums\Direction;
use PavloDotDev\LaravelTronModule\Api\Enums\OrderBy;

class Transfers implements \Iterator
{
    protected ?bool $onlyConfirmation = null;
    protected ?bool $onlyDirection = null;
    protected int $limit = 20;
    protected ?string $fingerprint = null;
    protected ?string $orderBy = null;
    protected ?int $minTimestamp = null;
    protected ?int $maxTimestamp = null;
    protected bool $searchInterval = true;

    protected Collection $collection;
    protected bool $hasNext = true;
    protected int $current = 0;

    public function __construct(protected readonly ApiManager $manager, public readonly string $address)
    {
        $this->collection = collect();
    }

    public function onlyConfirmation(?Confirmation $confirmed): static
    {
        $this->onlyConfirmation = $confirmed ? $confirmed->name === 'CONFIRMED' : null;

        return $this;
    }

    public function onlyDirection(?Direction $direction): static
    {
        $this->onlyDirection = $direction?->value;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = max(min($limit, 200), 1);

        return $this;
    }

    public function orderBy(?OrderBy $orderBy): static
    {
        $this->orderBy = $orderBy ? 'block_timestamp,' . $orderBy->value : null;

        return $this;
    }

    public function minTimestamp(?int $minTimestamp): static
    {
        $this->minTimestamp = $minTimestamp;

        return $this;
    }

    public function maxTimestamp(?int $maxTimestamp): static
    {
        $this->maxTimestamp = $maxTimestamp;

        return $this;
    }

    public function searchInterval(bool $searchInterval): static
    {
        $this->searchInterval = $searchInterval;

        return $this;
    }

    public function getQuery(): array
    {
        $query = [
            'limit' => $this->limit,
            'fingerprint' => $this->fingerprint,
            'order_by' => $this->orderBy,
            'min_timestamp' => $this->minTimestamp,
            'max_timestamp' => $this->maxTimestamp,
            'search_internal' => $this->searchInterval,
        ];

        if ($this->onlyConfirmation !== null) {
            $query[$this->onlyConfirmation ? 'only_confirmed' : 'only_unconfirmed'] = true;
        }
        if ($this->onlyDirection !== null) {
            $query[$this->onlyDirection === Direction::FROM ? 'only_from' : 'only_to'] = true;
        }

        return array_filter($query, fn($item) => $item !== null);
    }

    public function current(): TransferDTO
    {
        return $this->collection->get($this->current);
    }

    public function next(): void
    {
        $this->current++;
    }

    public function key(): mixed
    {
        return $this->current;
    }

    public function valid(): bool
    {
        if ($this->collection->has($this->current)) {
            return true;
        }

        if (!$this->hasNext) {
            return false;
        }

        $this->collection = $this->collection->merge(
            $this->request()
        );

        return $this->collection->has($this->current);
    }

    public function rewind(): void
    {
        $this->collection = $this->request();

        $this->current = 0;
    }

    protected function request(): Collection
    {
        $data = $this->manager->request(
            'v1/accounts/' . $this->address . '/transactions',
            $this->getQuery()
        );

        $this->fingerprint = $data['meta']['fingerprint'] ?? null;
        $this->hasNext = !!$this->fingerprint;

        return collect($data['data'])
            ->map(fn(array $item) => TransferDTO::fromArray($item))
            ->filter();
    }
}
