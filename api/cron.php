<?php
/*********************************************************************
    cron.php

    File to handle cron job calls (local and remote).

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
@chdir(realpath(dirname(__FILE__)).'/'); //Change dir.
require('api.inc.php');
require_once(INCLUDE_DIR.'class.cron.php');
Cron::run();
$ost->logDebug('Cron Job','External cron job executed ['.$_SERVER['REMOTE_ADDR'].']');
?>
