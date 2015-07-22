<?php
define('GROUP_TABLE', TABLE_PREFIX.'group');
define('GROUP_DEPT_TABLE', TABLE_PREFIX.'group_dept_access');
class Group extends VerySimpleModel {
    static $meta = array(
        'table' => GROUP_TABLE,
        'pk' => array('id'),
    );
    const FLAG_ENABLED = 0x0001;

    function getName() {
        return $this->group_name;
    }
    function getId() {
        return $this->id;
    }
}

Staff::getMeta()->addJoin('group', array(
    'constraint' => array('group_id' => 'Group.id'),
));

class GroupRoles extends MigrationTask {
    var $description = "Migrate permissions from Group to Role";

    static $pmap = array(
            'ticket.create' => 'can_create_tickets',
            'ticket.edit' => 'can_edit_tickets',
            'ticket.reply' => 'can_post_ticket_reply',
            'ticket.delete' => 'can_delete_tickets',
            'ticket.close' => 'can_close_tickets',
            'ticket.assign' => 'can_assign_tickets',
            'ticket.transfer' => 'can_transfer_tickets',
            'task.create' => 'can_create_tickets',
            'task.edit' => 'can_edit_tickets',
            'task.reply' => 'can_post_ticket_reply',
            'task.delete' => 'can_delete_tickets',
            'task.close' => 'can_close_tickets',
            'task.assign' => 'can_assign_tickets',
            'task.transfer' => 'can_transfer_tickets',
            'emails.banlist' => 'can_ban_emails',
            'canned.manage' => 'can_manage_premade',
            'faq.manage' => 'can_manage_faq',
            'stats.agents' => 'can_view_staff_stats',
    );

    function run($max_time) {
        global $cfg;
        // Select existing groups and create roles matching the current
        // settings
        foreach (Group::objects() as $group) {
            $ht=array(
                    'flags' => Group::FLAG_ENABLED,
                    'name' => sprintf('%s %s', $group->getName(),
                        // XXX: Translate based on the system language, not
                        //      the current agent's
                        __('Role')),
                    'notes' => $group->getName()
                    );
            $perms = array();
            foreach (self::$pmap as  $v => $k) {
                if ($group->{$k})
                    $perms[] = $v;
            }

            $ht['permissions'] = $perms;

            $errors = array();
            $role = Role::__create($ht, $errors);
            $group->role_id =  $role->getId();
            $group->save();
        }

        // Copy group default role to the agent for the respective primary
        // department role
        foreach (Staff::objects()->select_related('group') as $staff) {
            $staff->role_id = $staff->group->role_id;
            $staff->save();
        }
    }
}

return 'GroupRoles';
