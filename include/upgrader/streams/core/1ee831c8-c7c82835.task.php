<?php
class GroupRoles extends MigrationTask {

    var $pmap = array(
            'can_create_tickets'  => 'ticket.create',
            'can_edit_tickets' => 'ticket.edit',
            'can_post_ticket_reply' => 'ticket.reply',
            'can_delete_tickets' => 'ticket.delete',
            'can_close_tickets' => 'ticket.close',
            'can_assign_tickets' => 'ticket.assign',
            'can_transfer_tickets' => 'ticket.transfer',
            'can_ban_emails' => 'emails.banlist',
            'can_manage_premade' => 'kb.premade',
            'can_manage_faq' => 'kb.faq',
            'can_view_staff_stats' => 'stats.agents');

    function run($max_time) {
        global $cfg;
        // Select existing groups and create roles matching the current
        // settings
        foreach (Group::objects() as $group) {
            $ht=array(
                    'flags=1',
                    'name' => sprintf('%s %s', $group->getName(),
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
    }
}

return 'GroupRoles';
