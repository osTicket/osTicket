<?php

namespace Laminas\Mail\Storage;

use Traversable;
use Webmozart\Assert\Assert;

use function get_object_vars;
use function gettype;
use function is_array;
use function is_object;
use function iterator_to_array;
use function sprintf;

/**
 * @internal
 */
final class ParamsNormalizer
{
    /**
     * @return array<string, mixed>
     */
    public static function normalizeParams(mixed $params): array
    {
        if ($params instanceof Traversable) {
            $params = iterator_to_array($params);
        }

        if (is_object($params)) {
            $params = get_object_vars($params);
        }

        if (! is_array($params)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid $params provided; expected array|Traversable|object, received %s',
                gettype($params)
            ));
        }

        Assert::isMap($params, 'Expected $params to have only string keys');
        return $params;
    }
}
