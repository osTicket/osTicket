<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool;

use Laminas\ServiceManager\Exception;
use Laminas\Stdlib\ConsoleHelper;

use function array_shift;
use function assert;
use function class_exists;
use function in_array;
use function is_string;
use function sprintf;

use const STDERR;
use const STDOUT;

/**
 * @psalm-type ArgumentObject = object{command:string, class:null|string, message:null|string}
 */
class FactoryCreatorCommand
{
    public const COMMAND_DUMP  = 'dump';
    public const COMMAND_ERROR = 'error';
    public const COMMAND_HELP  = 'help';

    public const DEFAULT_SCRIPT_NAME = self::class;

    public const HELP_TEMPLATE = <<<EOH
<info>Usage:</info>

  %s [-h|--help|help] <className>

<info>Arguments:</info>

  <info>-h|--help|help</info>    This usage message
  <info><className></info>       Name of the class to reflect and for which to generate
                    a factory.

Generates to STDOUT a factory for creating the specified class; this may then
be added to your application, and configured as a factory for the class.
EOH;

    private ConsoleHelper $helper;

    /**
     * @param string $scriptName
     */
    public function __construct(private $scriptName = self::DEFAULT_SCRIPT_NAME, ?ConsoleHelper $helper = null)
    {
        $this->helper = $helper ?: new ConsoleHelper();
    }

    /**
     * @param array $args Argument list, minus script name
     * @return int Exit status
     */
    public function __invoke(array $args)
    {
        $arguments = $this->parseArgs($args);

        switch ($arguments->command) {
            case self::COMMAND_HELP:
                $this->help();
                return 0;
            case self::COMMAND_ERROR:
                assert(is_string($arguments->message));
                $this->helper->writeErrorMessage($arguments->message);
                $this->help(STDERR);
                return 1;
            case self::COMMAND_DUMP:
                // fall-through
            default:
                break;
        }

        $generator = new FactoryCreator();
        assert(is_string($arguments->class));
        try {
            $factory = $generator->createFactory($arguments->class);
        } catch (Exception\InvalidArgumentException $e) {
            $this->helper->writeErrorMessage(sprintf(
                'Unable to create factory for "%s": %s',
                $arguments->class,
                $e->getMessage()
            ));
            $this->help(STDERR);
            return 1;
        }

        $this->helper->write($factory, false);
        return 0;
    }

    /**
     * @return ArgumentObject
     */
    private function parseArgs(array $args)
    {
        if (! $args) {
            return $this->createArguments(self::COMMAND_HELP);
        }

        $arg1 = array_shift($args);

        if (in_array($arg1, ['-h', '--help', 'help'], true)) {
            return $this->createArguments(self::COMMAND_HELP);
        }

        $class = $arg1;

        if (! class_exists($class)) {
            return $this->createArguments(self::COMMAND_ERROR, null, sprintf(
                'Class "%s" does not exist or could not be autoloaded.',
                $class
            ));
        }

        return $this->createArguments(self::COMMAND_DUMP, $class);
    }

    /**
     * @param resource $resource Defaults to STDOUT
     * @return void
     */
    private function help($resource = STDOUT)
    {
        $this->helper->writeLine(sprintf(
            self::HELP_TEMPLATE,
            $this->scriptName
        ), true, $resource);
    }

    /**
     * @param string $command
     * @param string|null $class Name of class to reflect.
     * @param string|null $error Error message, if any.
     * @return ArgumentObject
     */
    private function createArguments($command, $class = null, $error = null)
    {
        return (object) [
            'command' => $command,
            'class'   => $class,
            'message' => $error,
        ];
    }
}
