/**
* @signature e7dfe82131b906a14f6a13163943855f
* @version v1.11.0
* @title Nested Knowledgebase Categories
*
* This patch adds a new field, category_pid, to the faq_category table
* to allow for adding nested categories to the knowledgebase.
*/
ALTER TABLE `%TABLE_PREFIX%faq_category`
    ADD `category_pid` int(10) unsigned DEFAULT NULL AFTER  `category_id`;

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'e7dfe82131b906a14f6a13163943855f'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
