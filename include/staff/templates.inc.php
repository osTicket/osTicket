<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qstr='';
$sql='SELECT tpl.*,count(dept.tpl_id) as depts '.
     'FROM '.EMAIL_TEMPLATE_GRP_TABLE.' tpl '.
     'LEFT JOIN '.DEPT_TABLE.' dept USING(tpl_id) '.
     'WHERE 1 ';
$sortOptions=array('name'=>'tpl.name','status'=>'tpl.isactive','created'=>'tpl.created','updated'=>'tpl.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'tpl.name';

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

$total=db_count('SELECT count(*) FROM '.EMAIL_TEMPLATE_GRP_TABLE.' tpl ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('templates.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql GROUP BY tpl.tpl_id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' Templates';
else
    $showing='No templates found!';

?>

<div style="width:700px;padding-top:5px; float:left;">
 <h2>Email Template Sets</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
 <b><a href="templates.php?a=add" class="Icon newEmailTemplate">Add New Template Set</a></b></div>
<div class="clear"></div>
<form action="templates.php" method="POST" name="tpls">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th width="350"><a <?php echo $name_sort; ?> href="templates.php?<?php echo $qstr; ?>&sort=name">Name</a></th>
            <th width="100"><a  <?php echo $status_sort; ?> href="templates.php?<?php echo $qstr; ?>&sort=status">Status</a></th>
            <th width="80"><a <?php echo $inuse_sort; ?> href="templates.php?<?php echo $qstr; ?>&sort=inuse">In-Use</a></th>
            <th width="120" nowrap><a  <?php echo $created_sort; ?>href="templates.php?<?php echo $qstr; ?>&sort=created">Date Added</a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="templates.php?<?php echo $qstr; ?>&sort=updated">Last Updated</a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            $defaultTplId=$cfg->getDefaultTemplateId();
            while ($row = db_fetch_array($res)) {
                $inuse=($row['depts'] || $row['tpl_id']==$defaultTplId);
                $sel=false;
                if($ids && in_array($row['tpl_id'],$ids))
                    $sel=true;

                $default=($defaultTplId==$row['tpl_id'])?'<small class="fadded">(System Default)</small>':'';
                ?>
            <tr id="<?php echo $row['tpl_id']; ?>">
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['tpl_id']; ?>"
                            <?php echo $sel?'checked="checked"':''; ?> <?php echo $default?'disabled="disabled"':''; ?> >
                </td>
                <td>&nbsp;<a href="templates.php?tpl_id=<?php echo $row['tpl_id']; ?>"><?php echo Format::htmlchars($row['name']); ?></a>
                &nbsp;<?php echo $default; ?></td>
                <td>&nbsp;<?php echo $row['isactive']?'Active':'<b>Disabled</b>'; ?></td>
                <td>&nbsp;&nbsp;<?php echo ($inuse)?'<b>Yes</b>':'No'; ?></td>
                <td>&nbsp;<?php echo Format::db_date($row['created']); ?></td>
                <td>&nbsp;<?php echo Format::db_datetime($row['updated']); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if($res && $num){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">All</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">None</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No templates found';
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
    <input class="button" type="submit" name="enable" value="Enable" >
    <input class="button" type="submit" name="disable" value="Disable" >
    <input class="button" type="submit" name="delete" value="Delete" >
</p>
<?php
endif;
?>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Please Confirm</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        Are you sure want to <b>enable</b> selected templates?
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        Are you sure want to <b>disable</b>  selected templates?
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Are you sure you want to DELETE selected templates?</strong></font>
        <br><br>Deleted templates CANNOT be recovered.
    </p>
    <div>Please confirm to continue.</div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="button" value="No, Cancel" class="close">
        </span>
        <span class="buttons" style="float:right">
            <input type="button" value="Yes, Do it!" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
