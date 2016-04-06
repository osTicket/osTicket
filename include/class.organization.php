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
            'cdata' => array(
                'constraint' => array('id' => 'OrganizationCdata.org_id'),
            ),
        )
    );

    const COLLAB_ALL_MEMBERS =      0x0001;
    const COLLAB_PRIMARY_CONTACT =  0x0002;
    const ASSIGN_AGENT_MANAGER =    0x0004;

    const SHARE_PRIMARY_CONTACT =   0x0008;
    const SHARE_EVERYBODY =         0x0010;

    const PERM_CREATE =     'org.create';
    const PERM_EDIT =       'org.edit';
    const PERM_DELETE =     'org.delete';

    static protected $perms = array(
        self::PERM_CREATE => array(
            'title' => /* @trans */ 'Create',
            'desc' => /* @trans */ 'Ability to create new organizations',
            'primary' => true,
        ),
        self::PERM_EDIT => array(
            'title' => /* @trans */ 'Edit',
            'desc' => /* @trans */ 'Ability to manage organizations',
            'primary' => true,
        ),
        self::PERM_DELETE => array(
            'title' => /* @trans */ 'Delete',
            'desc' => /* @trans */ 'Ability to delete organizations',
            'primary' => true,
        ),
    );

    var $_manager;

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getNumUsers() {
        return $this->users->count();
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

    function shareWithPrimaryContacts() {
        return $this->check(self::SHARE_PRIMARY_CONTACT);
    }

    function shareWithEverybody() {
        return $this->check(self::SHARE_EVERYBODY);
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

    static function getPermissions() {
        return self::$perms;
    }
}
include_once INCLUDE_DIR.'class.role.php';
RolePermission::register(/* @trans */ 'Organizations',
    OrganizationModel::getPermissions());

class OrganizationCdata extends VerySimpleModel {
    static $meta = array(
        'table' => ORGANIZATION_CDATA_TABLE,
        'pk' => array('org_id'),
        'joins' => array(
            'org' => array(
                'constraint' => array('ord_id' => 'OrganizationModel.id'),
            ),
        ),
    );
}

class Organization extends OrganizationModel
implements TemplateVariable {
    var $_entries;
    var $_forms;

    function addDynamicData($data) {
        $entry = $this->addForm(OrganizationForm::objects()->one(), 1, $data);
        // FIXME: For some reason, the second save here is required or the
        //        custom data is not properly saved
        $entry->save();

        return $entry;
    }

    function getDynamicData($create=true) {
        if (!isset($this->_entries)) {
            $this->_entries = DynamicFormEntry::forObject($this->id, 'O')->all();
            if (!$this->_entries && $create) {
                $g = OrganizationForm::getInstance($this->id, true);
                $g->save();
                $this->_entries[] = $g;
            }
        }

        return $this->_entries ?: array();
    }

    function getForms($data=null) {

        if (!isset($this->_forms)) {
            $this->_forms = array();
            foreach ($this->getDynamicData() as $entry) {
                $entry->addMissingFields();
                if(!$data
                        && ($form = $entry->getDynamicForm())
                        && $form->get('type') == 'O' ) {
                    foreach ($entry->getFields() as $f) {
                        if ($f->get('name') == 'name')
                            $f->value = $this->getName();
                    }
                }

                $this->_forms[] = $entry;
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
                'sharing-primary' => Organization::SHARE_PRIMARY_CONTACT,
                'sharing-all' => Organization::SHARE_EVERYBODY,
        ) as $ck=>$flag) {
            if ($this->check($flag))
                $base[$ck] = true;
        }
        return $base;
    }

    function isMappedToDomain($domain) {
        if (!$domain || !$this->domain)
            return false;
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
        if (!$domain)
            return null;
        foreach (static::objects()->filter(array(
            'domain__gt'=>'',
            'domain__contains'=>$domain
        )) as $org) {
            if ($org->isMappedToDomain($domain)) {
                return $org;
            }
        }
    }

    function addForm($form, $sort=1, $data=null) {
        $entry = $form->instanciate($sort, $data);
        $entry->set('object_type', 'O');
        $entry->set('object_id', $this->getId());
        $entry->save();
        return $entry;
    }

    function getFilterData() {
        $vars = array();
        foreach ($this->getDynamicData() as $entry) {
            if ($entry->getDynamicForm()->get('type') != 'O')
                continue;
            $vars += $entry->getFilterData();
            // Add special `name` field
            $f = $entry->getField('name');
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

    function asVar() {
        return (string) $this->getName();
    }

    function getVar($tag) {
        $tag = mb_strtolower($tag);
        foreach ($this->getDynamicData() as $e)
            if ($a = $e->getAnswer($tag))
                return $a;

        switch ($tag) {
        case 'members':
            return new UserList($this->users);
        case 'manager':
            return $this->getAccountManager();
        case 'contacts':
            return new UserList($this->users->filter(array(
                'flags__hasbit' => User::PRIMARY_ORG_CONTACT
            )));
        }
    }

    static function getVarScope() {
        $base = array(
            'contacts' => array('class' => 'UserList', 'desc' => __('Primary Contacts')),
            'manager' => __('Account Manager'),
            'members' => array('class' => 'UserList', 'desc' => __('Organization Members')),
            'name' => __('Name'),
        );
        $extra = VariableReplacer::compileFormScope(OrganizationForm::getInstance());
        return $base + $extra;
    }

    function update($vars, &$errors) {

        $valid = true;
        $forms = $this->getForms($vars);
        foreach ($forms as $entry) {
            if (!$entry->isValid())
                $valid = false;
            if ($entry->getDynamicForm()->get('type') == 'O'
                        && ($f = $entry->getField('name'))
                        && $f->getClean()
                        && ($o=Organization::lookup(array('name'=>$f->getClean())))
                        && $o->id != $this->getId()) {
                $valid = false;
                $f->addError(__('Organization with the same name already exists'));
            }
        }

        if ($vars['domain']) {
            foreach (explode(',', $vars['domain']) as $d) {
                if (!Validator::is_email('t@' . trim($d))) {
                    $errors['domain'] = __('Enter a valid email domain, like domain.com');
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
                $errors['manager'] = __('Select an agent or team from the list');
            }
        }

        if (!$valid || $errors)
            return false;

        foreach ($this->getDynamicData() as $entry) {
            if ($entry->getDynamicForm()->get('type') == 'O'
               && ($name = $entry->getField('name'))
            ) {
                $this->name = $name->getClean();
                $this->save();
            }
            $entry->setSource($vars);
            if ($entry->save())
                $this->updated = SqlFunction::NOW();
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

        foreach (array(
                'sharing-primary' => Organization::SHARE_PRIMARY_CONTACT,
                'sharing-all' => Organization::SHARE_EVERYBODY,
        ) as $ck=>$flag) {
            if ($vars['sharing'] == $ck)
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

    function delete() {
        if (!parent::delete())
            return false;

        // Remove users from this organization
        User::objects()
            ->filter(array('org' => $this))
            ->update(array('org_id' => 0));

        foreach ($this->getDynamicData(false) as $entry) {
            if (!$entry->delete())
                return false;
        }
        return true;
    }

    static function fromVars($vars) {

        $vars['name'] = Format::striptags($vars['name']);
        if (!($org = static::lookup(array('name' => $vars['name'])))) {
            $org = static::create(array(
                'name' => $vars['name'],
                'updated' => new SqlFunction('NOW'),
            ));
            $org->save(true);
            $org->addDynamicData($vars);
        }

        Signal::send('organization.created', $org);
        return $org;
    }

    static function fromForm($form) {

        if (!$form)
            return null;

        //Validate the form
        $valid = true;
        if (!$form->isValid())
            $valid  = false;

        // Make sure the name is not in-use
        if (($field=$form->getField('name'))
                && $field->getClean()
                && static::lookup(array('name' => $field->getClean()))) {
            $field->addError(__('Organization with the same name already exists'));
            $valid = false;
        }

        return $valid ? self::fromVars($form->getClean()) : null;
    }

    static function create($vars=false) {
        $org = new static($vars);

        $org->created = new SqlFunction('NOW');
        $org->setStatus(self::SHARE_PRIMARY_CONTACT);
        return $org;
    }

    // Custom create called by installer/upgrader to load initial data
    static function __create($ht, &$error=false) {

        $org = static::create($ht);
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

    static $cdata = array(
            'table' => ORGANIZATION_CDATA_TABLE,
            'object_id' => 'org_id',
            'object_type' => ObjectModel::OBJECT_TYPE_ORG,
        );

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

    static function getInstance($object_id=0, $new=false, $data=null) {
        if ($new || !isset(static::$instance))
            static::$instance = static::getDefaultForm()->instanciate(1, $data);

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
Filter::addSupportedMatches(/*@trans*/ 'Organization Data', function() {
    $matches = array();
    foreach (OrganizationForm::getInstance()->getFields() as $f) {
        if (!$f->hasData())
            continue;
        $matches['field.'.$f->get('id')] = __('Organization').' / '.$f->getLabel();
        if (($fi = $f->getImpl()) && $fi->hasSubFields()) {
            foreach ($fi->getSubFields() as $p) {
                $matches['field.'.$f->get('id').'.'.$p->get('id')]
                    = __('Organization').' / '.$f->getLabel().' / '.$p->getLabel();
            }
        }
    }
    return $matches;
},40);
?>
