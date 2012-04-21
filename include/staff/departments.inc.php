<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qstr='';
$sql='SELECT dept.dept_id,dept_name,email.email_id,email.email,email.name as email_name,ispublic,count(staff.staff_id) as users '.
     ',CONCAT_WS(" ",mgr.firstname,mgr.lastname) as manager,mgr.staff_id as manager_id,dept.created,dept.updated  FROM '.DEPT_TABLE.' dept '.
     ' LEFT JOIN '.STAFF_TABLE.' mgr ON dept.manager_id=mgr.staff_id '.
     ' LEFT JOIN '.EMAIL_TABLE.' email ON dept.email_id=email.email_id '.
     ' LEFT JOIN '.STAFF_TABLE.' staff ON dept.dept_id=staff.dept_id ';

$sql.=' WHERE 1';
$sortOptions=array('name'=>'dept.dept_name','type'=>'ispublic','users'=>'users','email'=>'email_name, email.email','manager'=>'manager');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'dept.dept_name';

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

$query="$sql GROUP BY dept.dept_id ORDER BY $order_by";
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing="Showing 1-$num of $num departments";
else
    $showing='No departments found!';

?>
<div style="width:700;padding-top:5px; float:left;">
 <h2>Departments</h2>
 </div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
    <b><a href="departments.php?a=add" class="Icon newDepartment">Add New Department</a></b></div>
<div class="clear"></div>
<form action="departments.php" method="POST" name="depts" onSubmit="return checkbox_checker(this,1,0);">
 <input type="hidden" name="do" value="mass_process" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7px">&nbsp;</th>        
            <th width="180"><a <?php echo $name_sort; ?> href="departments.php?<?php echo $qstr; ?>&sort=name">Name</a></th>
            <th width="80"><a  <?php echo $type_sort; ?> href="departments.php?<?php echo $qstr; ?>&sort=type">Type</a></th>
            <th width="70"><a  <?php echo $users_sort; ?>href="departments.php?<?php echo $qstr; ?>&sort=users">Users</a></th>
            <th width="300"><a  <?php echo $email_sort; ?> href="departments.php?<?php echo $qstr; ?>&sort=email">Email Address</a></th>
            <th width="200"><a  <?php echo $manager_sort; ?> href="departments.php?<?php echo $qstr; ?>&sort=manager">Dept. Manager</a></th>
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
                if($ids && in_array($row['dept_id'],$ids)){
                    $class="$class highlight";
                    $sel=true;
                }
                $row['email']=$row['email_name']?($row['email_name'].' &lt;'.$row['email'].'&gt;'):$row['email'];
                $default=($defaultId==$row['dept_id'])?' <small>(Default)</small>':'';
                ?>
            <tr id="<?php echo $row['dept_id']; ?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?php echo $row['dept_id']; ?>" 
                            <?php echo $sel?'checked="checked"':''; ?>  <?php echo $default?'disabled="disabled"':''; ?>
                                onClick="highLight(this.value,this.checked);"> </td>
                <td><a href="departments.php?id=<?php echo $row['dept_id']; ?>"><?php echo $row['dept_name']; ?></a>&nbsp;<?php echo $default; ?></td>
                <td><?php echo $row['ispublic']?'Public':'<b>Private</b>'; ?></td>
                <td>&nbsp;&nbsp;
                    <b>
                    <?php if($row['users']>0) { ?>
                        <a href="staff.php?did=<?php echo $row['dept_id']; ?>"><?php echo $row['users']; ?></a>
                    <?php }else{ ?> 0
                    <?php } ?>
                    </b>
                </td>
                <td><a href="emails.php?id=<?php echo $row['email_id']; ?>"><?php echo $row['email']; ?></a></td>
                <td><a href="staff.php?id=<?php echo $row['manager_id']; ?>"><?php echo $row['manager']; ?>&nbsp;</a></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if($res && $num){ ?>
            Select:&nbsp;
            <a href="#" onclick="return select_all(document.forms['depts'],true)">All</a>&nbsp;&nbsp;
            <a href="#" onclick="return reset_all(document.forms['depts'])">None</a>&nbsp;&nbsp;
            <a href="#" onclick="return toogle_all(document.forms['depts'],true)">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No department found';
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if($res && $num): //Show options..
?>
<p class="centered">
    <input class="button" type="submit" name="public" value="Make Public"
                onClick=' return confirm("Are you sure you want to make selected departments public?");'>
    <input class="button" type="submit" name="private" value="Make Private"
                onClick=' return confirm("Are you sure you want to make selected departments private?");'>
    <input class="button" type="submit" name="delete" value="Delete Dept(s)"
                onClick=' return confirm("Are you sure you want to DELETE selected departments?");'>
</p>
<?php
endif;
?>

</form>

