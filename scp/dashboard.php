<?php
/*********************************************************************
    dashboard.php

    Staff's Dashboard - basic stats...etc.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
$nav->setTabActive('dashboard');
$ost->addExtraHeader('<meta name="tip-namespace" content="dashboard.dashboard" />',
    "$('#content').data('tipNamespace', 'dashboard.dashboard');");
require(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.'dashboard.inc.php');
include(STAFFINC_DIR.'footer.inc.php');
?>
