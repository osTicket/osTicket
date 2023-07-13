<?php

declare(strict_types=1);

namespace Laminas\Mail\Header;

use function strtolower;

/**
 * Plugin Class Loader implementation for HTTP headers
 */
final class HeaderLocator implements HeaderLocatorInterface
{
    /** @var array Pre-aliased Header plugins */
    private array $plugins = [
        'bcc'                       => Bcc::class,
        'cc'                        => Cc::class,
        'contentdisposition'        => ContentDisposition::class,
        'content_disposition'       => ContentDisposition::class,
        'content-disposition'       => ContentDisposition::class,
        'contenttype'               => ContentType::class,
        'content_type'              => ContentType::class,
        'content-type'              => ContentType::class,
        'contenttransferencoding'   => ContentTransferEncoding::class,
        'content_transfer_encoding' => ContentTransferEncoding::class,
        'content-transfer-encoding' => ContentTransferEncoding::class,
        'date'                      => Date::class,
        'from'                      => From::class,
        'in-reply-to'               => InReplyTo::class,
        'message-id'                => MessageId::class,
        'mimeversion'               => MimeVersion::class,
        'mime_version'              => MimeVersion::class,
        'mime-version'              => MimeVersion::class,
        'received'                  => Received::class,
        'references'                => References::class,
        'replyto'                   => ReplyTo::class,
        'reply_to'                  => ReplyTo::class,
        'reply-to'                  => ReplyTo::class,
        'sender'                    => Sender::class,
        'subject'                   => Subject::class,
        'to'                        => To::class,
    ];

    public function get(string $name, ?string $default = null): ?string
    {
        $name = $this->normalizeName($name);
        return $this->plugins[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return isset($this->plugins[$this->normalizeName($name)]);
    }

    public function add(string $name, string $class): void
    {
        $this->plugins[$this->normalizeName($name)] = $class;
    }

    public function remove(string $name): void
    {
        unset($this->plugins[$this->normalizeName($name)]);
    }

    private function normalizeName(string $name): string
    {
        return strtolower($name);
    }
}
