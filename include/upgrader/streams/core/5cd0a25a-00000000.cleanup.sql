/**
 * @signature 0e47d678f50874fa0d33e1e3759f657e
 * @version v1.9.6
 * @title Make fields disable-able per help topic
 */

ALTER TABLE `%TABLE_PREFIX%help_topic`
    DROP `form_id` int(10) unsigned NOT NULL default '0';
