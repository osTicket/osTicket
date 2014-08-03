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
        echo  '<h1>Bienvenue sur la plate-forme de support</h1>';
    ?>
    <div id="new_ticket">
        <h3>Ouvrir un nouveau ticket</h3>
        <br>
        <div>Veuillez, s'il-vous-plaît, fournir autant de détails que possible pour que nous puissions vous assister au mieux. Veuillez vous authentifier pour mettre à jour un ticket précédemment créé.</div>
        <p>
            <a href="open.php" class="green button">Ouvrir un nouveau ticket</a>
        </p>
    </div>

    <div id="check_status">
        <h3>Vérifier le statut d'un ticket</h3>
        <br>
        <div>Nous fournissons des archives et l'historique de toutes vos demandes de support passées et présentes, avec les réponses qui y ont été apportées.</div>
        <p>
            <a href="view.php" class="blue button">Vérifier le statut d'un ticket</a>
        </p>
    </div>
</div>
<div class="clear"></div>
<?php
if($cfg && $cfg->isKnowledgebaseEnabled()){
    //FIXME: provide ability to feature or select random FAQs ??
?>
<p>Merci de consulter la <a href="kb/index.php">Foire Aux Questions (FAQs)</a> avant d'ouvrir un ticket.</p>
</div>
<?php
} ?>
<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>
