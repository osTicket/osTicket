<?php
require_once INCLUDE_DIR.'class.migrater.php';
class ConfigMigrater extends MigrationTask {
    var $description = "Migrating Plugin Config to central config table";
    var $status ="We are going back to central config y'all";
    function run($max_time) {
        // Migrate plugin instance config
        $sql = 'SELECT * FROM '.PLUGIN_INSTANCE_TABLE
            .' WHERE config IS NOT NULL';
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while($row = db_fetch_array($res)) {
                if (!($info=JsonDataParser::parse($row['config'])))
                    continue;
                 $c = new Config(sprintf('plugin.%d.instance.%d',
                            $row['plugin_id'], $row['id']));
                 $c->updateAll($info);
            }
        }
        // Migrate email username and passwd.
        $sql = 'SELECT * FROM '.EMAIL_ACCOUNT_TABLE
            .' WHERE username IS NOT NULL';
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while($row = db_fetch_array($res)) {
                $namespace = sprintf('email.%d.account.%d',
                        $row['email_id'], $row['id']);
                 $c = new Config($namespace);
                 // Decrpt the password the old way so we can re-encrypt the
                 // new way
                 $row['passwd'] = Crypto::decrypt($row['passwd'],
                         SECRET_SALT, $row['username']);
                 $c->updateAll([
                         'username' => $row['username'],
                         'passwd'   => Crypto::encrypt($row['passwd'],
                             SECRET_SALT,
                             md5($row['username'].$namespace))
                 ]);
            }
        }


    }
}
return 'ConfigMigrater';
?>
