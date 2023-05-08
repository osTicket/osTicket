<?php

namespace Laminas\Mail\Storage\Message;

use Laminas\Mail\Storage\Exception\ExceptionInterface;
use Laminas\Mail\Storage\Part;

use function array_combine;

class File extends Part\File implements MessageInterface
{
    /**
     * flags for this message
     *
     * @var array
     */
    protected $flags = [];

    /**
     * Public constructor
     *
     * In addition to the parameters of Laminas\Mail\Storage\Part::__construct() this constructor supports:
     * - flags array with flags for message, keys are ignored, use constants defined in Laminas\Mail\Storage
     *
     * @param  array $params
     * @throws ExceptionInterface
     */
    public function __construct(array $params)
    {
        if (! empty($params['flags'])) {
            // set key and value to the same value for easy lookup
            $this->flags = array_combine($params['flags'], $params['flags']);
        }

        parent::__construct($params);
    }

    /**
     * return toplines as found after headers
     *
     * @return string toplines
     */
    public function getTopLines()
    {
        return $this->topLines;
    }

    /**
     * check if flag is set
     *
     * @param mixed $flag a flag name, use constants defined in \Laminas\Mail\Storage
     * @return bool true if set, otherwise false
     */
    public function hasFlag($flag)
    {
        return isset($this->flags[$flag]);
    }

    /**
     * get all set flags
     *
     * @return array array with flags, key and value are the same for easy lookup
     */
    public function getFlags()
    {
        return $this->flags;
    }
}
