<?php
if (!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin())
    die('Access Denied');

$qstr='';
$qs = array();
$sortOptions = array(
        'name' => array('firstname', 'lastname'),
        'username' => 'username',
        'status' => 'isactive',
        'dept' => 'dept__name',
        'created' => 'created',
        'login' => 'lastlogin'
        );

$orderWays = array('DESC'=>'DESC', 'ASC'=>'ASC');
$sort = ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';

switch ($cfg->getAgentNameFormat()) {
case 'last':
case 'lastfirst':
case 'legal':
    $sortOptions['name'] = array('lastname', 'firstname');
    break;
// Otherwise leave unchanged
}

if ($sort && $sortOptions[$sort]) {
    $order_column = $sortOptions[$sort];
}

$order_column = $order_column ?: array('firstname', 'lastname');

if ($_REQUEST['order'] && isset($orderWays[strtoupper($_REQUEST['order'])])) {
    $order = $orderWays[strtoupper($_REQUEST['order'])];
} else {
    $order = 'ASC';
}

$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';

//Filters
$filters = array();
if ($_REQUEST['did'] && is_numeric($_REQUEST['did'])) {
    $filters += array('dept_id' => $_REQUEST['did']);
    $qs += array('did' => $_REQUEST['did']);
}

if ($_REQUEST['usr']) {
    $filters += array('name__contains' => $_REQUEST['usr']);
    $qs += array('name' => $_REQUEST['usr']);
}
//agents objects
$agents = Staff::objects()
    // ->annotate(array(
            // 'teams_count'=>SqlAggregate::COUNT('teams', true),
    // ))
    ->select_related('dept', 'group');

$order = strcasecmp($order, 'DESC') ? '' : '-';
foreach ((array) $order_column as $C) {
    $agents->order_by($order.$C);
}

if ($filters)
    $agents->filter($filters);

// paginate
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = $agents->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('staff.php', $qs);
$showing = $pageNav->showing().' '._N('agent', 'agents', $count);
$qstr = '&amp;'. Http::build_query($qs);
$qstr .= '&amp;order='.($order=='-' ? 'ASC' : 'DESC');

// add limits.
$agents->limit($pageNav->getLimit())->offset($pageNav->getStart());
?>


<div class="subnav">


                        <div class="float-left subnavtitle">
                        
                            <span ><a href="<?php echo $refresh_url; ?>"
                                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> 
                                </a> &nbsp;
            <?php echo __('Agents');?>
                                
                                </span>
                        
                       
                       
                        </div>
 
        <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
                    
                    <a class="btn btn-icon waves-effect waves-light btn-success"
                       href="staff.php?a=add" data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Add Agent'); ?>">
                        <i class="fa fa-plus-square"></i>
                    </a>
            
        <div class="btn-group btn-group-sm" role="group">
            <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
            data-toggle="dropdown"><i class="fa fa-cog" data-placement="bottom" data-toggle="tooltip" 
             title="More"></i>
            </button>
                    <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1" id="actions">
                    
                   <a class="confirm" data-form-id="mass-actions" data-name="enable" href="staff.php?a=enable">
                                <i class="icon-ok-sign icon-fixed-width"></i>
                                <?php echo __( 'Enable'); ?>
                            </a>
                       
                            <a class="confirm" data-form-id="mass-actions" data-name="disable" href="staff.php?a=disable">
                                <i class="icon-ban-circle icon-fixed-width"></i>
                                <?php echo __( 'Disable'); ?>
                            </a>
                       
                            <a class="dialog-first" data-action="permissions" href="#staff/reset-permissions">
                                <i class="icon-sitemap icon-fixed-width"></i>
                                <?php echo __( 'Reset Permissions'); ?>
                            </a>
                      
                            <a class="dialog-first" data-action="department" href="#staff/change-department">
                                <i class="icon-truck icon-fixed-width"></i>
                                <?php echo __( 'Change Team'); ?>
                            </a>
                      
                             
                                    
                    </div>
            </div>
        </div>   
        
        <div class="clearfix"></div>                      
 </div>
<div class="card-box">

<div class="row">
    <div class="col">
        <div class="float-right">
<form  class="form-inline" action="staff.php" method="get"  name="filter"  style="padding-bottom: 10px; margin-top: -5px;">
            <?php csrf_token(); ?>
            
             <div class="input-group input-group-sm">
             <input type="hidden" name="a" value="search">
                <input type="text"  id="usr" name="usr" value="<?php echo Format::htmlchars($_REQUEST['query']); ?>" class="form-control form-control-sm"  placeholder="Search Associates">
            <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
                
                
            
       <button type="submit" class="input-group-addon"  ><i class="fa fa-search"></i>
                </button>
                
                    <select name="did" id="did" class="form-control form-control-sm" style="height: 34px;">
             <option value="0">&mdash; <?php echo __( 'All Teams');?> &mdash;</option>
                    <?php if (($depts=Dept::getDepartments())) { foreach ($depts as $id=> $name) if (strlen($name) > 5 ){  $sel=($_REQUEST['did'] && $_REQUEST['did']==$id)?'selected="selected"':''; echo sprintf('
                    <option value="%d" %s>%s</option>',$id,$sel,$name); } } ?>
             <input type="submit" name="submit" value="&#xf0b0;" class="input-group-addon fa" style="padding-top: 7px"/>
        
            </div>
            &nbsp;<i class="help-tip icon-question-sign" href="#apply_filtering_criteria"></i>
        </form>
        </div>
    </div>
</div>

<div class="clear"></div>

<form id="mass-actions" action="staff.php" method="POST" name="staff" >

 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="table table-striped table-hover table-condensed table-sm">
    <thead>
        <tr>
            <th width="1%">&nbsp;</th>
            <th width="5%"><a <?php echo $name_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name');?></a></th>
            <th width="5%"><a <?php echo $username_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=username"><?php echo __('Username');?></a></th>
            <th width="8%"><a  <?php echo $status_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Status');?></a></th>
            <th width="14%"><a  <?php echo $dept_sort; ?>href="staff.php?<?php echo $qstr; ?>&sort=dept"><?php echo __('Primary Team');?></a></th>
            <th width="14%"><a <?php echo $created_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Created');?></a></th>
            <th width="16%"><a <?php echo $login_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=login"><?php echo __('Last Login');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        if ($count):
            $ids = ($errors && is_array($_POST['ids'])) ? $_POST['ids'] : null;
            foreach ($agents as $agent) {
                $id = $agent->getId();
                $sel=false;
                if ($ids && in_array($id, $ids))
                    $sel=true;
                ?>
               <tr id="<?php echo $id; ?>">
                <td align="center">
                  <input type="checkbox" class="ckb" name="ids[]"
                  value="<?php echo $id; ?>" <?php echo $sel ? 'checked="checked"' : ''; ?> >
                <td><a href="staff.php?id=<?php echo $id; ?>"><?php echo
                Format::htmlchars((string) $agent->getName()); ?></a></td>
                <td><?php echo $agent->getUserName(); ?></td>
                <td><?php echo $agent->isActive() ? __('Enabled') :'<b>'.__('Disabled').'</b>'; ?><?php
                    echo $agent->onvacation ? ' <small>(<i>'.__('vacation').'</i>)</small>' : ''; ?></td>

                <td><a href="departments.php?id=<?php echo
                    $agent->getDeptId(); ?>"><?php
                    echo Format::htmlchars((string) $agent->dept); ?></a></td>
                <td><?php echo Format::date($agent->created); ?></td>
                <td><?php echo Format::relativeTime(Misc::db2gmtime($agent->lastlogin)) ?: '<em class="faded">'.__('never').'</em>'; ?></td>
               </tr>
            <?php
            } //end of foreach
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="8">
            <?php if ($count) { ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No associates found!');
            } ?>
        </td>
     </tr>
    </tfoot>
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
</div></div>
</form>
</div>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>enable</b> (unlock) %s?'),
            _N('selected associate', 'selected associate', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> (lock) %s?'),
            _N('selected associate', 'selected associates', 2));?>
        <br><br><?php echo __("Disabled staff won't be able to login to Staff Control Panel.");?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected associate', 'selected associates', 2));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered.');?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel');?>" class="close btn btn-sm btn-primary">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!');?>" class="confirm btn btn-sm btn-warning">
        </span>
     </p>
    <div class="clear"></div>
</div>

<script type="text/javascript">
$(document).on('click', 'a.dialog-first', function(e) {
    e.preventDefault();
    var action = $(this).data('action'),
        $form = $('form#mass-actions');
    if ($(':checkbox.ckb:checked', $form).length == 0) {
        $.sysAlert(__('Oops'),
            __('You need to select at least one item'));
        return false;
    }
    ids = $form.find('.ckb');
    $.dialog('ajax.php/' + $(this).attr('href').substr(1), 201, function (xhr, data) {
        $form.find('#action').val(action);
        data = JSON.parse(data);
        if (data)
            $.each(data, function(k, v) {
              if (v.length) {
                  $.each(v, function() {
                      $form.append($('<input type="hidden">').attr('name', k+'[]').val(this));
                  })
              }
              else {
                  $form.append($('<input type="hidden">').attr('name', k).val(v));
              }
          });
          $form.submit();
    }, { data: ids.serialize()});
    return false;
});
</script>
