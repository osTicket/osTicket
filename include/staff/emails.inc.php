<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qstr='';
$sql='SELECT email.*,dept.dept_name as department,priority_desc as priority '.
     ' FROM '.EMAIL_TABLE.' email '.
     ' LEFT JOIN '.DEPT_TABLE.' dept ON (dept.dept_id=email.dept_id) '.
     ' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON (pri.priority_id=email.priority_id) ';
$sql.=' WHERE 1';
$sortOptions=array('email'=>'email.email','dept'=>'department','priority'=>'priority','created'=>'email.created','updated'=>'email.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'email';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'email.email';

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

$total=db_count('SELECT count(*) FROM '.EMAIL_TABLE.' email ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('emails.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql GROUP BY email.email_id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' emails';
else
    $showing='No emails found!';

?>
<div style="width:700;padding-top:5px; float:left;">
 <h2>Email Addresses</h2>
 </div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
    <b><a href="emails.php?a=add" class="Icon newEmail">Add New Email</a></b></div>
<div class="clear"></div>
<form action="emails.php" method="POST" name="emails" onSubmit="return checkbox_checker(this,1,0);">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>        
            <th width="400"><a <?php echo $email_sort; ?> href="emails.php?<?php echo $qstr; ?>&sort=email">Email</a></th>
            <th width="120"><a  <?php echo $priority_sort; ?> href="emails.php?<?php echo $qstr; ?>&sort=priority">Priority</a></th>
            <th width="250"><a  <?php echo $dept_sort; ?> href="emails.php?<?php echo $qstr; ?>&sort=dept">Department</a></th>
            <th width="110" nowrap><a  <?php echo $created_sort; ?>href="emails.php?<?php echo $qstr; ?>&sort=created">Created</a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="emails.php?<?php echo $qstr; ?>&sort=updated">Last Updated</a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            $defaultId=$cfg->getDefaultEmailId();
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['email_id'],$ids)){
                    $class="$class highlight";
                    $sel=true;
                }
                $default=($row['email_id']==$defaultId);
                $email=$row['email'];
                if($row['name'])
                    $email=$row['name'].' <'.$row['email'].'>';
                ?>
            <tr id="<?php echo $row['email_id']; ?>">
                <td width=7px>
                 <?php if($row['email_id']==$defaultId){ ?>
                    &nbsp;
                 <?php }else{ ?>
                  <input type="checkbox" name="ids[]" value="<?php echo $row['email_id']; ?>" 
                            <?php echo $sel?'checked="checked"':''; ?>  <?php echo $default?'disabled="disabled"':''; ?>
                                onClick="highLight(this.value,this.checked);"> 
                  <?php } ?>
                </td>
                <td><a href="emails.php?id=<?php echo $row['email_id']; ?>"><?php echo Format::htmlchars($email); ?></a>&nbsp;</td>
                <td><?php echo $row['priority']; ?></td>
                <td><a href="departments.php?id=<?php echo $row['dept_id']; ?>"><?php echo $row['department']; ?></a></td>
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
            <a href="#" onclick="return select_all(document.forms['emails'],true)">All</a>&nbsp;&nbsp;
            <a href="#" onclick="return reset_all(document.forms['emails'])">None</a>&nbsp;&nbsp;
            <a href="#" onclick="return toogle_all(document.forms['emails'],true)">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No help emails found';
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
    <input class="button" type="submit" name="delete" value="Delete Email(s)"
                onClick=' return confirm("Are you sure you want to DELETE selected emails?");'>
</p>
<?php
endif;
?>
</form>

