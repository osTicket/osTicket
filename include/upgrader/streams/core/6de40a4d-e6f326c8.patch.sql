/**
 *This patch adds the isrevolvign column to the sla table
*/
ALTER TABLE '%TABLE_PREFIX%sla'  ADD COLUMN 'isrevolving' tinyint(1) NOT NULL default '0';

