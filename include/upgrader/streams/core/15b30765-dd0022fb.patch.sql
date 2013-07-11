/**
 * @version v1.7 RC2+
 * @signature dd0022fb14892c0bb6a9700392df2de7
 *
 * Migrate file attachment data from %file to %file_chunk
 *
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%file_chunk`;
CREATE TABLE `%TABLE_PREFIX%file_chunk` (
    `file_id` int(11) NOT NULL,
    `chunk_id` int(11) NOT NULL,
    `filedata` longblob NOT NULL,
    PRIMARY KEY (`file_id`, `chunk_id`)
) DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%file_chunk` (`file_id`, `chunk_id`, `filedata`)
    SELECT `id`, 0, `filedata`
    FROM `%TABLE_PREFIX%file`;

ALTER TABLE `%TABLE_PREFIX%file` DROP COLUMN `filedata`;
OPTIMIZE TABLE `%TABLE_PREFIX%file`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='dd0022fb14892c0bb6a9700392df2de7';
