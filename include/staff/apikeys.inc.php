<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Accès refusé');

$qstr='';
$sql='SELECT * FROM '.API_KEY_TABLE.' WHERE 1';
$sortOptions=array('key'=>'apikey','status'=>'isactive','ip'=>'ipaddr','date'=>'created','created'=>'created','updated'=>'updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'key';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'key.created';

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])]) {
    $order=$orderWays[strtoupper($_REQUEST['order'])];
}
$order=$order?$order:'DESC';

if($order_column && strpos($order_column,',')){
    $order_column=str_replace(','," $order,",$order_column);
}
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$order_by="$order_column $order ";

$total=db_count('SELECT count(*) FROM '.API_KEY_TABLE.' ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,PAGE_LIMIT);
$pageNav->setURL('apikeys.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' Clés API';
else
    $showing='Aucune clé API trouvée&nbsp;!';

?>

<div style="width:700px;padding-top:5px; float:left;">
 <h2>Clés API</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
 <b><a href="apikeys.php?a=add" class="Icon newapi">Ajouter une clé API</a></b></div>
<div class="clear"></div>
<form action="apikeys.php" method="POST" name="keys">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>        
            <th width="320"><a <?php echo $key_sort; ?> href="apikeys.php?<?php echo $qstr; ?>&sort=key">Clé API</a></th>
            <th width="120"><a  <?php echo $ip_sort; ?> href="apikeys.php?<?php echo $qstr; ?>&sort=ip">Adresse&nbsp;IP</a></th>
            <th width="100"><a  <?php echo $status_sort; ?> href="apikeys.php?<?php echo $qstr; ?>&sort=status">Statut</a></th>
            <th width="150" nowrap><a  <?php echo $date_sort; ?>href="apikeys.php?<?php echo $qstr; ?>&sort=date">Date ajoutée/a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="apikeys.php?<?php echo $qstr; ?>&sort=updated">Dernière mise à jour</a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['id'],$ids))
                    $sel=true;
                ?>
            <tr id="<?php echo $row['id']; ?>">
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['id']; ?>" 
                            <?php echo $sel?'checked="checked"':''; ?>> </td>
                <td>&nbsp;<a href="apikeys.php?id=<?php echo $row['id']; ?>"><?php echo Format::htmlchars($row['apikey']); ?></a></td>
                <td><?php echo $row['ipaddr']; ?></td>
                <td><?php echo $row['isactive']?'Active':'<b>Disabled</b>'; ?></td>
                <td>&nbsp;<?php echo Format::db_date($row['created']); ?></td>
                <td>&nbsp;<?php echo Format::db_datetime($row['updated']); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if($res && $num){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">Tout</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">Aucun</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Basculer</a>&nbsp;&nbsp;
            <?php }else{
                echo 'Aucune clé API trouvée';
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
    <input class="button" type="submit" name="enable" value="Activer >
    <input class="button" type="submit" name="disable" value="Désactiver">
    <input class="button" type="submit" name="delete" value="Supprimer">
</p>
<?php
endif;
?>
</form>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Veuillez confirmer</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        Êtes-vous sûr.e de vouloir <b>activer</b> les clés API sélectionnées&nbsp;?
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        Êtes-vous sûr.e de vouloir <b>désactiver</b> les clés API sélectionnées&nbsp;?
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Êtes-vous sûr.e de vouloir SUPPRIMER les clés API sélectionnées&nbsp;?</strong></font>
        <br><br>Les clés supprimées ne POURRON PAS être récupérées.
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
