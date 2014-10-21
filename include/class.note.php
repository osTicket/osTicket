<?php
/*********************************************************************
    class.note.php

    Simple note interface for affixing notes to users and organizations

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once(INCLUDE_DIR . 'class.orm.php');

class QuickNoteModel extends VerySimpleModel {
    static $meta = array(
        'table' => NOTE_TABLE,
        'pk' => array('id'),
        'ordering' => array('sort', 'created')
    );
}

class QuickNote extends QuickNoteModel {

    static $types = array(
        'U' => /* @trans */ 'User',
        'O' => /* @trans */ 'Organization',
    );
    var $_staff;

    function display() {
        return Format::display($this->body);
    }

    function getStaff() {
        if (!isset($this->_staff) && $this->staff_id) {
            $this->_staff = Staff::lookup($this->staff_id);
        }
        return $this->_staff;
    }

    function getFormattedTime() {
        return Format::db_datetime(strpos($this->updated, '0000-') !== 0
            ? $this->updated : $this->created);
    }

    function getExtType() {
        return static::$types[$this->ext_id[0]];
    }

    function getExtIconClass() {
        switch ($this->ext_id[0]) {
        case 'U':
            return 'user';
        case 'O':
            return 'building';
        }
    }

    function getIconTitle() {
        return sprintf(__(
            // `%s` will be the type of note (`user` or `orgnaization`)
            "%s Note"),
            __(static::$types[$this->ext_id[0]]));
    }

    static function forUser($user, $org=false) {
        if ($org)
            return static::objects()->filter(array('ext_id__in' =>
                array('U'.$user->get('id'), 'O'.$org->get('id'))));
        else
            return static::objects()->filter(array('ext_id' => 'U'.$user->get('id')));
    }

    static function forOrganization($org) {
        return static::objects()->filter(array('ext_id' => 'O'.$org->get('id')));
    }

    function save($refetch=false) {
        if (count($this->dirty))
            $this->updated = new SQLFunction('NOW');
        return parent::save($refetch);
    }
}
