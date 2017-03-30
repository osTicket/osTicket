/**
 * @signature 2d590ffab4a6a928f08cc97aace1399e
 * @version v1.9.6
 * @title Make fields disable-able per help topic
 */

ALTER TABLE `%TABLE_PREFIX%help_topic`
    DROP `form_id`;

ALTER TABLE `%TABLE_PREFIX%filter`
  DROP `reject_ticket`,
  DROP `use_replyto_email`,
  DROP `disable_autoresponder`,
  DROP `canned_response_id`,
  DROP `status_id`,
  DROP `priority_id`,
  DROP `dept_id`,
  DROP `staff_id`,
  DROP `team_id`,
  DROP `sla_id`,
  DROP `form_id`;
