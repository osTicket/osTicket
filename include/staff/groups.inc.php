<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$qstr='';

$sql='SELECT grp.*,count(DISTINCT staff.staff_id) as users, count(DISTINCT dept.dept_id) as depts '
     .' FROM '.GROUP_TABLE.' grp '
     .' LEFT JOIN '.STAFF_TABLE.' staff ON(staff.group_id=grp.group_id) '
     .' LEFT JOIN '.GROUP_DEPT_TABLE.' dept ON(dept.group_id=grp.group_id) '
     .' WHERE 1';
$sortOptions=array('name'=>'grp.group_name','status'=>'grp.group_enabled', 
                   'users'=>'users', 'depts'=>'depts', 'created'=>'grp.created','updated'=>'grp.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'grp.group_name';

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

$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql GROUP BY grp.group_id ORDER BY $order_by";
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing="Showing 1-$num of $num groups";
else
    $showing='No groups found!';

?>
<div style="width:700px;padding-top:5px; float:left;">
 <h2>User Groups</h2>
 </div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
    <b><a href="groups.php?a=add" class="Icon newgroup">Add New Group</a></b></div>
<div class="clear"></div>
<form action="groups.php" method="POST" name="groups">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7px">&nbsp;</th>        
            <th width="200"><a <?php echo $name_sort; ?> href="groups.php?<?php echo $qstr; ?>&sort=name">Group Name</a></th>
            <th width="80"><a  <?php echo $status_sort; ?> href="groups.php?<?php echo $qstr; ?>&sort=status">Status</a></th>
            <th width="80" style="text-align:center;"><a  <?php echo $users_sort; ?>href="groups.php?<?php echo $qstr; ?>&sort=users">Members</a></th>
            <th width="80" style="text-align:center;"><a  <?php echo $depts_sort; ?>href="groups.php?<?php echo $qstr; ?>&sort=depts">Departments</a></th>
            <th width="100"><a  <?php echo $created_sort; ?> href="groups.php?<?php echo $qstr; ?>&sort=created">Created On</a></th>
            <th width="120"><a  <?php echo $updated_sort; ?> href="groups.php?<?php echo $qstr; ?>&sort=updated">Last Updated</a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['group_id'],$ids))
                    $sel=true;
                ?>
            <tr id="<?php echo $row['group_id']; ?>">
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['group_id']; ?>" 
                            <?php echo $sel?'checked="checked"':''; ?>> </td>
                <td><a href="groups.php?id=<?php echo $row['group_id']; ?>"><?php echo $row['group_name']; ?></a> &nbsp;</td>
                <td>&nbsp;<?php echo $row['group_enabled']?'Active':'<b>Disabled</b>'; ?></td>
                <td style="text-align:right;padding-right:30px">&nbsp;&nbsp;
                    <?php if($row['users']>0) { ?>
                        <a href="staff.php?gid=<?php echo $row['group_id']; ?>"><?php echo $row['users']; ?></a>
                    <?php }else{ ?> 0
                    <?php } ?>
                    &nbsp;
                </td>
                <td style="text-align:right;padding-right:30px">&nbsp;&nbsp;
                    <?php echo $row['depts']; ?>
                </td>
                <td><?php echo Format::db_date($row['created']); ?>&nbsp;</td>
                <td><?php echo Format::db_datetime($row['updated']); ?>&nbsp;</td>
            </tr>
            <?php
            } //end of while.
        } ?>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if($res && $num){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">All</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">None</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No groups found!';
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if($res && $num): //Show options..
?>
<p class="centered" id="actions">
    <input class="button" type="submit" name="enable" value="Enable" >
    <input class="button" type="submit" name="disable" value="Disable" >
    <input class="button" type="submit" name="delete" value="Delete">
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
        Are you sure want to <b>enable</b> selected groups?
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        Are you sure want to <b>disable</b> selected groups?
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Are you sure you want to DELETE selected groups?</strong></font>
        <br><br>Deleted groups CANNOT be recovered and might affect staff's access.
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

