<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qstr='';
$sql='SELECT tpl.*,count(dept.tpl_id) as depts '.
     'FROM '.EMAIL_TEMPLATE_TABLE.' tpl '.
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

$total=db_count('SELECT count(*) FROM '.EMAIL_TEMPLATE_TABLE.' tpl ');
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

<div style="width:700;padding-top:5px; float:left;">
 <h2>Email Templates</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
 <b><a href="templates.php?a=add" class="Icon newEmailTemplate">Add New Template</a></b></div>
<div class="clear"></div>
<form action="templates.php" method="POST" name="tpls" onSubmit="return checkbox_checker(this,1,0);">
 <input type="hidden" name="do" value="mass_process" >
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
                if($ids && in_array($row['tpl_id'],$ids)){
                    $class="$class highlight";
                    $sel=true;
                }
                $default=($defaultTplId==$row['tpl_id'])?'<small class="fadded">(System Default)</small>':'';
                ?>
            <tr id="<?php echo $row['tpl_id']; ?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?php echo $row['tpl_id']; ?>" 
                            <?php echo $sel?'checked="checked"':''; ?> onClick="highLight(this.value,this.checked);"> </td>
                <td>&nbsp;<a href="templates.php?id=<?php echo $row['tpl_id']; ?>"><?php echo Format::htmlchars($row['name']); ?></a>
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
            <a href="#" onclick="return select_all(document.forms['tpls'],true)">All</a>&nbsp;&nbsp;
            <a href="#" onclick="return reset_all(document.forms['tpls'])">None</a>&nbsp;&nbsp;
            <a href="#" onclick="return toogle_all(document.forms['tpls'],true)">Toggle</a>&nbsp;&nbsp;
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
<p class="centered">
    <input class="button" type="submit" name="enable" value="Enable"
                onClick=' return confirm("Are you sure you want to ENABLE selected templates?");'>
    <input class="button" type="submit" name="disable" value="Disable"
                onClick=' return confirm("Are you sure you want to DISABLE selected templates?");'>
    <input class="button" type="submit" name="delete" value="Delete"
                onClick=' return confirm("Are you sure you want to DELETE selected templates?");'>
</p>
<?php
endif;
?>
</form>

