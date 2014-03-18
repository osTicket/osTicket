<?php
/*********************************************************************
    index.php

    Helpdesk landing page. Please customize it to fit your needs.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
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
    <?php
    if($cfg && ($page = $cfg->getLandingPage()))
        echo $page->getBodyWithImages();
    else
        echo  '<h1>Welcome to the Support Center</h1>';
    ?>
    <div id="new_ticket">
        <h3>Open A New Ticket</h3>
        <br>
        <div>Please provide as much detail as possible so we can best assist you. To update a previously submitted ticket, please login.</div>
        <p>
            <a href="open.php" class="green button">Open a New Ticket</a>
        </p>
    </div>

    <div id="check_status">
        <h3>Check Ticket Status</h3>
        <br>
        <div>We provide archives and history of all your current and past support requests complete with responses.</div>
        <p>
            <a href="view.php" class="blue button">Check Ticket Status</a>
        </p>
    </div>
</div>
<div class="clear"></div>

<?php 
	if($cfg && $cfg->isKnowledgebaseEnabled()){ $displayfaq = '1'; }
	else { $displayfaq = '0'; }
	if ($ost->getConfig()->clientDisplayOpen()) { $displayopen = '1'; }
	else { $displayopen = '0'; }
	
	if ($displayopen == '1' && $displayfaq == '1') { ?>
		<p>Be sure to browse both our <a href="kb/index.php">Frequently Asked Questions (FAQs)</a>, and the open tickets below before opening a ticket.  Thank you.
		<div id="openticks"><?php include('display_open_topics.php'); ?></div>
		</p>
	<?php }
	else {
		if($displayfaq == '1') { ?>
			<p>Be sure to browse our <a href="kb/index.php">Frequently Asked Questions (FAQs)</a> before opening a ticket.  Thank you.
		<?php }
		if($displayopen == '1') { ?>
			<div id="openticks"><?php include('display_open_topics.php'); ?></div>
		<?php } 	
	} ?>

</div>
<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>
