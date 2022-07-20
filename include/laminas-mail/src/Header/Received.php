<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mail\Header;

use Laminas\Mail\Headers;

/**
 * @todo       Allow setting date from DateTime, Laminas\Date, or string
 */
class Received implements HeaderInterface, MultipleHeadersInterface
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
        if (strtolower($name) !== 'received') {
            throw new Exception\InvalidArgumentException('Invalid header line for Received string');
        }

        $header = new static($value);

        return $header;
    }

    public function __construct($value = '')
    {
        if (! HeaderValue::isValid($value)) {
            throw new Exception\InvalidArgumentException('Invalid Received value provided');
        }
        $this->value = $value;
    }

    public function getFieldName()
    {
        return 'Received';
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
            if (! $header instanceof Received) {
                throw new Exception\RuntimeException(
                    'The Received multiple header implementation can only accept an array of Received headers'
                );
            }
            $strings[] = $header->toString();
        }
        return implode(Headers::EOL, $strings);
    }
}
