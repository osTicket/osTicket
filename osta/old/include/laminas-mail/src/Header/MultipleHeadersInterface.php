<?php

namespace Laminas\Mail\Header;

interface MultipleHeadersInterface extends HeaderInterface
{
    public function toStringMultipleHeaders(array $headers);
}
