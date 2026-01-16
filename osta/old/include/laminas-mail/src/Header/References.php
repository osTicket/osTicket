<?php

namespace Laminas\Mail\Header;

class References extends IdentificationField
{
    /** @var string  */
    protected $fieldName = 'References';
    /** @var string  */
    protected static $type = 'references';
}
