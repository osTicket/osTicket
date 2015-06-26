<?php
/*********************************************************************
    class.dept.php

    Department class

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Dept extends VerySimpleModel
implements TemplateVariable {

    static $meta = array(
        'table' => DEPT_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'parent' => array(
                'constraint' => array('pid' => 'Dept.id'),
                'null' => true,
            ),
            'email' => array(
                'constraint' => array('email_id' => 'Email.email_id'),
                'null' => true,
             ),
            'sla' => array(
                'constraint' => array('sla_id' => 'SLA.sla_id'),
                'null' => true,
            ),
            'manager' => array(
                'null' => true,
                'constraint' => array('manager_id' => 'Staff.staff_id'),
            ),
            'members' => array(
                'null' => true,
                'list' => true,
                'reverse' => 'Staff.dept',
            ),
            'groups' => array(
                'null' => true,
                'list' => true,
                'reverse' => 'GroupDeptAccess.dept'
            ),
        ),
    );

    var $_members;
    var $_groupids;
    var $config;

    var $template;
    var $autorespEmail;

    const ALERTS_DISABLED = 2;
    const ALERTS_DEPT_AND_GROUPS = 1;
    const ALERTS_DEPT_ONLY = 0;

    const FLAG_ASSIGN_MEMBERS_ONLY = 0x0001;

    function asVar() {
        return $this->getName();
    }

    static function getVarScope() {
        return array(
            'name' => 'Department name',
            'manager' => array(
                'class' => 'Staff', 'desc' => 'Department manager',
                'exclude' => 'dept',
            ),
            'members' => array(
                'class' => 'UserList', 'desc' => 'Department members',
            ),
            'parent' => array(
                'class' => 'Dept', 'desc' => 'Parent department',
            ),
            'sla' => array(
                'class' => 'SLA', 'desc' => 'Service Level Agreement',
            ),
            'signature' => 'Department signature',
        );
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getLocalName($locale=false) {
        $tag = $this->getTranslateTag();
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->name;
    }
    static function getLocalById($id, $subtag, $default) {
        $tag = _H(sprintf('dept.%s.%s', $subtag, $id));
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $default;
    }

    function getTranslateTag($subtag='name') {
        return _H(sprintf('dept.%s.%s', $subtag, $this->getId()));
    }

    function getFullName() {
        return self::getNameById($this->getId());
    }

    function getEmailId() {
        return $this->email_id;
    }

    /**
     * getAlertEmail
     *
     * Fetches either the department email (for replies) if configured.
     * Otherwise, the system alert email address is used.
     */
    function getAlertEmail() {
        global $cfg;

        if ($this->email)
            return $this->email;

        return $cfg ? $cfg->getDefaultEmail() : null;
    }

    function getEmail() {
        global $cfg;

        if ($this->email)
            return $this->email;

        return $cfg? $cfg->getDefaultEmail() : null;
    }

    function getNumMembers() {
        return count($this->getMembers());
    }

    function getMembers($criteria=null) {
        global $cfg;

        if (!$this->_members || $criteria) {
            $members = Staff::objects()
                ->distinct('staff_id')
                ->filter(Q::any(array(
                    'dept_id' => $this->getId(),
                    new Q(array(
                        'group__depts__dept_id' => $this->getId(),
                        'group__depts__dept__group_membership' => self::ALERTS_DEPT_AND_GROUPS,
                    )),
                    'staff_id' => $this->manager_id
                )));

            if ($criteria && $criteria['available'])
                $members->filter(array(
                    'group__flags__hasbit' => Group::FLAG_ENABLED,
                    'isactive' => 1,
                    'onvacation' => 0,
                ));

            $members->distinct('staff_id');
            switch ($cfg->getAgentNameFormat()) {
            case 'last':
            case 'lastfirst':
            case 'legal':
                $members->order_by('lastname', 'firstname');
                break;

            default:
                $members->order_by('firstname', 'lastname');
            }

            if ($criteria)
                return $members->all();

            $this->_members = $members->all();
        }
        return new UserList($this->_members);
    }

    function getAvailableMembers() {
        return $this->getMembers(array('available'=>1));
    }

    function getMembersForAlerts() {
        if ($this->isGroupMembershipEnabled() == self::ALERTS_DISABLED) {
            // Disabled for this department
            $rv = array();
        }
        else {
            $rv = $this->getAvailableMembers();
        }
        return $rv;
    }

    function getSLAId() {
        return $this->sla_id;
    }

    function getSLA() {
        return $this->sla;
    }

    function getTemplateId() {
         return $this->tpl_id;
    }

    function getTemplate() {
        global $cfg;

        if (!$this->template) {
            if (!($this->template = EmailTemplateGroup::lookup($this->getTemplateId())))
                $this->template = $cfg->getDefaultTemplate();
        }

        return $this->template;
    }

    function getAutoRespEmail() {

        if (!$this->autorespEmail) {
            if (!$this->autoresp_email_id
                    || !($this->autorespEmail = Email::lookup($this->autoresp_email_id)))
                $this->autorespEmail = $this->getEmail();
        }

        return $this->autorespEmail;
    }

    function getEmailAddress() {
        if(($email=$this->getEmail()))
            return $email->getAddress();
    }

    function getSignature() {
        return $this->signature;
    }

    function canAppendSignature() {
        return ($this->getSignature() && $this->isPublic());
    }

    function getManagerId() {
        return $this->manager_id;
    }

    function getManager() {
        return $this->manager;
    }

    function isManager($staff) {

        if(is_object($staff)) $staff=$staff->getId();

        return ($this->getManagerId() && $this->getManagerId()==$staff);
    }

    function isMember($staff) {

        if (is_object($staff))
            $staff = $staff->getId();

        // Members are indexed by ID
        $members = $this->getMembers();

        return ($members && isset($members[$staff]));
    }

    function isPublic() {
         return $this->ispublic;
    }

    function autoRespONNewTicket() {
        return $this->ticket_auto_response;
    }

    function autoRespONNewMessage() {
        return $this->message_auto_response;
    }

    function noreplyAutoResp() {
         return $this->noreply_autoresp;
    }

    function assignMembersOnly() {
        return $this->flags & self::FLAG_ASSIGN_MEMBERS_ONLY;
    }

    function isGroupMembershipEnabled() {
        return $this->group_membership;
    }

    function getHashtable() {
        $ht = $this->ht;
        if (static::$meta['joins'])
            foreach (static::$meta['joins'] as $k => $v)
                unset($ht[$k]);

        $ht['assign_members_only'] = $this->flags & self::FLAG_ASSIGN_MEMBERS_ONLY;
        return $ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getAllowedGroups() {

        if (!isset($this->_groupids)) {
            $this->_groupids = array();
            $groups = GroupDeptAccess::objects()
                ->filter(array('dept_id' => $this->getId()))
                ->values_flat('group_id');

            foreach ($groups as $row)
                $this->_groupids[] = $row[0];
        }

        return $this->_groupids;
    }

    function updateGroups($groups_ids, $vars) {

        // Groups allowed to access department
        if (is_array($groups_ids)) {
            $groups = GroupDeptAccess::objects()
                ->filter(array('dept_id' => $this->getId()));
            foreach ($groups as $group) {
                if ($idx = array_search($group->group_id, $groups_ids)) {
                    unset($groups_ids[$idx]);
                    $roleId = $vars['group'.$group->group_id.'_role_id'];
                    if ($roleId != $group->role_id) {
                        $group->set('role_id', $roleId ?: 0);
                        $group->save();
                    }
                } else {
                    $group->delete();
                }
            }
            foreach ($groups_ids as $id) {
                $roleId = $vars['group'.$id.'_role_id'];
                GroupDeptAccess::create(array(
                    'dept_id' => $this->getId(),
                    'group_id' => $id,
                    'role_id' => $roleId ?: 0,
                ))->save();
            }
        }

    }

    function updateSettings($vars) {
        $this->updateGroups($vars['groups'] ?: array(), $vars);
        $this->path = $this->getFullPath();
        $this->save();
        return true;
    }

    function delete() {
        global $cfg;

        if (!$cfg
            // Default department cannot be deleted
            || $this->getId()==$cfg->getDefaultDeptId()
            // Department  with users cannot be deleted
            || $this->members->count()
        ) {
            return 0;
        }

        $id = $this->getId();
        if (parent::delete()) {
            // DO SOME HOUSE CLEANING
            //Move tickets to default Dept. TODO: Move one ticket at a time and send alerts + log notes.
            db_query('UPDATE '.TICKET_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            //Move Dept members: This should never happen..since delete should be issued only to empty Depts...but check it anyways
            db_query('UPDATE '.STAFF_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));

            // Clear any settings using dept to default back to system default
            db_query('UPDATE '.TOPIC_TABLE.' SET dept_id=0 WHERE dept_id='.db_input($id));
            db_query('UPDATE '.EMAIL_TABLE.' SET dept_id=0 WHERE dept_id='.db_input($id));
            db_query('UPDATE '.FILTER_TABLE.' SET dept_id=0 WHERE dept_id='.db_input($id));

            //Delete group access
            db_query('DELETE FROM '.GROUP_DEPT_TABLE.' WHERE dept_id='.db_input($id));
        }

        return true;
    }

    function __toString() {
        return $this->getName();
    }

    function getParent() {
        return static::lookup($this->ht['pid']);
    }

    /**
     * getFullPath
     *
     * Utility function to retrieve a '/' separated list of department IDs
     * in the ancestry of this department. This is used to populate the
     * `path` field in the database and is used for access control rather
     * than the ID field since nesting of departments is necessary and
     * department access can be cascaded.
     *
     * Returns:
     * Slash-separated string of ID ancestry of this department. The string
     * always starts and ends with a slash, and will always contain the ID
     * of this department last.
     */
    function getFullPath() {
        $path = '';
        if ($p = $this->getParent())
            $path .= $p->getFullPath();
        else
            $path .= '/';
        $path .= $this->getId() . '/';
        return $path;
    }

    /*----Static functions-------*/
	static function getIdByName($name, $pid=0) {
        $row = static::objects()
            ->filter(array(
                        'name' => $name,
                        'pid'  => $pid ?: 0))
            ->values_flat('id')
            ->first();

        return $row ? $row[0] : 0;
    }

    function getNameById($id) {
        $names = static::getDepartments();
        return $names[$id];
    }

    function getDefaultDeptName() {
        global $cfg;

        return ($cfg
            && ($did = $cfg->getDefaultDeptId())
            && ($names = self::getDepartments()))
            ? $names[$did]
            : null;
    }

    static function getDepartments( $criteria=null, $localize=true) {
        static $depts = null;

        if (!isset($depts) || $criteria) {
            // XXX: This will upset the static $depts array
            $depts = array();
            $query = self::objects();
            if (isset($criteria['publiconly']))
                $query->filter(array(
                            'ispublic' => ($criteria['publiconly'] ? 1 : 0)));

            if ($manager=$criteria['manager'])
                $query->filter(array(
                            'manager_id' => is_object($manager)?$manager->getId():$manager));

            if (isset($criteria['nonempty'])) {
                $query->annotate(array(
                    'user_count' => SqlAggregate::COUNT('members')
                ))->filter(array(
                    'user_count__gt' => 0
                ));
            }

            $query->order_by('name')
                ->values('id', 'pid', 'name', 'parent');

            foreach ($query as $row)
                $depts[$row['id']] = $row;

            $localize_this = function($id, $default) use ($localize) {
                if (!$localize)
                    return $default;

                $tag = _H("dept.name.{$id}");
                $T = CustomDataTranslation::translate($tag);
                return $T != $tag ? $T : $default;
            };

            // Resolve parent names
            $names = array();
            foreach ($depts as $id=>$info) {
                $name = $info['name'];
                $loop = array($id=>true);
                $parent = false;
                while ($info['pid'] && ($info = $depts[$info['pid']])) {
                    $name = sprintf('%s / %s', $info['name'], $name);
                    if (isset($loop[$info['pid']]))
                        break;
                    $loop[$info['pid']] = true;
                    $parent = $info;
                }
                // Fetch local names
                $names[$id] = $localize_this($id, $name);
            }
            asort($names);

            // TODO: Use locale-aware sorting mechanism

            if ($criteria)
                return $names;

            $depts = $names;
        }

        return $depts;
    }

    static function getPublicDepartments() {
        static $depts =null;

        if (!$depts)
            $depts = self::getDepartments(array('publiconly'=>true));

        return $depts;
    }

    static function create($vars=false, &$errors=array()) {
        $dept = parent::create($vars);
        $dept->created = SqlFunction::NOW();

        return $dept;
    }

    static function __create($vars, &$errors) {
        $dept = self::create();
        $dept->update($vars, $errors);

        return isset($dept->id) ? $dept : null;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();

        return parent::save($refetch || $this->dirty);
    }

    function update($vars, &$errors) {
        global $cfg;

        if (isset($this->id) && $this->getId() != $vars['id'])
            $errors['err']=__('Missing or invalid Dept ID (internal error).');

        if (!$vars['name']) {
            $errors['name']=__('Name required');
        } elseif (($did=static::getIdByName($vars['name'], $vars['pid']))
                && (!isset($this->id) || $did!=$this->getId())) {
            $errors['name']=__('Department already exists');
        }

        if (!$vars['ispublic'] && $cfg && ($vars['id']==$cfg->getDefaultDeptId()))
            $errors['ispublic']=__('System default department cannot be private');

        if ($vars['pid'] && !($p = static::lookup($vars['pid'])))
            $errors['pid'] = __('Department selection is required');

        if ($errors)
            return false;

        $this->pid = $vars['pid'] ?: 0;
        $this->updated = SqlFunction::NOW();
        $this->ispublic = isset($vars['ispublic'])?$vars['ispublic']:0;
        $this->email_id = isset($vars['email_id'])?$vars['email_id']:0;
        $this->tpl_id = isset($vars['tpl_id'])?$vars['tpl_id']:0;
        $this->sla_id = isset($vars['sla_id'])?$vars['sla_id']:0;
        $this->autoresp_email_id = isset($vars['autoresp_email_id'])?$vars['autoresp_email_id']:0;
        $this->manager_id = $vars['manager_id'] ?: 0;
        $this->name = Format::striptags($vars['name']);
        $this->signature = Format::sanitize($vars['signature']);
        $this->group_membership = $vars['group_membership'];
        $this->ticket_auto_response = isset($vars['ticket_auto_response'])?$vars['ticket_auto_response']:1;
        $this->message_auto_response = isset($vars['message_auto_response'])?$vars['message_auto_response']:1;
        $this->flags = isset($vars['assign_members_only']) ? self::FLAG_ASSIGN_MEMBERS_ONLY : 0;

        if ($this->save())
            return $this->updateSettings($vars);

        if (isset($this->id))
            $errors['err']=sprintf(__('Unable to update %s.'), __('this department'))
               .' '.__('Internal error occurred');
        else
            $errors['err']=sprintf(__('Unable to create %s.'), __('this department'))
               .' '.__('Internal error occurred');

        return false;
    }

}

class GroupDeptAccess extends VerySimpleModel {
    static $meta = array(
        'table' => GROUP_DEPT_TABLE,
        'pk' => array('dept_id', 'group_id'),
        'joins' => array(
            'dept' => array(
                'constraint' => array('dept_id' => 'Dept.id'),
            ),
            'group' => array(
                'constraint' => array('group_id' => 'Group.id'),
            ),
            'role' => array(
                'constraint' => array('role_id' => 'Role.id'),
            ),
        ),
    );
}

class DepartmentQuickAddForm
extends Form {
    function getFields() {
        if ($this->fields)
            return $this->fields;

        return $this->fields = array(
            'pid' => new ChoiceField(array(
                'label' => '',
                'default' => 0,
                'choices' => array_merge(
                    array(0 => __('Top-Level Department')),
                    Dept::getDepartments()
                )
            )),
            'name' => new TextboxField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __('Name'),
                    'classes' => 'span12',
                    'autofocus' => true,
                    'length' => 128,
                ),
            )),
            'email_id' => new ChoiceField(array(
                'label' => __('Email Mailbox'),
                'default' => 0,
                'choices' => array_merge(
                    array(0 => '— '.__('System Default').' —'),
                    Email::getAddresses()
                ),
                'configuration' => array(
                    'classes' => 'span12',
                ),
            )),
            'private' => new BooleanField(array(
                'configuration' => array(
                    'classes' => 'form footer',
                    'desc' => __('This department is for internal use'),
                ),
            )),
        );
    }

    function getClean() {
        $clean = parent::getClean();

        $clean['ispublic'] = !$clean['private'];
        unset($clean['private']);

        return $clean;
    }

    function render($staff=true) {
        return parent::render($staff, false, array('template' => 'dynamic-form-simple.tmpl.php'));
    }
}
