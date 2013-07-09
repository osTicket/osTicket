/**
 * Transitional patch - FIX on the INSTALLER schema
 * 
 * @version 1.7-dpr3 installerfix
 */

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='49478749dc680eef08b7954bd568cfd1';
