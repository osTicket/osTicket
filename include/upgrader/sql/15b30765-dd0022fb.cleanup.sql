
-- Drop fields we no longer need in the reference table.
-- NOTE: This was moved from the 1.6* major upgrade script because the
--       handling of attachments changed with dd0022fb
ALTER TABLE `%TABLE_PREFIX%ticket_attachment`
    DROP `file_size`,
    DROP `file_name`,
    DROP `file_key`,
    DROP `updated`,
    DROP `deleted`;
