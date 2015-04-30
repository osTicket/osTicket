/**
 * @signature 1ee831c854fe9f35115a3e672916bb91
 * @version v1.9.6
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
