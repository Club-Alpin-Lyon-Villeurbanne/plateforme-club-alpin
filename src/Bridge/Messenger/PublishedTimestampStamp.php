<?php

namespace App\Bridge\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

class PublishedTimestampStamp implements StampInterface
{
    private float $timestamp;

    public function __construct(?float $timestamp = null)
    {
        $this->timestamp = $timestamp ?: microtime(true);
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
}
