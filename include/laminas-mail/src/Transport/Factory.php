<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mail\Transport;

use Laminas\Stdlib\ArrayUtils;
use Traversable;

abstract class Factory
{
    /**
     * @var array Known transport types
     */
    protected static $classMap = [
        'file'      => 'Laminas\Mail\Transport\File',
        'inmemory'  => 'Laminas\Mail\Transport\InMemory',
        'memory'    => 'Laminas\Mail\Transport\InMemory',
        'null'      => 'Laminas\Mail\Transport\InMemory',
        'sendmail'  => 'Laminas\Mail\Transport\Sendmail',
        'smtp'      => 'Laminas\Mail\Transport\Smtp',
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
                (is_object($spec) ? get_class($spec) : gettype($spec))
            ));
        }

        $type = isset($spec['type']) ? $spec['type'] : 'sendmail';

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

        $transport = new $type;

        if (! $transport instanceof TransportInterface) {
            throw new Exception\DomainException(sprintf(
                '%s expects the "type" attribute to resolve to a valid'
                . ' Laminas\Mail\Transport\TransportInterface instance; received "%s"',
                __METHOD__,
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
