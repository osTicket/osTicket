<?php
/*********************************************************************
    attachment.php

    Attachments interface for clients.
    Clients should never see the dir paths.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('secure.inc.php');
require_once(INCLUDE_DIR.'class.attachment.php');
// Basic checks
if (!$thisclient
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

if (!$ticket->checkUserAccess($thisclient))
    die(__('Access Denied'));

// Download the file..
$file->download();
?>
