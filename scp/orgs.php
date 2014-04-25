<?php
/*********************************************************************
    orgs.php

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
require_once INCLUDE_DIR . 'class.note.php';

$org = null;
if ($_REQUEST['id'] || $_REQUEST['org_id'])
    $org = Organization::lookup($_REQUEST['org_id'] ?: $_REQUEST['id']);

if ($_POST) {
    switch ($_REQUEST['a']) {
    case 'import-users':
        if (!$org) {
            $errors['err'] = 'Organization ID must be specified for import';
            break;
        }
        $status = User::importFromPost($_FILES['import'] ?: $_POST['pasted'],
            array('org_id'=>$org->getId()));
        if (is_numeric($status))
            $msg = "Successfully imported $status clients";
        else
            $errors['err'] = $status;
    }
}

$page = $org? 'org-view.inc.php' : 'orgs.inc.php';
$nav->setTabActive('users');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
