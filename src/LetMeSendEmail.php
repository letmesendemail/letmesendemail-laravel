<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel;

use LetMeSendEmail\Configuration;
use LetMeSendEmail\Resources\ContactCategoriesResource;
use LetMeSendEmail\Resources\ContactsResource;
use LetMeSendEmail\Resources\DomainsResource;
use LetMeSendEmail\Resources\EmailsResource;
use LetMeSendEmail\Resources\EmailTopicsResource;

final class LetMeSendEmail
{
    private \LetMeSendEmail\LetMeSendEmail $client;

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?int $timeout = null,
    ) {
        $config = new Configuration(
            apiKey: $apiKey ?? '',
            baseUrl: $baseUrl,
            timeout: $timeout,
        );

        $this->client = new \LetMeSendEmail\LetMeSendEmail(configuration: $config);
    }

    public function emails(): EmailsResource
    {
        return $this->client->emails();
    }

    public function domains(): DomainsResource
    {
        return $this->client->domains();
    }

    public function contacts(): ContactsResource
    {
        return $this->client->contacts();
    }

    public function contactCategories(): ContactCategoriesResource
    {
        return $this->client->contactCategories();
    }

    public function emailTopics(): EmailTopicsResource
    {
        return $this->client->emailTopics();
    }
}
