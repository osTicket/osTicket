<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Accès refusé');

$qstr='';
$sql='SELECT cat.category_id, cat.name, cat.ispublic, cat.updated, count(faq.faq_id) as faqs '.
     ' FROM '.FAQ_CATEGORY_TABLE.' cat '.
     ' LEFT JOIN '.FAQ_TABLE.' faq ON (faq.category_id=cat.category_id) ';
$sql.=' WHERE 1';
$sortOptions=array('name'=>'cat.name','type'=>'cat.ispublic','faqs'=>'faqs','updated'=>'cat.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'cat.name';

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])]) {
    $order=$orderWays[strtoupper($_REQUEST['order'])];
}
$order=$order?$order:'ASC';

if($order_column && strpos($order_column,',')){
    $order_column=str_replace(','," $order,",$order_column);
}
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$order_by="$order_column $order ";

$total=db_count('SELECT count(*) FROM '.FAQ_CATEGORY_TABLE.' cat ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('categories.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql GROUP BY cat.category_id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' catégories';
else
    $showing='Aucune catégorie de la FAQ n’a été trouvée&nbsp;!'; <!-- pas terrible, mais encore une fois, il me manque du contexte -->

?>
<div style="width:700px;padding-top:5px; float:left;">
 <h2>Catégories de la FAQ&nbsp;<i class="help-tip icon-question-sign" href="#faq_categories"></i></h2>
 </div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
    <b><a href="categories.php?a=add" class="Icon newCategory">Ajouter une catégorie</a></b></div>
<div class="clear"></div>
<form action="categories.php" method="POST" name="cat">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th width="500"><a <?php echo $name_sort; ?> href="categories.php?<?php echo $qstr; ?>&sort=name">Nom</a></th>
            <th width="150"><a  <?php echo $type_sort; ?> href="categories.php?<?php echo $qstr; ?>&sort=type">Type</a></th>
            <th width="80"><a  <?php echo $faqs_sort; ?> href="categories.php?<?php echo $qstr; ?>&sort=faqs">FAQs</a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="categories.php?<?php echo $qstr; ?>&sort=updated">Dernière mise à jour</a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['category_id'],$ids))
                    $sel=true;
                
                $faqs=0;
                if($row['faqs'])
                    $faqs=sprintf('<a href="faq.php?cid=%d">%d</a>',$row['category_id'],$row['faqs']);
                ?>
            <tr id="<?php echo $row['category_id']; ?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?php echo $row['category_id']; ?>" class="ckb"
                            <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <td><a href="categories.php?id=<?php echo $row['category_id']; ?>"><?php echo Format::truncate($row['name'],200); ?></a>&nbsp;</td>
                <td><?php echo $row['ispublic']?'<b>Public</b>':'Internal'; ?></td>
                <td style="text-align:right;padding-right:25px;"><?php echo $faqs; ?></td>
                <td>&nbsp;<?php echo Format::db_datetime($row['updated']); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="5">
            <?php if($res && $num){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">Tout</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">Aucun</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Basculer</a>&nbsp;&nbsp;
            <?php }else{
                echo 'Aucune catégorie de la FAQ n’a été trouvée.';
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if($res && $num): //Show options..
    echo '<div>&nbsp;Page:'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
<p class="centered" id="actions">
    <input class="button" type="submit" name="make_public" value="Passer en statut public">
    <input class="button" type="submit" name="make_private" value="Passer en statut interne">
    <input class="button" type="submit" name="delete" value="Supprimer" >
</p>
<?php
endif;
?>
</form>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Veuillez confirmer</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="make_public-confirm">
        Êtes-vous sûr.e de vouloir passer les catégories sélectionnées en statut <b>public</b>&nbsp;?
    </p>
    <p class="confirm-action" style="display:none;" id="make_private-confirm">
        Êtes-vous sûr.e de vouloir passer les catégories sélectionnées en statut <b>privé</b> (interne)&nbsp;?
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Êtes-vous sûr.e de vouloir SUPPRIMER les catégories sélectionnées&nbsp;?</strong></font>
        <br><br>Les entrées supprimées ne POURRONT PAS être récupérées, y compris les éventuelles FAQ associées.
    </p>
    <div>Veuillez confirmer pour continuer.</div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="button" value="Non, annuler" class="close">
        </span>
        <span class="buttons" style="float:right">
            <input type="button" value="Oui, je confirme&nbsp;!" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
