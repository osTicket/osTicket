<?php

namespace Laminas\Mail\Header;

use function in_array;
use function preg_match;
use function strtolower;

class MimeVersion implements HeaderInterface
{
    /** @var string Version string */
    protected $version = '1.0';

    /**
     * @param string $headerLine
     * @return static
     */
    public static function fromString($headerLine)
    {
        [$name, $value] = GenericHeader::splitHeaderLine($headerLine);
        $value          = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (! in_array(strtolower($name), ['mimeversion', 'mime_version', 'mime-version'])) {
            throw new Exception\InvalidArgumentException('Invalid header line for MIME-Version string');
        }

        // Check for version, and set if found
        $header = new static();
        if (preg_match('/^(?P<version>\d+\.\d+)$/', $value, $matches)) {
            $header->setVersion($matches['version']);
        }

        return $header;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return 'MIME-Version';
    }

    /**
     * @inheritDoc
     */
    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        return $this->version;
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
        return 'MIME-Version: ' . $this->getFieldValue();
    }

    /**
     * Set the version string used in this header
     *
     * @param  string $version
     * @return MimeVersion
     */
    public function setVersion($version)
    {
        if (! preg_match('/^[1-9]\d*\.\d+$/', $version)) {
            throw new Exception\InvalidArgumentException('Invalid MIME-Version value detected');
        }
        $this->version = $version;
        return $this;
    }

    /**
     * Retrieve the version string for this header
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
