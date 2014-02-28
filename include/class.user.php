<?php
/*********************************************************************
    class.user.php

    External end-user identification for osTicket

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once(INCLUDE_DIR . 'class.orm.php');

class UserEmailModel extends VerySimpleModel {
    static $meta = array(
        'table' => USER_EMAIL_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'user' => array(
                'constraint' => array('user_id' => 'UserModel.id')
            )
        )
    );
}

class UserModel extends VerySimpleModel {
    static $meta = array(
        'table' => USER_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'emails' => array(
                'reverse' => 'UserEmailModel.user',
            ),
            'default_email' => array(
                'null' => true,
                'constraint' => array('default_email_id' => 'UserEmailModel.id')
            ),
        )
    );

    var $emails;

    static function objects() {
        $qs = parent::objects();
        #$qs->select_related('default_email');
        return $qs;
    }

    function getId() {
        return $this->id;
    }

    function getDefaultEmailAddress() {
        return $this->getDefaultEmail()->address;
    }

    function getDefaultEmail() {
        return $this->default_email;
    }
}

class User extends UserModel {

    var $_entries;
    var $_forms;

    function __construct($ht) {
        parent::__construct($ht);
        // TODO: Make this automatic with select_related()
        if (isset($ht['default_email_id']))
            $this->default_email = UserEmail::lookup($ht['default_email_id']);
    }

    static function fromVars($vars) {
        // Try and lookup by email address
        $user = User::lookup(array('emails__address'=>$vars['email']));
        if (!$user) {
            $user = User::create(array(
                'name'=>$vars['name'],
                'created'=>new SqlFunction('NOW'),
                'updated'=>new SqlFunction('NOW'),
                //XXX: Do plain create once the cause
                // of the detached emails is fixed.
                'default_email' => UserEmail::ensure($vars['email'])
            ));
            $user->save(true);
            $user->emails->add($user->default_email);
            // Attach initial custom fields
            $user->addDynamicData($vars);
        }

        return $user;
    }

    static function fromForm($form) {

        if(!$form) return null;

        //Validate the form
        $valid = true;
        if (!$form->isValid())
            $valid  = false;

        //Make sure the email is not in-use
        if (($field=$form->getField('email'))
                && $field->getClean()
                && User::lookup(array('emails__address'=>$field->getClean()))) {
            $field->addError('Email is assigned to another user');
            $valid = false;
        }

        return $valid ? self::fromVars($form->getClean()) : null;
    }

    function getEmail() {
        return $this->default_email->address;
    }

    function getFullName() {
        return $this->name;
    }

    function getPhoneNumber() {
        foreach ($this->getDynamicData() as $e)
            if ($a = $e->getAnswer('phone'))
                return $a;
    }

    function getName() {
        return new PersonsName($this->name);
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function getCreateDate() {
        return $this->created;
    }

    function to_json() {

        $info = array(
                'id'  => $this->getId(),
                'name' => (string) $this->getName(),
                'email' => (string) $this->getEmail(),
                'phone' => (string) $this->getPhoneNumber());

        return JsonDataEncoder::encode($info);
    }

    function asVar() {
        return (string) $this->getName();
    }

    function getVar($tag) {
        if($tag && is_callable(array($this, 'get'.ucfirst($tag))))
            return call_user_func(array($this, 'get'.ucfirst($tag)));

        $tag = strtolower($tag);
        foreach ($this->getDynamicData() as $e)
            if ($a = $e->getAnswer($tag))
                return $a;
    }

    function addDynamicData($data) {

        $uf = UserForm::getInstance();
        $uf->setClientId($this->id);
        foreach ($uf->getFields() as $f)
            if (isset($data[$f->get('name')]))
                $uf->setAnswer($f->get('name'), $data[$f->get('name')]);
        $uf->save();

        return $uf;
    }

    function getDynamicData() {
        if (!isset($this->_entries)) {
            $this->_entries = DynamicFormEntry::forClient($this->id)->all();
            if (!$this->_entries) {
                $g = UserForm::getInstance();
                $g->setClientId($this->id);
                $this->_entries[] = $g;
            }
        }
        return $this->_entries;
    }

    function getForms($data=null) {

        if (!isset($this->_forms)) {
            $this->_forms = array();
            foreach ($this->getDynamicData() as $cd) {
                $cd->addMissingFields();
                if(!$data
                        && ($form = $cd->getForm())
                        && $form->get('type') == 'U' ) {
                    foreach ($cd->getFields() as $f) {
                        if ($f->get('name') == 'name')
                            $f->value = $this->getFullName();
                        elseif ($f->get('name') == 'email')
                            $f->value = $this->getEmail();
                    }
                }

                $this->_forms[] = $cd->getForm();
            }
        }

        return $this->_forms;
    }

    function updateInfo($vars, &$errors) {

        $valid = true;
        $forms = $this->getForms($vars);
        foreach ($forms as $cd) {
            if (!$cd->isValid())
                $valid = false;
            if ($cd->get('type') == 'U'
                        && ($form= $cd->getForm($vars))
                        && ($f=$form->getField('email'))
                        && $f->getClean()
                        && ($u=User::lookup(array('emails__address'=>$f->getClean())))
                        && $u->id != $this->getId()) {
                $valid = false;
                $f->addError('Email is assigned to another user');
            }
        }

        if (!$valid)
            return false;

        foreach ($this->getDynamicData() as $cd) {
            if (($f=$cd->getForm()) && $f->get('type') == 'U') {
                if (($name = $f->getField('name'))) {
                    $this->name = $name->getClean();
                    $this->save();
                }

                if (($email = $f->getField('email'))) {
                    $this->default_email->address = $email->getClean();
                    $this->default_email->save();
                }
            }
            $cd->save();
        }

        return true;
    }

    function save($refetch=false) {
        // Drop commas and reorganize the name without them
        $parts = array_map('trim', explode(',', $this->name));
        switch (count($parts)) {
            case 2:
                // Assume last, first --or-- last suff., first
                $this->name = $parts[1].' '.$parts[0];
                // XXX: Consider last, first suff.
                break;
            case 3:
                // Assume last, first, suffix, write 'first last suffix'
                $this->name = $parts[1].' '.$parts[0].' '.$parts[2];
                break;
        }

        // Handle email addresses -- use the box name
        if (Validator::is_email($this->name)) {
            list($box, $domain) = explode('@', $this->name, 2);
            if (strpos($box, '.') !== false)
                $this->name = str_replace('.', ' ', $box);
            else
                $this->name = $box;
            $this->name = mb_convert_case($this->name, MB_CASE_TITLE);
        }

        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        return parent::save($refetch);
    }
}
User::_inspect();

class PersonsName {
    var $parts;
    var $name;

    static $formats = array(
        'first' => array("First", 'getFirst'),
        'last' => array("Last", 'getLast'),
        'full' => array("First Last", 'getFull'),
        'legal' => array("First M. Last", 'getLegal'),
        'lastfirst' => array("Last, First", 'getLastFirst'),
        'formal' => array("Mr. Last", 'getFormal'),
        'short' => array("First L.", 'getShort'),
        'shortformal' => array("F. Last", 'getShortFormal'),
        'complete' => array("Mr. First M. Last Sr.", 'getComplete'),
        'original' => array('-- As Entered --', 'getOriginal'),
    );

    function __construct($name) {
        $this->parts = static::splitName($name);
        $this->name = $name;
    }

    function getFirst() {
        return $this->parts['first'];
    }

    function getLast() {
        return $this->parts['last'];
    }

    function getMiddle() {
        return $this->parts['middle'];
    }

    function getMiddleInitial() {
        return mb_substr($this->parts['middle'],0,1).'.';
    }

    function getFormal() {
        return trim($this->parts['salutation'].' '.$this->parts['last']);
    }

    function getFull() {
        return trim($this->parts['first'].' '.$this->parts['last']);
    }

    function getLegal() {
        $parts = array(
            $this->parts['first'],
            mb_substr($this->parts['middle'],0,1),
            $this->parts['last'],
        );
        if ($parts[1]) $parts[1] .= '.';
        return implode(' ', array_filter($parts));
    }

    function getComplete() {
        $parts = array(
            $this->parts['salutation'],
            $this->parts['first'],
            mb_substr($this->parts['middle'],0,1),
            $this->parts['last'],
            $this->parts['suffix']
        );
        if ($parts[2]) $parts[2] .= '.';
        return implode(' ', array_filter($parts));
    }

    function getLastFirst() {
        $name = $this->parts['last'].', '.$this->parts['first'];
        if ($this->parts['suffix'])
            $name .= ', '.$this->parts['suffix'];
        return $name;
    }

    function getShort() {
        return $this->parts['first'].' '.mb_substr($this->parts['last'],0,1).'.';
    }

    function getShortFormal() {
        return mb_substr($this->parts['first'],0,1).'. '.$this->parts['last'];
    }

    function getOriginal() {
        return $this->name;
    }

    function getInitials() {
        $names = array($this->parts['first']);
        $names = array_merge($names, explode(' ', $this->parts['middle']));
        $names[] = $this->parts['last'];
        $initials = '';
        foreach (array_filter($names) as $n)
            $initials .= mb_substr($n,0,1);
        return mb_convert_case($initials, MB_CASE_UPPER);
    }

    function getName() {
        return $this;
    }

    function asVar() {
        return $this->__toString();
    }

    function __toString() {
        global $cfg;
        $format = $cfg->getDefaultNameFormat();
        list(,$func) = static::$formats[$format];
        if (!$func) $func = 'getFull';
        return call_user_func(array($this, $func));
    }

    static function allFormats() {
        return static::$formats;
    }

    /**
     * Thanks, http://stackoverflow.com/a/14420217
     */
    static function splitName($name) {
        $results = array();

        $r = explode(' ', $name);
        $size = count($r);

        //check first for period, assume salutation if so
        if (mb_strpos($r[0], '.') === false)
        {
            $results['salutation'] = '';
            $results['first'] = $r[0];
        }
        else
        {
            $results['salutation'] = $r[0];
            $results['first'] = $r[1];
        }

        //check last for period, assume suffix if so
        if (mb_strpos($r[$size - 1], '.') === false)
        {
            $results['suffix'] = '';
        }
        else
        {
            $results['suffix'] = $r[$size - 1];
        }

        //combine remains into last
        $start = ($results['salutation']) ? 2 : 1;
        $end = ($results['suffix']) ? $size - 2 : $size - 1;

        $middle = array();
        for ($i = $start; $i <= $end; $i++)
        {
            $middle[] = $r[$i];
        }
        if (count($middle) > 1) {
            $results['last'] = array_pop($middle);
            $results['middle'] = implode(' ', $middle);
        }
        else {
            $results['last'] = $middle[0];
            $results['middle'] = '';
        }

        return $results;
    }

}

class UserEmail extends UserEmailModel {
    static function ensure($address) {
        $email = static::lookup(array('address'=>$address));
        if (!$email) {
            $email = static::create(array('address'=>$address));
            $email->save();
        }
        return $email;
    }
}


/*
 *  Generic user list.
 */
class UserList implements  IteratorAggregate, ArrayAccess {
    private $users;

    function __construct($list = array()) {
        $this->users = $list;
    }

    function add($user) {
        $this->offsetSet(null, $user);
    }

    function offsetSet($offset, $value) {

        if (is_null($offset))
            $this->users[] = $value;
        else
            $this->users[$offset] = $value;
    }

    function offsetExists($offset) {
        return isset($this->users[$offset]);
    }

    function offsetUnset($offset) {
        unset($this->users[$offset]);
    }

    function offsetGet($offset) {
        return isset($this->users[$offset]) ? $this->users[$offset] : null;
    }

    function getIterator() {
        return new ArrayIterator($this->users);
    }

    function __toString() {

        $list = array();
        foreach($this->users as $user) {
            if (is_object($user))
                $list [] = $user->getName();
        }

        return $list ? implode(', ', $list) : '';
    }
}

?>
