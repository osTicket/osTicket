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

// Basic checks
if (!$thisstaff
        || !$_GET['id']
        || !$_GET['h']
        || !($attachment=Attachment::lookup($_GET['id']))
        || !($file=$attachment->getFile())
        || strcasecmp(trim($_GET['h']), $file->getDownloadHash())
        || !($object=$attachment->getObject())
        || !$object instanceof ThreadEntry
        || !($ticket=$object->getThread()->getObject())
        || !$ticket instanceof Ticket
        )
    Http::response(404, __('Unknown or invalid file'));

if (!$ticket->checkStaffPerm($thisstaff))
    die(__('Access Denied'));

//Download the file..
$file->download();
?>
