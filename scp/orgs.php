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
        break;
    case 'remove-users':
        if (!$org)
            $errors['err'] = ' Trying to remove users from unknown
                 organization';
        elseif (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
            $errors['err'] = 'You must select at least one user to remove';
        } else {
            $i = 0;
            foreach ($_POST['ids'] as $k=>$v) {
                if (($u=User::lookup($v)) && $org->removeUser($u))
                    $i++;
            }
            $num = count($_POST['ids']);
            if ($i && $i == $num)
                $msg = 'Selected users removed successfully';
            elseif ($i > 0)
                $warn = "$i of $num selected users removed";
            elseif (!$errors['err'])
                $errors['err'] = 'Unable to remove selected users';
        }
        break;
    default:
        $errors['err'] = 'Unknown action';
    }
} elseif ($_REQUEST['a'] == 'export') {
    require_once(INCLUDE_DIR.'class.export.php');
    $ts = strftime('%Y%m%d');
    if (!($token=$_REQUEST['qh']))
        $errors['err'] = 'Query token required';
    elseif (!($query=$_SESSION['orgs_qs_'.$token]))
        $errors['err'] = 'Query token not found';
    elseif (!Export::saveOrganizations($query, "organizations-$ts.csv", 'csv'))
        $errors['err'] = 'Internal error: Unable to export results';
}

$page = $org? 'org-view.inc.php' : 'orgs.inc.php';
$nav->setTabActive('users');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
