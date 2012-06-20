<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qstr='';
$sql='SELECT filter.*,count(rule.id) as rules '.
     'FROM '.EMAIL_FILTER_TABLE.' filter '.
     'LEFT JOIN '.EMAIL_FILTER_RULE_TABLE.' rule ON(rule.filter_id=filter.id) '.
     'GROUP BY filter.id';
$sortOptions=array('name'=>'filter.name','status'=>'filter.isactive','order'=>'filter.execorder','rules'=>'rules',
                   'created'=>'filter.created','updated'=>'filter.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'filter.name';

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

$total=db_count('SELECT count(*) FROM '.EMAIL_FILTER_TABLE.' filter ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('filters.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' filters';
else
    $showing='No filters found!';

?>

<div style="width:700;padding-top:5px; float:left;">
 <h2>Email Filters</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
 <b><a href="filters.php?a=add" class="Icon newEmailFilter">Add New Filter</a></b></div>
<div class="clear"></div>
<form action="filters.php" method="POST" name="filters" onSubmit="return checkbox_checker(this,1,0);">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>        
            <th width="320"><a <?php echo $name_sort; ?> href="filters.php?<?php echo $qstr; ?>&sort=name">Name</a></th>
            <th width="100"><a  <?php echo $status_sort; ?> href="filters.php?<?php echo $qstr; ?>&sort=status">Status</a></th>
            <th width="80" style="text-align:center;"><a  <?php echo $order_sort; ?> href="filters.php?<?php echo $qstr; ?>&sort=order">Order</a></th>
            <th width="80" style="text-align:center;"><a  <?php echo $rules_sort; ?> href="filters.php?<?php echo $qstr; ?>&sort=rules">Rules</a></th>
            <th width="120" nowrap><a  <?php echo $created_sort; ?>href="filters.php?<?php echo $qstr; ?>&sort=created">Date Added</a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="filters.php?<?php echo $qstr; ?>&sort=updated">Last Updated</a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['id'],$ids)){
                    $class="$class highlight";
                    $sel=true;
                }
                ?>
            <tr id="<?php echo $row['id']; ?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>" 
                            <?php echo $sel?'checked="checked"':''; ?> onClick="highLight(this.value,this.checked);"> </td>
                <td>&nbsp;<a href="filters.php?id=<?php echo $row['id']; ?>"><?php echo Format::htmlchars($row['name']); ?></a></td>
                <td><?php echo $row['isactive']?'Active':'<b>Disabled</b>'; ?></td>
                <td style="text-align:right;padding-right:25px;"><?php echo $row['execorder']; ?>&nbsp;</td>
                <td style="text-align:right;padding-right:25px;"><?php echo $row['rules']; ?>&nbsp;</td>
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
            <a href="#" onclick="return select_all(document.forms['filters'],true)">All</a>&nbsp;&nbsp;
            <a href="#" onclick="return reset_all(document.forms['filters'])">None</a>&nbsp;&nbsp;
            <a href="#" onclick="return toogle_all(document.forms['filters'],true)">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No filters found';
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
                onClick=' return confirm("Are you sure you want to ENABLE selected filters?");'>
    <input class="button" type="submit" name="disable" value="Disable"
                onClick=' return confirm("Are you sure you want to DISABLE selected filters?");'>
    <input class="button" type="submit" name="delete" value="Delete"
                onClick=' return confirm("Are you sure you want to DELETE selected filters?");'>
</p>
<?php
endif;
?>
</form>

