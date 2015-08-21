<div class="row">
    <div class="col-xs-12 col-sm-8">
        <?php
    $categories = Category::objects()
        ->exclude(Q::any(array(
            'ispublic'=>Category::VISIBILITY_PRIVATE,
            'faqs__ispublished'=>FAQ::VISIBILITY_PRIVATE,
        )))
        ->annotate(array('faq_count'=>SqlAggregate::COUNT('faqs')))
        ->filter(array('faq_count__gt'=>0));
    if ($categories->exists(true)) { ?>
        <h2><?php echo __('Click on the category to browse FAQs.'); ?></h2>
        <div class="row">
            <div class="col-xs-12">
<?php
        foreach ($categories as $C) { ?>
            <h3><?php echo sprintf('<a href="faq.php?cid=%d">%s</a>',
                $C->getId(), Format::htmlchars($C->getLocalName())); ?></h3>
            <div class="list-group">
                <div class="list-group-item text-muted">
                    <?php echo Format::safe_html($C->getLocalDescriptionWithImages()); ?>
                    <span class="badge"><?php echo $C->faq_count; ?></span>
                </div>
<?php       foreach ($C->faqs
                    ->exclude(array('ispublished'=>FAQ::VISIBILITY_PRIVATE))
                    ->limit(5) as $F) { ?>
                <div class="popular-faq"><i class="icon-file-alt"></i>
                <a href="faq.php?id=<?php echo $F->getId(); ?>">
                <?php echo $F->getLocalQuestion() ?: $F->getQuestion(); ?>
                </a></div>
<?php       } ?>
            </div>
<?php   } ?>
            </div>
        </div>
<?php
    } else {
        echo __('NO FAQs found');
    }
?>
    </div>
    <div class="col-xs-12 col-sm-4">
        <div class="searchbar">
            <form method="get" action="faq.php">
                <input type="hidden" name="a" value="search"/>
                <select class="form-control" name="topicId"
                    onchange="javascript:this.form.submit();">
                    <option value="">— Browse by Topic —</option>
<?php
                    $topics = Topic::objects()
                        ->annotate(array('has_faqs'=>SqlAggregate::COUNT('faqs')))
                        ->filter(array('has_faqs__gt'=>0));
                    foreach ($topics as $T) { ?>
                        <option value="<?php echo $T->getId(); ?>"><?php echo $T->getFullName();?></option>
<?php               } ?>
                </select>
            </form>
        </div>
        <br/>
        <div class="content">
            <div class="panel panel-primary">
                <div class="panel-heading"><?php echo __('Other Resources'); ?></div>
                <div class="panel-body"></div>
            </div>
        </div>
    </div>
</div>
</div>
