<?php
if(!defined('OSTCLIENTINC') || !$category || !$category->isPublic()) die('Access Denied');

?>
<div style="width:700;padding-top:10px; float:left;">
  <h2>Frequently Asked Questions</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">&nbsp;</div>
<div class="clear"></div>
<br>
<div><strong><?php echo $category->getName() ?></strong></div>
<p>
<?php echo Format::safe_html($category->getDescription()); ?>
</p>
<hr>
<?php
$sql='SELECT faq.faq_id, question '
    .' FROM '.FAQ_TABLE.' faq '
    .' LEFT JOIN '.FAQ_ATTACHMENT_TABLE.' attach ON(attach.faq_id=faq.faq_id) '
    .' WHERE faq.ispublished=1 AND faq.category_id='.db_input($category->getId())
    .' GROUP BY faq.faq_id';
if(($res=db_query($sql)) && db_num_rows($res)) {
    echo '<div id="faq">
            <ol>';
    while($row=db_fetch_array($res)) {
        echo sprintf('
            <li><a href="faq.php?id=%d" >%s</a></li>',
            $row['faq_id'],Format::htmlchars($row['question']));
    }
    echo '  </ol>
         </div>';
}else {
    echo '<strong>Category does not have any FAQs. <a href="index.php">Back To Index</a></strong>';
}
?>
