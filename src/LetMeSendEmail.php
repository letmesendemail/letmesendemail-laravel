<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel;

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\Resources\ContactCategoriesResource;
use LetMeSendEmail\Resources\ContactsResource;
use LetMeSendEmail\Resources\DomainsResource;
use LetMeSendEmail\Resources\EmailsResource;
use LetMeSendEmail\Resources\EmailTopicsResource;

final class LetMeSendEmail
{
    private Client $client;

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?int $timeout = null,
        ?int $retries = null,
        ?TransportInterface $transport = null,
        ?Client $client = null,
    ) {
        if ($client !== null) {
            $this->client = $client;
            return;
        }

        $config = new Configuration(
            apiKey: $apiKey ?? '',
            baseUrl: $baseUrl,
            timeout: $timeout,
            retries: $retries,
        );

        $this->client = new \LetMeSendEmail\Client($config, $transport ?? new \LetMeSendEmail\Http\GuzzleTransport(new \GuzzleHttp\Client()));
    }

    public function emails(): EmailsResource
    {
        return new EmailsResource($this->client);
    }

    public function domains(): DomainsResource
    {
        return new DomainsResource($this->client);
    }

    public function contacts(): ContactsResource
    {
        return new ContactsResource($this->client);
    }

    public function contactCategories(): ContactCategoriesResource
    {
        return new ContactCategoriesResource($this->client);
    }

    public function emailTopics(): EmailTopicsResource
    {
        return new EmailTopicsResource($this->client);
    }
}
