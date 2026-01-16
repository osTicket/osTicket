<?php
require_once INCLUDE_DIR.'class.migrater.php';

class APIKeyMigrater extends MigrationTask {
    var $description = "Migrating v1.6 API keys";

    function run() {
        $res = db_query('SELECT api_whitelist, api_key FROM '.CONFIG_TABLE.' WHERE id=1');
        if(!$res || !db_num_rows($res))
            return 0;  //Reporting success.

        list($whitelist, $key) = db_fetch_row($res);

        $ips=array_filter(array_map('trim', explode(',', $whitelist)));
        foreach($ips as $ip) {
            $sql='INSERT INTO '.API_KEY_TABLE.' SET created=NOW(), updated=NOW(), isactive=1 '
                .',ipaddr='.db_input($ip)
                .',apikey='.db_input(strtoupper(md5($ip.md5($key))));
            db_query($sql);
        }
    }
}

return 'APIKeyMigrater';
?>
