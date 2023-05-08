<?php

namespace Laminas\Mail\Header;

use Laminas\Loader\PluginClassLoader;

/**
 * Plugin Class Loader implementation for HTTP headers
 */
class HeaderLoader extends PluginClassLoader
{
    /** @var array Pre-aliased Header plugins */
    protected $plugins = [
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
}
