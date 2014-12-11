<?php
/*********************************************************************
    class.role.php

    Role-based access

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class RoleModel extends VerySimpleModel {
    static $meta = array(
        'table' => ROLE_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'groups' => array(
                'null' => true,
                'list' => true,
                'reverse' => 'Group.role',
            ),
            'agents' => array(
                'reverse' => 'Staff.role',
            ),
        ),
    );

    // Flags
    const FLAG_ENABLED   = 0x0001;

    protected function hasFlag($flag) {
        return ($this->get('flags') & $flag) !== 0;
    }

    protected function clearFlag($flag) {
        return $this->set('flags', $this->get('flags') & ~$flag);
    }

    protected function setFlag($flag) {
        return $this->set('flags', $this->get('flags') | $flag);
    }

    function getId() {
        return $this->id;
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

    function getInfo() {
        return $this->ht;
    }

    function isEnabled() {
        return $this->hasFlag(self::FLAG_ENABLED);
    }

    function isDeleteable() {
        return $this->groups->count() + $this->agents->count() == 0;
    }

}

class Role extends RoleModel {
    var $form;
    var $entry;

    var $_perm;

    function getPermission() {
        if (!$this->_perm) {
            $this->_perm = new RolePermission(
                isset($this->permissions) ? $this->permissions : array()
            );
        }
        return $this->_perm;
    }

    function getPermissionInfo() {
        return $this->getPermission()->getInfo();
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('role.%s.%s', $subtag, $this->getId()));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->ht[$subtag];
    }

    function to_json() {

        $info = array(
                'id'    => $this->getId(),
                'name'  => $this->getName()
                );

        return JsonDataEncoder::encode($info);
    }

    function __toString() {
        return (string) $this->getName();
    }

    function __call($what, $args) {
        $rv = null;
        if($this->getPermission() && is_callable(array($this->_perm, $what)))
            $rv = $args
                ? call_user_func_array(array($this->_perm, $what), $args)
                : call_user_func(array($this->_perm, $what));

        return $rv;
    }

    private function updatePerms($vars, &$errors=array()) {

        $config = array();
        $permissions = $this->getPermission();
        foreach (RolePermission::allPermissions() as $g => $perms) {
            foreach($perms as $k => $v) {
                $permissions->set($k, in_array($k, $vars) ? 1 : 0);
            }
        }
        $this->permissions = $permissions->toJson();
    }

    function update($vars, &$errors) {

        if (!$vars['name'])
            $errors['name'] = __('Name required');
        elseif (($r=Role::lookup(array('name'=>$vars['name'])))
                && $r->getId() != $vars['id'])
            $errors['name'] = __('Name already in-use');
        elseif (!$vars['perms'] || !count($vars['perms']))
            $errors['err'] = __('Must check at least one permission for the role');

        if ($errors)
            return false;

        $this->name = $vars['name'];
        $this->notes = $vars['notes'];

        $this->updatePerms($vars['perms'], $errors);

        if (!$this->save(true))
            return false;

        return true;
    }

    function save($refetch=false) {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        if (isset($this->dirty['notes']))
            $this->notes = Format::sanitize($this->notes);

        return parent::save($refetch | $this->dirty);
    }

    function delete() {

        if (!$this->isDeleteable())
            return false;

        if (!parent::delete())
            return false;

        // Remove dept access entries
        GroupDeptAccess::objects()
            ->filter(array('role_id'=>$this->getId()))
            ->update(array('role_id' => 0));

        return true;
    }

    static function create($vars=false) {
        $role = parent::create($vars);
        $role->created = SqlFunction::NOW();
        return $role;
    }

    static function __create($vars, &$errors) {
        $role = self::create($vars);
        if ($vars['permissions'])
            $role->updatePerms($vars['permissions']);

        $role->save();
        return $role;
    }

    static function getRoles($criteria=null, $localize=true) {
        static $roles = null;

        if (!isset($roles) || $criteria) {

            $filters = array();
            if (isset($criteria['enabled'])) {
                $q = new Q(array('flags__hasbit' => self::FLAG_ENABLED));
                if (!$criteria['enabled'])
                    $q->negate();
                $filters[] = $q;
            }

            $query = self::objects()
                ->order_by('name')
                ->values_flat('id', 'name');

            if ($filters)
                $query->filter($filters);

            $localize_this = function($id, $default) use ($localize) {
                if (!$localize)
                    return $default;
                $tag = _H("role.name.{$id}");
                $T = CustomDataTranslation::translate($tag);
                return $T != $tag ? $T : $default;
            };

            $names = array();
            foreach ($query as $row)
                $names[$row[0]] = $localize_this($row[0], $row[1]);

            if ($criteria || !$localize)
                return $names;

            $roles = $names;
        }

        return $roles;
    }

    static function getActiveRoles() {
        static $roles = null;

        if (!isset($roles))
            $roles = self::getRoles(array('enabled' => true));

        return $roles;
    }
}


class RolePermission {

    static $_permissions = array(
            /* @trans */ 'Tickets' => array(
                'ticket.create'  => array(
                    /* @trans */ 'Create',
                    /* @trans */ 'Ability to open tickets on behalf of users'),
                'ticket.edit'   => array(
                    /* @trans */ 'Edit',
                    /* @trans */ 'Ability to edit tickets'),
                'ticket.assign'   => array(
                    /* @trans */ 'Assign',
                    /* @trans */ 'Ability to assign tickets to agents or teams'),
                'ticket.transfer'   => array(
                    /* @trans */ 'Transfer',
                    /* @trans */ 'Ability to transfer tickets between departments'),
                'ticket.reply'  => array(
                    /* @trans */ 'Post Reply',
                    /* @trans */ 'Ability to post a ticket reply'),
                'ticket.close'   => array(
                    /* @trans */ 'Close',
                    /* @trans */ 'Ability to close tickets'),
                'ticket.delete'   => array(
                    /* @trans */ 'Delete',
                    /* @trans */ 'Ability to delete tickets'),
                ),
            /* @trans */ 'Tasks' => array(
                'task.create'  => array(
                    /* @trans */ 'Create',
                    /* @trans */ 'Ability to create tasks'),
                'task.edit'   => array(
                    /* @trans */ 'Edit',
                    /* @trans */ 'Ability to edit tasks'),
                'task.assign'   => array(
                    /* @trans */ 'Assign',
                    /* @trans */ 'Ability to assign tasks to agents or teams'),
                'task.transfer'   => array(
                    /* @trans */ 'Transfer',
                    /* @trans */ 'Ability to transfer tasks between departments'),
                'task.close'   => array(
                    /* @trans */ 'Close',
                    /* @trans */ 'Ability to close tasks'),
                'tasks.delete'   => array(
                    /* @trans */ 'Delete',
                    /* @trans */ 'Ability to delete tasks'),
                ),
            /* @trans */ 'Knowledgebase' => array(
                'kb.premade'   => array(
                    /* @trans */ 'Premade',
                    /* @trans */ 'Ability to add/update/disable/delete canned responses'),
                'kb.faq'   => array(
                    /* @trans */ 'FAQ',
                    /* @trans */ 'Ability to add/update/disable/delete knowledgebase categories and FAQs'),
                ),
            /* @trans */ 'Misc.' => array(
                'stats.agents'   => array(
                    /* @trans */ 'Stats',
                    /* @trans */ 'Ability to view stats of other agents in allowed departments'),
                'emails.banlist'   => array(
                    /* @trans */ 'Banlist',
                    /* @trans */ 'Ability to add/remove emails from banlist via ticket interface'),
                ),
            );

    var $perms;

    static function allPermissions() {
        return static::$_permissions;
    }

    function __construct($perms) {
        $this->perms = $perms;
        if (is_string($this->perms))
            $this->perms = JsonDataParser::parse($this->perms);
        elseif (!$this->perms)
            $this->perms = array();
    }

    function has($perm) {
        return (bool) $this->get($perm);
    }

    function get($perm) {
        return @$this->perms[$perm];
    }

    function set($perm, $value) {
        if (!$value)
            unset($this->perms[$perm]);
        else
            $this->perms[$perm] = $value;
    }

    function toJson() {
        return JsonDataEncoder::encode($this->perms);
    }

    function getInfo() {
        return $this->perms;
    }

    /* tickets */
    function canCreateTickets() {
        return ($this->has('ticket.create'));
    }

    function canEditTickets() {
        return ($this->has('ticket.edit'));
    }

    function canAssignTickets() {
        return ($this->has('ticket.assign'));
    }

    function canTransferTickets() {
        return ($this->has('ticket.transfer'));
    }

    function canPostReply() {
        return ($this->has('ticket.reply'));
    }

    function canCloseTickets() {
        return ($this->has('ticket.close'));
    }

    function canDeleteTickets() {
        return ($this->has('ticket.delete'));
    }

    /* Knowledge base */
    function canManagePremade() {
        return ($this->has('kb.premade'));
    }

    function canManageCannedResponses() {
        return ($this->canManagePremade());
    }

    function canManageFAQ() {
        return ($this->has('kb.faq'));
    }

    function canManageFAQs() {
        return ($this->canManageFAQ());
    }

    /* stats */
    function canViewStaffStats() {
        return ($this->has('stats.agents'));
    }

    /* email */
    function canBanEmails() {
        return ($this->has('emails.banlist'));
    }
}
?>
