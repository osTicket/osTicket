<?php
if(!defined('OSTSTAFFINC') || !$thisstaff || !$thisstaff->isStaff()) die('Access Denied');
$qs = array();

$agents = Staff::objects()
    ->select_related('dept');

if($_REQUEST['q']) {
    $searchTerm=$_REQUEST['q'];
    if($searchTerm){
        if(is_numeric($searchTerm)){
            $agents->filter(Q::any(array(
                'phone__contains'=>$searchTerm,
                'phone_ext__contains'=>$searchTerm,
                'mobile__contains'=>$searchTerm,
            )));
        }elseif(strpos($searchTerm,'@') && Validator::is_email($searchTerm)){
            $agents->filter(array('email'=>$searchTerm));
        }else{
            $agents->filter(Q::any(array(
                'email__contains'=>$searchTerm,
                'lastname__contains'=>$searchTerm,
                'firstname__contains'=>$searchTerm,
            )));
        }
    }
}

if($_REQUEST['did'] && is_numeric($_REQUEST['did'])) {
    $agents->filter(array('dept'=>$_REQUEST['did']));
    $qs += array('did' => $_REQUEST['did']);
}

$sortOptions=array('name'=>array('firstname','lastname'),'email'=>'email','dept'=>'dept__name',
                   'phone'=>'phone','mobile'=>'mobile','ext'=>'phone_ext',
                   'created'=>'created','login'=>'lastlogin');
$orderWays=array('DESC'=>'-','ASC'=>'');

switch ($cfg->getAgentNameFormat()) {
case 'last':
case 'lastfirst':
case 'legal':
    $sortOptions['name'] = array('lastname', 'firstname');
    break;
// Otherwise leave unchanged
}

$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column = $order_column ?: 'firstname,lastname';

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])]) {
    $order=$orderWays[strtoupper($_REQUEST['order'])];
}

$x=$sort.'_sort';
$$x=' class="'.strtolower($_REQUEST['order'] ?: 'desc').'" ';
foreach ((array) $order_column as $C) {
    $agents->order_by($order.$C);
}

$total=$agents->count();
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('directory.php', $qs);
$pageNav->paginate($agents);

//Ok..lets roll...create the actual query
$qstr.='&amp;order='.($order=='DESC' ? 'ASC' : 'DESC');

?>

<div id="basic_search">
    <div style="min-height:25px;">
    <form action="directory.php" method="GET" name="filter">
       <input type="text" name="q" value="<?php echo Format::htmlchars($_REQUEST['q']); ?>" >
        <select name="did" id="did">
             <option value="0">&mdash; <?php echo __('All Departments');?> &mdash;</option>
             <?php
                foreach (Dept::getDepartments(array('nonempty'=>1)) as $id=>$name) {
                    $sel=($_REQUEST['did'] && $_REQUEST['did']==$id)?'selected="selected"':'';
                    echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
                }
             ?>
        </select>
        &nbsp;&nbsp;
        <input type="submit" name="submit" value="<?php echo __('Filter');?>"/>
        &nbsp;<i class="help-tip icon-question-sign" href="#apply_filtering_criteria"></i>
    </form>
 </div>
</div>
<div class="clear"></div>
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="pull-left flush-left">
        <h2><?php echo __('Agents');?>
            &nbsp;<i class="help-tip icon-question-sign" href="#staff_members"></i>
        </h2>
    </div>
</div>
    <?php
    if ($agents->exists(true))
        $showing=$pageNav->showing();
    else
        $showing=__('No agents found!');
    ?>
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="20%"><a <?php echo $name_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name');?></a></th>
            <th width="15%"><a  <?php echo $dept_sort; ?>href="directory.php?<?php echo $qstr; ?>&sort=dept"><?php echo __('Department');?></a></th>
            <th width="25%"><a  <?php echo $email_sort; ?>href="directory.php?<?php echo $qstr; ?>&sort=email"><?php echo __('Email Address');?></a></th>
            <th width="15%"><a <?php echo $phone_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=phone"><?php echo __('Phone Number');?></a></th>
            <th width="10%"><a <?php echo $ext_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=ext"><?php echo __(/* As in a phone number `extension` */ 'Extension');?></a></th>
            <th width="15%"><a <?php echo $mobile_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=mobile"><?php echo __('Mobile Number');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        foreach ($agents as $A) { ?>
           <tr id="<?php echo $A->staff_id; ?>">
                <td>&nbsp;<?php echo Format::htmlchars($A->getName()); ?></td>
                <td>&nbsp;<?php echo Format::htmlchars((string) $A->dept); ?></td>
                <td>&nbsp;<?php echo Format::htmlchars($A->email); ?></td>
                <td>&nbsp;<?php echo Format::phone($A->phone); ?></td>
                <td>&nbsp;<?php echo $A->phone_ext; ?></td>
                <td>&nbsp;<?php echo Format::phone($A->mobile); ?></td>
           </tr>
            <?php
            } // end of foreach
        ?>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if ($agents->exists(true)) {
                echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
                ?>
            <?php } else {
                echo __('No agents found!');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>

