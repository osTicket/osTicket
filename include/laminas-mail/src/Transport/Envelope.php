<?php

namespace Laminas\Mail\Transport;

use Laminas\Stdlib\AbstractOptions;

/**
 * @extends AbstractOptions<string|list<string>>
 */
class Envelope extends AbstractOptions
{
    /** @var string|null */
    protected $from;

    /** @var string|null */
    protected $to;

    /**
     * Get MAIL FROM
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set MAIL FROM
     *
     * @param  string $from
     */
    public function setFrom($from)
    {
        $this->from = (string) $from;
    }

    /**
     * Get RCPT TO
     *
     * @return string|null
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set RCPT TO
     *
     * @param  string $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }
}
