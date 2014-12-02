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

class Dept extends VerySimpleModel {

    static $meta = array(
        'table' => DEPT_TABLE,
        'pk' => array('dept_id'),
        'joins' => array(
            'sla' => array(
                'constraint' => array('sla_id' => 'SLA.sla_id'),
                'null' => true,
            ),
            'manager' => array(
                'constraint' => array('manager_id' => 'Staff.staff_id'),
            ),
            'groups' => array(
                'reverse' => 'GroupDeptAccess.dept'
            ),
        ),
    );

    var $members;
    var $config;

    var $template;
    var $email;
    var $autorespEmail;

    const ALERTS_DISABLED = 2;
    const ALERTS_DEPT_AND_GROUPS = 1;
    const ALERTS_DEPT_ONLY = 0;

    function getConfig() {
        if (!isset($this->config))
            $this->config = new Config('dept.'. $this->getId());
        return $this->config;
    }

    function asVar() {
        return $this->getName();
    }

    function getId() {
        return $this->dept_id;
    }

    function getName() {
        return $this->dept_name;
    }

    function getLocalName($locale=false) {
        $tag = $this->getTranslateTag();
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->dept_name;
    }
    static function getLocalById($id, $subtag, $default) {
        $tag = _H(sprintf('dept.%s.%s', $subtag, $id));
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $default;
    }

    function getTranslateTag($subtag='name') {
        return _H(sprintf('dept.%s.%s', $subtag, $this->getId()));
    }

    function getEmailId() {
        return $this->email_id;
    }

    function getEmail() {
        global $cfg;

        if(!$this->email)
            if(!($this->email = Email::lookup($this->getEmailId())) && $cfg)
                $this->email = $cfg->getDefaultEmail();

        return $this->email;
    }

    function getNumMembers() {
        return count($this->getMembers());
    }

    function getMembers($criteria=null) {
        if (!$this->members || $criteria) {
            $members = Staff::objects()
                ->filter(Q::any(array(
                    'dept_id' => $this->getId(),
                    new Q(array(
                        'group__depts__dept_id' => $this->getId(),
                        'group__depts__group_membership' => self::ALERTS_DEPT_AND_GROUPS,
                    )),
                    'staff_id' => $this->manager_id
                )));

            if ($criteria && $criteria['available'])
                $members->filter(array(
                    'group__group_enabled' => 1,
                    'isactive' => 1,
                    'onvacation' => 0,
                ));

            $members->order_by('lastname', 'firstname');

            if ($criteria)
                return $members->all();

            $this->members = $members->all();
        }
        return $this->members;
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
        return $this->dept_signature;
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
        return $this->getConfig()->get('assign_members_only', 0);
    }

    function isGroupMembershipEnabled() {
        return $this->group_membership;
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getConfig()->getInfo() + $this->getHashtable();
    }

    function getAllowedGroups() {
        if ($this->groups)
            return $this->groups;

        $groups = GroupDept::objects()
            ->filter(array('dept_id' => $this->getId()))
            ->values_flat('group_id');

        foreach ($groups as $row) {
            list($id) = $row;
            $this->groups[] = $id;
        }
        return $this->groups;
    }

    function updateGroups($groups_ids) {

        // Groups allowes to access department
        if (is_array($groups_ids)) {
            $groups = GroupDept::objects()
                ->filter(array('dept_id' => $this->getId()));
            foreach ($groups as $group) {
                if ($idx = array_search($group->group_id, $groups_ids))
                    unset($groups_ids[$idx]);
                else
                    $group->delete();
            }
            foreach ($groups_ids as $id) {
                GroupDept::create(array(
                    'dept_id'=>$this->getId(), 'group_id'=>$id
                ))->save();
            }
        }

    }

    function updateSettings($vars) {
        $this->updateGroups($vars['groups'] ?: array());
        $this->getConfig()->set('assign_members_only', $vars['assign_members_only']);
        return true;
    }

    function delete() {
        global $cfg;

        if (!$cfg
            // Default department cannot be deleted
            || $this->getId()==$cfg->getDefaultDeptId()
            // Department  with users cannot be deleted
            || Staff::objects()
                ->filter(array('dept_id'=>$this->getId()))
                ->count()
        ) {
            return 0;
        }

        parent::delete();
        $id = $this->getId();
        $sql='DELETE FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($id).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
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

            // Destrory config settings
            $this->getConfig()->destroy();
        }

        return $num;
    }

    function __toString() {
        return $this->getName();
    }

    /*----Static functions-------*/
	static function getIdByName($name) {
        $row = static::objects()
            ->filter(array('dept_name'=>$name))
            ->values_flat('dept_id')
            ->first();

        return $row ? $row[0] : 0;
    }

    function getNameById($id) {

        if($id && ($dept=static::lookup($id)))
            $name= $dept->getName();

        return $name;
    }

    function getDefaultDeptName() {
        global $cfg;
        return ($cfg && $cfg->getDefaultDeptId() && ($name=Dept::getNameById($cfg->getDefaultDeptId())))?$name:null;
    }

    static function getDepartments( $criteria=null) {

        $depts = self::objects();
        if ($criteria['publiconly'])
            $depts->filter(array('public' => 1));

        if ($manager=$criteria['manager'])
            $depts->filter(array('manager_id' => is_object($manager)?$manager->getId():$manager));

        $depts->order_by('dept_name')
            ->values_flat('dept_id', 'dept_name');

        $names = array();
        foreach ($depts as $row) {
            list($id, $name) = $row;
            $names[$id] = $name;
        }

        // Fetch local names
        foreach (CustomDataTranslation::getDepartmentNames(array_keys($names)) as $id=>$name) {
            // Translate the department
            $names[$id] = $name;
        }
        return $names;
    }

    function getPublicDepartments() {
        return self::getDepartments(array('publiconly'=>true));
    }

    static function create($vars, &$errors=array()) {
        $dept = parent::create($vars);
        $dept->created = SqlFunction::NOW();
        return $dept;
    }

    static function __create($vars, &$errors) {
        $dept = self::create($vars);
        $dept->save();
        return $dept;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    function update($vars, &$errors) {
        global $cfg;

        if (isset($this->dept_id) && $this->getId() != $vars['id'])
            $errors['err']=__('Missing or invalid Dept ID (internal error).');

        if (!$vars['name']) {
            $errors['name']=__('Name required');
        } elseif (strlen($vars['name'])<4) {
            $errors['name']=__('Name is too short.');
        } elseif (($did=static::getIdByName($vars['name']))
                && (!isset($this->dept_id) || $did!=$this->getId())) {
            $errors['name']=__('Department already exists');
        }

        if (!$vars['ispublic'] && $cfg && ($vars['id']==$cfg->getDefaultDeptId()))
            $errors['ispublic']=__('System default department cannot be private');

        if ($errors)
            return false;

        $this->updated = SqlFunction::NOW();
        $this->ispublic = isset($vars['ispublic'])?$vars['ispublic']:0;
        $this->email_id = isset($vars['email_id'])?$vars['email_id']:0;
        $this->tpl_id = isset($vars['tpl_id'])?$vars['tpl_id']:0;
        $this->sla_id = isset($vars['sla_id'])?$vars['sla_id']:0;
        $this->autoresp_email_id = isset($vars['autoresp_email_id'])?$vars['autoresp_email_id']:0;
        $this->manager_id = $vars['manager_id']?$vars['manager_id']:0;
        $this->dept_name = Format::striptags($vars['name']);
        $this->dept_signature = Format::sanitize($vars['signature']);
        $this->group_membership = $vars['group_membership'];
        $this->ticket_auto_response = isset($vars['ticket_auto_response'])?$vars['ticket_auto_response']:1;
        $this->message_auto_response = isset($vars['message_auto_response'])?$vars['message_auto_response']:1;

        if ($this->save())
            return $this->updateSettings($vars);

        if (isset($this->dept_id))
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
                'constraint' => array('dept_id' => 'Dept.dept_id'),
            ),
            'group' => array(
                'constraint' => array('group_id' => 'Group.group_id'),
            ),
        ),
    );
}
?>
