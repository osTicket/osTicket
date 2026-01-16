<?php

namespace Laminas\Mail\Transport;

use Laminas\Mail\Message;

/**
 * InMemory transport
 *
 * This transport will just store the message in memory.  It is helpful
 * when unit testing, or to prevent sending email when in development or
 * testing.
 */
class InMemory implements TransportInterface
{
    /** @var null|Message */
    protected $lastMessage;

    /**
     * Takes the last message and saves it for testing.
     */
    public function send(Message $message)
    {
        $this->lastMessage = $message;
    }

    /**
     * Get the last message sent.
     *
     * @return null|Message
     */
    public function getLastMessage()
    {
        return $this->lastMessage;
    }
}
