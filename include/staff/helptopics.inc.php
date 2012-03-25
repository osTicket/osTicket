<?php
if(!defined('OSTADMININC') || !$thisstaff->isadmin()) die('Access Denied');

$qstr='';
$sql='SELECT topic.*,dept.dept_name as department,priority_desc as priority '.
     ' FROM '.TOPIC_TABLE.' topic '.
     ' LEFT JOIN '.DEPT_TABLE.' dept ON (dept.dept_id=topic.dept_id) '.
     ' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON (pri.priority_id=topic.priority_id) ';
$sql.=' WHERE 1';
$sortOptions=array('name'=>'topic.topic','status'=>'topic.isactive','type'=>'topic.ispublic',
                   'dept'=>'department','priority'=>'priority','updated'=>'topic.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'topic.topic';

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

$total=db_count('SELECT count(*) FROM '.TOPIC_TABLE.' topic ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('helptopics.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql GROUP BY topic.topic_id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' help topics';
else
    $showing='No help topic found!';

?>
<div style="width:700;padding-top:5px; float:left;">
 <h2>Help Topics</h2>
 </div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
    <b><a href="helptopics.php?a=add" class="Icon newHelpTopic">Add New Help Topic</a></b></div>
<div class="clear"></div>
<form action="helptopics.php" method="POST" name="topics" onSubmit="return checkbox_checker(this,1,0);">
 <input type="hidden" name="do" value="mass_process" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>        
            <th width="320"><a <?php echo $name_sort; ?> href="helptopics.php?<?php echo $qstr; ?>&sort=name">Help Topic</a></th>
            <th width="80"><a  <?php echo $status_sort; ?> href="helptopics.php?<?php echo $qstr; ?>&sort=status">Status</a></th>
            <th width="100"><a  <?php echo $type_sort; ?> href="helptopics.php?<?php echo $qstr; ?>&sort=type">Type</a></th>
            <th width="100"><a  <?php echo $priority_sort; ?> href="helptopics.php?<?php echo $qstr; ?>&sort=priority">Priority</a></th>
            <th width="200"><a  <?php echo $dept_sort; ?> href="helptopics.php?<?php echo $qstr; ?>&sort=dept">Department</a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="helptopics.php?<?php echo $qstr; ?>&sort=updated">Last Updated</a></th>
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
                if($ids && in_array($row['topic_id'],$ids)){
                    $class="$class highlight";
                    $sel=true;
                }
                ?>
            <tr id="<?php echo $row['topic_id']; ?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?php echo $row['topic_id']; ?>" 
                            <?php echo $sel?'checked="checked"':''; ?>  <?php echo $default?'disabled="disabled"':''; ?>
                                onClick="highLight(this.value,this.checked);"> </td>
                <td><a href="helptopics.php?id=<?php echo $row['topic_id']; ?>"><?php echo $row['topic']; ?></a>&nbsp;</td>
                <td><?php echo $row['isactive']?'Active':'<b>Disabled</b>'; ?></td>
                <td><?php echo $row['ispublic']?'Public':'<b>Private</b>'; ?></td>
                <td><?php echo $row['priority']; ?></td>
                <td><a href="departments.php?id=<?php echo $row['dept_id']; ?>"><?php echo $row['department']; ?></a></td>
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
            <a href="#" onclick="return select_all(document.forms['topics'],true)">All</a>&nbsp;&nbsp;
            <a href="#" onclick="return reset_all(document.forms['topics'])">None</a>&nbsp;&nbsp;
            <a href="#" onclick="return toogle_all(document.forms['topics'],true)">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No help topics found';
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
                onClick=' return confirm("Are you sure you want to ENABLE selected help topics?");'>
    <input class="button" type="submit" name="disable" value="Disable"
                onClick=' return confirm("Are you sure you want to DISABLE selected help topics?");'>
    <input class="button" type="submit" name="delete" value="Delete"
                onClick=' return confirm("Are you sure you want to DELETE selected help topics?");'>
</p>
<?php
endif;
?>

</form>

