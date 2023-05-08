<?php

namespace Laminas\Mail\Header;

class InReplyTo extends IdentificationField
{
    /** @var string  */
    protected $fieldName = 'In-Reply-To';
    /** @var string  */
    protected static $type = 'in-reply-to';
}
