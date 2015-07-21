<?php
/*********************************************************************
    class.message.php

    Simple messages interface used to stash messages for display in a future
    request. Mainly useful for the post-redirect-get pattern.

    Usage:

    <?php Messages::success('It worked!!'); ?>

    // In a later request
    <?php
    foreach (Messages::getMessages() as $msg) {
        include 'path/to/message-template.tmp.php';
    }
    ?>

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2015 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

interface Message {
    function getTags();
    function getLevel();
    function __toString();
}

class Messages {
    const ERROR = 50;
    const WARNING = 40;
    const WARN = self::WARNING;
    const SUCCESS = 30;
    const INFO = 20;
    const DEBUG = 10;
    const NOTSET = 0;

    static $_levelNames = array(
        self::ERROR => 'ERROR',
        self::WARNING => 'WARNING',
        self::SUCCESS => 'SUCCESS',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG',
        self::NOTSET => 'NOTSET',
        'ERROR' => self::ERROR,
        'WARN' => self::WARNING,
        'WARNING' => self::WARNING,
        'SUCCESS' => self::SUCCESS,
        'INFO' => self::INFO,
        'DEBUG' => self::DEBUG,
        'NOTSET' => self::NOTSET,
    );

    static $messageClass = 'SimpleMessage';
    static $backend = 'SessionMessageStorage';

    static function debug($message) {
        static::addMessage(self::DEBUG, $message);
    }
    static function info($message) {
        static::addMessage(self::INFO, $message);
    }
    static function success($message) {
        static::addMessage(self::SUCCESS, $message);
    }
    static function warning($message) {
        static::addMessage(self::WARNING, $message);
    }
    static function error($message) {
        static::addMessage(self::ERROR, $message);
    }

    static function addMessage($level, $message) {
        $msg = new static::$messageClass($level, $message);
        $bk = static::getMessages();
        $bk->add($level, $msg);
    }

    static function getMessages() {
        static $messages;
        if (!isset($messages))
            $messages = new static::$backend();
        return $messages;
    }

    static function setMessageClass($class) {
        if (!is_subclass_of($class, 'Message'))
            throw new InvalidArgumentException('Class must extend Message');
        self::$messageClass = $class;
    }

    static function checkLevel($level) {
        if (is_int($level)) {
            $rv = $level;
        }
        elseif ((string) $level == $level) {
            if (!isset(static::$_levelNames[$level]))
                throw new InvalidArgumentException(
                    sprintf('Unknown level: %s', $level));
            $rv = static::$_levelNames[$level];
        }
        else {
            throw new InvalidArgumentException(
                sprintf('Level not an integer or a valid string: %s',
                    $level));
        }
        return $rv;
    }

    static function getLevelName($level) {
        return @self::$_levelNames[$level];
    }
}

class SimpleMessage implements Message {
    var $tags;
    var $level;
    var $msg;

    function __construct($level, $message, $extra_tags=array()) {
        $this->level = $level;
        $this->msg = $message;
        $this->tags = $extra_tags ?: null;
    }

    function getTags() {
        $tags = array_merge(
            array(strtolower(Messages::getLevelName($this->level))),
            $this->tags ?: array());
        return implode(' ', $tags);
    }

    function getLevel() {
        return Messages::getLevelName($this->level);
    }

    function __toString() {
        return $this->msg;
    }
}

interface MessageStorageBackend extends \IteratorAggregate {
    function setLevel($level);
    function getLevel();

    function update();
    function add($level, $message);
}

abstract class BaseMessageStorage implements MessageStorageBackend {
    var $level = Messages::NOTSET;
    var $queued = array();
    var $used = false;
    var $added_new = false;

    function isEnabledFor($level) {
        Messages::checkLevel($level);
        return $level >= $this->getLevel();
    }

    function setLevel($level) {
        Messages::checkLevel($level);
        $this->level = $level;
    }

    function getLevel() {
        return $this->level;
    }

    function load() {
        static $messages = false;

        if (!$messages) {
            $messages = new ListObject($this->get());
        }
        return $messages;
    }

    function getIterator() {
        $this->used = true;
        $messages = $this->load();
        if ($this->queued) {
            $messages->extend($this->queued);
            $this->queued = array();
        }
        if ($messages instanceof ListObject)
            return $messages->getIterator();
        else
            return new \ArrayIterator($messages);
    }

    function update() {
        if ($this->used) {
            return $this->store($this->queued);
        }
        else {
            $messages = $this->load();
            $messages->extend($this->queued);
            return $this->store($messages);
        }
    }

    function add($level, $message) {
        if (!$message)
            return;
        elseif (!$this->isEnabledFor($level))
            return;

        $this->added_new = true;
        $this->queued[] = $message;
    }

    abstract function get();
    abstract function store($messages);
}

class SessionMessageStorage extends BaseMessageStorage {
    var $list;

    function __construct() {
        $this->list = @$_SESSION[':msgs'] ?: array();
        // Since no middleware exists in this framework, register a
        // pre-shutdown hook
        $self = $this;
        Signal::connect('session.close', function($null, $info) use ($self) {
            // Whether or not the session data should be re-encoded to
            // reflect changes made in this routine
            $info['touched'] = $self->added_new || ($self->used && count($self->list));
            $self->update();
        });
    }

    function get() {
        return $this->list;
    }

    function store($messages) {
        $_SESSION[':msgs'] = $messages;
        return array();
    }
}
