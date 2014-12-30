<?php
if (!defined('OSTCLIENTINC') || !$faq || !$faq->isPublished()) {
    die('Access Denied');
}
$category = $faq->getCategory();
?>
<h1><?= __('Frequently Asked Questions'); ?></h1>
<div id="breadcrumbs">
    <a href="index.php"><?= __('All Categories'); ?></a>
    &raquo; <a href="faq.php?cid=<?= $category->getId(); ?>"><?= $category->getName(); ?></a>
</div>
<div style="width:700px;padding-top:2px;" class="pull-left">
    <strong style="font-size:16px;"><?= $faq->getQuestion() ?></strong>
</div>
<div class="pull-right flush-right" style="padding-top:5px;padding-right:5px;"></div>
<div class="clear"></div>
<div class="thread-body">?= Format::safe_html($faq->getAnswerWithImages()); ?></div>
<p>
    <?php if ($faq->getNumAttachments()) : ?>
    <div><span class="faded"><b><?= __('Attachments'); ?>:</b></span>  <?= $faq->getAttachmentsLinks(); ?></div>
<?php endif; ?>

<div class="article-meta"><span class="faded"><b><?= __('Help Topics'); ?>:</b></span>
    <?= ($topics = $faq->getHelpTopics()) ? implode(', ', $topics) : ' '; ?>
</div>
</p>
<hr>
<div class="faded">&nbsp;<?= __('Last updated') . ' ' . Format::db_daydatetime($category->getUpdateDate()); ?></div>
