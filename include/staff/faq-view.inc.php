<?php
if(!defined('OSTSTAFFINC') || !$faq || !$thisstaff) die('Accès refusé');

$category=$faq->getCategory();

?>
<h2>Questions fréquemment posées</h2>
<div id="breadcrumbs">
    <a href="kb.php">Toutes les catégories</a>
    &raquo; <a href="kb.php?cid=<?php echo $category->getId(); ?>"><?php echo $category->getName(); ?></a>
    <span class="faded">(<?php echo $category->isPublic()?'Public':'Interne'; ?>)</span>
</div>
<div style="width:700px;padding-top:2px; float:left;">
<strong style="font-size:16px;"><?php echo $faq->getQuestion() ?></strong>&nbsp;&nbsp;<span class="faded"><?php echo $faq->isPublished()?'(Publié)':''; ?></span>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
<?php
if($thisstaff->canManageFAQ()) {
    echo sprintf('<a href="faq.php?id=%d&a=edit" class="Icon newHelpTopic">Éditer la FAQ</a>',
            $faq->getId());
}
?>
&nbsp;
</div>
<div class="clear"></div>
<div class="thread-body">
<?php echo $faq->getAnswerWithImages(); ?>
</div>
<div class="clear"></div>
<p>
 <div><span class="faded"><b>Fichiers attachés</b></span> <?php echo $faq->getAttachmentsLinks(); ?></div>
 <div><span class="faded"><b>Rubriques d’aide</b></span>
    <?php echo ($topics=$faq->getHelpTopics())?implode(', ',$topics):' '; ?>
    </div>
</p>
<div class="faded">&nbsp;Dernière mise à jour <?php echo Format::db_daydatetime($faq->getUpdateDate()); ?></div>
<hr>
<?php
if($thisstaff->canManageFAQ()) {
    //TODO: add js confirmation....
    ?>
   <div>
    <form action="faq.php?id=<?php echo  $faq->getId(); ?>" method="post">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo  $faq->getId(); ?>">
        <input type="hidden" name="do" value="manage-faq">
        <div>
            <strong>Options </strong>
            <select name="a" style="width:200px;">
                <option value="">Sélectionner une action</option>
                <?php
                if($faq->isPublished()) { ?>
                <option value="unpublish">Dépublier la FAQ</option>
                <?php
                }else{ ?>
                <option value="publish">Publier la FAQ</option>
                <?php
                } ?>
                <option value="edit">Éditer la FAQ</option>
                <option value="delete">Supprimer la FAQ</option>
            </select>
            &nbsp;&nbsp;<input type="submit" name="submit" value="OK">
        </div>
    </form>
   </div>
<?php
}
?>
