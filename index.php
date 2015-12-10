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
require(CLIENTINC_DIR.'header.inc.php');
?>

	<div class="clearfix"></div>
<div id="landing_page" class="container">
    <div class="row">
        <div class="col-xs-12 col-md-8">
            
           
                    <?php if($cfg && ($page = $cfg->getLandingPage()))
                        echo $page->getBodyWithImages();
                    else
                        echo  '<h1>'.__('Welcome to the Support Center').'</h1>';
                    ?>
            
        </div>
        <?php
            $BUTTONS = isset($BUTTONS) ? $BUTTONS : true;
        ?>
        <div class="sidebar col-xs-12 col-sm-4">
		
		<div class="row">
                <div class="col-xs-12" >
                    <?php if ($cfg && $cfg->isKnowledgebaseEnabled()) { ?>
                        <form method="get" action="kb/faq.php" class="search-form">
                            <div class="input-group">
                                <input type="hidden" name="a" value="search"/>
                                <input type="text" name="q" class="search form-control" placeholder="Search our knowledge base"/>
                                <span class="input-group-btn">
                                    <button type="submit" class="btn btn-info">Search</button>
                                </span>
                            </div>
                        </form><br>
                    <?php } ?>
                </div>
            </div>
			
            <?php
            $faqs = FAQ::getFeatured()->select_related('category')->limit(5); 
            if ($faqs->all()) { ?>
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <?php echo __('Featured Questions'); ?>
                        </h3>
                    </div>
                    <ul class="list-group">
                        <?php foreach ($faqs as $F) { ?>
                            <li class="list-group-item">
                                <a href="<?php echo ROOT_PATH; ?>kb/faq.php?id=<?php echo $F->getId(); ?>">
                                    <?php echo $F->getLocalQuestion(); ?>
                                </a>
                            </li>
                        <?php } ?>
                     </ul>
                </div>
            <?php }
            $resources = Page::getActivePages()->filter(array('type'=>'other'));
            if ($resources->all()) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php echo __('Other Resources'); ?>
                    </div>
                    <ul class="list-group">
                        <?php foreach ($resources as $page) { ?>
                            <li class="list-group-item">
                                <a href="<?php echo ROOT_PATH; ?>pages/<?php echo $page->getNameAsSlug(); ?>">
                                    <?php echo $page->getLocalName(); ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } 
            if ($BUTTONS) { ?>
                <a href="open.php" style="display:block" class="btn btn-success btn-lg btn-block">
                    <?php echo __('Open a New Ticket');?>
                </a>
                <?php if ($cfg && !$cfg->isKnowledgebaseEnabled()) { ?>
                    <a href="view.php" style="display:block" class="btn btn-success btn-lg btn-block">
                        <?php echo __('Check Ticket Status');?>
                    </a>
                <?php } 
            } ?>
        </div>
    </div>
</div>
<div class="container row">
    <div class="col-xs-12 col-sm-6">
    <?php if($cfg && $cfg->isKnowledgebaseEnabled()){
        //FIXME: provide ability to feature or select random FAQs ??
    ?>
    </div>
</div>
</div>
<div class="container row">
    <div class="col-xs-12">
        <?php
        $cats = Category::getFeatured();
        if ($cats->all()) { ?>
            <h1>Featured Knowledge Base Articles</h1>
        <?php }
        foreach ($cats as $C) { ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span>
                        &nbsp;<?php echo $C->getName(); ?>
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <?php foreach ($C->getTopArticles() as $F) { ?>
                            <div class="col-sm-6">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">
                                            <a href="<?php echo ROOT_PATH; ?>kb/faq.php?id=<?php echo $F->getId(); ?>">
                                                <?php echo $F->getQuestion(); ?>
                                            </a>
                                        </h3>
                                    </div>
                                    <div class="panel-body">
                                        <?php echo $F->getTeaser(); ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php }
    } ?>
</div>
</div>
<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>
