<?php

namespace App\Bridge\Monolog\Handler;

use Symfony\Bridge\Monolog\Handler\ConsoleHandler as BaseConsoleHandler;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A console handler that logs ONLY when console run with `-v, -vv, -vvv` option.
 */
class VerboseConsoleHandler extends BaseConsoleHandler
{
    private ?OutputInterface $output;

    public function __construct(
        OutputInterface $output = null,
        bool $bubble = true,
        array $verbosityLevelMap = [],
        array $consoleFormatterOptions = []
    ) {
        parent::__construct($output, $bubble, $verbosityLevelMap, $consoleFormatterOptions);
        $this->output = $output;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        parent::setOutput($output);
    }

    public function close(): void
    {
        $this->output = null;

        parent::close();
    }

    public function handle(array $record): bool
    {
        if (!$this->output || !$this->output->isVerbose()) {
            return false;
        }

        return parent::handle($record);
    }
}
