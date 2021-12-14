<?php

namespace App\Bridge\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class PublishedTimestampMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (!$envelope->last(PublishedTimestampStamp::class)) {
            $envelope = $envelope->with(new PublishedTimestampStamp());
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
