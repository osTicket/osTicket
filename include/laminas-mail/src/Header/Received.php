<?php

namespace Laminas\Mail\Header;

use Laminas\Mail\Headers;

use function implode;
use function strtolower;

/**
 * @todo       Allow setting date from DateTime, Laminas\Date, or string
 */
class Received implements HeaderInterface, MultipleHeadersInterface
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
        if (strtolower($name) !== 'received') {
            throw new Exception\InvalidArgumentException('Invalid header line for Received string');
        }

        return new static($value);
    }

    /**
     * @param string $value
     */
    public function __construct($value = '')
    {
        if (! HeaderValue::isValid($value)) {
            throw new Exception\InvalidArgumentException('Invalid Received value provided');
        }
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return 'Received';
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
        return 'Received: ' . $this->getFieldValue();
    }

    /**
     * Serialize collection of Received headers to string
     *
     * @param  array $headers
     * @throws Exception\RuntimeException
     * @return string
     */
    public function toStringMultipleHeaders(array $headers)
    {
        $strings = [$this->toString()];
        foreach ($headers as $header) {
            if (! $header instanceof self) {
                throw new Exception\RuntimeException(
                    'The Received multiple header implementation can only accept an array of Received headers'
                );
            }
            $strings[] = $header->toString();
        }
        return implode(Headers::EOL, $strings);
    }
}
