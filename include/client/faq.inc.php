<?php
if(!defined('OSTCLIENTINC') || !$faq  || !$faq->isPublished()) die('Access Denied');

$category=$faq->getCategory();

?>
<div class="row">
<div class="span8">

<h1><?php echo __('Frequently Asked Questions');?></h1>
<div id="breadcrumbs">
    <a href="index.php"><?php echo __('All Categories');?></a>
    &raquo; <a href="faq.php?cid=<?php echo $category->getId(); ?>"><?php echo $category->getName(); ?></a>
</div>

<div class="faq-content">
<div class="article-title flush-left">
<?php echo $faq->getLocalQuestion() ?>
</div>
<div class="faded"><?php echo sprintf(__('Last Updated %s'),
    Format::relativeTime(Misc::db2gmtime($category->getUpdateDate()))); ?></div>
<br/>
<div class="thread-body bleed">
<?php echo $faq->getLocalAnswerWithImages(); ?>
</div>
</div>
</div>

<div class="span4 pull-right">
<div class="sidebar">
<div class="searchbar">
    <form method="get" action="faq.php">
    <input type="hidden" name="a" value="search"/>
    <input type="text" name="q" class="search" placeholder="<?php
        echo __('Search our knowledge base'); ?>"/>
    <input type="submit" style="display:none" value="search"/>
    </form>
</div>
<div class="content"><?php
    if ($attachments = $faq->getLocalAttachments()->all()) { ?>
<section>
    <strong><?php echo __('Attachments');?>:</strong>
<?php foreach ($attachments as $att) { ?>
    <div>
    <a href="<?php echo $att->file->getDownloadUrl(); ?>" class="no-pjax">
        <i class="icon-file"></i>
        <?php echo Format::htmlchars($att->getFilename()); ?>
    </a>
    </div>
<?php } ?>
</section>
<?php }
if ($faq->getHelpTopics()->count()) { ?>
<section>
    <strong><?php echo __('Help Topics'); ?></strong>
<?php foreach ($faq->getHelpTopics() as $T) { ?>
    <div><?php echo $T->topic->getFullName(); ?></div>
<?php } ?>
</section>
<?php }
?></div>
</div>
</div>

</div>
