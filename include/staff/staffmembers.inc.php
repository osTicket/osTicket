<?php
if (!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin())
    die('Access Denied');

$qstr='';
$qs = array();
$sortOptions = array(
        'name' => 'lastname',
        'username' => 'username',
        'status' => 'isactive',
        'group' => 'group__name',
        'dept' => 'dept__name',
        'created' => 'created',
        'login' => 'lastlogin'
        );

$orderWays = array('DESC'=>'DESC', 'ASC'=>'ASC');
$sort = ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';

if ($sort && $sortOptions[$sort]) {
    $order_column = $sortOptions[$sort];
}

$order_column = $order_column ? $order_column : 'lastname';

if ($_REQUEST['order'] && isset($orderWays[strtoupper($_REQUEST['order'])])) {
    $order = $orderWays[strtoupper($_REQUEST['order'])];
} else {
    $order = 'ASC';
}

if ($order_column && strpos($order_column,',')) {
    $order_column=str_replace(','," $order,",$order_column);
}
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';

//Filers
$filters = array();
if ($_REQUEST['did'] && is_numeric($_REQUEST['did'])) {
    $filters += array('dept_id' => $_REQUEST['did']);
    $qs += array('did' => $_REQUEST['did']);
}

if ($_REQUEST['gid'] && is_numeric($_REQUEST['gid'])) {
    $filters += array('group_id' => $_REQUEST['gid']);
    $qs += array('gid' => $_REQUEST['gid']);
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
    ->select_related('dept', 'group')
    ->order_by(sprintf('%s%s',
                strcasecmp($order, 'DESC') ? '' : '-',
                $order_column));

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
$qstr .= '&amp;order='.($order=='DESC' ? 'ASC' : 'DESC');

// add limits.
$agents->limit($pageNav->getLimit())->offset($pageNav->getStart());
?>
<h2><?php echo __('Agents');?></h2>

<div class="pull-left" style="width:700px;">
    <form action="staff.php" method="GET" name="filter">
     <input type="hidden" name="a" value="filter" >
        <select name="did" id="did">
             <option value="0">&mdash; <?php echo __('All Department');?> &mdash;</option>
             <?php
             if (($depts=Dept::getDepartments())) {
                 foreach ($depts as $id => $name) {
                     $sel=($_REQUEST['did'] && $_REQUEST['did']==$id)?'selected="selected"':'';
                     echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
                 }
             }
             ?>
        </select>
        <select name="gid" id="gid">
            <option value="0">&mdash; <?php echo __('All Groups');?> &mdash;</option>
             <?php
             if (($groups=Group::getGroups())) {
                 foreach ($groups as $id => $name) {
                     $sel=($_REQUEST['gid'] && $_REQUEST['gid']==$id)?'selected="selected"':'';
                     echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
                 }
             }
             ?>
        </select>
        <select name="tid" id="tid">
            <option value="0">&mdash; <?php echo __('All Teams');?> &mdash;</option>
             <?php
             if (($teams=Team::getTeams())) {
                 foreach ($teams as $id => $name) {
                     $sel=($_REQUEST['tid'] && $_REQUEST['tid']==$id)?'selected="selected"':'';
                     echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
                 }
             }
             ?>
        </select>
        &nbsp;&nbsp;
        <input type="submit" name="submit" value="<?php echo __('Apply');?>"/>
    </form>
 </div>
<div class="pull-right flush-right" style="padding-right:5px;"><b><a href="staff.php?a=add" class="Icon newstaff"><?php echo __('Add New Agent');?></a></b></div>
<div class="clear"></div>
<form action="staff.php" method="POST" name="staff" >
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7px">&nbsp;</th>
            <th width="200"><a <?php echo $name_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name');?></a></th>
            <th width="100"><a <?php echo $username_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=username"><?php echo __('Username');?></a></th>
            <th width="100"><a  <?php echo $status_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Status');?></a></th>
            <th width="120"><a  <?php echo $group_sort; ?>href="staff.php?<?php echo $qstr; ?>&sort=group"><?php echo __('Group');?></a></th>
            <th width="150"><a  <?php echo $dept_sort; ?>href="staff.php?<?php echo $qstr; ?>&sort=dept"><?php echo __('Department');?></a></th>
            <th width="100"><a <?php echo $created_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Created');?></a></th>
            <th width="145"><a <?php echo $login_sort; ?> href="staff.php?<?php echo $qstr; ?>&sort=login"><?php echo __('Last Login');?></a></th>
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
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]"
                  value="<?php echo $id; ?>" <?php echo $sel ? 'checked="checked"' : ''; ?> >
                <td><a href="staff.php?id=<?php echo $id; ?>"><?php echo
                Format::htmlchars($agent->getName()); ?></a>&nbsp;</td>
                <td><?php echo $agent->getUserName(); ?></td>
                <td><?php echo $agent->isActive() ? __('Active') :'<b>'.__('Locked').'</b>'; ?>&nbsp;<?php
                    echo $agent->onvacation ? '<small>(<i>'.__('vacation').'</i>)</small>' : ''; ?></td>
                <td><a href="groups.php?id=<?php echo $agent->group_id; ?>"><?php
                    echo Format::htmlchars($agent->group->getName()); ?></a></td>
                <td><a href="departments.php?id=<?php echo
                    $agent->getDeptId(); ?>"><?php
                    echo Format::htmlchars((string) $agent->dept); ?></a></td>
                <td><?php echo Format::date($agent->created); ?></td>
                <td><?php echo Format::datetime($agent->lastlogin); ?>&nbsp;</td>
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
if ($count): //Show options..
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
<p class="centered" id="actions">
    <input class="button" type="submit" name="enable" value="<?php echo __('Enable');?>" >
    &nbsp;&nbsp;
    <input class="button" type="submit" name="disable" value="<?php echo __('Lock');?>" >
    &nbsp;&nbsp;
    <input class="button" type="submit" name="delete" value="<?php echo __('Delete');?>">
</p>
<?php
endif;
?>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>enable</b> (unlock) %s?'),
            _N('selected agent', 'selected agents', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>disable</b> (lock) %s?'),
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

