<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qstr='';
$sql='SELECT canned.*, count(attach.file_id) as files, dept.dept_name as department '.
     ' FROM '.CANNED_TABLE.' canned '.
     ' LEFT JOIN '.DEPT_TABLE.' dept ON (dept.dept_id=canned.dept_id) '.
     ' LEFT JOIN '.CANNED_ATTACHMENT_TABLE.' attach ON (attach.canned_id=canned.canned_id) ';
$sql.=' WHERE 1';

$sortOptions=array('title'=>'canned.title','status'=>'canned.isenabled','dept'=>'department','updated'=>'canned.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'title';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}

$order_column=$order_column?$order_column:'canned.title';

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

$total=db_count('SELECT count(*) FROM '.CANNED_TABLE.' canned ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('canned.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql GROUP BY canned.canned_id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' premade responses';
else
    $showing='No premade responses found!';

?>
<div style="width:700;padding-top:5px; float:left;">
 <h2>Canned Replies</h2>
 </div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
    <b><a href="canned.php?a=add" class="Icon newReply">Add New Reply</a></b></div>
<div class="clear"></div>
<form action="canned.php" method="POST" name="canned" onSubmit="return checkbox_checker(this,1,0);">
 <input type="hidden" name="do" value="mass_process" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th width="500"><a <?php echo $title_sort; ?> href="canned.php?<?php echo $qstr; ?>&sort=title">Title</a></th>
            <th width="80"><a  <?php echo $status_sort; ?> href="canned.php?<?php echo $qstr; ?>&sort=status">Status</a></th>
            <th width="200"><a  <?php echo $dept_sort; ?> href="canned.php?<?php echo $qstr; ?>&sort=dept">Department</a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="canned.php?<?php echo $qstr; ?>&sort=updated">Last Updated</a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            $defaultId=$cfg->getDefaultDeptId();
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['canned_id'],$ids)){
                    $class="$class highlight";
                    $sel=true;
                }
                $files=$row['files']?'<span class="Icon file">&nbsp;</span>':'';
                ?>
            <tr id="<?php echo $row['canned_id']; ?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?php echo $row['canned_id']; ?>"
                            <?php echo $sel?'checked="checked"':''; ?>  <?php echo $default?'disabled="disabled"':''; ?>
                                onClick="highLight(this.value,this.checked);"/>
                </td>
                <td>
                    <a href="canned.php?id=<?php echo $row['canned_id']; ?>"><?php echo Format::truncate($row['title'],200); echo "&nbsp;$files"; ?></a>&nbsp;
                </td>
                <td><?php echo $row['isenabled']?'Active':'<b>Disabled</b>'; ?></td>
                <td><?php echo $row['department']?$row['department']:'-- All Departments --'; ?></td>
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
            <a href="#" onclick="return select_all(document.forms['canned'],true)">All</a>&nbsp;&nbsp;
            <a href="#" onclick="return reset_all(document.forms['canned'])">None</a>&nbsp;&nbsp;
            <a href="#" onclick="return toogle_all(document.forms['canned'],true)">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No premade replies';
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
                onClick=' return confirm("Are you sure you want to ENABLE selected replies?");'>
    <input class="button" type="submit" name="disable" value="Disable"
                onClick=' return confirm("Are you sure you want to DISABLE selected replies?");'>
    <input class="button" type="submit" name="delete" value="Delete"
                onClick=' return confirm("Are you sure you want to DELETE selected replies?");'>
</p>
<?php
endif;
?>
</form>
