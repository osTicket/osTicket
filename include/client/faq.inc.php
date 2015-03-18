<?php
if(!defined('OSTCLIENTINC') || !$faq  || !$faq->isPublished()) die('Access Denied');

$category=$faq->getCategory();

?>
<h1><?php echo __('Frequently Asked Questions');?></h1>
<div id="breadcrumbs">
  <a href="index.php"><?php echo __('All Categories');?></a>
  &raquo; <a href="faq.php?cid=<?php echo $category->getId(); ?>"><?php echo $category->getName(); ?></a>
</div>
<div class="panel panel-default">
  <div class="panel-heading">
    <strong style="font-size:16px;"><?php echo $faq->getQuestion() ?></strong>
  </div>
  <div class="panel-body">
    <?php echo Format::safe_html($faq->getAnswerWithImages()); 
    if($faq->getNumAttachments()) { ?>
      <div><span class="text-muted"><b><?php echo __('Attachments');?>:</b></span>  <?php echo $faq->getAttachmentsLinks(); ?></div>
      <?php
    } ?>
  </div>
  <div class="panel-footer clearfix">
    <div class="article-meta pull-left">
      <span class="text-muted"><b><?php echo __('Help Topics');?>:</b></span>
      <?php echo ($topics=$faq->getHelpTopics())?implode(', ',$topics):' '; ?>
    </div>
    <div class="text-muted pull-right">&nbsp;<?php echo __('Last updated').' '.Format::db_daydatetime($category->getUpdateDate()); ?></div>
  </div>
</div>
