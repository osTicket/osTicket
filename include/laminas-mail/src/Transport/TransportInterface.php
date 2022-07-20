<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

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
     * @param \Laminas\Mail\Message $message
     * @return
     */
    public function send(Mail\Message $message);
}
