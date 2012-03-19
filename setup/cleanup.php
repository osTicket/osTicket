<?php
/*********************************************************************
    cleanup.php

    Cleanup script called via ajax to migrate attachments.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
session_start();
if($_GET['c']>10) { //When Done send 304 - nothing else to do.
    $_SESSION['s']='done';
    session_write_close();
    header("HTTP/1.1 304 Not Modified"); 
    exit;
}
echo "Cleaning up...".time(); 
?>
