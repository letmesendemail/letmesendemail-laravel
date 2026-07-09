<?php

declare(strict_types=1);

use LetMeSendEmail\Exceptions\ApiException;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\AuthorizationError;
use LetMeSendEmail\Exceptions\ConflictError;
use LetMeSendEmail\Exceptions\NetworkError;
use LetMeSendEmail\Exceptions\NotFoundError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\TimeoutError;
use LetMeSendEmail\Exceptions\ValidationError;

test('exception classes are instantiable from the Laravel package', function () {
    expect(new ValidationError('test', 422))->toBeInstanceOf(ApiException::class);
    expect(new AuthenticationError('test', 401))->toBeInstanceOf(ApiException::class);
    expect(new AuthorizationError('test', 403))->toBeInstanceOf(ApiException::class);
    expect(new NotFoundError('test', 404))->toBeInstanceOf(ApiException::class);
    expect(new ConflictError('test', 409))->toBeInstanceOf(ApiException::class);
    expect(new RateLimitError('test', 429))->toBeInstanceOf(ApiException::class);
    expect(new NetworkError('test', 0))->toBeInstanceOf(ApiException::class);
    expect(new TimeoutError('test', 0))->toBeInstanceOf(ApiException::class);
});
