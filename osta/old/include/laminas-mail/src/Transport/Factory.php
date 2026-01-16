<?php

namespace Laminas\Mail\Transport;

use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function class_exists;
use function gettype;
use function is_array;
use function is_object;
use function sprintf;
use function strtolower;

// phpcs:ignore WebimpressCodingStandard.NamingConventions.AbstractClass.Prefix
abstract class Factory
{
    /** @var array Known transport types */
    protected static $classMap = [
        'file'     => File::class,
        'inmemory' => InMemory::class,
        'memory'   => InMemory::class,
        'null'     => InMemory::class,
        'sendmail' => Sendmail::class,
        'smtp'     => Smtp::class,
    ];

    /**
     * @param array $spec
     * @return TransportInterface
     * @throws Exception\InvalidArgumentException
     * @throws Exception\DomainException
     */
    public static function create($spec = [])
    {
        if ($spec instanceof Traversable) {
            $spec = ArrayUtils::iteratorToArray($spec);
        }

        if (! is_array($spec)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable argument; received "%s"',
                __METHOD__,
                is_object($spec) ? $spec::class : gettype($spec)
            ));
        }

        $type = $spec['type'] ?? 'sendmail';

        $normalizedType = strtolower($type);

        if (isset(static::$classMap[$normalizedType])) {
            $type = static::$classMap[$normalizedType];
        }

        if (! class_exists($type)) {
            throw new Exception\DomainException(sprintf(
                '%s expects the "type" attribute to resolve to an existing class; received "%s"',
                __METHOD__,
                $type
            ));
        }

        $transport = new $type();

        if (! $transport instanceof TransportInterface) {
            throw new Exception\DomainException(sprintf(
                '%s expects the "type" attribute to resolve to a valid %s instance; received "%s"',
                __METHOD__,
                TransportInterface::class,
                $type
            ));
        }

        if ($transport instanceof Smtp && isset($spec['options'])) {
            $transport->setOptions(new SmtpOptions($spec['options']));
        }

        if ($transport instanceof File && isset($spec['options'])) {
            $transport->setOptions(new FileOptions($spec['options']));
        }

        return $transport;
    }
}
