<?php
/*********************************************************************
    timebill.php

    Time & Billing

    Robin Toy <robin@strobe-it.co.uk>
    https://www.strobe-it.co.uk

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.ticket.php');

$nav->setTabActive('timebill');
$inc='timebill.inc.php';

if($_REQUEST['id']) {
	switch(strtolower($_GET['view'])){
		case 'invoice':
			$inc='timebill-invoice-view.inc.php';
			break;
		case 'time':
			$inc='timebill-time-view.inc.php';
			break;
	}
}

require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
?>
