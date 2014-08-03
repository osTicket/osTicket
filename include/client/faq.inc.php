<?php
if(!defined('OSTCLIENTINC') || !$faq  || !$faq->isPublished()) die('Accès Refusé');

$category=$faq->getCategory();

?>
<h1>Foire Aux Questions</h1>
<div id="breadcrumbs">
    <a href="index.php">Toutes les catégories</a>
    &raquo; <a href="faq.php?cid=<?php echo $category->getId(); ?>"><?php echo $category->getName(); ?></a>
</div>
<div style="width:700px;padding-top:2px; float:left;">
<strong style="font-size:16px;"><?php echo $faq->getQuestion() ?></strong>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;"></div>
<div class="clear"></div>
<p>
<?php echo Format::safe_html($faq->getAnswerWithImages()); ?>
</p>
<p>
<?php
if($faq->getNumAttachments()) { ?>
 <div><span class="faded"><b>Fichiers joints&nbsp;:</b></span> <?php echo $faq->getAttachmentsLinks(); ?></div>
<?php
} ?>

<div class="article-meta"><span class="faded"><b>Articles d'aide et assistance&nbsp;:</b></span>
    <?php echo ($topics=$faq->getHelpTopics())?implode(', ',$topics):' '; ?>
</div>
</p>
<hr>
<div class="faded">&nbsp;Dernière mise à jour <?php echo Format::db_daydatetime($category->getUpdateDate()); ?></div>
