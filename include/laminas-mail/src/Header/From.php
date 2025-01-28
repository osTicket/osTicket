<?php

namespace Laminas\Mail\Header;

class From extends AbstractAddressList
{
    /** @var string  */
    protected $fieldName = 'From';
    /** @var string  */
    protected static $type = 'from';
}
