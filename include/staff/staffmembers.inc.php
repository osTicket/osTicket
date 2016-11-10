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

//Filers
$filters = array();
if ($_REQUEST['did'] && is_numeric($_REQUEST['did'])) {
    $filters += array('dept_id' => $_REQUEST['did']);
    $qs += array('did' => $_REQUEST['did']);
}

if ($_REQUEST['tid'] && is_numeric($_REQUEST['tid'])) {
    $filters += array('teams__team_id' => $_REQUEST['tid']);
    $qs += array('tid' => $_REQUEST['tid']);
}

//agents objects
$agents = Staff::objects()
    ->annotate(array(
            'teams_count'=>SqlAggregate::COUNT('teams', true),
    ))
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
<div id="basic_search">
    <div style="min-height:25px;">
        <div class="pull-left">
            <form action="staff.php" method="GET" name="filter">
                <input type="hidden" name="a" value="filter">
                <select name="did" id="did">
                    <option value="0">&mdash;
                        <?php echo __( 'All Departments');?> &mdash;</option>
                    <?php if (($depts=Dept::getDepartments())) { foreach ($depts as $id=> $name) { $sel=($_REQUEST['did'] && $_REQUEST['did']==$id)?'selected="selected"':''; echo sprintf('
                    <option value="%d" %s>%s</option>',$id,$sel,$name); } } ?>
                </select>
                <select name="tid" id="tid">
                    <option value="0">&mdash;
                        <?php echo __( 'All Teams');?> &mdash;</option>
                    <?php if (($teams=Team::getTeams())) { foreach ($teams as $id=> $name) { $sel=($_REQUEST['tid'] && $_REQUEST['tid']==$id)?'selected="selected"':''; echo sprintf('
                    <option value="%d" %s>%s</option>',$id,$sel,$name); } } ?>
                </select>
                <input type="submit" name="submit" class="button muted" value="<?php echo __('Apply');?>" />
            </form>
        </div>
    </div>
</div>
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Agents');?></h2>
            </div>
            <div class="pull-right">
                <a class="green button action-button" href="staff.php?a=add">
                    <i class="icon-plus-sign"></i>
                    <?php echo __( 'Add New Agent'); ?>
                </a>
                <span class="action-button" data-dropdown="#action-dropdown-more">
                <i class="icon-caret-down pull-right"></i>
                <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                </span>
                <div id="action-dropdown-more" class="action-dropdown anchor-right">
                    <ul id="actions">
                        <li>
                            <a class="confirm" data-form-id="mass-actions" data-name="enable" href="staff.php?a=enable">
                                <i class="icon-ok-sign icon-fixed-width"></i>
                                <?php echo __( 'Enable'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="confirm" data-form-id="mass-actions" data-name="disable" href="staff.php?a=disable">
                                <i class="icon-ban-circle icon-fixed-width"></i>
                                <?php echo __( 'Disable'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="dialog-first" data-action="permissions" href="#staff/reset-permissions">
                                <i class="icon-sitemap icon-fixed-width"></i>
                                <?php echo __( 'Reset Permissions'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="dialog-first" data-action="department" href="#staff/change-department">
                                <i class="icon-truck icon-fixed-width"></i>
                                <?php echo __( 'Change Department'); ?>
                            </a>
                        </li>
                        <!-- TODO: Implement "Reset Access" mass action
                    <li><a class="dialog-first" href="#staff/reset-access">
                    <i class="icon-puzzle-piece icon-fixed-width"></i>
                        <?php echo __('Reset Access'); ?></a></li>
                    -->
                        <li class="danger">
                            <a class="confirm" data-form-id="mass-actions" data-name="delete" href="staff.php?a=delete">
                                <i class="icon-trash icon-fixed-width"></i>
                                <?php echo __( 'Delete'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>

<form id="mass-actions" action="staff.php" method="POST" name="staff" >

 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th width="28%"><a <?php echo $name_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name');?></a></th>
            <th width="16%"><a <?php echo $username_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=username"><?php echo __('Username');?></a></th>
            <th width="8%"><a  <?php echo $status_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Status');?></a></th>
            <th width="14%"><a  <?php echo $dept_sort; ?>href="staff.php?<?php echo $qstr; ?>&sort=dept"><?php echo __('Department');?></a></th>
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
                <td><?php echo $agent->isActive() ? __('Active') :'<b>'.__('Locked').'</b>'; ?><?php
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
                echo __('No agents found!');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count) { //Show options..
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
}
?>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>enable</b> (unlock) %s?'),
            _N('selected agent', 'selected agents', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> (lock) %s?'),
            _N('selected agent', 'selected agents', 2));?>
        <br><br><?php echo __("Locked staff won't be able to login to Staff Control Panel.");?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected agent', 'selected agents', 2));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered.');?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel');?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!');?>" class="confirm">
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
