<?php
if(!defined('OSTCLIENTINC') || !$faq  || !$faq->isPublished()) die('Access Denied');

$category=$faq->getCategory();

?>
<div class="row">
<div class="col-xs-12 col-sm-8">

<h1><?php echo __('Frequently Asked Questions');?></h1>
<div id="breadcrumbs">
    <a href="index.php"><?php echo __('All Categories');?></a>
    &raquo; <a href="faq.php?cid=<?php echo $category->getId(); ?>"><?php echo $category->getName(); ?></a>
</div>

<div class="faq-content panel panel-default">
  <div class="panel-heading">
    <?php echo $faq->getLocalQuestion() ?>
  </div>
  <div class="panel-body">
    <?php echo Format::safe_html($faq->getLocalAnswerWithImages()); ?>
  </div>
  <div class="panel-footer text-muted">
    <?php echo __('Last updated').' '.Format::daydatetime($category->getUpdateDate()); ?>
  </div>
</div>
</div>

<div class="col-xs-12 col-sm-4">
  <div class="searchbar">
    <form method="get" action="faq.php">
    <div class="input-group">
      <input type="hidden" name="a" value="search"/>
      <input type="text" class="form-control" name="q" class="search" placeholder="<?php
        echo __('Search our knowledge base'); ?>"/>
      <span class="input-group-btn">
        <input type="submit" class="btn btn-default" style="display:none" value="search"/>
      </span>
    </div>
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

<?php $faq->logView(); ?>
