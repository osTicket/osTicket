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

<div class="subnav">


                        <div class="float-left subnavtitle">
                        
                            <span ><a href="<?php echo $refresh_url; ?>"
                                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> 
                                </a> &nbsp;
            <?php echo __('Agent Directory');?>
                                
                                </span>
                        
                       
                       
                        </div>
                         <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
                         &nbsp;
                         </div>
                        
                        <div class="clearfix"></div>
                        
                  
 </div>

<div class="card-box">

<div class="row">
    <div class="col">
        <div class="float-right">
<form  class="form-inline" action="directory.php" method="get"  name="filter"  style="padding-bottom: 10px; margin-top: -5px;">
            <?php csrf_token(); ?>
            
             <div class="input-group input-group-sm">
             <input type="hidden" name="a" value="search">
                <input type="text" name="q" value="<?php echo Format::htmlchars($_REQUEST['q']); ?>" class="form-control form-control-sm"  placeholder="Search Agents">
            <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
                
                
            
       <button type="submit" class="input-group-addon"  ><i class="fa fa-search"></i>
                </button>
                
                    <select name="did" id="did" class="form-control form-control-sm" style="height: 34px;">
             <option value="0">&mdash; <?php echo __('All Departments');?> &mdash;</option>
             <?php
                foreach (Dept::getDepartments(array('nonempty'=>1)) as $id=>$name) {
                    $sel=($_REQUEST['did'] && $_REQUEST['did']==$id)?'selected="selected"':'';
                    echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
                }
             ?>
             <input type="submit" name="submit" value="&#xf0b0;" class="input-group-addon fa" style="padding-top: 7px"/>
        
            </div>
            &nbsp;<i class="help-tip icon-question-sign" href="#apply_filtering_criteria"></i>
        </form>
        </div>
    </div>
</div>
<div class="row">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
    <div class="clear"></div>
<div>                        
                        


    <?php
    if ($agents->exists(true))
        $showing=$pageNav->showing();
    else
        $showing=__('No agents found!');
    ?>
 <table  id="agents" class="table table-striped table-hover table-condensed table-sm">
    <thead>
        <tr>
            <th><a <?php echo $name_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name');?></a></th>
            <th data-breakpoints="xs sm"><a  <?php echo $dept_sort; ?>href="directory.php?<?php echo $qstr; ?>&sort=dept"><?php echo __('Department');?></a></th>
            <th><a  <?php echo $email_sort; ?>href="directory.php?<?php echo $qstr; ?>&sort=email"><?php echo __('Email Address');?></a></th>
            <th data-breakpoints="xs sm"><a <?php echo $phone_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=phone"><?php echo __('Phone Number');?></a></th>
            <th data-breakpoints="xs sm"><a <?php echo $ext_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=ext"><?php echo __(/* As in a phone number `extension` */ 'Extension');?></a></th>
            <th data-breakpoints="xs sm"><a <?php echo $mobile_sort; ?> href="directory.php?<?php echo $qstr; ?>&sort=mobile"><?php echo __('Mobile Number');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        foreach ($agents as $A) { ?>
           <tr id="<?php echo $A->staff_id; ?>">
                <td>&nbsp;<span class="notranslate"><?php echo Format::htmlchars($A->getName()); ?></span></td>
                <td>&nbsp;<?php echo Format::htmlchars((string) $A->dept); ?></td>
                <td>&nbsp;<?php echo Format::htmlchars($A->email); ?></td>
                <td>&nbsp;<?php echo Format::phone($A->phone); ?></td>
                <td>&nbsp;<?php echo $A->phone_ext; ?></td>
                <td>&nbsp;<?php echo Format::phone($A->mobile); ?></td>
           </tr>
            <?php
            } // end of foreach
        ?>
   
</table>
<div class="row">
    <div class="col">
        <div class="float-left">
        <nav>
        <ul class="pagination">   
            <?php
                echo $pageNav->getPageLinks();
            ?>
        </ul>
        </nav>
        </div>

            
           
            <div class="float-right">
                  <span class="faded"><?php echo $pageNav->showing(); ?></span>
            </div>  
    </div>
</div>
</div>
</div>

<script type="text/javascript">

jQuery(function($){
	$('#agents').footable();
});

</script>
