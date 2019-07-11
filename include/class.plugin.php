<?php

require_once(INCLUDE_DIR.'/class.config.php');
class PluginConfig extends Config {
    var $table = CONFIG_TABLE;
    var $form;

    function __construct($name) {
        // Use parent constructor to place configurable information into the
        // central config table in a namespace of "plugin.<id>"
        parent::__construct("plugin.$name");
        foreach ($this->getOptions() as $name => $field) {
            if ($this->exists($name))
                $this->config[$name]->value = $field->to_php($this->get($name));
            elseif ($default = $field->get('default'))
                $this->defaults[$name] = $default;
        }
    }

    /* abstract */
    function getOptions() {
        return array();
    }

    function hasCustomConfig() {
        return $this instanceof PluginCustomConfig;
    }

    /**
     * Retreive a Form instance for the configurable options offered in
     * ::getOptions
     */
    function getForm() {
        if (!isset($this->form)) {
            $this->form = new SimpleForm($this->getOptions());
            if ($_SERVER['REQUEST_METHOD'] != 'POST')
                $this->form->data($this->getInfo());
        }
        return $this->form;
    }

    /**
     * commit
     *
     * Used in the POST request of the configuration process. The
     * ::getForm() method should be used to retrieve a configuration form
     * for this plugin. That form should be submitted via a POST request,
     * and this method should be called in that request. The data from the
     * POST request will be interpreted and will adjust the configuration of
     * this field
     *
     * Parameters:
     * errors - (OUT array) receives validation errors of the parsed
     *      configuration form
     *
     * Returns:
     * (bool) true if the configuration was updated, false if there were
     * errors. If false, the errors were written into the received errors
     * array.
     */
    function commit(&$errors=array()) {
        global $msg;

        if ($this->hasCustomConfig())
            return $this->saveCustomConfig($errors);

        return $this->commitForm($errors);
    }

    function commitForm(&$errors=array()) {
        global $msg;

        $f = $this->getForm();
        $commit = false;
        if ($f->isValid()) {
            $config = $f->getClean();
            $commit = $this->pre_save($config, $errors);
        }
        $errors += $f->errors();
        if ($commit && count($errors) === 0) {
            $dbready = array();
            foreach ($config as $name => $val) {
                $field = $f->getField($name);
                try {
                    $dbready[$name] = $field->to_database($val);
                }
                catch (FieldUnchanged $e) {
                    // Don't save the field value
                    continue;
                }
            }
            if ($this->updateAll($dbready)) {
                if (!$msg)
                    $msg = 'Successfully updated configuration';
                return true;
            }
        }
        return false;
    }

    /**
     * Pre-save hook to check configuration for errors (other than obvious
     * validation errors) prior to saving. Add an error to the errors list
     * or return boolean FALSE if the config commit should be aborted.
     */
    function pre_save($config, &$errors) {
        return true;
    }

    /**
     * Remove all configuration for this plugin -- used when the plugin is
     * uninstalled
     */
    function purge() {
        $sql = 'DELETE FROM '.$this->table
            .' WHERE `namespace`='.db_input($this->getNamespace());
        return (db_query($sql) && db_affected_rows());
    }
}

/**
 * Interface: PluginCustomConfig
 *
 * Allows a plugin to specify custom configuration pages. If the
 * configuration cannot be suited by a single page, single form, then
 * the plugin can use the ::renderCustomConfig() method to trigger
 * rendering the page, and use ::saveCustomConfig() to trigger
 * validating and saving the custom configuration.
 */
interface PluginCustomConfig {
    function renderCustomConfig();
    function saveCustomConfig();
}

class PluginManager {
    static private $plugin_info = array();
    static private $plugin_list = array();

    /**
     * boostrap
     *
     * Used to bootstrap the plugin subsystem and initialize all the plugins
     * currently enabled.
     */
    function bootstrap() {
        foreach ($this->allActive() as $p)
            $p->bootstrap();
    }

    /**
     * allActive
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

        $sql = 'SELECT * FROM '.PLUGIN_TABLE;
        if (!($res = db_query($sql)))
            return static::$plugin_list;

        while ($ht = db_fetch_array($res)) {
            // XXX: Only read active plugins here. allInfos() will
            //      read all plugins
            $info = static::getInfoForPath(
                INCLUDE_DIR . $ht['install_path'], $ht['isphar']);

            list($path, $class) = explode(':', $info['plugin']);
            if (!$class)
                $class = $path;
            elseif ($ht['isphar'])
                @include_once('phar://' . INCLUDE_DIR . $ht['install_path']
                    . '/' . $path);
            else
                @include_once(INCLUDE_DIR . $ht['install_path']
                    . '/' . $path);

            if (!class_exists($class)) {
                $class = 'DefunctPlugin';
                $ht['isactive'] = false;
                $info = array('name' => $ht['name'] . ' '. __('(defunct — missing)'));
            }

            if ($ht['isactive']) {
                static::$plugin_list[$ht['install_path']]
                    = new $class($ht['id']);
            }
            else {
                // Get instance without calling the constructor. Thanks
                // http://stackoverflow.com/a/2556089
                $a = unserialize(
                    sprintf(
                        'O:%d:"%s":0:{}',
                        strlen($class), $class
                    )
                );
                // Simulate __construct() and load()
                $a->id = $ht['id'];
                $a->ht = $ht;
                $a->info = $info;
                static::$plugin_list[$ht['install_path']] = &$a;
                unset($a);
            }
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
        return self::getPluginByName('Help Desk Audit', true);
    }

    static function allActive() {
        $plugins = array();
        foreach (static::allInstalled() as $p)
            if ($p instanceof Plugin && $p->isActive())
                $plugins[] = $p;
        return $plugins;
    }

    function throwException($errno, $errstr) {
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
                GLOB_NOSORT|GLOB_BRACE) as $p) {
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

    function getInstance($path) {
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

    /**
     * install
     *
     * Used to install a plugin that is in-place on the filesystem, but not
     * registered in the plugin registry -- the %plugin table.
     */
    function install($path) {
        $is_phar = substr($path, strlen($path) - 5) == '.phar';
        if (!($info = $this->getInfoForPath(INCLUDE_DIR . $path, $is_phar)))
            return false;

        $sql='INSERT INTO '.PLUGIN_TABLE.' SET installed=NOW() '
            .', install_path='.db_input($path)
            .', name='.db_input($info['name'])
            .', isphar='.db_input($is_phar);
        if (!db_query($sql) || !db_affected_rows())
            return false;
        static::clearCache();
        return true;
    }

    static function clearCache() {
        static::$plugin_list = array();
    }
}

/**
 * Class: Plugin (abstract)
 *
 * Base class for plugins. Plugins should inherit from this class and define
 * the useful pieces of the
 */
abstract class Plugin {
    /**
     * Configuration manager for the plugin. Should be the name of a class
     * that inherits from PluginConfig. This is abstract and must be defined
     * by the plugin subclass.
     */
    var $config_class = null;
    var $id;
    var $info;

    const VERIFIED = 1;             // Thumbs up
    const VERIFY_EXT_MISSING = 2;   // PHP extension missing
    const VERIFY_FAILED = 3;        // Bad signature data
    const VERIFY_ERROR = 4;         // Unable to verify (unexpected error)
    const VERIFY_NO_KEY = 5;        // Public key missing
    const VERIFY_DNS_PASS = 6;      // DNS check passes, cannot verify sig

    static $verify_domain = 'updates.osticket.com';

    function __construct($id) {
        $this->id = $id;
        $this->load();
    }

    function load() {
        $sql = 'SELECT * FROM '.PLUGIN_TABLE.' WHERE
            `id`='.db_input($this->id);
        if (($res = db_query($sql)) && ($ht=db_fetch_array($res)))
            $this->ht = $ht;
        $this->info = PluginManager::getInfoForPath($this->ht['install_path'],
            $this->isPhar());
    }

    function getId() { return $this->id; }
    function getName() { return $this->__($this->info['name']); }
    function isActive() { return $this->ht['isactive']; }
    function isPhar() { return $this->ht['isphar']; }
    function getInstallDate() { return $this->ht['installed']; }
    function getInstallPath() { return $this->ht['install_path']; }

    function getIncludePath() {
        return realpath(INCLUDE_DIR . $this->info['install_path'] . '/'
            . $this->info['include_path']) . '/';
    }

    /**
     * Main interface for plugins. Called at the beginning of every request
     * for each installed plugin. Plugins should register functionality and
     * connect to signals, etc.
     */
    abstract function bootstrap();

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

        $sql = 'DELETE FROM '.PLUGIN_TABLE
            .' WHERE id='.db_input($this->getId());
        PluginManager::clearCache();
        if (!db_query($sql) || !db_affected_rows())
            return false;

        if ($config = $this->getConfig())
            $config->purge();

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
        $sql = 'UPDATE '.PLUGIN_TABLE
            .' SET isactive=1 WHERE id='.db_input($this->getId());
        PluginManager::clearCache();
        return (db_query($sql) && db_affected_rows());
    }

    function disable() {
        $sql = 'UPDATE '.PLUGIN_TABLE
            .' SET isactive=0 WHERE id='.db_input($this->getId());
        PluginManager::clearCache();
        return (db_query($sql) && db_affected_rows());
    }

    /**
     * upgrade
     *
     * Upgrade the plugin. This is used to migrate the database pieces of
     * the plugin using the database migration stream packaged with the
     * plugin.
     */
    function upgrade() {
    }

    function getConfig() {
        static $config = null;
        if ($config === null && $this->config_class)
            $config = new $this->config_class($this->getId());

        return $config;
    }

    function source($what) {
        $what = str_replace('\\', '/', $what);
        if ($what && $what[0] != '/')
            $what = $this->getIncludePath() . $what;
        include_once $what;
    }

    static function lookup($id) { //Assuming local ID is the only lookup used!
        $path = false;
        if ($id && is_numeric($id)) {
            $sql = 'SELECT install_path FROM '.PLUGIN_TABLE
                .' WHERE id='.db_input($id);
            $path = db_result(db_query($sql));
        }
        if ($path)
           return PluginManager::getInstance($path);
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
     *      Plugin::VERIFIED upon success
     *      Plugin::VERIFY_DNS_PASS if found in DNS but cannot verify sig
     *      Plugin::VERIFY_NO_KEY if public key not found in include/plugins
     *      Plugin::VERIFY_FAILED if the plugin fails validation
     *      Plugin::VERIFY_EXT_MISSING if a PHP extension is required
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
        if ($r = dns_get_record($sig['hash'].'.'.self::$verify_domain.'.',
            DNS_TXT, $ignored, $ignored, true)
        ) {
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
}

class DefunctPlugin extends Plugin {
    function bootstrap() {}

    function enable() {
        return false;
    }
}
?>
