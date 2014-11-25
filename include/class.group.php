<?php
/*********************************************************************
    class.group.php

    User Group - Everything about a group!

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Group extends VerySimpleModel {

    static $meta = array(
        'table' => GROUP_TABLE,
        'pk' => array('group_id'),
    );

    var $members;
    var $departments;

    function getHashtable() {
        $base = $this->ht;
        $base['name'] = $base['group_name'];
        $base['isactive'] = $base['group_enabled'];
        return $base;
    }

    function getInfo(){
        return $this->getHashtable();
    }

    function getId(){
        return $this->group_id;
    }

    function getName(){
        return $this->group_name;
    }

    function getNumUsers(){
        return Staff::objects()->filter(array('group_id'=>$this->getId()))->count();
    }

    function isEnabled(){
        return $this->group_enabled;
    }

    function isActive(){
        return $this->isEnabled();
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('group.%s.%s', $subtag, $this->getId()));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->ht[$subtag];
    }
    static function getLocalById($id, $subtag, $default) {
        $tag = _H(sprintf('group.%s.%s', $subtag, $id));
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $default;
    }

    //Get members of the group.
    function getMembers() {

        if (!$this->members) {
            $this->members = Staff::objects()
                ->filter(array('group_id'=>$this->getId()))
                ->order_by('lastname', 'firstname')
                ->all();
        }
        return $this->members;
    }

    //Get departments the group is allowed to access.
    function getDepartments() {
        if (!isset($this->departments)) {
            $this->departments = array();
            foreach (GroupDeptAccess::objects()
                ->filter(array('group_id'=>$this->getId()))
                ->values_flat('dept_id') as $gda
            ) {
                $this->departments[] = $gda[0];
            }
        }
        return $this->departments;
    }


    function updateDeptAccess($dept_ids) {
        if ($dept_ids && is_array($dept_ids)) {
            $groups = GroupDeptAccess::objects()
                ->filter(array('group_id' => $this->getId()));
            foreach ($groups as $group) {
                if ($idx = array_search($group->dept_id, $dept_ids))
                    unset($dept_ids[$idx]);
                else
                    $group->delete();
            }
            foreach ($dept_ids as $id) {
                GroupDeptAccess::create(array(
                    'group_id'=>$this->getId(), 'dept_id'=>$id
                ))->save();
            }
            return true;
        }
        return false;
    }

    function delete() {

        // Can't delete with members
        if ($this->getNumUsers())
            return false;

        if (!parent::delete())
            return false;

        // Remove dept access entries
        GroupDeptAccess::objects()
            ->filter(array('group_id'=>$this->getId()))
            ->delete();

        return true;
    }

    /*** Static functions ***/
    static function getIdByName($name){
        $id = static::objects()->filter(array('group_name'=>trim($name)))
            ->values_flat('group_id')->first();

        return $id ? $id[0] : 0;
    }

    static function getGroupNames($localize=true) {
        static $groups=array();

        if (!$groups) {
            $query = static::objects()
                ->values_flat('group_id', 'group_name', 'group_enabled')
                ->order_by('group_name');
            foreach ($query as $row) {
                list($id, $name, $enabled) = $row;
                $groups[$id] = sprintf('%s%s',
                    self::getLocalById($id, 'name', $name),
                    $enabled ? '' : ' ' . __('(disabled)'));
            }
        }
        // TODO: Sort groups if $localize
        return $groups;
    }

    static function create($vars=false) {
        $group = parent::create($vars);
        $group->created = SqlFunction::NOW();
        return $group;
    }

    function save($refetch=false) {
        if ($this->dirty) {
            $this->updated = SqlFunction::NOW();
        }
        return parent::save($refetch || $this->dirty);
    }

    function update($vars,&$errors) {
        if (isset($this->group_id) && $this->getId() != $vars['id'])
            $errors['err']=__('Missing or invalid group ID');

        if (!$vars['name']) {
            $errors['name']=__('Group name required');
        } elseif(strlen($vars['name'])<3) {
            $errors['name']=__('Group name must be at least 3 chars.');
        } elseif (($gid=static::getIdByName($vars['name']))
                && (!isset($this->group_id) || $gid!=$this->getId())) {
            $errors['name']=__('Group name already exists');
        }

        if ($errors)
            return false;

        $this->group_name=Format::striptags($vars['name']);
        $this->group_enabled=$vars['isactive'];
        $this->can_create_tickets=$vars['can_create_tickets'];
        $this->can_delete_tickets=$vars['can_delete_tickets'];
        $this->can_edit_tickets=$vars['can_edit_tickets'];
        $this->can_assign_tickets=$vars['can_assign_tickets'];
        $this->can_transfer_tickets=$vars['can_transfer_tickets'];
        $this->can_close_tickets=$vars['can_close_tickets'];
        $this->can_ban_emails=$vars['can_ban_emails'];
        $this->can_manage_premade=$vars['can_manage_premade'];
        $this->can_manage_faq=$vars['can_manage_faq'];
        $this->can_post_ticket_reply=$vars['can_post_ticket_reply'];
        $this->can_view_staff_stats=$vars['can_view_staff_stats'];
        $this->notes=Format::sanitize($vars['notes']);

        if ($this->save())
            return $this->updateDeptAccess($vars['depts']);

        if (isset($this->group_id)) {
            $errors['err']=sprintf(__('Unable to update %s.'), __('this group'))
               .' '.__('Internal error occurred');
        }
        else {
            $errors['err']=sprintf(__('Unable to create %s.'), __('this group'))
               .' '.__('Internal error occurred');
        }
        return false;
    }
}
?>
