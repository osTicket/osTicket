<?php

declare(strict_types=1);

namespace Laminas\Mail\Protocol\Pop3;

/**
 * POP3 response value object
 *
 * @internal
 */
final class Response
{
    /** @var string $status */
    private $status;

    /** @var string $message */
    private $message;

    public function __construct(string $status, string $message)
    {
        $this->status  = $status;
        $this->message = $message;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function message(): string
    {
        return $this->message;
    }
}
