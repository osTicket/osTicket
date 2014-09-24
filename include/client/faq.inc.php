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
<div class="faded"><?php echo __('Last updated').' '.Format::db_daydatetime($category->getUpdateDate()); ?></div>
<br/>
<div class="thread-body bleed">
<?php echo Format::safe_html($faq->getLocalAnswerWithImages()); ?>
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
<div class="content">
<?php if ($attachments = $faq->getVisibleAttachments()) { ?>
<section>
    <strong><?php echo __('Attachments');?>:</strong>
<?php foreach ($attachments as $att) { ?>
    <div>
    <a href="file.php?h=<?php echo $att['download']; ?>" class="no-pjax">
        <i class="icon-file"></i>
        <?php echo Format::htmlchars($att['name']); ?>
    </a>
    </div>
<?php } ?>
</section>
<?php } ?>

<?php if ($faq->getHelpTopics()->count()) { ?>
<section>
    <strong><?php echo __('Help Topics'); ?></strong>
<?php foreach ($faq->getHelpTopics() as $topic) { ?>
    <div><?php echo $topic->getFullName(); ?></div>
<?php } ?>
</section>
<?php } ?>
</div>
</div>
</div>

</div>

<?php $faq->logView(); ?>
