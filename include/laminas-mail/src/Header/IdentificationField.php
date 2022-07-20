<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mail\Header;

use Laminas\Mail\Headers;

/**
 * @see https://tools.ietf.org/html/rfc5322#section-3.6.4
 */
abstract class IdentificationField implements HeaderInterface
{
    /**
     * @var string lower case field name
     */
    protected static $type;

    /**
     * @var string[]
     */
    protected $messageIds;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @param string $headerLine
     * @return static
     */
    public static function fromString($headerLine)
    {
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
        if (strtolower($name) !== static::$type) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid header line for "%s" string',
                __CLASS__
            ));
        }

        $value = HeaderWrap::mimeDecodeValue($value);

        $messageIds = array_map(
            [IdentificationField::class, "trimMessageId"],
            explode(" ", $value)
        );

        $header = new static();
        $header->setIds($messageIds);

        return $header;
    }

    /**
     * @param string $id
     * @return string
     */
    private static function trimMessageId($id)
    {
        return trim($id, "\t\n\r\0\x0B<>");
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param bool $format
     * @return string
     */
    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        return implode(Headers::FOLDING, array_map(function ($id) {
            return sprintf('<%s>', $id);
        }, $this->messageIds));
    }

    /**
     * @param string $encoding Ignored; headers of this type MUST always be in
     *     ASCII.
     * @return static This method is a no-op, and implements a fluent interface.
     */
    public function setEncoding($encoding)
    {
        return $this;
    }

    /**
     * @return string Always returns ASCII
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
        return sprintf('%s: %s', $this->getFieldName(), $this->getFieldValue());
    }

    /**
     * Set the message ids
     *
     * @param string[] $ids
     * @return static This method implements a fluent interface.
     */
    public function setIds($ids)
    {
        foreach ($ids as $id) {
            if (! HeaderValue::isValid($id)
                || preg_match("/[\r\n]/", $id)
            ) {
                throw new Exception\InvalidArgumentException('Invalid ID detected');
            }
        }

        $this->messageIds = array_map([IdentificationField::class, "trimMessageId"], $ids);
        return $this;
    }

    /**
     * Retrieve the message ids
     *
     * @return string[]
     */
    public function getIds()
    {
        return $this->messageIds;
    }
}
