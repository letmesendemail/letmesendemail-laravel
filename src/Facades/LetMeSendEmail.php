<?php

declare(strict_types=1);

namespace LetMeSendEmail\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \LetMeSendEmail\Resources\EmailsResource emails()
 * @method static \LetMeSendEmail\Resources\DomainsResource domains()
 * @method static \LetMeSendEmail\Resources\ContactsResource contacts()
 * @method static \LetMeSendEmail\Resources\ContactCategoriesResource contactCategories()
 * @method static \LetMeSendEmail\Resources\EmailTopicsResource emailTopics()
 */
final class LetMeSendEmail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LetMeSendEmail\Laravel\LetMeSendEmail::class;
    }
}
