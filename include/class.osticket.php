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

    function osTicket() {

        require_once(INCLUDE_DIR.'class.config.php'); //Config helper
        require_once(INCLUDE_DIR.'class.company.php');

        $this->session = osTicketSession::start(SESSION_TTL); // start DB based session

        $this->config = new OsticketConfig();

        $this->csrf = new CSRF('__CSRFToken__');

        $this->company = new Company();

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

    function checkCSRFToken($name='') {

        $name = $name?$name:$this->getCSRF()->getTokenName();
        if(isset($_POST[$name]) && $this->validateCSRFToken($_POST[$name]))
            return true;

        if(isset($_SERVER['HTTP_X_CSRFTOKEN']) && $this->validateCSRFToken($_SERVER['HTTP_X_CSRFTOKEN']))
            return true;

        $msg=sprintf('Invalid CSRF token [%s] on %s',
                ($_POST[$name].''.$_SERVER['HTTP_X_CSRFTOKEN']), THISPAGE);
        $this->logWarning('Invalid CSRF Token '.$name, $msg, false);

        return false;
    }

    function getLinkToken() {
        return md5($this->getCSRFToken().SECRET_SALT.session_id());
    }

    function validateLinkToken($token) {
            return ($token && !strcasecmp($token, $this->getLinkToken()));
    }

    function isFileTypeAllowed($file, $mimeType='') {

        if(!$file || !($allowedFileTypes=$this->getConfig()->getAllowedFileTypes()))
            return false;

        //Return true if all file types are allowed (.*)
        if(trim($allowedFileTypes)=='.*') return true;

        $allowed = array_map('trim', explode(',', strtolower($allowedFileTypes)));
        $filename = is_array($file)?$file['name']:$file;

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        //TODO: Check MIME type - file ext. shouldn't be solely trusted.

        return ($ext && is_array($allowed) && in_array(".$ext", $allowed));
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
        return $this->system['err'];
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
        return $this->system['notice'];
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
            Mailer::sendmail($to, $subject, $message, sprintf('"osTicket Alerts"<%s>',$to));
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
        $bt = str_replace(ROOT_DIR, '(root)/', $e->getTraceAsString());
        $error .= nl2br("\n\n---- Backtrace ----\n".$bt);

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
        if($alert && $this->getConfig()->getLogLevel() >= $level)
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

    function get_path_info() {
        if(isset($_SERVER['PATH_INFO']))
            return $_SERVER['PATH_INFO'];

        if(isset($_SERVER['ORIG_PATH_INFO']))
            return $_SERVER['ORIG_PATH_INFO'];

        //TODO: conruct possible path info.

        return null;
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

    /**
     * Returns TRUE if the request was made via HTTPS and false otherwise
     */
    function is_https() {
        return (isset($_SERVER['HTTPS'])
                && strtolower($_SERVER['HTTPS']) == 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');
    }

    /* returns true if script is being executed via commandline */
    function is_cli() {
        return (!strcasecmp(substr(php_sapi_name(), 0, 3), 'cli')
                || (!isset($_SERVER['REQUEST_METHOD']) &&
                    !isset($_SERVER['HTTP_HOST']))
                    //Fallback when php-cgi binary is used via cli
                );
    }

    /**** static functions ****/
    function start() {

        if(!($ost = new osTicket()))
            return null;

        //Set default time zone... user/staff settting will override it (on login).
        $_SESSION['TZ_OFFSET'] = $ost->getConfig()->getTZoffset();
        $_SESSION['TZ_DST'] = $ost->getConfig()->observeDaylightSaving();

        // Bootstrap installed plugins
        $ost->plugins->bootstrap();

        return $ost;
    }
}

?>
