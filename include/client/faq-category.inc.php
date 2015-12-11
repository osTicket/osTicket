<?php
if(!defined('OSTCLIENTINC') || !$category || !$category->isPublic()) die('Access Denied');
?>

<div class="row">
<div class="span8">
    <h1><?php echo __('Frequently Asked Questions');?></h1>
    <h2><strong><?php echo $category->getLocalName() ?></strong></h2>
<p>
<?php echo Format::safe_html($category->getLocalDescriptionWithImages()); ?>
</p>
<hr>
<?php
$faqs = FAQ::objects()
    ->filter(array('category'=>$category))
    ->exclude(array('ispublished'=>FAQ::VISIBILITY_PRIVATE))
    ->annotate(array('has_attachments' => SqlAggregate::COUNT(SqlCase::N()
        ->when(array('attachments__inline'=>0), 1)
        ->otherwise(null)
    )))
    ->order_by('-ispublished', 'question');

if ($faqs->exists(true)) {
    echo '
         <h2>'.__('Further Articles').'</h2>
         <div id="faq">
            <ol>';
foreach ($faqs as $F) {
        $attachments=$F->has_attachments?'<span class="Icon file"></span>':'';
        echo sprintf('
            <li><a href="faq.php?id=%d" >%s &nbsp;%s</a></li>',
            $F->getId(),Format::htmlchars($F->question), $attachments);
    }
    echo '  </ol>
         </div>';
}else {
    echo '<strong>'.__('This category does not have any FAQs.').' <a href="index.php">'.__('Back To Index').'</a></strong>';
}
?>
</div>

<div class="span4">
    <div class="sidebar">
    <div class="searchbar">
        <form method="get" action="faq.php">
        <input type="hidden" name="a" value="search"/>
        <input type="text" name="q" class="search" placeholder="<?php
            echo __('Search our knowledge base'); ?>"/>
        <input type="submit" style="display:none" value="search"/>
        </form>
    </div>
    <div class="content">
        <section>
            <div class="header"><?php echo __('Help Topics'); ?></div>
<?php
foreach (Topic::objects()
    ->filter(array('faqs__faq__category__category_id'=>$category->getId()))
    as $t) { ?>
        <a href="?topicId=<?php echo urlencode($t->getId()); ?>"
            ><?php echo $t->getFullName(); ?></a>
<?php } ?>
        </section>
    </div>
    </div>
</div>
</div>
