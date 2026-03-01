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
require_once INCLUDE_DIR . 'class.forms.php';

class RoleModel extends VerySimpleModel {
    static $meta = array(
        'table' => ROLE_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'extensions' => array(
                'null' => true,
                'list' => true,
                'reverse' => 'StaffDeptAccess.role',
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
        return $this->extensions->count() + $this->agents->count() == 0;
    }

}

class Role extends RoleModel {
    var $form;
    var $entry;

    var $_perm;

    function hasPerm($perm) {
        return $this->getPermission()->has($perm);
    }

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

        foreach ($vars as $k => $val) {
            if (!array_key_exists($val, $permissions->perms)) {
                $type = array('type' => 'edited', 'key' => $val);
                Signal::send('object.edited', $this, $type);
            }
        }

        foreach (RolePermission::allPermissions() as $g => $perms) {
            foreach($perms as $k => $v) {
                if (!in_array($k, $vars) && array_key_exists($k, $permissions->perms)) {
                    $type = array('type' => 'edited', 'key' => $k);
                    Signal::send('object.edited', $this, $type);
                }
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
            $errors['name'] = __('Name already in use');
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

        $type = array('type' => 'deleted');
        Signal::send('object.deleted', $this, $type);

        // Remove dept access entries
        StaffDeptAccess::objects()
            ->filter(array('role_id'=>$this->getId()))
            ->update(array('role_id' => 0));

        return true;
    }

    static function create($vars=false) {
        $role = new static($vars);
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

    // Predefined groups are for sort order.
    // New groups will be appended to the bottom
    static protected $_permissions = array(
            /* @trans */ 'Tickets' => array(),
            /* @trans */ 'Tasks' => array(),
            /* @trans */ 'Users' => array(),
            /* @trans */ 'Organizations' => array(),
            /* @trans */ 'Knowledgebase' => array(),
            /* @trans */ 'Miscellaneous' => array(),
            );

    var $perms;


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

    function merge($perms) {
        if ($perms instanceof self)
            $perms = $perms->getInfo();
        foreach ($perms as $perm=>$value) {
            if (is_numeric($perm)) {
                // Array of perm names
                $perm = $value;
                $value = true;
            }
            $this->set($perm, $value);
        }
    }

    static function allPermissions() {
        static $sorted = false;

        if (!$sorted) {
            // Sort permissions in alphabetical order
            foreach (static::$_permissions as $k => $v) {
                asort(static::$_permissions[$k]);
            }
            $sorted = true;
        }

        return static::$_permissions;
    }

    static function register($group, $perms, $prepend=false) {
        if ($prepend) {
            static::$_permissions[$group] = array_merge(
                $perms, static::$_permissions[$group] ?: array());
        }
        else {
            static::$_permissions[$group] = array_merge(
                static::$_permissions[$group] ?: array(), $perms);
        }
    }
}

class RoleQuickAddForm
extends AbstractForm {
    function buildFields() {
        $permissions = array();
        foreach (RolePermission::allPermissions() as $g => $perms) {
            foreach ($perms as $k => $v) {
                if ($v['primary'])
                    continue;
                $permissions[$g][$k] = "{$v['title']} — {$v['desc']}";
            }
        }
        return array(
            'name' => new TextboxField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __('Name'),
                    'classes' => 'span12',
                    'autofocus' => true,
                    'length' => 128,
                ),
            )),
            'clone' => new ChoiceField(array(
                'default' => 0,
                'choices' =>
                    array(0 => '— '.__('Clone an existing role').' —')
                    + Role::getRoles(),
                'configuration' => array(
                    'classes' => 'span12',
                ),
            )),
            'perms' => new ChoiceField(array(
                'choices' => $permissions,
                'widget' => 'TabbedBoxChoicesWidget',
                'configuration' => array(
                    'multiple' => true,
                    'classes' => 'vertical-pad',
                ),
            )),
        );
    }

    function getClean($validate = true) {
        $clean = parent::getClean();
        // Index permissions as ['ticket.edit' => 1]
        $clean['perms'] = array_keys($clean['perms']);
        return $clean;
    }

    function render($staff=true, $title=false, $options=array()) {
        return parent::render($staff, $title, $options + array('template' => 'dynamic-form-simple.tmpl.php'));
    }
}
