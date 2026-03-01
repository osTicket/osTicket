-- set thread entry pid's to show Service/Response time in Dashboard
UPDATE `%TABLE_PREFIX%thread_entry` AS this
INNER JOIN (
	SELECT `%TABLE_PREFIX%thread_entry`.`id`,`%TABLE_PREFIX%thread_entry`.`thread_id`,`%TABLE_PREFIX%thread_entry`.`type`
	FROM `%TABLE_PREFIX%thread_entry` WHERE `%TABLE_PREFIX%thread_entry`.`type` = 'M') AS that
SET this.`pid` = that.`id`
WHERE this.`thread_id` = that.`thread_id` AND that.`type` = 'M' AND this.type = 'R' AND this.`id` > that.`id` AND this.`pid` = 0
