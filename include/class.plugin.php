<?php
require_once(INCLUDE_DIR.'/class.config.php');

/*
 * PluginConfig
 *
 * Base class for plugin configs
 *
 */
abstract class PluginConfig extends Config {
    var $table = CONFIG_TABLE;
    var $instance;
    var $form;
    var $_config = [];

    function __construct($namespace, $defaults=[]) {
        // Use parent constructor to place configurable information into the
        // central config table with $namespace
        parent::__construct($namespace, $defaults);
        // Cache decoded config
        $this->_config = [];
        foreach ($this->getOptions() as $name => $field) {
            if ($this->exists($name)) {
                $val = $this->get($name);
                $this->_config[$name] = $field->to_php($val);
            } elseif (($default = $field->get('default')))
                $this->_config[$name] = $default;
        }
    }

    function getId() {
        if ($this->instance)
            return sprintf('p%di%d',
                    $this->instance->getPluginId(),
                    $this->instance->getId());
    }

    function getName() {
        if ($this->instance)
            return $this->instance->getName();
    }

    function get($key, $default=null) {
        if (isset($this->_config[$key]))
            return $this->_config[$key];
        return parent::get($key, $default);
    }

    function getOptions() {
        return array();
    }

    // getFields is same as getOptions but can be used downstream to filter
    // or modify fields based on settings
    function getFields() {
        return $this->getOptions();
    }

    // Returns form options like title and notices as accepted by SimpleForm
    function getFormOptions() {
        return array();
    }

    function getInfo() {
        return array_merge(parent::getInfo(), $this->_config);
    }

    function getInstance() {
        return $this->instance;
    }

    function setInstance(PluginInstance $i=null) {
        $this->instance = $i;
    }

    function hasCustomConfig() {
        return $this instanceof PluginCustomConfig;
    }

    /**
     * Retreive a Form instance for the configurable options offered in
     * ::getOptions
     */
    function getForm($vars=[]) {
        if (!isset($this->form)) {
            $this->form = new SimpleForm($this->getFields(),
                    null, $this->getFormOptions());
            // set data if any
            if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                // defaults + current info
                $this->form->data(array_merge($vars ?: [],
                            $this->getInfo()));
            } elseif ($vars) {
                 // set possible defaults
                $this->form->data($vars);
            }
        }
        return $this->form;
    }

    /**
     * store
     *
     * Used in the POST request of the configuration process. The
     * ::getForm() method should be used to retrieve a configuration form
     * for this plugin. That form should be submitted via a POST request,
     * and this method should be called in that request. The data from the
     * POST request will be interpreted and will adjust the configuration of
     * this field
     *
     * Parameters:
     * form - plugin confirm form
     * errors - (OUT array) receives validation errors of the parsed
     *      configuration form
     *
     * returns (true | false)
     *
     */
    function store(SimpleForm $form = null, &$errors=array()) {

        try {
            if ($this->hasCustomConfig())
                return $this->saveConfig($form, $errors);

            $form = $form ?: $this->getForm();
            if (($clean=$form->getClean())
                    && $this->pre_save($clean, $errors)
                    && count($errors) === 0
                    && ($data=$form->to_db($clean)))
                return $this->updateAll($data);
        } catch (Throwable $t) {
            if  (!isset($errors['err']))
                $errors['err'] = $t->getMessage();
        }
        return false;
    }

    /**
     * Pre-save hook to check configuration for errors (other than obvious
     * validation errors) prior to saving. Can be also used to encrypt the
     * data if necessary.
     *
     * Add an error to the errors list
     * or return boolean FALSE if the config commit should be aborted.
     */
    function pre_save(&$config, &$errors) {
        return true;
    }


    /**
     * Remove all configuration for this plugin -- used when the plugin is
     * uninstalled / deleted.
     * Plugin
     */
    function purge() {
        return $this->destroy();
    }
}

/**
 * Interface: PluginCustomConfig
 *
 * Allows a plugin to specify custom configuration pages. If the
 * configuration cannot be suited by a single page, single form, then
 * the plugin can use the ::renderConfig() method to trigger
 * rendering the page, and use ::saveConfig() to trigger
 * validating and saving the custom configuration.
 */
interface PluginCustomConfig {
    function renderConfig();
    function saveConfig();
}

class PluginManager {
    const VERIFIED = 1;             // Thumbs up
    const VERIFY_EXT_MISSING = 2;   // PHP extension missing
    const VERIFY_FAILED = 3;        // Bad signature data
    const VERIFY_ERROR = 4;         // Unable to verify (unexpected error)
    const VERIFY_NO_KEY = 5;        // Public key missing
    const VERIFY_DNS_PASS = 6;      // DNS check passes, cannot verify sig

    static private $verify_domain = 'updates.osticket.com';
    static private $plugin_info = array();
    static private $plugin_list = array();

    /**
     * boostrap
     *
     * Used to bootstrap the plugin subsystem and initialize all the plugins
     * currently enabled.
     */
    function bootstrap() {
        foreach ($this->allInstalled() as $p) {
            if (!$p->isCompatible())
                continue;
            // Init plugin even when disabled
            $p->init();
            // If the plugin is active then boostrap active instances
            if ($p->isActive()) {
                foreach($p->getActiveInstances() as $i)
                    $i->bootstrap();
            }
            // Clear any side loaded config
            $p->config = null;
        }
    }

    /**
     * allInstalled
     *
     * Scans the plugin registry to find all installed and active plugins.
     * Those plugins are included, instanciated, and cached in a list.
     *
     * Returns:
     * Array<Plugin> a cached list of instanciated plugins for all installed
     * and active plugins
     */
    static function allInstalled() {
        if (static::$plugin_list)
            return static::$plugin_list;
        foreach (Plugin::objects() as $p) {
            $a = $p->getImpl() ?: $p;
            static::$plugin_list[$p->getInstallPath()] = &$a;
            unset($a);
        }
        return static::$plugin_list;
    }

    static function getPluginByName($name, $active=false) {
        $sql = sprintf('SELECT * FROM %s WHERE name="%s"', PLUGIN_TABLE, $name);
        if ($active)
            $sql = sprintf('%s AND isactive = true', $sql);
        if (!($res = db_query($sql)))
            return false;
        $ht = db_fetch_array($res);
        return $ht['name'];
    }

    static function auditPlugin() {
        global $ost;
        if (!$ost || $ost->isUpgradePending())
            return false;

        return self::getPluginByName('Help Desk Audit', true);
    }

    static function allActive() {
        $plugins = array();
        foreach (static::allInstalled() as $p)
            if ($p instanceof Plugin && $p->isActive())
                $plugins[] = $p;
        return $plugins;
    }

    static function throwException($errno, $errstr) {
        throw new RuntimeException($errstr);
    }

    /**
     * allInfos
     *
     * Scans the plugin folders for installed plugins. For each one, the
     * plugin.php file is included and the info array returned in added to
     * the list returned.
     *
     * Returns:
     * Information about all available plugins. The registry will have to be
     * queried to determine if the plugin is installed
     */
    static function allInfos() {
        foreach (glob(INCLUDE_DIR . 'plugins/*',
                GLOB_NOSORT) as $p) {
            $is_phar = false;
            if (substr($p, strlen($p) - 5) == '.phar'
                    && class_exists('Phar')
                    && Phar::isValidPharFilename($p)) {
                try {
                // When public key is invalid, openssl throws a
                // 'supplied key param cannot be coerced into a public key' warning
                // and phar ignores sig verification.
                // We need to protect from that by catching the warning
                // Thanks, https://github.com/koto/phar-util
                set_error_handler(array('self', 'throwException'));
                $ph = new Phar($p);
                restore_error_handler();
                // Verify the signature
                $ph->getSignature();
                $p = 'phar://' . $p;
                $is_phar = true;
                } catch (UnexpectedValueException $e) {
                    // Cannot find signature file
                } catch (RuntimeException $e) {
                    // Invalid signature file
                }

            }

            if (!is_file($p . '/plugin.php'))
                // Invalid plugin -- must define "/plugin.php"
                continue;

            // Cache the info into static::$plugin_info
            static::getInfoForPath($p, $is_phar);
        }
        return static::$plugin_info;
    }

    static function getInfoForPath($path, $is_phar=false) {
        static $defaults = array(
            'include' => 'include/',
            'stream' => false,
        );

        $install_path = str_replace(INCLUDE_DIR, '', $path);
        $install_path = str_replace('phar://', '', $install_path);
        if ($is_phar && substr($path, 0, 7) != 'phar://')
            $path = 'phar://' . $path;
        if (!isset(static::$plugin_info[$install_path])) {
            // plugin.php is require to return an array of informaiton about
            // the plugin.
            if (!file_exists($path . '/plugin.php'))
                return false;
            $info = array_merge($defaults, (@include $path . '/plugin.php'));
            $info['install_path'] = $install_path;

            // XXX: Ensure 'id' key isset
            static::$plugin_info[$install_path] = $info;
        }
        return static::$plugin_info[$install_path];
    }

    static function getInstance($path) {
        static $instances = array();
        if (!isset($instances[$path])
                && ($ps = static::allInstalled())
                && ($ht = $ps[$path])) {

            $info = static::getInfoForPath($path);

            // $ht may be the plugin instance
            if ($ht instanceof Plugin)
                return $ht;

            // Usually this happens when the plugin is being enabled
            list($path, $class) = explode(':', $info['plugin']);
            if (!$class)
                $class = $path;
            else
                require_once(INCLUDE_DIR . $info['install_path'] . '/' . $path);
            $instances[$path] = new $class($ht['id']);
        }
        return $instances[$path];
    }

    static function lookup($id) {
        if (!($p=Plugin::lookup( (int) $id)))
            return null;

        return $p->getImpl() ?: $p;
    }


    /**
     * install
     *
     * Used to install a plugin that is in-place on the filesystem, but not
     * registered in the plugin registry -- the %plugin table.
     */
    function install($path) {
        // Fixup $path to ensure we are only installing from the plugin dir
        $path = 'plugins/' . basename($path);
        $is_phar = substr($path, strlen($path) - 5) == '.phar';
        if (!($info = $this->getInfoForPath(INCLUDE_DIR . $path, $is_phar)))
            return false;

        if (isset($info['ost_version']) && !self::isCompatible($info['ost_version']))
            return false;

        $vars = [
            'name' => $info['name'],
            'isphar' => $is_phar,
            'version' => $info['version'] ?: '',
            'install_path' => $path
        ];
        $p = Plugin::create($vars);
        if ($p->save(true)) {
            // TODO: Trigger enable() for the plugin (for now)
            $p->getImpl()->enable();
            static::clearCache();
            return $p;
        }
    }

    static function clearCache() {
        static::$plugin_list = array();
        static::$plugin_info = array();
    }

    /**
     * Function: isCompatible
     *
     * Check if provided plugin (info) is compatible with the current version
     * of osTicket.
     *
     * Compatibility is only checked aganist osTicket major version by
     * default because full version is only available on packaged
     * versions and not git repo clones.
     *
     */
     static function isCompatible($version, $ost_version=null) {
         // Assume compatible if specific osTicket version is not required.
         if (!$version)
             return true;

         $matches = array();
         if (!$ost_version
                 && preg_match_all('/\./', $version, $matches, PREG_OFFSET_CAPTURE) == 2)
             $version = substr($version, 0, $matches[0][1][1]);

         return version_compare($ost_version ?: MAJOR_VERSION, $version, '>=');
     }

    /**
     * Function: isVerified
     *
     * This will help verify the content, integrity, oversight, and origin
     * of plugins, language packs and other modules distributed for
     * osTicket.
     *
     * This idea is that the signature of the PHAR file will be registered
     * in DNS, for instance,
     * `7afc8bf80b0555bed88823306744258d6030f0d9.updates.osticket.com`, for
     * a PHAR file with a SHA1 signature of
     * `7afc8bf80b0555bed88823306744258d6030f0d9 `, which will resolve to a
     * string like the following:
     * ```
     * "v=1; i=storage:s3; s=MEUCIFw6A489eX4Oq17BflxCZ8+MH6miNjtcpScUoKDjmb
     * lsAiEAjiBo9FzYtV3WQtW6sbhPlJXcoPpDfYyQB+BFVBMps4c=; V=0.1;"
     * ```
     * Which is a simple semicolon separated key-value pair string with the
     * following keys
     *
     *   Key | Description
     *  :----|:---------------------------------------------------
     *   v   | Algorithm version
     *   i   | Plugin 'id' registered in plugin.php['id']
     *   V   | Plugin 'version' registered in plugin.php['version']
     *   s   | OpenSSL signature of the PHAR SHA1 signature using a
     *       | private key (specified on the command line)
     *
     * The public key, which will be distributed with osTicket, can be used
     * to verify the signature of the PHAR file from the data received from
     * DNS.
     *
     * Parameters:
     * $phar - (string) filename of phar file to verify
     *
     * Returns:
     * (int) -
     *      PluginManager::VERIFIED upon success
     *      PluginManager::VERIFY_DNS_PASS if found in DNS but cannot verify sig
     *      PluginManager::VERIFY_NO_KEY if public key not found in include/plugins
     *      PluginManager::VERIFY_FAILED if the plugin fails validation
     *      PluginManager::VERIFY_EXT_MISSING if a PHP extension is required
     *      Plugin::VERIFY_ERROR if an unexpected error occurred
     */
    static function isVerified($phar) {
        static $pubkey = null;

        if (!class_exists('Phar') || !extension_loaded('openssl'))
            return self::VERIFY_EXT_MISSING;
        elseif (!file_exists(INCLUDE_DIR . '/plugins/updates.pem'))
            return self::VERIFY_NO_KEY;

        if (!isset($pubkey)) {
            $pubkey = openssl_pkey_get_public(
                    file_get_contents(INCLUDE_DIR . 'plugins/updates.pem'));
        }
        if (!$pubkey) {
            return self::VERIFY_ERROR;
        }

        $P = new Phar($phar);
        $sig = $P->getSignature();
        $info = array();
        $ignored = null;
        if ($r = dns_get_record($sig['hash'].'.'.self::$verify_domain.'.', DNS_TXT)) {
            foreach ($r as $rec) {
                foreach (explode(';', $rec['txt']) as $kv) {
                    list($k, $v) = explode('=', trim($kv));
                    $info[$k] = trim($v);
                }
                if ($info['v'] && $info['s'])
                    break;
            }
        }

        if (is_array($info) && isset($info['v'])) {
            switch ($info['v']) {
            case '1':
                if (!($signature = base64_decode($info['s'])))
                    return self::VERIFY_FAILED;
                elseif (!function_exists('openssl_verify'))
                    return self::VERIFY_DNS_PASS;

                $codes = array(
                    -1 => self::VERIFY_ERROR,
                    0 => self::VERIFY_FAILED,
                    1 => self::VERIFIED,
                );
                $result = openssl_verify($sig['hash'], $signature, $pubkey,
                    OPENSSL_ALGO_SHA1);
                return $codes[$result];
            }
        }
        return self::VERIFY_FAILED;
    }

    static function showVerificationBadge($phar) {
        switch (self::isVerified($phar)) {
        case self::VERIFIED:
            $show_lock = true;
        case self::VERIFY_DNS_PASS: ?>
        &nbsp;
        <span class="label label-verified" title="<?php
            if ($show_lock) echo sprintf(__('Verified by %s'), self::$verify_domain);
            ?>"> <?php
            if ($show_lock) echo '<i class="icon icon-lock"></i>'; ?>
            <?php echo $show_lock ? __('Verified') : __('Registered'); ?></span>
<?php       break;
        case self::VERIFY_FAILED: ?>
        &nbsp;
        <span class="label label-danger" title="<?php
            echo __('The originator of this extension cannot be verified');
            ?>"><i class="icon icon-warning-sign"></i></span>
<?php       break;
        }
    }
}


class Plugin extends VerySimpleModel {
    static $meta = array(
        'table' => PLUGIN_TABLE,
        'ordering' => array('name'),
        'pk' => array('id'),
        'joins' => array(
            'instances' => array(
                'reverse' => 'PluginInstance.plugin',
            ),
        ),
    );

    /**
     * Configuration manager for the plugin. Should be the name of a class
     * that inherits from PluginConfig. This is abstract and must be defined
     * by the plugin subclass.
     */
    var $config_class = null;
    //  plugin subclass impl
    var $_impl;
    // config instance
    var $config;
    // active instances
    var $active_instances;

    //
    var $info;
    var $form;
    var $defunct;

    /**
     * init
     *
     * Used to initialize the plugin as part of bootstrapping process
     */

    function init() {
        //noop
    }

    function __onload() {
        $this->info = PluginManager::getInfoForPath(INCLUDE_DIR.$this->ht['install_path'],
            $this->isPhar());
        // Auto upgrade / downgrade plugins on-load - if we have version
        // mismatch. See upgrade routine for more information
        if ($this->info['version'] && $this->getVersion() != $this->info['verion'])
            $this->upgrade();
    }

    /*
     * useModalConfig
     *
     * Plugin instance can be configured via a dialog modal or inline page.
     * A modal is suitable for plugins with short or no configuration,
     * whereas inline page caters for more complex / larger configuration
     */
    function useModalConfig() {
        return false;
    }


    /*
     * canAddInstance
     *
     * Multi-instance plugins can add unlimited instances, otherwise only
     * one plugin instance is allowed.
     *
     */
    function canAddInstance() {

        // No instances yet
        if (!$this->getNumInstances())
            return true;

        // We have at least one instance already.
        if (!$this->isMultiInstance())
            return false;

        // Some Plugins DO Not or SHOULDN'T support multiple instances due
        // design issues or simply because of the fact that it doesn't make
        // sense to do so.

        // TODO: The Black List is to be removed down the road once plugins
        // are forced to declare core osTicket version and if it supports
        // multiple instances.
        $blackList =[
            // 2FA Plugin up to v0.3 cannot handle multiple instances
            'Auth2FAPlugin' => 0.3,
            // It doesn't make sense for Audit Plugin to have
            // multiple instances
            'AuditPlugin' => '*',
            // File storage plugins don't currently support multiple instances
            'S3StoragePlugin' => '*',
            'FsStoragePlugin' => '*',
        ];
        foreach ($blackList as $c => $v) {
            if (is_a($this, $c) && ($v == '*' || $this->getVersion() <= $v))
                return false;
        }
        // Yes, let's make instances - Genesis 9:7
        return true;
    }

   /*
    * Get Namespace of the instance otherwise return plugin's namespace
    *
    */
    function getNamespace() {
        if (($c=$this->getConfig()) && ($i=$c->getInstance()))
            return $i->getNamespace();

        return sprintf('plugin.%d', $this->getId());
    }

    /*
     *
     * isMultiInstance
     *
     * Indicates if the plugin supports multiple instances
     * Default is true unless overwritten downstream.
     *
     */

    function isMultiInstance() {
        return true;
    }

    /*
     * getNewInstanceOptions
     *
     * Plugin can return new instance options if it supports different types
     *  of instances.
     */
    public function getNewInstanceOptions() {
        return false;
    }

    /*
     * getNewInstanceDefaults
     *
     * Plugin can return new instance options if it supports different types
     *  of instances.
     */
    public function getNewInstanceDefaults($options) {
        return [];
    }

    /*
     * getImpl
     *
     * Returns plugin subclass impl if any
     */
    function getImpl() {
        if (!isset($this->_impl) && $this->info['plugin']) {
            // TODO: if if the class is already cached in PluginManager
            list($file, $class) = explode(':', $this->info['plugin']);
            $path = INCLUDE_DIR . $this->getInstallPath();
            if ($this->isPhar())
                $path = 'phar://' . $path;
            $file = "$path/$file";
            if (file_exists($file)) {
                // Register possible plugin namespace before init
                osTicket::register_namespace(dirname($file).'/lib');
                @include_once $file;
                if (class_exists($class))
                    $this->_impl = $class::lookup($this->getId());
            }
        }
        $this->defunct = !isset($this->_impl);

        return $this->_impl;
    }

    function getId() { return $this->get('id'); }
    function getName() { return $this->__($this->get('name', $this->info['name'])); }
    function isActive() { return ($this->get('isactive')); }
    function isPhar() { return $this->get('isphar'); }
    function getVersion() { return $this->get('version', $this->info['version']); }
    function getosTicketVersion() { return $this->info['ost_version']; }
    function getAuthor() { return $this->info['author']; }
    function getInstallDate() { return $this->get('installed'); }
    function getInstallPath() { return $this->get('install_path'); }
    function getNotes() { return $this->get('notes') ?: $this->info['description']; }

    function getStatus() {
        return __($this->isActive() ? 'Active' : 'Disabled');
    }

    function getNumInstances() {
        return $this->getInstances()->count();
    }

    function getInstances() {
        return $this->instances;
    }

    function getActiveInstances() {
        if (!isset($this->active_instances)) {
            $instances = clone $this->instances;
            $this->active_instances = $instances->filter(
                    ['flags__hasbit' => PluginInstance::FLAG_ENABLED]);
        }
        return $this->active_instances;
    }

    function getInstance($id) {
        return $this->instances->findFirst(['id' => (int) $id]);
    }

    function getIncludePath() {
        return realpath(INCLUDE_DIR . $this->info['install_path'] . '/'
            . $this->info['include_path']) . '/';
    }

    function isDefunct() {
        if (!isset($this->defunct)) {
            $this->defunct = false;
            if (!$this->info['plugin'] || !$this->getImpl())
                $this->defunct = true;
        }
        return  $this->defunct;
    }

    function isCompatible() {
        if (!($v=$this->getosTicketVersion()))
            return true;

        return PluginManager::isCompatible($v);
    }

    function update($vars, &$errors) {
        $this->isactive = $vars['isactive'];
        $this->notes = $vars['notes'];
        return $this->save(true);
    }

    function getConfigClass() {
        return $this->config_class;
    }

    function getConfig(PluginInstance $instance = null, $defaults = []) {
        if ((!isset($this->config) || $instance)
                && ($class=$this->getConfigClass())) {
            // We cache instance config for side loading purposes on
            // plugin instantace boostrapping
            if ($instance)
                // Config for an instance.
                $this->config = $instance->getConfig($defaults);
            else
                //  New instance config
                $this->config = new $class(null, $defaults);
        }
        return $this->config;
    }

    function getConfigForm($vars=null) {
        if (!isset($this->form) || $vars)
            $this->form = $this->getConfig(null, $vars)->getForm($vars);

        return $this->form;
    }

    function isInstanceUnique($vars, $id=0) {
        $criteria = array();
        // Make sure name is unique
        $criteria = ['name' => $vars['name']];
        $i = $this->instances->findFirst($criteria);
        return !($i && $i->getId() != $id);
    }

    function addInstance($vars, &$errors) {
        $form = $this->getConfigForm($vars);
        if (!$vars['name'])
            $errors['name']= __('Name Required');
        elseif (!$this->isInstanceUnique($vars))
            $errors['name']= __('Name already in use');
        if ($form->isValid() && !$errors) {
            // Basic info required to create instance
            $info = [
                'name' => $vars['name'],
                'plugin_id' => $this->getId(),
                'flags' => 0];
            if (($i=PluginInstance::create($info)) && $i->save()) {
                // clear temp config
                $this->config = null;
                // update newly created instance...
                $i->update($vars, $errors);
                return $i;
            }
        }
        return false;
    }

    function delete() {
        foreach ($this->getInstances() as $i)
            $i->delete();
        parent::delete();
    }

    /**
     * upgrade
     *
     * Plugins can implement upgrade / downgrade process downsteam as needed.
     *
     */
    function upgrade(&$errors=[]) {
        if ($this->pre_upgrade($errors) === false)
            return false;

        // For now we're just updating the version if we have a mismatch
        // The true version is what is packaged with the plugin
        if ($this->getVersion() != $this->info['version'])
            $this->version = $this->info['version'];

        $this->save();
        return true;
    }

    /**
     * pre_upgrade
     *
     * Hook function to veto the upgrade request. Return boolean
     * FALSE if the upgrade operation should be aborted.
     */
    function pre_upgrade(&$errors) {
        return true;
    }

    /**
     * uninstall
     *
     * Removes the plugin from the plugin registry. The files remain on the
     * filesystem which would allow the plugin to be reinstalled. The
     * configuration for the plugin is also removed. If the plugin is
     * reinstalled, it will have to be reconfigured.
     */
    function uninstall(&$errors) {
        if ($this->pre_uninstall($errors) === false)
            return false;

        $this->delete();
        PluginManager::clearCache();
        return true;
    }

    /**
     * pre_uninstall
     *
     * Hook function to veto the uninstallation request. Return boolean
     * FALSE if the uninstall operation should be aborted.
     */
    function pre_uninstall(&$errors) {
        return true;
    }

    function enable() {
        return true;
    }

    /**
     * Function: __
     *
     * Translate a single string (without plural alternatives) from the
     * langauge pack installed in this plugin. The domain is auto-configured
     * and detected from the plugin install path.
     */
    function __($msgid) {
        if (!isset($this->translation)) {
            // Detect the domain from the plugin install-path
            $groups = array();
            preg_match('`plugins/(\w+)(?:.phar)?`', $this->getInstallPath(), $groups);

            $domain = $groups[1];
            if (!$domain)
                return $msgid;

            $this->translation = self::translate($domain);
        }
        list($__, $_N) = $this->translation;
        return $__($msgid);
    }

    // Domain-specific translations (plugins)
    /**
     * Function: translate
     *
     * Convenience function to setup translation functions for other
     * domains. This is of greatest benefit for plugins. This will return
     * two functions to perform the translations. The first will translate a
     * single string, the second will translate a plural string.
     *
     * Parameters:
     * $domain - (string) text domain. The location of the MO.php file
     *      will be (path)/LC_MESSAGES/(locale)/(domain).mo.php. The (path)
     *      can be set via the $options parameter
     * $options - (array<string:mixed>) Extra options for the setup
     *      "path" - (string) path to the folder containing the LC_MESSAGES
     *          folder. The (locale) setting is set externally respective to
     *          the user. If this is not set, the directory of the caller is
     *          assumed, plus '/i18n'.  This is geared for plugins to be
     *          built with i18n content inside the '/i18n/' folder.
     *
     * Returns:
     * Translation utility functions which mimic the __() and _N()
     * functions. Note that two functions are returned. Capture them with a
     * PHP list() construct.
     *
     * Caveats:
     * When desiging plugins which might be installed in versions of
     * osTicket which don't provide this function, use this compatibility
     * interface:
     *
     * // Provide compatibility function for versions of osTicket prior to
     * // translation support (v1.9.4)
     * function translate($domain) {
     *     if (!method_exists('Plugin', 'translate')) {
     *         return array(
     *             function($x) { return $x; },
     *             function($x, $y, $n) { return $n != 1 ? $y : $x; },
     *         );
     *     }
     *     return Plugin::translate($domain);
     * }
     */
    static function translate($domain, $options=array()) {

        // Configure the path for the domain. If no
        $path = @$options['path'];
        if (!$path) {
            # Fetch the working path of the caller
            $bt = debug_backtrace(false);
            $path = dirname($bt[0]["file"]) . '/i18n';
        }
        $path = rtrim($path, '/') . '/';

        $D = TextDomain::lookup($domain);
        $D->setPath($path);
        $trans = $D->getTranslation();

        return array(
            // __()
            function($msgid) use ($trans) {
                return $trans->translate($msgid);
            },
            // _N()
            function($singular, $plural, $n) use ($trans) {
                return $trans->ngettext($singular, $plural, $n);
            },
        );
    }

    static function create($vars=false) {
        $p = new Static($vars);
        $p->installed = SqlFunction::NOW();
        return $p;
    }
}


/**
 * Represents an instance of a plugin
 *
 */
class PluginInstance extends VerySimpleModel {
    static $meta = array(
        'table' => PLUGIN_INSTANCE_TABLE,
        'pk' => array('id'),
        'ordering' => array('name'),
        'joins' => array(
            'plugin' => array(
                'null' => false,
                'constraint' => array('plugin_id' => 'Plugin.id'),
            ),
        ),
    );

    // Config class that plugin can set.
    private $config_class = null;
    // Plugin Config for the instance
    var $_config;
    var $_form;
    var $_data;

    const FLAG_ENABLED  =  0x0001;

    protected function hasFlag($flag) {
        return ($this->get('flags') & $flag) !== 0;
    }

    protected function clearFlag($flag) {
        return $this->set('flags', $this->get('flags') & ~$flag);
    }

    protected function setFlag($flag, $val) {
        if ($val)
            $this->flags |= $flag;
        else
            $this->flags &= ~$flag;
    }

    function getId() {
        return $this->get('id');
    }

    function getName() {
        return $this->get('name');
    }

    function getCreateDate() {
        return $this->get('created');
    }

    function getUpdateDate() {
        return $this->get('updated');
    }

    function getPluginId() {
        return $this->plugin_id;
    }

    function getPlugin() {
        return $this->plugin->getImpl();
    }

    function isEnabled() {
        return $this->hasFlag(self::FLAG_ENABLED);
    }

    function setStatus($status) {
        $this->setFlag(self::FLAG_ENABLED, $status);
    }

    function setConfigClass($class) {
        $this->config_class  = $class;
        // Clear current config so it can be reloaded
        $this->_config = null;
    }

    function getConfigClass() {
        return $this->config_class ?: $this->getPlugin()->getConfigClass();
    }

    function getConfig($defaults=[]) {
        $class = $this->getConfigClass();
        if (!isset($this->_config) && $class) {
            $this->_config =  new $class($this->getNamespace(), $defaults);
            $this->_config->setInstance($this);
        }
        return $this->_config;
    }

    function getForm($vars=null) {
        if (!isset($this->_form) || $vars)
            $this->_form = $this->getConfig()->getForm($vars);

        return $this->_form;
    }

    function getSignature() {
        // get Config
        $config = $this->getConfiguration() ?: [];
        // Key sort to normalize config - key order matters with md5
        ksort($config);
        // Json encode and md5
        return md5(json_encode($config));
    }

    function getNamespace() {
        return sprintf('plugin.%d.instance.%d',
                $this->getPluginId(),
                $this->getId() ?: 0);
    }

    function getConfiguration() {
         return $this->getConfig()->getInfo();
    }

    function saveConfiguration($form, &$errors) {
        return $this->getConfig()->store($form, $errors);
    }

    function getInfo() {
        $ht = array_intersect_key($this->ht, array_flip(['name', 'notes']));
        $ht['isactive'] = $this->isEnabled() ? 1 : 0;
        return $ht;
    }

    function isNameUnique($name) {
        return $this->plugin->isInstanceUnique(['name' =>
                Format::htmlchars($name)],
                $this->getId());
    }

    function update($vars, &$errors) {
        if (!$vars['name'])
            $errors['name'] = __('Name Required');
        elseif (!$this->isNameUnique($vars['name']))
            $errors['name'] = __('Name already in-use');

        $form = $this->getForm($vars);
        if ($form->isValid()
                && $this->saveConfiguration($form, $errors)
                && !$errors) {
            $this->setFlag(self::FLAG_ENABLED, ($vars['isactive']));
            $this->name = Format::htmlchars($vars['name']);
            $this->notes = Format::sanitize($vars['notes']);
            $this->updated = SqlFunction::NOW();
            return $this->save();
        }
        return false;
    }

    /**
     * boostrap plugin instance.
     *
     */
    function bootstrap() {
        if ($this->isEnabled()
                && ($plugin = $this->getPlugin())
                // Side load this instance config
                && ($plugin->getConfig($this)))
            return $plugin->bootstrap();
    }

    function delete() {
        $this->getConfig()->purge();
        parent::delete();
    }

    static function create($vars=false) {
        $i = new Static($vars);
        $i->created = SqlFunction::NOW();
        return $i;
    }

}

class DefunctPlugin extends Plugin {
    function bootstrap() {}

    function enable() {
        return false;
    }
}
?>
