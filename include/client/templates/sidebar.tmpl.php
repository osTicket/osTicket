<?php
$BUTTONS = isset($BUTTONS) ? $BUTTONS : true;
?>
    <div class="">
<?php if ($BUTTONS) { ?>
        <div class="text-start mt-5">

<?php
    if ($cfg->getClientRegistrationMode() != 'disabled'
        || !$cfg->isClientLoginRequired()) { ?>
            <a href="open.php"  class=""><?php
                echo __('<button class="btn btn-info text-white alg-text-p alg-bg-secondary-50 home-btn">Open New Ticket</button>');?></a>

<?php } ?>

            <a href="view.php"  class=""><?php
                echo __('<button class="btn btn-info mt-md-0 mt-2 text-white alg-text-p alg-bg-secondary-100 home-btn home-btn-two">Check Ticket Status</button>');?></a>

        </div>
<?php } ?>
        <div class="content"><?php
    if ($cfg->isKnowledgebaseEnabled()
        && ($faqs = FAQ::getFeatured()->select_related('category')->limit(5))
        && $faqs->all()) { ?>
            <section><div class="header"><?php echo __('Featured Questions'); ?></div>
<?php   foreach ($faqs as $F) { ?>
            <div><a href="<?php echo ROOT_PATH; ?>kb/faq.php?id=<?php
                echo urlencode($F->getId());
                ?>"><?php echo $F->getLocalQuestion(); ?></a></div>
<?php   } ?>
            </section>
<?php
    }
    $resources = Page::getActivePages()->filter(array('type'=>'other'));
    if ($resources->all()) { ?>
            <section><div class="header"><?php echo __('Other Resources'); ?></div>
<?php   foreach ($resources as $page) { ?>
            <div><a href="<?php echo ROOT_PATH; ?>pages/<?php echo $page->getNameAsSlug();
            ?>"><?php echo $page->getLocalName(); ?></a></div>
<?php   } ?>
            </section>
<?php
    }
        ?></div>
    </div>

