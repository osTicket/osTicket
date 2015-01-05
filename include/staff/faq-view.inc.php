<?php
if(!defined('OSTSTAFFINC') || !$faq || !$thisstaff) die('Access Denied');

$category=$faq->getCategory();

?>
<h2><?php echo __('Frequently Asked Questions');?></h2>
<div id="breadcrumbs">
    <a href="kb.php"><?php echo __('All Categories');?></a>
    &raquo; <a href="kb.php?cid=<?php echo $category->getId(); ?>"><?php echo $category->getName(); ?></a>
    <span class="faded">(<?php echo $category->isPublic()?__('Public'):__('Internal'); ?>)</span>
</div>

<div class="pull-right sidebar faq-meta">
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

<?php
$displayLang = $faq->getDisplayLang();
$otherLangs = array();
if ($cfg->getPrimaryLanguage() != $displayLang)
    $otherLangs[] = $cfg->getPrimaryLanguage();
foreach ($faq->getAllTranslations() as $T) {
    if ($T->lang != $displayLang)
        $otherLangs[] = $T->lang;
}
if ($otherLangs) { ?>
<section>
    <div><strong><?php echo __('Other Languages'); ?></strong></div>
<?php
    foreach ($otherLangs as $lang) { ?>
    <div><a href="faq.php?kblang=<?php echo $lang; ?>&id=<?php echo $faq->getId(); ?>">
        <?php echo Internationalization::getLanguageDescription($lang); ?>
    </a></div>
    <?php } ?>
</section>
<?php } ?>

<section>
<div>
    <strong><?php echo $faq->isPublished()?__('Published'):__('Internal'); ?></strong>
</div>
<a href="#"><?php echo __('manage access'); ?></a>
</section>

</div>

<div class="faq-content">
<div class="faq-manage pull-right">
    <button>
    <i class="icon-print"></i>
<?php
$query = array();
parse_str($_SERVER['QUERY_STRING'], $query);
$query['a'] = 'print';
$query['id'] = $faq->getId();
$query = http_build_query($query); ?>
    <a href="faq.php?<?php echo $query; ?>" class="no-pjax"><?php
        echo __('Print'); ?>
    </a></button>
<?php
if ($thisstaff->getRole()->hasPerm(FAQ::PERM_MANAGE)) { ?>
    <button>
    <i class="icon-edit"></i>
    <a href="faq.php?id=<?php echo $faq->getId(); ?>&a=edit"><?php
        echo __('Edit FAQ'); ?>
    </a></button>
<?php } ?>
</div>

<div class="faq-title flush-left"><?php echo $faq->getLocalQuestion() ?>
</div>

<div class="faded"><?php echo __('Last updated');?>
    <?php echo Format::daydatetime($category->getUpdateDate()); ?>
</div>
<br/>
<div class="thread-body bleed">
<?php echo $faq->getLocalAnswerWithImages(); ?>
</div>

</div>
<div class="clear"></div>
<hr>

<?php
if ($thisstaff->getRole()->hasPerm(FAQ::PERM_MANAGE)) { ?>
<form action="faq.php?id=<?php echo  $faq->getId(); ?>" method="post">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="manage-faq">
    <input type="hidden" name="id" value="<?php echo  $faq->getId(); ?>">
    <button name="a" value="delete"><?php echo __('Delete FAQ'); ?></button>
</form>
<?php }
?>
