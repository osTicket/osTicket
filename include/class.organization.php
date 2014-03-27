<?php
/*********************************************************************
    class.organization.php

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once(INCLUDE_DIR . 'class.orm.php');

class OrganizationModel extends VerySimpleModel {
    static $meta = array(
        'table' => ORGANIZATION_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'users' => array(
                'reverse' => 'UserAccountModel.org',
            ),
        )
    );

    var $users;

    static function objects() {
        $qs = parent::objects();

        return $qs;
    }

    function getId() {
        return $this->id;
    }
}

class Organization extends OrganizationModel {

    function __construct($ht) {
        parent::__construct($ht);
    }

    //XXX: Shouldn't getName use magic get method to figure this out?
    function getName() {
        return $this->name;
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
                'name' => (string) $this->getName()
                );

        return JsonDataEncoder::encode($info);
    }


    function __toString() {
        return (string) $this->getName();
    }

    function delete() {
        return parent::delete();
    }

    static function fromVars($vars) {

        if (!($org = Organization::lookup(array('name' => $vars['name'])))) {
            $org = Organization::create(array(
                'name' => $vars['name'],
                'created' => new SqlFunction('NOW'),
                'updated' => new SqlFunction('NOW'),
            ));
            $org->save(true);
        }

        return $org;
    }

    static function fromForm($form) {

        if(!$form) return null;

        //Validate the form
        $valid = true;
        if (!$form->isValid())
            $valid  = false;

        //Make sure the email is not in-use
        if (($field=$form->getField('name'))
                && $field->getClean()
                && Organization::lookup(array('name' => $field->getClean()))) {
            $field->addError('Organization with the same name already exists');
            $valid = false;
        }

        return $valid ? self::fromVars($form->getClean()) : null;
    }

}
//Organization::_inspect();

?>
