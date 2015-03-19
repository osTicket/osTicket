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
<div id="landing_page">
  <div class="row">
    <div class="col-xs-12 col-sm-8">
      <div class="row">
        <div class="col-xs-12">
          <?php if ($cfg && $cfg->isKnowledgebaseEnabled()) { ?>
            <form method="get" action="kb/faq.php" class="search-form">
              <div class="input-group">
                <input type="hidden" name="a" value="search"/>
                <input type="text" name="q" class="search form-control" placeholder="Search our knowledge base"/>
                <span class="input-group-btn">
                  <button type="submit" class="btn btn-success">Search</button>
                </span>
              </div>
            </form>
          <?php } ?>
        </div>
      </div>
      <div class="row">
        <div class="welcome col-xs-12">
          <?php 
          if($cfg && ($page = $cfg->getLandingPage()))
            echo $page->getBodyWithImages();
          else
            echo  '<h1>'.__('Welcome to the Support Center').'</h1>';
          ?>
        </div>
      </div>
    </div>
    <div class="sidebar col-xs-12 col-sm-4">
      <a href="open.php" style="display:block" class="btn btn-primary btn-lg">
        <?php echo __('Open a New Ticket');?>
      </a>
      <?php
          $faqs = FAQ::getFeatured()->select_related('category')->limit(5);
          if ($faqs->all()) { ?>
                  <div class="panel panel-default">
                    <div class="panel-heading"><?php echo __('Featured Questions'); ?></div>
                      <ul class="list-group">
      <?php   foreach ($faqs as $F) { ?>
                  <li class="list-group-item"><a href="<?php echo ROOT_PATH; ?>/kb/faq.php?id=<?php
                      echo urlencode($F->getId());
                      ?>"><?php echo $F->getLocalQuestion(); ?></a></li>
      <?php   } ?>
                     </ul>
                  </div>
      <?php
          }
          $resources = Page::getActivePages()->filter(array('type'=>'other'));
          if ($resources->all()) { ?>
                  <div class="panel panel-default">
                    <div class="panel-heading"><?php echo __('Other Resources'); ?></div>
                      <ul class="list-group">
      <?php   foreach ($resources as $page) { ?>
                  <li class="list-group-item"><a href="<?php echo ROOT_PATH; ?>pages/<?php echo $page->getNameAsSlug();
                  ?>"><?php echo $page->getLocalName(); ?></a></li>
      <?php   } ?>
                  </ul>
                </div>
      <?php
          } ?>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-xs-12">
    <?php
    if($cfg && $cfg->isKnowledgebaseEnabled()){
        //FIXME: provide ability to feature or select random FAQs ??
    ?>
  </div>
</div>
<div class="row">
  <div class="col-xs-12">
    <?php
    $cats = Category::getFeatured();
    if ($cats->all()) { ?>
    <h1>Featured Knowledge Base Articles</h1>
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
<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>
