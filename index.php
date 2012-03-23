<?php
/*********************************************************************
    index.php

    Helpdesk landing page. Please customize it to fit your needs.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('client.inc.php');
$section = 'home';
require(CLIENTINC_DIR.'header.inc.php');
?>

<div id="landing_page">
    <h1>Welcome to the Support Center</h1>
    <p>
        In order to streamline support requests and better serve you, we utilize a support ticket system. Every support request is assigned a unique ticket number which you can use to track the progress and responses online. For your reference we provide complete archives and history of all your support requests. A valid email address is required to submit a ticket.
    </p>

    <div id="new_ticket">
        <h3>Open A New Ticket</h3>
        <form method="get" action="open.php">  
            <div>Please provide as much detail as possible so we can best assist you. To update a previously submitted ticket, please login.</div>
            <input type="submit" value="Open a New Ticket">
        </form>
    </div>

    <div id="check_status">
        <h3>Check Ticket Status</h3>
        <form class="status_form" action="view.php" method="get">
            <div>We provide archives and history of all your current and past support requests complete with responses.</div>
            <input type="submit" value="Check Ticket Status">
        </form>
    </div>
</div>
<div class="clear"></div>
<?php
if($cfg && $cfg->isKnowledgebaseEnabled()){ 
    //FIXME: provide ability to feature or select random FAQs ??
?>
<p>Be sure to browse our <a href="kb/index.php">Frequently Asked Questions (FAQs)</a>, before opening a ticket.</p>
</div>
<?php
} ?>
<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>
