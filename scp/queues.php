<?php
/*********************************************************************
    queues.php

    Handles management of custom queues

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2015 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require('admin.inc.php');

require_once INCLUDE_DIR . 'class.queue.php';

$nav->setTabActive('settings', 'settings.php?t='.urlencode($_GET['t']));

require_once(STAFFINC_DIR.'header.inc.php');
include_once(STAFFINC_DIR."queue.inc.php");
include_once(STAFFINC_DIR.'footer.inc.php');
