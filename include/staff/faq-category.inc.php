<?php
if(!defined('OSTSTAFFINC') || !$category || !$thisstaff) die('Accès refusé');

?>
<div style="width:700px;padding-top:10px; float:left;">
  <h2>Questions fréquemment posées</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">&nbsp;</div>
<div class="clear"></div>
<br>
<div>
    <strong><?php echo $category->getName() ?></strong>
    <span>(<?php echo $category->isPublic()?'Public':'Internal'; ?>)</span>
    <time>Last updated <?php echo Format::db_date($category->getUpdateDate()); ?></time>
</div>
<div class="cat-desc">
<?php echo Format::display($category->getDescription()); ?>
</div>
<?php
if($thisstaff->canManageFAQ()) {
    echo sprintf('<div class="cat-manage-bar"><a href="categories.php?id=%d" class="Icon editCategory">Éditer la catégorie</a>
             <a href="categories.php" class="Icon deleteCategory">Supprimer la catégorie</a>
             <a href="faq.php?cid=%d&a=add" class="Icon newFAQ">Ajouter une FAQ</a></div>',
            $category->getId(),
            $category->getId());
} else {
?>
<hr>
<?php
}

$sql='SELECT faq.faq_id, question, ispublished, count(attach.file_id) as attachments '
    .' FROM '.FAQ_TABLE.' faq '
    .' LEFT JOIN '.ATTACHMENT_TABLE.' attach
         ON(attach.object_id=faq.faq_id AND attach.type=\'F\' AND attach.inline = 0) '
    .' WHERE faq.category_id='.db_input($category->getId())
    .' GROUP BY faq.faq_id ORDER BY question';
if(($res=db_query($sql)) && db_num_rows($res)) {
    echo '<div id="faq">
            <ol>';
    while($row=db_fetch_array($res)) {
        echo sprintf('
            <li><a href="faq.php?id=%d" class="previewfaq">%s <span>- %s</span></a></li>',
            $row['faq_id'],$row['question'],$row['ispublished']?'Publié':'Interne');
    }
    echo '  </ol>
         </div>';
}else {
    echo '<strong>Il n’y a pas de FAQ dans cette catégorie</strong>';
}
?>
