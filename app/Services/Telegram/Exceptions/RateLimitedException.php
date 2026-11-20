<?php

namespace App\Services\Telegram\Exceptions;

class RateLimitedException extends TelegramSendException
{
    public function __construct(public readonly int $retryAfter, string $message = 'Too Many Requests')
    {
        parent::__construct($message, 429);
    }
}
