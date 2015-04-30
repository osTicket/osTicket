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
<div id="landing_page" class="row">
    <div class="col-md-12">
        <?php
            if($cfg && ($page = $cfg->getLandingPage()))
                echo $page->getBodyWithImages();
            else
                echo  '<h1>'.__('Welcome to the Support Center').'</h1>';
        ?>
    </div>
    <div id="new_ticket" class="col-sm-6">
        <h3><?php echo __('Open a New Ticket');?></h3>
        <div><?php echo __('Please provide as much detail as possible so we can best assist you. To update a previously submitted ticket, please login.');?></div>
        <p>
            <a href="open.php" class="btn btn-success"><?php echo __('Open a New Ticket');?></a>
        </p>
    </div>

    <div id="check_status" class="col-sm-6">
        <h3><?php echo __('Check Ticket Status');?></h3>
        <div><?php echo __('We provide archives and history of all your current and past support requests complete with responses.');?></div>
        <p>
            <a href="view.php" class="btn btn-primary"><?php echo __('Check Ticket Status');?></a>
        </p>
    </div>
</div>
<?php
if($cfg && $cfg->isKnowledgebaseEnabled()){
    //FIXME: provide ability to feature or select random FAQs ??
?>
<div class="row">
    <div class="col-md-12">
        <br/><p><?php echo sprintf(
    __('Be sure to browse our %s before opening a ticket'),
    sprintf('<a href="kb/index.php">%s</a>',
        __('Frequently Asked Questions (FAQs)')
    )); ?></p>
</div>
</div>
</div>
<?php
} ?>
<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>
