<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$filter) die('Accès refusé');

$qstr='';
$select='SELECT rule.* ';
$from='FROM '.FILTER_RULE_TABLE.' rule ';
$where='WHERE rule.filter_id='.db_input($filter->getId());
$search=false;
if($_REQUEST['q'] && strlen($_REQUEST['q'])>3) {
    $search=true;
    if(strpos($_REQUEST['q'],'@') && Validator::is_email($_REQUEST['q']))
        $where.=' AND rule.val='.db_input($_REQUEST['q']);
    else
        $where.=' AND rule.val LIKE "%'.db_input($_REQUEST['q'],false).'%"';

}elseif($_REQUEST['q']) {
    $errors['q']='Term too short!';
}

$sortOptions=array('email'=>'rule.val','status'=>'isactive','created'=>'rule.created','created'=>'rule.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'email';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'rule.val';

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

$total=db_count('SELECT count(DISTINCT rule.id) '.$from.' '.$where);
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('banlist.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$select $from $where ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
?>
<h2>Adresses courriel interdites
    <i class="help-tip icon-question-sign" href="#ban_list"></i>
    </h2>
<div style="width:600; float:left;padding-top:5px;">
    <form action="banlist.php" method="GET" name="filter">
     <input type="hidden" name="a" value="filter" >
     <div>
       Requête&nbsp;: <input name="q" type="text" size="20" value="<?php echo Format::htmlchars($_REQUEST['q']); ?>">
        &nbsp;&nbsp;
        <input type="submit" name="submit" value="Rechercher"/>
     </div>
    </form>
 </div>
<div style="float:right;text-align:right;padding-right:5px;"><b><a href="banlist.php?a=add" class="Icon newstaff">Interdire une adresse courriel</a></b></div>
<div class="clear"></div>
<?php
if(($res=db_query($query)) && ($num=db_num_rows($res)))
    $showing=$pageNav->showing();
else
    $showing='Aucune adresse courriel correspondant à la requête n’a été trouvée!';

if($search)
    $showing='Search Results: '.$showing;
    
?>
<form action="banlist.php" method="POST" name="banlist">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7px">&nbsp;</th>        
            <th width="350"><a <?php echo $email_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=email">Adresses courriel</a></th>
            <th width="200"><a  <?php echo $status_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=status">Statut</a></th>
            <th width="120"><a <?php echo $created_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=created">Date de création</a></th>
            <th width="120"><a <?php echo $updated_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=updated">Dernière mise à jour</a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        if($res && db_num_rows($res)):
            $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['id'],$ids))
                    $sel=true;
                ?>
               <tr id="<?php echo $row['id']; ?>">
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <td>&nbsp;<a href="banlist.php?id=<?php echo $row['id']; ?>"><?php echo Format::htmlchars($row['val']); ?></a></td>
                <td>&nbsp;&nbsp;<?php echo $row['isactive']?'Active':'<b>Autorisé</b>'; ?></td>
                <td><?php echo Format::db_date($row['created']); ?></td>
                <td><?php echo Format::db_datetime($row['updated']); ?>&nbsp;</td>
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
                echo 'Aucune adresse courriel interdite n’a été trouvée!';
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
    <input class="button" type="submit" name="enable" value="Activer" >
    &nbsp;&nbsp;
    <input class="button" type="submit" name="disable" value="Désactiver" >
    &nbsp;&nbsp;
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
        Êtes-vous sûr.e de vouloir <b>activer</b> les règles d’interdiction sélectionnées&nbsp;?
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        Êtes-vous sûr.e de vouloir <b>désactiver</b> les règles d’interdiction sélectionnées&nbsp;?
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Êtes-vous sûr.e de vouloir SUPPRIMER les règles d’interdiction sélectionnées&nbsp;?</strong></font>
    </p>
    <div>Veuillez confirmer pour continuer</div>
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

