<?php
/*********************************************************************
    attachment.php

    Handles attachment downloads & access validation.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.attachment.php');

//Basic checks
if(!$thisstaff || !$_GET['id'] || !$_GET['h']
        || !($attachment=Attachment::lookup($_GET['id']))
        || !($file=$attachment->getFile()))
    Http::response(404, __('Unknown or invalid file'));

//Validate session access hash - we want to make sure the link is FRESH! and the user has access to the parent ticket!!
$vhash=md5($attachment->getFileId().session_id().strtolower($file->getKey()));
if(strcasecmp(trim($_GET['h']),$vhash) || !($ticket=$attachment->getTicket()) || !$ticket->checkStaffAccess($thisstaff)) die(__('Access Denied'));

//Download the file..
$file->download();
?>
