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
require_once(INCLUDE_DIR . 'class.user.php');

class OrganizationModel extends VerySimpleModel {
    static $meta = array(
        'table' => ORGANIZATION_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'users' => array(
                'reverse' => 'User.org',
            ),
        )
    );

    const COLLAB_ALL_MEMBERS =      0x0001;
    const COLLAB_PRIMARY_CONTACT =  0x0002;
    const ASSIGN_AGENT_MANAGER =    0x0004;

    var $_manager;

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getAccountManager() {
        if (!isset($this->_manager)) {
            if ($this->manager[0] == 't')
                $this->_manager = Team::lookup(substr($this->manager, 1));
            elseif ($this->manager[0] == 's')
                $this->_manager = Staff::lookup(substr($this->manager, 1));
            else
                $this->_manager = ''; // None.
        }

        return $this->_manager;
    }

    function getAccountManagerId() {
        return $this->manager;
    }

    function autoAddCollabs() {
        return $this->check(self::COLLAB_ALL_MEMBERS | self::COLLAB_PRIMARY_CONTACT);
    }

    function autoAddPrimaryContactsAsCollabs() {
        return $this->check(self::COLLAB_PRIMARY_CONTACT);
    }

    function autoAddMembersAsCollabs() {
        return $this->check(self::COLLAB_ALL_MEMBERS);
    }

    function autoAssignAccountManager() {
        return $this->check(self::ASSIGN_AGENT_MANAGER);
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function getCreateDate() {
        return $this->created;
    }

    function check($flag) {
        return 0 !== ($this->status & $flag);
    }

    protected function clearStatus($flag) {
        return $this->set('status', $this->get('status') & ~$flag);
    }

    protected function setStatus($flag) {
        return $this->set('status', $this->get('status') | $flag);
    }

    function allMembers() {
        return $this->users;
    }
}

class Organization extends OrganizationModel {
    var $_entries;
    var $_forms;

    function addDynamicData($data) {

        $of = OrganizationForm::getInstance($this->id, true);
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
                $g = OrganizationForm::getInstance($this->id, true);
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

    function getInfo() {

        $base = array_filter($this->ht,
                    function ($e) { return !is_object($e); }
                );

        foreach (array(
                'collab-all-flag' => Organization::COLLAB_ALL_MEMBERS,
                'collab-pc-flag' => Organization::COLLAB_PRIMARY_CONTACT,
                'assign-am-flag' => Organization::ASSIGN_AGENT_MANAGER,
        ) as $ck=>$flag) {
            if ($this->check($flag))
                $base[$ck] = true;
        }
        return $base;
    }

    function isMappedToDomain($domain) {
        foreach (explode(',', $this->domain) as $d) {
            $d = trim($d);
            if ($d[0] == '.') {
                // Subdomain syntax (.osticket.com accepts all subdomains of
                // osticket.com)
                if (strcasecmp(mb_substr($domain, -mb_strlen($d)), $d) === 0)
                    return true;
            }
            elseif (strcasecmp($domain, $d) === 0) {
                return true;
            }
        }
        return false;
    }

    static function forDomain($domain) {
        foreach (static::objects()
                ->filter(array('domain__contains'=>$domain)) as $org) {
            if ($org->isMappedToDomain($domain)) {
                return $org;
            }
        }
    }

    function addForm($form, $sort=1) {
        $form = $form->instanciate();
        $form->set('sort', $sort);
        $form->set('object_type', 'O');
        $form->set('object_id', $this->getId());
        $form->save();
    }

    function getFilterData() {
        $vars = array();
        foreach ($this->getDynamicData() as $entry) {
            if ($entry->getForm()->get('type') != 'O')
                continue;
            $vars += $entry->getFilterData();
            // Add special `name` field
            $f = $entry->getForm()->getField('name');
            $vars['field.'.$f->get('id')] = $this->getName();
        }
        return $vars;
    }

    function removeUser($user) {

        if (!$user instanceof User)
            return false;

        if (!$user->setOrganization(null, false))
            return false;

        // House cleaning - remove user from org contact..etc
        $user->setPrimaryContact(false);

        return $user->save();
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

        if ($vars['domain']) {
            foreach (explode(',', $vars['domain']) as $d) {
                if (!Validator::is_email('t@' . trim($d))) {
                    $errors['domain'] = 'Enter a valid email domain, like domain.com';
                }
            }
        }

        if ($vars['manager']) {
            switch ($vars['manager'][0]) {
            case 's':
                if ($staff = Staff::lookup(substr($vars['manager'], 1)))
                    break;
            case 't':
                if ($vars['manager'][0] == 't'
                        && $team = Team::lookup(substr($vars['manager'], 1)))
                    break;
            default:
                $errors['manager'] = 'Select a staff member or team from the list';
            }
        }

        if (!$valid || $errors)
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

        // Set flags
        foreach (array(
                'collab-all-flag' => Organization::COLLAB_ALL_MEMBERS,
                'collab-pc-flag' => Organization::COLLAB_PRIMARY_CONTACT,
                'assign-am-flag' => Organization::ASSIGN_AGENT_MANAGER,
        ) as $ck=>$flag) {
            if ($vars[$ck])
                $this->setStatus($flag);
            else
                $this->clearStatus($flag);
        }

        // Set staff and primary contacts
        $this->set('domain', $vars['domain']);
        $this->set('manager', $vars['manager'] ?: '');
        if ($vars['contacts'] && is_array($vars['contacts'])) {
            foreach ($this->allMembers() as $u) {
                $u->setPrimaryContact(array_search($u->id, $vars['contacts']) !== false);
                $u->save();
            }
        }

        return $this->save();
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

    // Custom create called by installer/upgrader to load initial data
    static function __create($ht, &$error=false) {

        $ht['created'] = new SqlFunction('NOW');
        $org = Organization::create($ht);
        // Add dynamic data (if any)
        if ($ht['fields']) {
            $org->save(true);
            $org->addDynamicData($ht['fields']);
        }

        return $org;
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

    static function getInstance($object_id=0, $new=false) {
        if ($new || !isset(static::$instance))
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

        if (!$form || !($o=static::objects()))
            return false;

        // Create sample organization.
        if (($orgs = $i18n->getTemplate('organization.yaml')->getData()))
            foreach($orgs as $org)
                Organization::__create($org);

        return $o[0];
    }

}
Filter::addSupportedMatches('Organization Data', function() {
    $matches = array();
    foreach (OrganizationForm::getInstance()->getFields() as $f) {
        if (!$f->hasData())
            continue;
        $matches['field.'.$f->get('id')] = 'Organization / '.$f->getLabel();
        if (($fi = $f->getImpl()) instanceof SelectionField) {
            foreach ($fi->getList()->getProperties() as $p) {
                $matches['field.'.$f->get('id').'.'.$p->get('id')]
                    = 'Organization / '.$f->getLabel().' / '.$p->getLabel();
            }
        }
    }
    return $matches;
},40);
Organization::_inspect();
?>
