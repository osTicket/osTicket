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

    function __construct($ht) {
        parent::__construct($ht);
        // TODO: Make this automatic with select_related()
        if (isset($ht['default_email_id']))
            $this->default_email = UserEmail::lookup($ht['default_email_id']);
    }

    static function fromForm($data=false) {
        // Try and lookup by email address
        $user = User::lookup(array('emails__address'=>$data['email']));
        if (!$user) {
            $user = User::create(array('name'=>$data['name'],
                'default_email'=>
                UserEmail::create(array('address'=>$data['email']))
            ));
            $user->save();
            $user->emails->add($user->default_email);
        }

        return $user;
    }

    function getEmail() {
        return $this->default_email->address;
    }

    function getFullName() {
        return $this->name;
    }

    function getName() {
        return new PersonsName($this->name);
    }

    function getDynamicData() {
        $data = DynamicFormEntry::forClient($this->id);
        if (!$data[0]) {
            $data = array();
            foreach (UserForm::objects() as $f) {
                $g = $f->instanciate();
                $g->setClientId($this->id);
                $data[] = $g;
            }
        }
        return $data;
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
        return parent::save($refetch);
    }
}
User::_inspect();

class PersonsName {
    var $parts;
    var $name;

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

    function getFormal() {
        return trim($this->parts['salutation'].' '.$this->parts['last']);
    }

    function getFull() {
        return trim($this->parts['first'].' '.$this->parts['last']);
    }

    function getComplete() {
        return trim($this->parts['salutation'].' '.$this->parts['first']
            .' '.$this->parts['last'].' '.$this->parts['suffix']);
    }

    function getLastFirst() {
        $name = $this->parts['last'].', '.$this->parts['first'];
        if ($this->parts['suffix'])
            $name .= ', '.$this->parts['suffix'];
        return $name;
    }

    function __toString() {
        return $this->getLastFirst();
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

        $last = '';
        for ($i = $start; $i <= $end; $i++)
        {
            $last .= ' '.$r[$i];
        }
        $results['last'] = trim($last);

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
