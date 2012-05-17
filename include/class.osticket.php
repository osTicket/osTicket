<?php
/*************************************************************************
    class.osticket.php

    osTicket (sys) -> Config.

    Core osTicket object: loads congfig and provides loggging facility.

    Use osTicket::start(configId)

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once(INCLUDE_DIR.'class.config.php'); //Config helper
define('LOG_WARN',LOG_WARNING);

class osTicket {

    var $loglevel=array(1=>'Error','Warning','Debug');
    var $errors;
    var $warning;
    var $message;

    var $headers;

    var $config;
    var $session;

    function osTicket($cfgId) {
        $this->config = Config::lookup($cfgId);
        $this->session = osTicketSession::start(SESSION_TTL); // start_session 
    }

    function isSystemOnline() {
        return ($this->getConfig() && $this->getConfig()->isHelpdeskOnline() && !$this->isUpgradePending());
    }

    function isUpgradePending() {
        return (defined('SCHEMA_SIGNATURE') && strcasecmp($this->getConfig()->getSchemaSignature(), SCHEMA_SIGNATURE));
    }

    function getSession() {
        return $this->session;
    }

    function getConfig() {
        return $this->config;
    }

    function getConfigId() {

        return $this->getConfig()?$this->getConfig()->getId():0;
    }

    function addExtraHeader($header) {
        $this->headers[md5($header)] = $header;
    }

    function getExtraHeaders() {
        return $this->headers;
    }

    function getErrors() {
        return $this->errors;
    }

    function setErrors($errors) {
        if(!is_array($errors))
            return  $this->setError($errors);

        $this->errors = $errors;
    }

    function getError() {
        return $this->errors['err'];
    }

    function setError($error) {
        $this->errors['err'] = $error;
    }

    function clearError() {
        $this->setError('');
    }

    function getWarning() {
        return $this->warning;
    }

    function setWarning($warn) {
        $this->warning = $warn;
    }

    function clearWarning() {
        $this->setWarning('');
    }


    function getMessage() {
        return $this->message;
    }

    function setMessage($msg) {
        $this->message = $msg;
    }

    function clearMessage() {
        $this->setMessage('');
    }


    function alertAdmin($subject, $message, $log=false) {
                
        //Set admin's email address
        if(!($to=$this->getConfig()->getAdminEmail()))
            $to=ADMIN_EMAIL;

        //Try getting the alert email.
        $email=null;
        if(!($email=$this->getConfig()->getAlertEmail())) 
            $email=$this->getConfig()->getDefaultEmail(); //will take the default email.

        if($email) {
            $email->send($to, $subject, $message);
        } else {//no luck - try the system mail.
            Email::sendmail($to, $subject, $message, sprintf('"osTicket Alerts"<%s>',$to));
        }

        //log the alert? Watch out for loops here.
        if($log)
            $this->log(LOG_CRIT, $subject, $message, false); //Log the entry...and make sure no alerts are resent.

    }

    function logDebug($title, $message, $alert=false) {
        return $this->log(LOG_DEBUG, $title, $message, $alert);
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

    function log($priority, $title, $message, $alert=false) {

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

        //Alert admin if enabled...
        if($alert)
            $this->alertAdmin($title, $message);


        if($this->getConfig()->getLogLevel()<$level)
            return false;

        //Save log based on system log level settings.
        $loglevel=array(1=>'Error','Warning','Debug');
        $sql='INSERT INTO '.SYSLOG_TABLE.' SET created=NOW(), updated=NOW() '.
            ',title='.db_input($title).
            ',log_type='.db_input($loglevel[$level]).
            ',log='.db_input($message).
            ',ip_address='.db_input($_SERVER['REMOTE_ADDR']);
        
        mysql_query($sql); //don't use db_query to avoid possible loop.
        
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

    /**** static functions ****/
    function start($configId) {

        if(!$configId || !($ost = new osTicket($configId)) || $ost->getConfigId()!=$configId)
            return null;

        //Set default time zone... user/staff settting will overwrite it (on login).
        $_SESSION['TZ_OFFSET'] = $ost->getConfig()->getTZoffset();
        $_SESSION['TZ_DST'] = $ost->getConfig()->observeDaylightSaving();

        return $ost;
    }
}

?>
