<?php
if(!defined('OSTCLIENTINC') || !$category || !$category->isPublic()) die('Access Denied');
?>

<div class="row">
<div class="col-xs-12 col-sm-8">
    <h1><?php echo __('Frequently Asked Questions');?></h1>
    <h3><?php echo $category->getLocalName() ?></h3>
    <div class="list-group">
        <div class="list-group-item text-muted">
            <?php echo Format::safe_html($category->getLocalDescriptionWithImages()); ?>
        </div>
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
foreach ($faqs as $F) {
        $attachments=$F->has_attachments?'<span class="glyphicon glyphicon-file"></span>':'';
        echo sprintf('
            <div class="list-group-item">
              <a href="faq.php?id=%d" >%s &nbsp;%s</a></div>',
            $F->getId(),Format::htmlchars($F->question), $attachments);
    }
}else {
    echo '<div class="list-group-item"><strong>'.__('This category does not have any FAQs.').' <a href="index.php">'.__('Back To Index').'</a></strong></div>';
}
?>
</div>
</div>

<div class="col-xs-12 col-sm-4">
    <div class="sidebar">
    <div class="searchbar">
        <form method="get" action="faq.php">
            <div class="input-group">
                <input type="hidden" name="a" value="search"/>
                <input type="text" class="form-control" name="q" class="search" placeholder="<?php
                    echo __('Search our knowledge base'); ?>"/>
                <span class="input-group-btn">
                    <button type="submit" class="btn btn-success">Search</button>
                </span>
            </div>
        </form>
    </div>
    <div class="clearfix">&nbsp;</div>
    <div class="content">
        <div class="panel panel-primary">
            <div class="panel-heading"><?php echo __('Help Topics'); ?></div>
            <div class="panel-body">
<?php
foreach (Topic::objects()
    ->filter(array('faqs__faq__category__category_id'=>$category->getId()))
    as $t) { ?>
        <a href="?topicId=<?php echo urlencode($t->getId()); ?>"
            ><?php echo $t->getFullName(); ?></a>
<?php } ?>
            </div>
        </div>
    </div>
    </div>
</div>
</div>
