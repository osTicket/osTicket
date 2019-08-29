<?php
/*********************************************************************
    export.php

    Export downloader

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2019 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.export.php');

// Look up export by ID.
if (!$_GET['id'] || !($export=Exporter::load($_GET['id'])))
    Http::response(404, __('Unknown or invalid export'));
elseif (!$export->isReady())
     Http::response(416, __('Export is not ready yet'));

$export->download();
?>
