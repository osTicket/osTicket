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
        'pk' => array('id'),
        'joins' => array(
            'members' => array(
                'null' => true,
                'list' => true,
                'reverse' => 'Staff.group',
            ),
            'depts' => array(
                'null' => true,
                'list' => true,
                'reverse' => 'GroupDeptAccess.group',
            ),
            'role' => array(
                'constraint' => array('role_id' => 'Role.id')
            ),
        ),
    );

    const FLAG_ENABLED = 0X0001;

    var $departments;

    function getHashtable() {
        $base = $this->ht;
        $base['name'] = $base['name'];
        $base['isactive'] = $base['flags'];
        return $base;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getId() {
        return $this->id;
    }

    function getRoleId() {
        return $this->role_id;
    }

    function getRole($deptId=0) {

        if ($deptId // Department specific role.
                && ($roles=$this->getDepartmentsAccess())
                && isset($roles[$deptId])
                && $roles[$deptId]
                && ($role=Role::lookup($roles[$deptId]))
                && $role->isEnabled())
            return $role;

        // Default role for this group.
        return $this->role;
    }

    function getName() {
        return $this->name;
    }

    function getCreateDate() {
        return $this->created;
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function getNumMembers() {
        return $this->members ? $this->members->count() : 0;
     }

    function isEnabled() {
        return ($this->get('flags') & self::FLAG_ENABLED !== 0);
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

    //Get departments & roles the group is allowed to access.
    function getDepartments() {
        return array_keys($this->getDepartmentsAccess());
    }

    function getDepartmentsAccess() {
        if (!isset($this->departments)) {
            $this->departments = array();
            foreach (GroupDeptAccess::objects()
                ->filter(array('group_id'=>$this->getId()))
                ->values_flat('dept_id', 'role_id') as $gda
            ) {
                $this->departments[$gda[0]] = $gda[1];
            }
        }

        return $this->departments;
    }

    function updateDeptAccess($dept_ids, $vars=array()) {
        if (is_array($dept_ids)) {
            $groups = GroupDeptAccess::objects()
                ->filter(array('group_id' => $this->getId()));
            foreach ($groups as $group) {
                if ($idx = array_search($group->dept_id, $dept_ids)) {
                    unset($dept_ids[$idx]);
                    $roleId = $vars['dept'.$group->dept_id.'_role_id'];
                    if ($roleId != $group->role_id) {
                        $group->set('role_id', $roleId ?: 0);
                        $group->save();
                    }
                } else {
                    $group->delete();
                }
            }
            foreach ($dept_ids as $id) {
                $roleId = $vars['dept'.$id.'_role_id'];
                GroupDeptAccess::create(array(
                    'group_id' => $this->getId(),
                    'dept_id' => $id,
                    'role_id' => $roleId ?: 0
                ))->save();
            }
        }
        return true;
    }

    function delete() {

        // Can't delete with members
        if ($this->getNumMembers())
            return false;

        if (!parent::delete())
            return false;

        // Remove dept access entries
        GroupDeptAccess::objects()
            ->filter(array('group_id'=>$this->getId()))
            ->delete();

        return true;
    }

    function __toString() {
        return $this->getName();
    }

    function save($refetch=false) {
        if ($this->dirty) {
            $this->updated = SqlFunction::NOW();
        }
        return parent::save($refetch || $this->dirty);
    }

    function update($vars,&$errors) {
        if (isset($this->id) && $this->getId() != $vars['id'])
            $errors['err'] = __('Missing or invalid group ID');

        if (!$vars['name']) {
            $errors['name'] = __('Group name required');
        } elseif(strlen($vars['name'])<3) {
            $errors['name'] = __('Group name must be at least 3 chars.');
        } elseif (($gid=static::getIdByName($vars['name']))
                && (!isset($this->id) || $gid!=$this->getId())) {
            $errors['name'] = __('Group name already exists');
        }

        if (!$vars['role_id'])
            $errors['role_id'] = __('Role selection required');

        if ($errors)
            return false;

        $this->name = Format::striptags($vars['name']);
        $this->role_id = $vars['role_id'];
        $this->notes = Format::sanitize($vars['notes']);

        if ($vars['isactive'])
            $this->flags = ($this->flags | self::FLAG_ENABLED);
        else
            $this->flags =  ($this->flags & ~self::FLAG_ENABLED);

        if ($this->save())
            return $this->updateDeptAccess($vars['depts'] ?: array(), $vars);

        if (isset($this->id)) {
            $errors['err']=sprintf(__('Unable to update %s.'), __('this group'))
               .' '.__('Internal error occurred');
        }
        else {
            $errors['err']=sprintf(__('Unable to create %s.'), __('this group'))
               .' '.__('Internal error occurred');
        }
        return false;
    }

    /*** Static functions ***/
    static function getIdByName($name){
        $id = static::objects()->filter(array('name'=>trim($name)))
            ->values_flat('id')->first();

        return $id ? $id[0] : 0;
    }

    static function create($vars=false) {
        $group = parent::create($vars);
        $group->created = SqlFunction::NOW();
        return $group;
    }

    static function __create($vars, &$errors) {
        $g = self::create($vars);
        $g->save();
        if ($vars['depts'])
            $g->updateDeptAccess($vars['depts'], $vars);

        return $g;
    }

    static function getGroups($criteria=array()) {
        static $groups = null;
        if (!isset($groups) || $criteria) {
            $groups = array();
            $query = static::objects()
                ->values_flat('id', 'name', 'flags')
                ->order_by('name');

            $filters = array();
            if (isset($criteria['active']))
                $filters += array(
                        'isactive' => $criteria['active'] ? 1 : 0);

            if ($filters)
                $query->filter($filters);

            $names = array();
            foreach ($query as $row) {
                list($id, $name, $flags) = $row;
                $names[$id] = sprintf('%s%s',
                    self::getLocalById($id, 'name', $name),
                    $flags ? '' : ' ' . __('(disabled)'));
            }

            //TODO: sort if $criteria['localize'];
            if ($criteria)
                return $names;

            $groups = $names;
        }

        return $groups;
    }

    static function getActiveGroups() {
        static $groups = null;

        if (!isset($groups))
            $groups = self::getGroups(array('active'=>true));

        return $groups;
    }

}
?>
