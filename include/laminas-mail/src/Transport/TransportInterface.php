<?php

namespace Laminas\Mail\Transport;

use Laminas\Mail;

/**
 * Interface for mail transports
 */
interface TransportInterface
{
    /**
     * Send a mail message
     *
     * @return void
     */
    public function send(Mail\Message $message);
}
