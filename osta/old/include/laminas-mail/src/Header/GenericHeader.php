<?php

namespace Laminas\Mail\Header;

use Laminas\Mail\Header\Exception\InvalidArgumentException;
use Laminas\Mime\Mime;

use function count;
use function explode;
use function is_string;
use function ltrim;
use function str_replace;
use function strtoupper;
use function ucwords;

class GenericHeader implements HeaderInterface, UnstructuredInterface
{
    /** @var string */
    protected $fieldName;

    /** @var string */
    protected $fieldValue = '';

    /**
     * Header encoding
     *
     * @var null|string
     */
    protected $encoding;

    /**
     * @param string $headerLine
     * @return GenericHeader
     */
    public static function fromString($headerLine)
    {
        [$name, $value] = self::splitHeaderLine($headerLine);
        $value          = HeaderWrap::mimeDecodeValue($value);
        return new static($name, $value);
    }

    /**
     * Splits the header line in `name` and `value` parts.
     *
     * @param string $headerLine
     * @return string[] `name` in the first index and `value` in the second.
     * @throws InvalidArgumentException If header does not match with the format ``name:value``.
     */
    public static function splitHeaderLine($headerLine)
    {
        $parts = explode(':', $headerLine, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Header must match with the format "name:value"');
        }

        if (! HeaderName::isValid($parts[0])) {
            throw new InvalidArgumentException('Invalid header name detected');
        }

        if (! HeaderValue::isValid($parts[1])) {
            throw new InvalidArgumentException('Invalid header value detected');
        }

        $parts[1] = ltrim($parts[1]);

        return $parts;
    }

    /**
     * Constructor
     *
     * @param string $fieldName  Optional
     * @param null|string $fieldValue Optional
     */
    public function __construct($fieldName = null, $fieldValue = null)
    {
        if (! $fieldName) {
            throw new InvalidArgumentException('Header MUST contain a field name');
        }

        $this->setFieldName($fieldName);

        if ($fieldValue !== null) {
            $this->setFieldValue($fieldValue);
        }
    }

    /**
     * Set header name
     *
     * @param  string $fieldName
     * @return GenericHeader
     * @throws Exception\InvalidArgumentException;
     */
    public function setFieldName($fieldName)
    {
        if (! is_string($fieldName) || empty($fieldName)) {
            throw new InvalidArgumentException('Header name must be a string');
        }

        // Pre-filter to normalize valid characters, change underscore to dash
        $fieldName = str_replace(' ', '-', ucwords(str_replace(['_', '-'], ' ', $fieldName)));

        if (! HeaderName::isValid($fieldName)) {
            throw new InvalidArgumentException(
                'Header name must be composed of printable US-ASCII characters, except colon.'
            );
        }

        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set header value
     *
     * @param  string $fieldValue
     * @return GenericHeader
     * @throws Exception\InvalidArgumentException;
     */
    public function setFieldValue($fieldValue)
    {
        $fieldValue = (string) $fieldValue;

        if (! HeaderWrap::canBeEncoded($fieldValue)) {
            throw new InvalidArgumentException(
                'Header value must be composed of printable US-ASCII characters and valid folding sequences.'
            );
        }

        $this->fieldValue = $fieldValue;
        $this->encoding   = null;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        if (HeaderInterface::FORMAT_ENCODED === $format) {
            return HeaderWrap::wrap($this->fieldValue, $this);
        }

        return $this->fieldValue;
    }

    /**
     * @param string $encoding
     * @return self
     */
    public function setEncoding($encoding)
    {
        if ($encoding === $this->encoding) {
            return $this;
        }

        if ($encoding === null) {
            $this->encoding = null;
            return $this;
        }

        $encoding = strtoupper($encoding);
        if ($encoding === 'UTF-8') {
            $this->encoding = $encoding;
            return $this;
        }

        if ($encoding === 'ASCII' && Mime::isPrintable($this->fieldValue)) {
            $this->encoding = $encoding;
            return $this;
        }

        $this->encoding = null;

        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        if (! $this->encoding) {
            $this->encoding = Mime::isPrintable($this->fieldValue) ? 'ASCII' : 'UTF-8';
        }

        return $this->encoding;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $name = $this->getFieldName();
        if (empty($name)) {
            throw new Exception\RuntimeException('Header name is not set, use setFieldName()');
        }
        $value = $this->getFieldValue(HeaderInterface::FORMAT_ENCODED);

        return $name . ': ' . $value;
    }
}
