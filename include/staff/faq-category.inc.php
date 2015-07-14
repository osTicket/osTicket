<?php
if(!defined('OSTSTAFFINC') || !$category || !$thisstaff) die('Access Denied');

?>
<div class="pull-left" style="width:700px;padding-top:10px;">
  <h2><?php echo __('Frequently Asked Questions');?></h2>
</div>
<div class="pull-right flush-right" style="padding-top:5px;padding-right:5px;">&nbsp;</div>
<div class="clear"></div>
<br>
<div>
    <strong><?php echo $category->getName() ?></strong>
    <span>(<?php echo $category->isPublic()?__('Public'):__('Internal'); ?>)</span>
    <time class="faq"> <?php echo __('Last updated').' '. Format::daydatetime($category->getUpdateDate()); ?></time>
</div>
<div class="cat-desc">
<?php echo Format::display($category->getDescription()); ?>
</div>
<?php
if ($thisstaff->hasPerm(FAQ::PERM_MANAGE)) {
    echo sprintf('<div class="cat-manage-bar"><a href="categories.php?id=%d" class="Icon editCategory">'.__('Edit Category').'</a>
             <a href="categories.php" class="Icon deleteCategory">'.__('Delete Category').'</a>
             <a href="faq.php?cid=%d&a=add" class="Icon newFAQ">'.__('Add New FAQ').'</a></div>',
            $category->getId(),
            $category->getId());
} else {
?>
<hr>
<?php
}

$faqs = $category->faqs
    ->constrain(array('attachments__inline' => 0))
    ->annotate(array('attachments' => SqlAggregate::COUNT('attachments')));
if ($faqs->exists(true)) {
    echo '<div id="faq">
            <ol>';
    foreach ($faqs as $faq) {
        echo sprintf('
            <li><a href="faq.php?id=%d" class="previewfaq">%s <span>- %s</span></a> %s</li>',
            $faq->getId(),$faq->getQuestion(),$faq->isPublished() ? __('Published'):__('Internal'),
            $faq->attachments ? '<i class="icon-paperclip"></i>' : ''
        );
    }
    echo '  </ol>
         </div>';
}else {
    echo '<strong>'.__('Category does not have FAQs').'</strong>';
}
?>
