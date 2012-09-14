/**
 * @version v1.7 RC2+
 * @signature dd0022fb14892c0bb6a9700392df2de7
 *
 * Migrate file attachment data from %file to %file_chunk
 *  
 */

CREATE TABLE `%TABLE_PREFIX%T_file_chunk_id` ( `id` int(11) );
-- Support up to 16MB attachments
INSERT INTO `%TABLE_PREFIX%T_file_chunk_id` VALUES (0), (1), (2), (3), (4),
(5), (6), (7), (8), (9), (10), (11), (12), (13), (14), (15), (16), (17),
(18), (19), (20), (21), (22), (23), (24), (25), (26), (27), (28), (29),
(30), (31), (32), (33), (34), (35), (36), (37), (38), (39), (40), (41),
(42), (43), (44), (45), (46), (47), (48), (49), (50), (51), (52), (53),
(54), (55), (56), (57), (58), (59), (60), (61), (62), (63);

DROP TABLE IF EXISTS `%TABLE_PREFIX%file_chunk`;
CREATE TABLE `%TABLE_PREFIX%file_chunk` (
    `file_id` int(11) NOT NULL,
    `chunk_id` int(11) NOT NULL,
    `filedata` longblob NOT NULL,
    PRIMARY KEY (`file_id`, `chunk_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%file_chunk` (`file_id`, `chunk_id`, `filedata`)
    SELECT T1.`id`, T2.`id`,
        SUBSTR(T1.`filedata`, T2.`id` * 256 * 1024 + 1, 256 * 1024)
    FROM `%TABLE_PREFIX%file` T1, `%TABLE_PREFIX%T_file_chunk_id` T2
    WHERE T2.`id` * 256 * 1024 < LENGTH(T1.`filedata`);

ALTER TABLE `%TABLE_PREFIX%file` DROP COLUMN `filedata`;
OPTIMIZE TABLE `%TABLE_PREFIX%file`;

DROP TABLE `%TABLE_PREFIX%T_file_chunk_id`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='dd0022fb14892c0bb6a9700392df2de7';
