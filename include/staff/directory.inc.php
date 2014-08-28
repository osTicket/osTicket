<?php
if(!defined('OSTSTAFFINC') || !$thisstaff || !$thisstaff->isStaff()) die('Access Denied');
$qstr='';
$select='SELECT staff.*,CONCAT_WS(" ",firstname,lastname) as name,dept.dept_name as dept ';
$from='FROM '.STAFF_TABLE.' staff '.
      'LEFT JOIN '.DEPT_TABLE.' dept ON(staff.dept_id=dept.dept_id) ';
$where='WHERE staff.isvisible=1 ';

if($_REQUEST['q']) {
    $searchTerm=$_REQUEST['q'];
    if($searchTerm){
        $query=db_real_escape($searchTerm,false); //escape the term ONLY...no quotes.
        if(is_numeric($searchTerm)){
            $where.=" AND (staff.phone LIKE '%$query%' OR staff.phone_ext LIKE '%$query%' OR staff.mobile LIKE '%$query%') ";
        }elseif(strpos($searchTerm,'@') && Validator::is_email($searchTerm)){
            $where.=" AND staff.email='$query'";
        }else{
            $where.=" AND ( staff.email LIKE '%$query%'".
                         " OR staff.lastname LIKE '%$query%'".
                         " OR staff.firstname LIKE '%$query%'".
                        ' ) ';
        }
    }
}

if($_REQUEST['did'] && is_numeric($_REQUEST['did'])) {
    $where.=' AND staff.dept_id='.db_input($_REQUEST['did']);
    $qstr.='&did='.urlencode($_REQUEST['did']);
}

$sortOptions=array('name'=>'staff.firstname,staff.lastname','email'=>'staff.email','dept'=>'dept.dept_name',
                   'phone'=>'staff.phone','mobile'=>'staff.mobile','ext'=>'phone_ext',
                   'created'=>'staff.created','login'=>'staff.lastlogin');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'staff.firstname,staff.lastname';

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

$total=db_count('SELECT count(DISTINCT staff.staff_id) '.$from.' '.$where);
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('directory.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$select $from $where GROUP BY staff.staff_id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
?>
<h2><?php echo __('Agents');?>
&nbsp;<i class="help-tip icon-question-sign" href="#staff_members"></i></h2>
<div class="pull-left" style="width:700px">
    <form action="directory.php" method="GET" name="filter">
       <input type="text" name="q" value="<?php echo Format::htmlchars($_REQUEST['q']); ?>" >
        <select name="did" id="did">
             <option value="0">&mdash; <?php echo __('All Departments');?> &mdash;</option>
             <?php
             $sql='SELECT dept.dept_id, dept.dept_name,count(staff.staff_id) as users  '.
                  'FROM '.DEPT_TABLE.' dept '.
                  'INNER JOIN '.STAFF_TABLE.' staff ON(staff.dept_id=dept.dept_id AND staff.isvisible=1) '.
                  'GROUP By dept.dept_id HAVING users>0 ORDER BY dept_name';
             if(($res=db_query($sql)) && db_num_rows($res)){
                 while(list($id,$name, $users)=db_fetch_row($res)){
                     $sel=($_REQUEST['did'] && $_REQUEST['did']==$id)?'selected="selected"':'';
                     echo sprintf('<option value="%d" %s>%s (%s)</option>',$id,$sel,$name,$users);
                 }
             }
             ?>
        </select>
        &nbsp;&nbsp;
        <input type="submit" name="submit" value="<?php echo __('Filter');?>"/>
        &nbsp;<i class="help-tip icon-question-sign" href="#apply_filtering_criteria"></i>
    </form>
 </div>
<div class="clear"></div>
<?php
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing();
else
    $showing=__('No agents found!');
?>
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="160"><a <?php echo $name_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name');?></a></th>
            <th width="150"><a  <?php echo $dept_sort; ?>href="directory.php?<?php echo $qstr; ?>&sort=dept"><?php echo __('Department');?></a></th>
            <th width="180"><a  <?php echo $email_sort; ?>href="directory.php?<?php echo $qstr; ?>&sort=email"><?php echo __('Email Address');?></a></th>
            <th width="120"><a <?php echo $phone_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=phone"><?php echo __('Phone Number');?></a></th>
            <th width="80"><a <?php echo $ext_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=ext"><?php echo __(/* As in a phone number `extension` */ 'Extension');?></a></th>
            <th width="120"><a <?php echo $mobile_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=mobile"><?php echo __('Mobile Number');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        if($res && db_num_rows($res)):
            $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
            while ($row = db_fetch_array($res)) { ?>
               <tr id="<?php echo $row['staff_id']; ?>">
                <td>&nbsp;<?php echo Format::htmlchars($row['name']); ?></td>
                <td>&nbsp;<?php echo Format::htmlchars($row['dept']); ?></td>
                <td>&nbsp;<?php echo Format::htmlchars($row['email']); ?></td>
                <td>&nbsp;<?php echo Format::phone($row['phone']); ?></td>
                <td>&nbsp;<?php echo $row['phone_ext']; ?></td>
                <td>&nbsp;<?php echo Format::phone($row['mobile']); ?></td>
               </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if($res && $num) {
                echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
                ?>
            <?php } else {
                echo __('No agents found!');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>

