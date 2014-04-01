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
require_once(INCLUDE_DIR . 'class.forms.php');
require_once(INCLUDE_DIR . 'class.dynamic_forms.php');

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
    var $_entries;
    var $_forms;

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

    function addDynamicData($data) {

        $of = OrganizationForm::getInstance($this->id);
        foreach ($of->getFields() as $f)
            if (isset($data[$f->get('name')]))
                $of->setAnswer($f->get('name'), $data[$f->get('name')]);

        $of->save();

        return $of;
    }

    function getDynamicData() {
        if (!isset($this->_entries)) {
            $this->_entries = DynamicFormEntry::forOrganization($this->id)->all();
            if (!$this->_entries) {
                $g = OrganizationForm::getInstance($this->id);
                $g->save();
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
                        && $form->get('type') == 'O' ) {
                    foreach ($cd->getFields() as $f) {
                        if ($f->get('name') == 'name')
                            $f->value = $this->getName();
                    }
                }

                $this->_forms[] = $cd->getForm();
            }
        }

        return $this->_forms;
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


    function update($vars, &$errors) {

        $valid = true;
        $forms = $this->getForms($vars);
        foreach ($forms as $cd) {
            if (!$cd->isValid())
                $valid = false;
            if ($cd->get('type') == 'O'
                        && ($form= $cd->getForm($vars))
                        && ($f=$form->getField('name'))
                        && $f->getClean()
                        && ($o=Organization::lookup(array('name'=>$f->getClean())))
                        && $o->id != $this->getId()) {
                $valid = false;
                $f->addError('Organization with the same name already exists');
            }
        }

        if (!$valid)
            return false;

        foreach ($this->getDynamicData() as $cd) {
            if (($f=$cd->getForm())
                    && ($f->get('type') == 'O')
                    && ($name = $f->getField('name'))) {
                    $this->name = $name->getClean();
                    $this->save();
                }
            $cd->save();
        }

        return true;
    }

    static function fromVars($vars) {

        if (!($org = Organization::lookup(array('name' => $vars['name'])))) {
            $org = Organization::create(array(
                'name' => $vars['name'],
                'created' => new SqlFunction('NOW'),
                'updated' => new SqlFunction('NOW'),
            ));
            $org->save(true);
            $org->addDynamicData($vars);
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

class OrganizationForm extends DynamicForm {
    static $instance;
    static $form;

    static function objects() {
        $os = parent::objects();
        return $os->filter(array('type'=>'O'));
    }

    static function getDefaultForm() {
        if (!isset(static::$form)) {
            if (($o = static::objects()) && $o[0])
                static::$form = $o[0];
            else //TODO: Remove the code below and move it to task??
                static::$form = self::__loadDefaultForm();
        }

        return static::$form;
    }

    static function getInstance($object_id=0) {
        if (!isset(static::$instance))
            static::$instance = static::getDefaultForm()->instanciate();

        static::$instance->object_type = 'O';

        if ($object_id)
            static::$instance->object_id = $object_id;

        return static::$instance;
    }

    static function __loadDefaultForm() {
        require_once(INCLUDE_DIR.'class.i18n.php');

        $i18n = new Internationalization();
        $tpl = $i18n->getTemplate('form.yaml');
        foreach ($tpl->getData() as $f) {
            if ($f['type'] == 'O') {
                $form = DynamicForm::create($f);
                $form->save();
                break;
            }
        }

        $o =static::objects();

        return $o[0];
    }

}

//Organization::_inspect();

?>
