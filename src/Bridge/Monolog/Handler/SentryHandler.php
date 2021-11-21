<?php

namespace App\Bridge\Monolog\Handler;

use App\Bridge\Monolog\SentryHandlerLogFilter\LogFilterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Severity;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * This Monolog handler logs every message to a Sentry's server using the given
 * hub instance.
 */
class SentryHandler extends AbstractProcessingHandler
{
    /** @var LogFilterInterface[] */
    private $logFilters = [];
    private HubInterface $hub;
    private array $records = [];

    /**
     * @param HubInterface $hub    The hub to which errors are reported
     * @param int          $level  The minimum logging level at which this
     *                             handler will be triggered
     * @param bool         $bubble Whether the messages that are handled can
     *                             bubble up the stack or not
     */
    public function __construct(HubInterface $hub, iterable $logFilters, $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->hub = $hub;
        foreach ($logFilters as $logFilter) {
            $this->addLogFilter($logFilter);
        }

        parent::__construct($level, $bubble);
    }

    public function addLogFilter(LogFilterInterface $logFilter)
    {
        $this->logFilters[] = $logFilter;
    }

    public function flush(): void
    {
        $this->pushEvent();

        if (null !== $client = $this->hub->getClient()) {
            $client->flush();
        }

        $this->records = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        if ($this->shouldSkip($record)) {
            return;
        }

        $this->records[] = $record;
    }

    public function clean(): void
    {
        $this->records = [];
    }

    private function pushEvent(): void
    {
        if (0 === \count($this->records)) {
            return;
        }

        // create breadcrumbs and find the highest record on the road
        $highest = null;

        $breadcrumbs = [];
        foreach ($this->records as $record) {
            $breadcrumbs[] = $this->createBreadcrumb($record);

            if (null === $highest) {
                $highest = $record;
            } elseif ($record['level'] > $highest['level']) {
                $highest = $record;
            } elseif ($record['level'] === $highest['level'] && isset($record['context']['exception']) && !isset($highest['context']['exception'])) {
                // this condition means the record being read has same level.
                // And also, the current record has an exception, whereas the highest has not.
                // In this case, we prefer the line with an exception.
                $highest = $record;
            }
        }
        $this->records = [];

        if ($highest['level'] < Logger::ERROR) {
            return;
        }

        $event = Event::createEvent();
        $event->setLevel(self::getSeverityFromLevel($highest['level']));
        $event->setMessage($highest['message']);
        $event->setLogger(sprintf('monolog.%s', $highest['channel']));

        $this->hub->withScope(function (Scope $scope) use ($breadcrumbs, $highest, $event): void {
            foreach ($breadcrumbs as $breadcrumb) {
                $this->hub->addBreadcrumb($breadcrumb);
            }

            if (null !== $user = $highest['extra']['user'] ?? null) {
                $scope->setUser($user);
                unset($highest['extra']['user']);
            }

            foreach (array_keys($highest['extra'] ?? []) as $key) {
                $scope->setExtra($key, $highest['extra'][$key]);
            }

            $hint = null;
            if (isset($highest['context']['exception'])) {
                $exception = $highest['context']['exception'];

                if ($exception instanceof \Throwable) {
                    $hint = new EventHint();
                    $hint->exception = $exception;

                    $exception = $this->formatException($exception);
                }

                // Sentry remove the "type" from the array... Dont know why....
                if (isset($exception['type'])) {
                    $exception['exception_type'] = $exception['type'];
                }

                $scope->setContext('exception', $exception);
                unset($highest['context']['exception']);
            }

            if (!empty($highest['context'])) {
                $scope->setContext('context', $highest['context']);
            }

            $this->hub->captureEvent($event, $hint);
        });
    }

    private function shouldSkip(array $record): bool
    {
        foreach ($this->logFilters as $logFilter) {
            if ($logFilter->shouldSkip($record)) {
                return true;
            }
        }

        return false;
    }

    private function createBreadcrumb(array $record): Breadcrumb
    {
        return new Breadcrumb(
            (string) $this->getSeverityFromLevel($record['level']),
            Breadcrumb::TYPE_DEFAULT,
            $record['channel'],
            $record['message'],
            $record['context'],
            (float) $record['datetime']->format('U.u')
        );
    }

    /**
     * Translates the Monolog level into the Sentry severity.
     *
     * @param int $level The Monolog log level
     */
    private function getSeverityFromLevel(int $level): Severity
    {
        if ($level < Logger::INFO) {
            return Severity::debug();
        }

        if ($level < Logger::WARNING) {
            return Severity::info();
        }

        if ($level < Logger::ERROR) {
            return Severity::warning();
        }

        if ($level < Logger::CRITICAL) {
            return Severity::error();
        }

        return Severity::fatal();
    }

    private function formatException(\Throwable $exception): array
    {
        $data = [
            'exception_type' => \get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        if ($exception instanceof HttpExceptionInterface) {
            $data['http_exception_status_code'] = $exception->getStatusCode();
        }

        if ($exception->getPrevious()) {
            $data['previous'] = $this->formatException($exception->getPrevious());
        }

        return $data;
    }
}
