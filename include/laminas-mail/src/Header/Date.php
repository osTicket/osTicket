<?php

namespace Laminas\Mail\Header;

use function strtolower;

/**
 * @todo       Add accessors for setting date from DateTime, Laminas\Date, or a string
 */
class Date implements HeaderInterface
{
    /** @var string */
    protected $value;

    /**
     * @param string $headerLine
     * @return static
     */
    public static function fromString($headerLine)
    {
        [$name, $value] = GenericHeader::splitHeaderLine($headerLine);
        $value          = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'date') {
            throw new Exception\InvalidArgumentException('Invalid header line for Date string');
        }

        return new static($value);
    }

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        if (! HeaderValue::isValid($value)) {
            throw new Exception\InvalidArgumentException('Invalid Date header value detected');
        }
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return 'Date';
    }

    /**
     * @inheritDoc
     */
    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        return $this->value;
    }

    /**
     * @param string $encoding
     * @return self
     */
    public function setEncoding($encoding)
    {
        // This header must be always in US-ASCII
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return 'ASCII';
    }

    /**
     * @return string
     */
    public function toString()
    {
        return 'Date: ' . $this->getFieldValue();
    }
}
