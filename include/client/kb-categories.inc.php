<div class="row">
<div class="col-xs-12 col-sm-8">
<?php
    $categories = Category::objects()
        ->exclude(Q::any(array('ispublic'=>false, 'faqs__ispublished'=>false)))
        ->annotate(array('faq_count'=>SqlAggregate::COUNT('faqs')))
        ->filter(array('faq_count__gt'=>0));
    if ($categories->all()) { ?>
        <h2><?php echo __('Click on the category to browse FAQs.'); ?></h2>
        <div class="row">
<?php
        foreach ($categories as $C) { ?>
            <div class="panel panel-primary">
              <div class="panel-heading">
                <h3 class="panel-title"><?php echo sprintf('<a href="faq.php?cid=%d">%s (%d)</a>',
                $C->getId(), Format::htmlchars($C->getLocalName()), $C->faq_count); ?></h3>
              </div>
              <div class="panel-body">
              <div class="text-muted">
                <?php echo Format::safe_html($C->getLocalDescriptionWithImages()); ?>
              </div>
              </div>
              <ul class="list-group">
  <?php       foreach ($C->faqs
                    ->exclude(array('ispublished'=>false))
                    ->order_by('-views')->limit(5) as $F) { ?>
                <li class="list-group-item"><i class="icon-file-alt"></i>
                <a href="faq.php?id=<?php echo $F->getId(); ?>">
                <?php echo $F->getLocalQuestion() ?: $F->getQuestion(); ?>
                </a></li>
<?php       } ?>
              </ul>
           </div>
<?php   } ?>
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
        <option value="<?php echo $T->getId(); ?>"><?php echo $T->getFullName();
            ?></option>
<?php } ?>
        </select>
        </form>
    </div>
    <br/>
    <div class="content">
        <section>
            <div class="header"><?php echo __('Other Resources'); ?></div>
        </section>
    </div>
  </div>
</div>
