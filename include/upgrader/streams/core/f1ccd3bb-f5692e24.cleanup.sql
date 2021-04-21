/**
 * @version v1.8.1
 * @signature f5692e24c7afba7ab6168dde0b3bb3c8
 * @title Add regex field to ticket filters
 *
 * This fixes a glitch introduced @934954de8914d9bd2bb8343e805340ae where
 * a primary key was added to the %ticket_email_info table so that deleting
 * can be supported in a clustered environment. The patch added the
 * `thread_id` column as the primary key, which was incorrect, because the
 * `thread_id` may be null when rejected emails are recorded so they are
 * never considered again if found in the inbox.
 */

-- Add the primary key. The PK on `thread_id` would have been removed in the
-- task if it existed
ALTER TABLE `%TABLE_PREFIX%ticket_email_info`
    ADD `id` int(11) unsigned not null auto_increment FIRST,
    ADD PRIMARY KEY (`id`);

-- Drop the CDATA table, if any
DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket__cdata`;
