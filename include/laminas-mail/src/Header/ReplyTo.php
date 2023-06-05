<?php

namespace Laminas\Mail\Header;

class ReplyTo extends AbstractAddressList
{
    /** @var string  */
    protected $fieldName = 'Reply-To';
    /** @var string  */
    protected static $type = 'reply-to';
    /** @var string[] */
    protected static $typeAliases = ['replyto', 'reply_to'];
}
