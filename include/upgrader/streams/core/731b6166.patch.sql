/**
 * @version v1.9.4 RC4
 * @signature 731b6166edd67b4db8f8a9380309ff62b7feb145
 * @file name 731b6166.patch.sql
 *
 *  - Adds required fields to ticket table
 *  - Adds required fields to ticket_thread table
 */

-- Adds time_spent field to ticket table
ALTER TABLE `%TABLE_PREFIX%ticket` ADD COLUMN `time_spent` FLOAT(4,2)  NOT NULL DEFAULT '0.00' AFTER `closed`;

-- Adds time_spent & time_type field to ticket_thread table
ALTER TABLE `%TABLE_PREFIX%ticket_thread` ADD COLUMN `time_spent` FLOAT(4,2) NOT NULL DEFAULT '0.00' AFTER `thread_type`;
ALTER TABLE `%TABLE_PREFIX%ticket_thread` ADD COLUMN `time_type` CHAR(1) NOT NULL AFTER `time_spent`;
