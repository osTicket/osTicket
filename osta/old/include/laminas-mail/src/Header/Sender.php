<?php

namespace Laminas\Mail\Header;

use Laminas\Mail;
use Laminas\Mail\Address\AddressInterface;
use Laminas\Mime\Mime;

use function gettype;
use function is_object;
use function is_string;
use function preg_match;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Sender header class methods.
 *
 * @see https://tools.ietf.org/html/rfc2822 RFC 2822
 * @see https://tools.ietf.org/html/rfc2047 RFC 2047
 */
class Sender implements HeaderInterface
{
    /** @var AddressInterface */
    protected $address;

    /**
     * Header encoding
     *
     * @var null|string
     */
    protected $encoding;

    /**
     * @param string $headerLine
     * @return static
     */
    public static function fromString($headerLine)
    {
        [$name, $value] = GenericHeader::splitHeaderLine($headerLine);
        $value          = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'sender') {
            throw new Exception\InvalidArgumentException('Invalid header name for Sender string');
        }

        $header = new static();

        /**
         * matches the header value so that the email must be enclosed by < > when a name is present
         * 'name' and 'email' capture groups correspond respectively to 'display-name' and 'addr-spec' in the ABNF
         *
         * @see https://tools.ietf.org/html/rfc5322#section-3.4
         */
        $hasMatches = preg_match(
            '/^(?:(?P<name>.+)\s)?(?(name)<|<?)(?P<email>[^\s]+?)(?(name)>|>?)$/',
            $value,
            $matches
        );

        if ($hasMatches !== 1) {
            throw new Exception\InvalidArgumentException('Invalid header value for Sender string');
        }

        $senderName = trim($matches['name']);

        if (empty($senderName)) {
            $senderName = null;
        }

        $header->setAddress($matches['email'], $senderName);

        return $header;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return 'Sender';
    }

    /**
     * @inheritDoc
     */
    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        if (! $this->address instanceof Mail\Address\AddressInterface) {
            return '';
        }

        $email = sprintf('<%s>', $this->address->getEmail());
        $name  = $this->address->getName();

        if (! empty($name)) {
            if ($format == HeaderInterface::FORMAT_ENCODED) {
                $encoding = $this->getEncoding();
                if ('ASCII' !== $encoding) {
                    $name = HeaderWrap::mimeEncodeValue($name, $encoding);
                }
            }
            $email = sprintf('%s %s', $name, $email);
        }

        return $email;
    }

    /**
     * @param string $encoding
     * @return self
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        if (! $this->encoding) {
            $this->encoding = Mime::isPrintable($this->getFieldValue(HeaderInterface::FORMAT_RAW))
                ? 'ASCII'
                : 'UTF-8';
        }

        return $this->encoding;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return 'Sender: ' . $this->getFieldValue(HeaderInterface::FORMAT_ENCODED);
    }

    /**
     * Set the address used in this header
     *
     * @param string|AddressInterface $emailOrAddress
     * @param  null|string $name
     * @throws Exception\InvalidArgumentException
     * @return Sender
     */
    public function setAddress($emailOrAddress, $name = null)
    {
        if (is_string($emailOrAddress)) {
            $emailOrAddress = new Mail\Address($emailOrAddress, $name);
        } elseif (! $emailOrAddress instanceof Mail\Address\AddressInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string or AddressInterface object; received "%s"',
                __METHOD__,
                is_object($emailOrAddress) ? $emailOrAddress::class : gettype($emailOrAddress)
            ));
        }
        $this->address = $emailOrAddress;
        return $this;
    }

    /**
     * Retrieve the internal address from this header
     *
     * @return AddressInterface|null
     */
    public function getAddress()
    {
        return $this->address;
    }
}
