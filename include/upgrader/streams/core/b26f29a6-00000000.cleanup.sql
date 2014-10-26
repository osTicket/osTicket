/**
 * @signature d7480e1c31a1f20d6954ecbb342722d3
 * @version v1.9.5
 * @title Make editable content translatable
 *
 * This patch adds support for translatable administratively editable
 * content, such as help topic names, department and group names, site page
 * and faq content, etc.
 */

DROP TABLE `%TABLE_PREFIX%timezone`;

ALTER TABLE `%TABLE_PREFIX%staff`
    DROP `timezone_id`,
    DROP `daylight_saving`;

ALTER TABLE `%TABLE_PREFIX%user_account`
    DROP `timezone_id`,
    DROP `dst`;
