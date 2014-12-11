-- drop old permissions from group table
ALTER TABLE `%TABLE_PREFIX%group`
    DROP `group_enabled`,
    DROP `can_create_tickets`,
    DROP `can_edit_tickets`,
    DROP `can_post_ticket_reply`,
    DROP `can_delete_tickets`,
    DROP `can_close_tickets`,
    DROP `can_assign_tickets`,
    DROP `can_transfer_tickets`,
    DROP `can_ban_emails`,
    DROP `can_manage_premade`,
    DROP `can_manage_faq`,
    DROP `can_view_staff_stats`;

-- drop useless updated column
ALTER TABLE  `%TABLE_PREFIX%team_member` DROP  `updated`;
