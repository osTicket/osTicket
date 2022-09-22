<?php
/*******************************************************************
    class.osticket.php

    osTicket (sys) -> Config.

    Core osTicket object: loads congfig and provides loggging facility.

    Use osTicket::start(configId)

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once(INCLUDE_DIR.'class.csrf.php'); //CSRF token class.
require_once(INCLUDE_DIR.'class.migrater.php');
require_once(INCLUDE_DIR.'class.plugin.php');
require_once INCLUDE_DIR . 'class.message.php';

define('LOG_WARN',LOG_WARNING);

class osTicket {

    var $loglevel=array(1=>'Error','Warning','Debug');

    //Page errors.
    var $errors;

    //System
    var $system;




    var $warning;
    var $message;

    var $title; //Custom title. html > head > title.
    var $headers;
    var $pjax_extra;

    var $config;
    var $session;
    var $csrf;
    var $company;
    var $plugins;

    function __construct() {

        require_once(INCLUDE_DIR.'class.config.php'); //Config helper
        require_once(INCLUDE_DIR.'class.company.php');
        // Load the config
        $this->config = new OsticketConfig();
        // Start session  (if not disabled)
        if (!defined('DISABLE_SESSION') || !DISABLE_SESSION)
            $this->session = osTicketSession::start(SESSION_TTL,
                    $this->isUpgradePending());
        // CSRF Token
        $this->csrf = new CSRF('__CSRFToken__');
        // Company information
        $this->company = new Company();
        // Load Plugin Manager
        $this->plugins = new PluginManager();
    }

    function isSystemOnline() {
        return ($this->getConfig() && $this->getConfig()->isHelpDeskOnline() && !$this->isUpgradePending());
    }

    function isUpgradePending() {
		foreach (DatabaseMigrater::getUpgradeStreams(UPGRADE_DIR.'streams/') as $stream=>$hash)
			if (strcasecmp($hash,
					$this->getConfig()->getSchemaSignature($stream)))
				return true;
		return false;
    }

    function getSession() {
        return $this->session;
    }

    function getConfig() {
        return $this->config;
    }

    function getDBSignature($namespace='core') {
        return $this->getConfig()->getSchemaSignature($namespace);
    }

    function getVersion() {
        return THIS_VERSION;
    }

    function getCSRF(){
        return $this->csrf;
    }

    function getCSRFToken() {
        return $this->getCSRF()->getToken();
    }

    function getCSRFFormInput() {
        return $this->getCSRF()->getFormInput();
    }

    function validateCSRFToken($token) {
        return ($token && $this->getCSRF()->validateToken($token));
    }

    function checkCSRFToken($name=false, $rotate=false) {
        $name = $name ?: $this->getCSRF()->getTokenName();
        $token = $_POST[$name] ?: $_SERVER['HTTP_X_CSRFTOKEN'];
        if ($token && $this->validateCSRFToken($token)) {
            if ($rotate) $this->getCSRF()->rotate();
            return true;
        }

        $msg=sprintf(__('Invalid CSRF token [%1$s] on %2$s'),
                Format::htmlchars(Format::sanitize($token)), THISPAGE);
        $this->logWarning(__('Invalid CSRF Token').' '.$name, $msg, false);

        return false;
    }

    function getLinkToken() {
        return md5($this->getCSRFToken().SECRET_SALT.session_id());
    }

    function validateLinkToken($token) {
            return ($token && !strcasecmp($token, $this->getLinkToken()));
    }

    /* Replace Template Variables */
    function replaceTemplateVariables($input, $vars=array()) {

        $replacer = new VariableReplacer();
        $replacer->assign(array_merge($vars,
            array('url' => $this->getConfig()->getBaseUrl(),
                'company' => $this->company)
                    ));

        return $replacer->replaceVars($input);
    }

    static function getVarScope() {
        return array(
            'url' => __("osTicket's base url (FQDN)"),
            'company' => array('class' => 'Company', 'desc' => __('Company Information')),
        );
    }

    function addExtraHeader($header, $pjax_script=false) {
        $this->headers[md5($header)] = $header;
        $this->pjax_extra[md5($header)] = $pjax_script;
    }

    function getExtraHeaders() {
        return $this->headers;
    }
    function getExtraPjax() {
        return $this->pjax_extra;
    }

    function setPageTitle($title) {
        $this->title = $title;
    }

    function getPageTitle() {
        return $this->title;
    }

    function getErrors() {
        return $this->errors;
    }

    function setErrors($errors) {
        $this->errors = $errors;
    }

    function getError() {
        return $this->system['err'] ?? null;
    }

    function setError($error) {
        $this->system['error'] = $error;
    }

    function clearError() {
        $this->setError('');
    }

    function getWarning() {
        return $this->system['warning'];
    }

    function setWarning($warning) {
        $this->system['warning'] = $warning;
    }

    function clearWarning() {
        $this->setWarning('');
    }


    function getNotice() {
        return $this->system['notice'] ?? null;
    }

    function setNotice($notice) {
        $this->system['notice'] = $notice;
    }

    function clearNotice() {
        $this->setNotice('');
    }


    function alertAdmin($subject, $message, $log=false) {

        //Set admin's email address
        if (!($to = $this->getConfig()->getAdminEmail()))
            $to = ADMIN_EMAIL;

        //append URL to the message
        $message.="\n\n".$this->getConfig()->getBaseUrl();

        //Try getting the alert email.
        $email=null;
        if(!($email=$this->getConfig()->getAlertEmail()))
            $email=$this->getConfig()->getDefaultEmail(); //will take the default email.

        if($email) {
            $email->sendAlert($to, $subject, $message, null, array('text'=>true, 'reply-tag'=>false));
        } else {//no luck - try the system mail.
            Mailer::sendmail($to, $subject, $message, '"'.__('osTicket Alerts').sprintf('" <%s>',$to));
        }

        //log the alert? Watch out for loops here.
        if($log)
            $this->log(LOG_CRIT, $subject, $message, false); //Log the entry...and make sure no alerts are resent.

    }

    function logDebug($title, $message, $force=false) {
        return $this->log(LOG_DEBUG, $title, $message, false, $force);
    }

    function logInfo($title, $message, $alert=false) {
        return $this->log(LOG_INFO, $title, $message, $alert);
    }

    function logWarning($title, $message, $alert=true) {
        return $this->log(LOG_WARN, $title, $message, $alert);
    }

    function logError($title, $error, $alert=true) {
        return $this->log(LOG_ERR, $title, $error, $alert);
    }

    function logDBError($title, $error, $alert=true) {

        if($alert && !$this->getConfig()->alertONSQLError())
            $alert =false;

        $e = new Exception();
        $bt = str_replace(ROOT_DIR, _S(/* `root` is a root folder */ '(root)').'/',
            $e->getTraceAsString());
        $error .= nl2br("\n\n---- "._S('Backtrace')." ----\n".$bt);

        // Prevent recursive loops through this code path
        if (substr_count($bt, __FUNCTION__) > 1)
            return;

        return $this->log(LOG_ERR, $title, $error, $alert);
    }

    function log($priority, $title, $message, $alert=false, $force=false) {

        //We are providing only 3 levels of logs. Windows style.
        switch($priority) {
            case LOG_EMERG:
            case LOG_ALERT:
            case LOG_CRIT:
            case LOG_ERR:
                $level=1; //Error
                break;
            case LOG_WARN:
            case LOG_WARNING:
                $level=2; //Warning
                break;
            case LOG_NOTICE:
            case LOG_INFO:
            case LOG_DEBUG:
            default:
                $level=3; //Debug
        }

        $loglevel=array(1=>'Error','Warning','Debug');

        $info = array(
            'title' => &$title,
            'level' => $loglevel[$level],
            'level_id' => $level,
            'body' => &$message,
        );
        Signal::send('syslog', null, $info);

        //Logging everything during upgrade.
        if($this->getConfig()->getLogLevel()<$level && !$force)
            return false;

        //Alert admin if enabled...
        $alert = $alert && !$this->isUpgradePending();
        if ($alert && $this->getConfig()->getLogLevel() >= $level)
            $this->alertAdmin($title, $message);

        //Save log based on system log level settings.
        $sql='INSERT INTO '.SYSLOG_TABLE.' SET created=NOW(), updated=NOW() '
            .',title='.db_input(Format::sanitize($title, true))
            .',log_type='.db_input($loglevel[$level])
            .',log='.db_input(Format::sanitize($message, false))
            .',ip_address='.db_input($_SERVER['REMOTE_ADDR']);

        db_query($sql, false);

        return true;
    }

    function purgeLogs() {

        if(!($gp=$this->getConfig()->getLogGracePeriod()) || !is_numeric($gp))
            return false;

        //System logs
        $sql='DELETE  FROM '.SYSLOG_TABLE.' WHERE DATE_ADD(created, INTERVAL '.$gp.' MONTH)<=NOW()';
        db_query($sql);

        //TODO: Activity logs

        return true;
    }
    /*
     * Util functions
     *
     */

    function get_var($index, $vars, $default='', $type=null) {

        if(is_array($vars)
                && array_key_exists($index, $vars)
                && (!$type || gettype($vars[$index])==$type))
            return $vars[$index];

        return $default;
    }

    function get_db_input($index, $vars, $quote=true) {
        return db_input($this->get_var($index, $vars), $quote);
    }

    static function get_path_info() {
        if(isset($_SERVER['PATH_INFO']))
            return $_SERVER['PATH_INFO'];

        if(isset($_SERVER['ORIG_PATH_INFO']))
            return $_SERVER['ORIG_PATH_INFO'];

        //TODO: conruct possible path info.

        return null;
    }

    /**
     * Fetch the current version(s) of osTicket softwares via DNS. The
     * constants of MAJOR_VERSION, THIS_VERSION, and GIT_VERSION will be
     * consulted to arrive at the most relevant version code for the latest
     * release.
     *
     * Parameters:
     * $product - (string|default:'core') the product to fetch versions for
     * $major - (string|optional) optional major version to compare. This is
     *      useful if more than one version is available. Only versions
     *      specifying this major version ('m') are considered as version
     *      candidates.
     *
     * Dns:
     * The DNS zone will have TXT records for the product will be published
     * in this format:
     *
     * "v=1; m=1.9; V=1.9.11; c=deadbeef"
     *
     * Where the string is a semicolon-separated string of key/value pairs
     * with the following meanings:
     *
     * --+--------------------------
     * v | DNS record format version
     *
     * For v=1, this is the meaning of the other keys
     * --+-------------------------------------------
     * m | (optional) major product version
     * V | Full product version (usually a git tag)
     * c | Git commit id of the release tag
     * s | Schema signature of the version, which might help detect
     *   | required migration
     *
     * Returns:
     * (string|bool|null)
     *  - 'v1.9.11' or 'deadbeef' if release tag or git commit id seems to
     *      be most appropriate based on the value of GIT_VERSION
     *  - null if the $major version is no longer supported
     *  - false if no information is available in DNS
     */
     function getLatestVersion($product='core', $major=null) {
        $records = dns_get_record($product.'.updates.osticket.com', DNS_TXT);
        if (!$records)
            return false;

        $versions = array();
        foreach ($records as $r) {
            $txt = $r['txt'];
            $info = array();
            foreach (explode(';', $r['txt']) as $kv) {
                list($k, $v) = explode('=', $kv);
                if (!($k = trim($k)))
                    continue;
                $info[$k] = trim($v);
            }
            $versions[] = $info;
        }
        foreach ($versions as $info) {
            switch ($info['v']) {
            case '1':
                if ($major && $info['m'] && $info['m'] != $major)
                    continue 2;
                if ($product == 'core' && GIT_VERSION == '$git')
                    return $info['c'];
                return $info['V'];
            }
        }
    }

   /*
    * getTrustedProxies
    *
    * Get defined trusted proxies
    */

    static function getTrustedProxies() {
        static $proxies = null;
        // Parse trusted proxies from config file
        if (!isset($proxies) && defined('TRUSTED_PROXIES'))
            $proxies = array_filter(
                    array_map('trim', explode(',', TRUSTED_PROXIES)));

        return $proxies ?: array();
    }

    /*
     * getLocalNetworkAddresses
     *
     * Get defined local network addresses
     */
    static function getLocalNetworkAddresses() {
        static $ips = null;
        // Parse local addreses from config file
        if (!isset($ips) && defined('LOCAL_NETWORKS'))
            $ips = array_filter(
                    array_map('trim', explode(',', LOCAL_NETWORKS)));

        return $ips ?: array();
    }

    static function get_root_path($dir) {

        /* If run from the commandline, DOCUMENT_ROOT will not be set. It is
         * also likely that the ROOT_PATH will not be necessary, so don't
         * bother attempting to figure it out.
         *
         * Secondly, if the directory of main.inc.php is the same as the
         * document root, the the ROOT path truly is '/'
         */
        if(!isset($_SERVER['DOCUMENT_ROOT'])
                || !strcasecmp($_SERVER['DOCUMENT_ROOT'], $dir))
            return '/';

        /* The main idea is to try and use full-path filename of PHP_SELF and
         * SCRIPT_NAME. The SCRIPT_NAME should be the path of that script
         * inside the DOCUMENT_ROOT. This is most likely useful if osTicket
         * is run using something like Apache UserDir setting where the
         * DOCUMENT_ROOT of Apache and the installation path of osTicket
         * have nothing in comon.
         *
         * +---------------------------+-------------------+----------------+
         * | PHP Script                | SCRIPT_NAME       | ROOT_PATH      |
         * +---------------------------+-------------------+----------------+
         * | /home/u1/www/osticket/... | /~u1/osticket/... | /~u1/osticket/ |
         * +---------------------------+-------------------+----------------+
         *
         * The algorithm will remove the directory of main.inc.php from
         * as seen. What's left should be the script executed inside
         * the osTicket installation. That is removed from SCRIPT_NAME.
         * What's left is the ROOT_PATH.
         */
        $bt = debug_backtrace(false);
        $frame = array_pop($bt);
        $file = str_replace('\\','/', $frame['file']);
        $path = substr($file, strlen(ROOT_DIR));
        if($path && ($pos=strpos($_SERVER['SCRIPT_NAME'], $path))!==false)
            return ($pos) ? substr($_SERVER['SCRIPT_NAME'], 0, $pos) : '/';

        if (self::is_cli())
            return '/';

        return null;
    }

    /*
     * get_client_ip
     *
     * Get client IP address from "Http_X-Forwarded-For" header by following a
     * chain of trusted proxies.
     *
     * "Http_X-Forwarded-For" header value is a comma+space separated list of IP
     * addresses, the left-most being the original client, and each successive
     * proxy that passed the request all the way to the originating IP address.
     *
     */
    static function get_client_ip($header='HTTP_X_FORWARDED_FOR') {

        // Request IP
        $ip = $_SERVER['REMOTE_ADDR'];
        // Trusted proxies.
        $proxies = self::getTrustedProxies();
        // Return current IP address if header is not set and
        // request is not from a trusted proxy.
        if (!isset($_SERVER[$header])
                || !$proxies
                || !self::is_trusted_proxy($ip, $proxies))
            return $ip;

        // Get chain of proxied ip addresses
        $ips = array_map('trim', explode(',', $_SERVER[$header]));
        // Add request IP to the chain
        $ips[] = $ip;
        // Walk the chain in reverse - remove invalid IPs
        $ips = array_reverse($ips);
        foreach ($ips as $k => $ip) {
            // Make sure the IP is valid and not a trusted proxy
            if ($k && !Validator::is_ip($ip))
                unset($ips[$k]);
            elseif ($k && !self::is_trusted_proxy($ip, $proxies))
                return $ip;
        }

        // We trust the 400 lb hacker... return left most valid IP
        return array_pop($ips);
    }

    /*
     * Checks if the IP is that of a trusted proxy
     *
     */
    static function is_trusted_proxy($ip, $proxies=array()) {
        $proxies = $proxies ?: self::getTrustedProxies();
        // We don't have any proxies set.
        if (!$proxies)
            return false;
        // Wildcard set - trust all proxies
        else if ($proxies == '*')
            return true;

        return ($proxies && Validator::check_ip($ip, $proxies));
    }

    /**
     * is_local_ip
     *
     * Check if a given IP is part of defined local address blocks
     *
     */
    static function is_local_ip($ip, $ips=array()) {
        $ips = $ips
            ?: self::getLocalNetworkAddresses()
            ?: array();

        foreach ($ips as $addr) {
            if (Validator::check_ip($ip, $addr))
                return true;
        }

        return false;
    }

    /**
     * Returns TRUE if the request was made via HTTPS and false otherwise
     */
    static function is_https() {

        // Local server flags
        if (isset($_SERVER['HTTPS'])
                && strtolower($_SERVER['HTTPS']) == 'on')
            return true;

        // Check if SSL was terminated by a loadbalancer
        return (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && !strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https'));
    }

    /**
     * Returns TRUE if the current browser is IE and FALSE otherwise
     */
    static function is_ie() {
        if (preg_match('/MSIE|Internet Explorer|Trident\/[\d]{1}\.[\d]{1,2}/',
                $_SERVER['HTTP_USER_AGENT']))
            return true;

        return false;
    }

    /* returns true if script is being executed via commandline */
    static function is_cli() {
        return (!strcasecmp(substr(php_sapi_name(), 0, 3), 'cli')
                || (!isset($_SERVER['REQUEST_METHOD']) &&
                    !isset($_SERVER['HTTP_HOST']))
                    //Fallback when php-cgi binary is used via cli
                );
    }

    /**** static functions ****/
    static function start() {
        // Prep basic translation support
        Internationalization::bootstrap();

        if(!($ost = new osTicket()))
            return null;

        // Bootstrap installed plugins
        $ost->plugins->bootstrap();

        // Mirror content updates to the search backend
        $ost->searcher = new SearchInterface();

        return $ost;
    }
}

?>
