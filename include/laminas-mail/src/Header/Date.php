<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mail\Header;

/**
 * @todo       Add accessors for setting date from DateTime, Laminas\Date, or a string
 */
class Date implements HeaderInterface
{
    /**
     * @var string
     */
    protected $value;

    public static function fromString($headerLine)
    {
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
        $value = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'date') {
            throw new Exception\InvalidArgumentException('Invalid header line for Date string');
        }

        $header = new static($value);

        return $header;
    }

    public function __construct($value)
    {
        if (! HeaderValue::isValid($value)) {
            throw new Exception\InvalidArgumentException('Invalid Date header value detected');
        }
        $this->value = $value;
    }

    public function getFieldName()
    {
        return 'Date';
    }

    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        return $this->value;
    }

    public function setEncoding($encoding)
    {
        // This header must be always in US-ASCII
        return $this;
    }

    public function getEncoding()
    {
        return 'ASCII';
    }

    public function toString()
    {
        return 'Date: ' . $this->getFieldValue();
    }
}
