<?php
/*********************************************************************
    api.inc.php

    File included on every API page...handles security and abuse issues

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
//postfix exit codes see /usr/include/sysexits.h 
define('EX_DATAERR', 65);       /* data format error */
define('EX_NOINPUT', 66);       /* cannot open input */
define('EX_UNAVAILABLE', 69);   /* service unavailable */
define('EX_IOERR', 74);         /* input/output error */
define('EX_TEMPFAIL',75);       /* temp failure; user is invited to retry */
define('EX_NOPERM',  77);       /* permission denied */
define('EX_CONFIG',  78);       /* configuration error */

define('EX_SUCCESS',0);         /* success baby */

if(!file_exists('../main.inc.php')) exit(EX_CONFIG);
require_once('../main.inc.php');
if(!defined('INCLUDE_DIR')) exit(EX_CONFIG);

require_once(INCLUDE_DIR.'class.http.php');
require_once(INCLUDE_DIR.'class.api.php');

define('OSTAPIINC',TRUE); // Define tag that included files can check

$remotehost=(isset($_SERVER['HTTP_HOST']) || isset($_SERVER['REMOTE_ADDR']))?TRUE:FALSE;
/* API exit helper */
function api_exit($code,$msg='') {
    global $remotehost, $ost;
    
    if($code!=EX_SUCCESS) {
        //Error occured...
        $_SESSION['api']['errors']+=1;
        $_SESSION['api']['time']=time();
        $ost->logWarning("API error - code #$code", $msg, ($_SESSION['api']['errors']>10));
        //echo "API Error:.$msg";
    }
    if($remotehost){
        switch($code) {
        case EX_SUCCESS:
            Http::response(200,$code,'text/plain');
            break;
        case EX_UNAVAILABLE:
            Http::response(405,$code,'text/plain');
            break;
        case EX_NOPERM:
            Http::response(403,$code,'text/plain');
            break;
        case EX_DATAERR:
        case EX_NOINPUT:
        default:
            Http::response(416,$code,'text/plain');
        }
    }
    exit($code);
}

//Remote hosts need authorization.
$apikey = null;
if($remotehost) {
    //Upto 10 consecutive errors allowed...before a 2 minute timeout.
    //One more error during timeout and timeout starts a new clock
    if($_SESSION['api']['errors']>10 && (time()-$_SESSION['api']['time'])<=2*60)  // timeout!
        api_exit(EX_NOPERM, 'Remote host ['.$_SERVER['REMOTE_ADDR'].'] in timeout - error #'.$_SESSION['api']['errors']);
        
    if(!isset($_SERVER['HTTP_X_API_KEY']) || !isset($_SERVER['REMOTE_ADDR']))
        api_exit(EX_NOPERM, 'API key required');
    elseif(!($apikey=API::lookupByKey($_SERVER['HTTP_X_API_KEY'], $_SERVER['REMOTE_ADDR']))
                || !$apikey->isActive()
                || $apikey->getIPAddr()!=$_SERVER['REMOTE_ADDR'])
        api_exit(EX_NOPERM, 'API key not found/active or source IP not authorized');
    
    //At this point we know the remote host/IP is allowed.
    $_SESSION['api']['errors']=0; //clear errors for the session.
}
?>
