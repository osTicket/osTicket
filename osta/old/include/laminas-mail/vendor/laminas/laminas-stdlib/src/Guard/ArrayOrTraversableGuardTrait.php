<?php

declare(strict_types=1);

namespace Laminas\Stdlib\Guard;

use Exception;
use Laminas\Stdlib\Exception\InvalidArgumentException;
use Traversable;

use function get_debug_type;
use function is_array;
use function sprintf;

/**
 * Provide a guard method for array or Traversable data
 */
trait ArrayOrTraversableGuardTrait
{
    /**
     * Verifies that the data is an array or Traversable
     *
     * @param mixed  $data           the data to verify
     * @param string $dataName       the data name
     * @param string $exceptionClass FQCN for the exception
     * @return void
     * @throws Exception
     */
    protected function guardForArrayOrTraversable(
        mixed $data,
        $dataName = 'Argument',
        $exceptionClass = InvalidArgumentException::class
    ) {
        if (! is_array($data) && ! $data instanceof Traversable) {
            $message = sprintf(
                "%s must be an array or Traversable, [%s] given",
                $dataName,
                get_debug_type($data)
            );
            throw new $exceptionClass($message);
        }
    }
}
