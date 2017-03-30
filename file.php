<?php
/*********************************************************************
    file.php

    File download facilitator for clients

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('client.inc.php');
require_once(INCLUDE_DIR.'class.file.php');

//Basic checks
if (!$_GET['key']
    || !$_GET['signature']
    || !$_GET['expires']
    || !($file = AttachmentFile::lookup($_GET['key']))
) {
    Http::response(404, __('Unknown or invalid file'));
}

// Enforce security settings
if ($cfg->isAuthRequiredForFiles() && !$thisclient) {
    if (!($U = StaffAuthenticationBackend::getUser())) {
        // Try and determine if a staff is viewing this page
        if (strpos($_SERVER['HTTP_REFERRER'], ROOT_PATH .  'scp/') !== false) {
            $_SESSION['_staff']['auth']['dest'] =
                '/' . ltrim($_SERVER['REQUEST_URI'], '/');
            Http::redirect(ROOT_PATH.'scp/login.php');
        }
        else {
            require 'secure.inc.php';
        }
    }
}

// Validate session access hash - we want to make sure the link is FRESH!
// and the user has access to the parent ticket!!
if ($file->verifySignature($_GET['signature'], $_GET['expires'])) {
    try {
        if (($s = @$_GET['s']) && strpos($file->getType(), 'image/') === 0)
            return $file->display($s);

        // Download the file..
        $file->download(@$_GET['disposition'] ?: false, $_GET['expires']);
    }
    catch (Exception $ex) {
        Http::response(500, 'Unable to find that file: '.$ex->getMessage());
    }
}
// else
Http::response(404, __('Unknown or invalid file'));
