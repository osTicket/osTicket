<?php

namespace Laminas\Mail\Header;

class Cc extends AbstractAddressList
{
    /** @var string  */
    protected $fieldName = 'Cc';
    /** @var string  */
    protected static $type = 'cc';
}
