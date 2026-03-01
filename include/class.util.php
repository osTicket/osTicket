<?php

require_once INCLUDE_DIR . 'class.variable.php';

// Used by the email system
interface EmailContact {
    function getId();
    function getUserId();
    function getName();
    function getEmail();
}


class EmailRecipient
implements EmailContact {
    protected $contact;
    protected $type;

    function __construct(EmailContact $contact, $type='to') {
        $this->contact = $contact;
        $this->type = $type;
    }

    function getContact() {
        return $this->contact;
    }

    function getId() {
        return $this->contact->getId();
    }

    function getUserId() {
        return $this->contact->getUserId();
    }

    function getEmail() {
        return $this->contact->getEmail();
    }

    function getName() {
        return $this->contact->getName();
    }

    function getType() {
        return $this->type;
    }
}

abstract class BaseList
implements IteratorAggregate, Countable {
    protected $storage = array();

    /**
     * Sort the list in place.
     *
     * Parameters:
     * $key - (callable|int) A callable function to produce the sort keys
     *      or one of the SORT_ constants used by the array_multisort
     *      function
     * $reverse - (bool) true if the list should be sorted descending
     */
    function sort($key=false, $reverse=false) {
        if (is_callable($key)) {
            $keys = array_map($key, $this->storage);
            array_multisort($keys, $this->storage,
                $reverse ? SORT_DESC : SORT_ASC);
        }
        elseif ($key) {
            array_multisort($this->storage,
                $reverse ? SORT_DESC : SORT_ASC, $key);
        }
        elseif ($reverse) {
            rsort($this->storage);
        }
        else
            sort($this->storage);
    }

    function reverse() {
        return array_reverse($this->storage);
    }

    // IteratorAggregate
    function getIterator() {
        return new ArrayIterator($this->storage);
    }

    // Countable
    function count($mode=COUNT_NORMAL) {
        return count($this->storage, $mode);
    }

    function __toString() {
        return '['.implode(', ', $this->storage).']';
    }
}

/**
 * Jared Hancock <jared@osticket.com>
 * Copyright (c)  2014
 *
 * Lightweight implementation of the Python list in PHP. This allows for
 * treating an array like a simple list of items. The numeric indexes are
 * automatically updated so that the indeces of the list will alway be from
 * zero and increasing positively.
 *
 * Negative indexes are supported which reference from the end of the list.
 * Therefore $queue[-1] will refer to the last item in the list.
 */
class ListObject
extends BaseList
implements ArrayAccess, Serializable {

    function __construct($array=array()) {
        if (!is_array($array) && !$array instanceof Traversable)
            throw new InvalidArgumentException('Traversable object or array expected');
        foreach ($array as $v)
            $this->storage[] = $v;
    }

    function append($what) {
        if (is_array($what))
            return $this->extend($what);

        $this->storage[] = $what;
    }

    function add($what) {
        $this->append($what);
    }

    function extend($iterable) {
        foreach ($iterable as $v)
            $this->storage[] = $v;
    }

    function insert($i, $value) {
        if ($i < 0)
            $i += count($this->storage) + 1;
        array_splice($this->storage, $i, 0, array($value));
    }

    function remove($value) {
        if (!($k = $this->index($value)))
            throw new OutOfRangeException('No such item in the list');
        unset($this->storage[$k]);
    }

    function pop($at=false) {
        if ($at === false)
            return array_pop($this->storage);
        elseif (!isset($this->storage[$at]))
            throw new OutOfRangeException('Index out of range');
        else {
            $rv = array_splice($this->storage, $at, 1);
            return $rv[0];
        }
    }

    function slice($offset, $length=null) {
        return array_slice($this->storage, $offset, $length);
    }

    function splice($offset, $length=0, $replacement=null) {
        return array_splice($this->storage, $offset, $length, $replacement);
    }

    function index($value) {
        return array_search($this->storage, $value);
    }

    function filter($callable) {
        $new = new static();
        foreach ($this->storage as $i=>$v)
            if ($callable($v, $i))
                $new[] = $v;
        return $new;
    }

    // ArrayAccess
    function offsetGet($offset) {
        if (!is_int($offset))
            throw new InvalidArgumentException('List indices should be integers');
        elseif ($offset < 0)
            $offset += count($this->storage);
        if (!isset($this->storage[$offset]))
            throw new OutOfBoundsException('List index out of range');
        return $this->storage[$offset];
    }
    function offsetSet($offset, $value) {
        if ($offset === null)
            return $this->storage[] = $value;
        elseif (!is_int($offset))
            throw new InvalidArgumentException('List indices should be integers');
        elseif ($offset < 0)
            $offset += count($this->storage);

        if (!isset($this->storage[$offset]))
            throw new OutOfBoundsException('List assignment out of range');

        $this->storage[$offset] = $value;
    }
    function offsetExists($offset) {
        if (!is_int($offset))
            throw new InvalidArgumentException('List indices should be integers');
        elseif ($offset < 0)
            $offset += count($this->storage);
        return isset($this->storage[$offset]);
    }
    function offsetUnset($offset) {
        if (!is_int($offset))
            throw new InvalidArgumentException('List indices should be integers');
        elseif ($offset < 0)
            $offset += count($this->storage);
        unset($this->storage[$offset]);
    }

    // Serializable
    function serialize() {
        return serialize($this->storage);
    }
    function unserialize($what) {
        $this->storage = unserialize($what);
    }
}

class MailingList extends ListObject
implements TemplateVariable {

    function add($recipient) {
        if (!$recipient instanceof EmailRecipient)
            throw new InvalidArgumentException('Email Recipient expected');

        return parent::add($recipient);
    }

    function addRecipient($contact, $to='to') {
        return $this->add(new EmailRecipient($contact, $to));
    }

    function addTo(EmailContact $contact) {
        return $this->addRecipient($contact, 'to');
    }

    function addCc(EmailContact $contact) {
        return $this->addRecipient($contact, 'cc');
    }

    function addBcc(EmailContact $contact) {
        return $this->addRecipient($contact, 'bcc');
    }

    function __toString() {
        return $this->getNames();
    }

    // Recipients' email addresses
    function getEmailAddresses() {
        $list = array();
        foreach ($this->storage as $u) {
            $list[$u->getType()][$u->getId()] = sprintf("%s <%s>",
                    $u->getName(), $u->getEmail());
        }
        return $list;
    }

    function getNames() {
        $list = array();
        foreach($this->storage as $user) {
            if (is_object($user))
                $list [] = $user->getName();
        }
        return $list ? implode(', ', $list) : '';
    }

    function getFull() {
        $list = array();
        foreach($this->storage as $user) {
            if (is_object($user))
                $list[] = sprintf("%s <%s>", $user->getName(), $user->getEmail());
        }

        return $list ? implode(', ', $list) : '';
    }

    function getEmails() {
        $list = array();
        foreach($this->storage as $user) {
            if (is_object($user))
                $list[] = $user->getEmail();
        }
        return $list ? implode(', ', $list) : '';
    }

    static function getVarScope() {
        return array(
            'names' => __('List of names'),
            'emails' => __('List of email addresses'),
            'full' => __('List of names and email addresses'),
        );
    }
}
