<?php
class GroupRoles extends MigrationTask {
    var $description = "Migrate permissions from Group to Role";

    static $pmap = array(
            'can_create_tickets'    => 'ticket.create',
            'can_edit_tickets'      => 'ticket.edit',
            'can_post_ticket_reply' => 'ticket.reply',
            'can_delete_tickets'    => 'ticket.delete',
            'can_close_tickets'     => 'ticket.close',
            'can_assign_tickets'    => 'ticket.assign',
            'can_transfer_tickets'  => 'ticket.transfer',
            'can_ban_emails'        => 'emails.banlist',
            'can_manage_premade'    => 'canned.manage',
            'can_manage_faq'        => 'faq.manage',
            'can_view_staff_stats'  => 'stats.agents',
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
            foreach (self::$pmap as  $k => $v) {
                if ($group->{$k})
                    $perms[] = $v;
            }

            $ht['permissions'] = $perms;

            $role = Role::__create($ht);
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
