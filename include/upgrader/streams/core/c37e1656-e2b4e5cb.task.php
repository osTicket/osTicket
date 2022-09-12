<?php
include_once INCLUDE_DIR.'class.plugin.php';
include_once INCLUDE_DIR.'class.config.php';

class OldPluginConfig extends Config {
    var $table = CONFIG_TABLE;
    function __construct($name) {
         parent::__construct("plugin.$name");
    }
}

class PluginMigration extends MigrationTask {
    var $description = "Migrate Plugins to multi-instance";
    function run($time) {
        global $ost;
        // Migrate old config to Plugin Instance store
        foreach (Plugin::objects() as $plugin) {
            $c = new OldPluginConfig($plugin->getId());
            $config = $c->getInfo() ?: [];
            $instance = [
                'name' => $plugin->getName(),
                'plugin_id' => $plugin->getId(),
                'config' => Format::json_encode($config)];
            $i = PluginInstance::create($instance);
            $i->setStatus($plugin->isActive());
            $i->updated = SqlFunction::NOW();
            $i->save();
            // Delete old config entries
            $c->destroy();
        }
        // Update Plugin ids used to the new format.
        PluginManager::clearCache();
        $ost->plugins->bootstrap();
        // Staff auth backends
        foreach (StaffAuthenticationBackend::allRegistered() as $p) {
            if ($p::$id && $p->getBkId() == $p::$id)
                continue;
            Staff::objects()
                ->filter(['backend' => $p::$id])
                ->update(['backend' => $p->getBkId()]);
        }
        // User auth backends
        foreach (UserAuthenticationBackend::allRegistered() as $p) {
            if ($p::$id && $p->getBkId() == $p::$id)
                continue;
            UserAccount::objects()
                ->filter(['backend' => $p::$id])
                ->update(['backend' => $p->getBkId()]);
        }
        // Password Policies
        $config = $ost->getConfig();
        foreach (PasswordPolicy::allActivePolicies() as $p) {
            if ($config->get('agent_passwd_policy') == $p::$id)
                $config->set('agent_passwd_policy', $p->getBkId());
            if ($config->get('client_passwd_policy') == $p::$id)
                $config->set('client_passwd_policy', $p->getBkId());
        }
    }
}
return 'PluginMigration';
