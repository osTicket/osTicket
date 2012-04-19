<?php
if(!defined('OSTSTAFFINC') || !$category || !$thisstaff) die('Access Denied');

?>
<div style="width:700;padding-top:10px; float:left;">
  <h2>Frequently Asked Questions</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">&nbsp;</div>
<div class="clear"></div>
<br>
<div>
    <strong><?php echo $category->getName() ?></strong>
    <span>(<?php echo $category->isPublic()?'Public':'Internal'; ?>)</span>
    <br>
    <div class="faded">&nbsp;Last updated <?php echo Format::db_daydatetime($category->getUpdateDate()); ?></div>
</div>
<p>
<?php echo Format::safe_html($category->getDescription()); ?>
</p>
<?php
if($thisstaff->canManageFAQ()) {
    echo sprintf('<a href="categories.php?id=%d" class="Icon editCategory">Edit Category</a>
            | <a href="categories.php" class="Icon deleteCategory">Delete Category</a>
            | <a href="faq.php?cid=%d&a=add" class="Icon newFAQ">Add New FAQ</a>',
            $category->getId(),
            $category->getId());
}
?>
<hr>
<?php

$sql='SELECT faq.faq_id, question, ispublished, count(attach.file_id) as attachments '
    .' FROM '.FAQ_TABLE.' faq '
    .' LEFT JOIN '.FAQ_ATTACHMENT_TABLE.' attach ON(attach.faq_id=faq.faq_id) '
    .' WHERE faq.category_id='.db_input($category->getId())
    .' GROUP BY faq.faq_id';
if(($res=db_query($sql)) && db_num_rows($res)) {
    echo '<div id="faq">
            <ol>';
    while($row=db_fetch_array($res)) {
        echo sprintf('
            <li><a href="faq.php?id=%d" class="previewfaq">%s</a> - <span>%s</span></li>',
            $row['faq_id'],$row['question'],$row['ispublished']?'Published':'Internal');
    }
    echo '  </ol>
         </div>';
}else {
    echo '<strong>Category does not have FAQs</strong>';
}
?>
