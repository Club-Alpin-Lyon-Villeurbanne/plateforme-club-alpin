<?php

namespace App\Bridge\Monolog\ActivationStrategy;

use Monolog\Handler\FingersCrossed\ActivationStrategyInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class HttpKernelExceptionActivationStrategy implements ActivationStrategyInterface
{
    public const EXCLUDED_STATUS_CODES = [
        400,
        401,
        402, // Payment required
        403,
        404,
        405,
        410, // Gone
        412, // Precondition failed
        423, // Locked
    ];

    /**
     * {@inheritdoc}
     */
    public function isHandlerActivated(LogRecord $record): bool
    {
        $default = $record['level'] >= Level::Error;

        if (!isset($record['context']['exception'])) {
            return $default;
        }

        $exception = $record['context']['exception'];

        if ($exception instanceof \Exception) {
            do {
                if ($exception instanceof HttpExceptionInterface) {
                    return $this->isStatusCodeActive($exception->getStatusCode());
                }

                if ($exception instanceof AccessDeniedException) {
                    return $this->isStatusCodeActive($exception->getCode());
                }
            } while ($exception = $exception->getPrevious());
        }

        return $default;
    }

    private function isStatusCodeActive(int $statusCode): bool
    {
        return !\in_array($statusCode, self::EXCLUDED_STATUS_CODES, true);
    }
}
