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

require_once INCLUDE_DIR . 'class.page.php';

$section = 'home';
require(CLIENTINC_DIR . 'header.inc.php');
?>
<div id="landing_page">
    <div class="">
        <?php
        if ($cfg && $cfg->isKnowledgebaseEnabled()) { ?>
            <div class="search-form">
                <form method="get" action="kb/faq.php">
                    <input type="hidden" name="a" value="search" />
                    <input type="text" name="q" class="search" placeholder="<?php echo __('Search our knowledge base'); ?>" />
                    <button type="submit" class="green button"><?php echo __('Search'); ?></button>
                </form>
            </div>
        <?php } ?>
        <div class="container">
            <div class="row">
                <div class="col-md-7 mt-md-0 mt-4">
                    <?php
                    if ($cfg && ($page = $cfg->getLandingPage()))
                        echo '<h1 class="alg-text-h1 alg-text-secondary-100 text-center  " style="color:#013237;">Welcome!<br>  Support Center</h1> 
    <p class="alg-text-p text-start mt-4">In order to streamline support requests and better serve you, we utilize a support ticket system. Every support request is assigned a unique ticket number which you can use to track the progress and responses online. For your reference we provide complete archives and history of all your support requests. A valid email address is required to submit a ticket.
</p>
    ';
                    else
                        echo  '<h1 class="alg-text-h1">' . __('Welcome to the Support Center') . '</h1>';
                    ?>
                    <?php include CLIENTINC_DIR . 'templates/sidebar.tmpl.php'; ?>

                </div>
                <div class="col-md-5 text-center order-md-last order-first">
                    <img src="./images/hero.png" alt="" class=" home-img">

                </div>

            </div>

        </div>
    </div>
    <div class="clear"></div>

    <div>
        <?php
        if ($cfg && $cfg->isKnowledgebaseEnabled()) {
            //FIXME: provide ability to feature or select random FAQs ??
        ?>
            <br /><br />
            <?php
            $cats = Category::getFeatured();
            if ($cats->all()) { ?>
                <h1><?php echo __('Featured Knowledge Base Articles'); ?></h1>
            <?php
            }

            foreach ($cats as $C) { ?>
                <div class="featured-category front-page">
                    <i class="icon-folder-open icon-2x"></i>
                    <div class="category-name">
                        <?php echo $C->getName(); ?>
                    </div>
                    <?php foreach ($C->getTopArticles() as $F) { ?>
                        <div class="article-headline">
                            <div class="article-title"><a href="<?php echo ROOT_PATH;
                                                                ?>kb/faq.php?id=<?php echo $F->getId(); ?>"><?php
                                                                                                            echo $F->getQuestion(); ?></a></div>
                            <div class="article-teaser"><?php echo $F->getTeaser(); ?></div>
                        </div>
                    <?php } ?>
                </div>
        <?php
            }
        }
        ?>
    </div>
</div>

<?php require(CLIENTINC_DIR . 'footer.inc.php'); ?>