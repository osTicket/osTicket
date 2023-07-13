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
    || !($file = AttachmentFile::lookupByHash($_GET['key']))
) {
    Http::response(404, __('Unknown or invalid file'));
}

// Get the object type the file is attached to
$type = '';
$attachment = null;
if ($_GET['id']
        && ($attachment=$file->attachments->findFirst(array(
                    'id' => $_GET['id']))))
    $type = $attachment->type;

// Enforce security settings if enabled.
if ($cfg->isAuthRequiredForFiles()
        // FAQ & Page files allowed without login.
        && !in_array($type, ['P', 'F'])
        // Check user login
        && !$thisuser
        // Check staff login
        && !StaffAuthenticationBackend::getUser()
        ) {

    // Try and determine if an agent is viewing the page / file
    if (strpos($_SERVER['HTTP_REFERRER'], ROOT_PATH .  'scp/') !== false) {
        $_SESSION['_staff']['auth']['dest'] = Http::refresh_url();
        Http::redirect(ROOT_PATH.'scp/login.php');
    } else {
        require 'secure.inc.php';
    }
}


// Validate session access hash - we want to make sure the link is FRESH!
// and the user has access to the parent ticket!!
if ($file->verifySignature($_GET['signature'], $_GET['expires'])) {
    try {
        if (($s = @$_GET['s']) && strpos($file->getType(), 'image/') === 0)
            return $file->display($s);

        // Download the file..
        $filename = $attachment ? $attachment->name : $file->getName();
        $disposition = @$_GET['disposition'] ?: false;
        $file->download($filename, $disposition, @$_GET['expires']);
    }
    catch (Exception $ex) {
        Http::response(500, 'Unable to find that file: '.$ex->getMessage());
    }
}
// else
Http::response(404, __('Unknown or invalid file'));
