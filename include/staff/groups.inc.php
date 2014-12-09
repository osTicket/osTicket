<?php
if (!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin())
    die('Access Denied');

$qstr = '';
$sortOptions = array(
        'name'   => 'name',
        'users'  => 'members_count',
        'depts'  => 'depts_count',
        'status' => 'isenabled',
        'created'=> 'created',
        'updated'=> 'updated');

$orderWays = array('DESC'=>'DESC', 'ASC'=>'ASC');
$sort = ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';

//Sorting options...
if ($sort && $sortOptions[$sort]) {
    $order_column = $sortOptions[$sort];
}

$order_column = $order_column ? $order_column : 'name';

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
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = Group::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$_qstr = $qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']);
$pageNav->setURL('groups.php', $_qstr);
$showing = $pageNav->showing().' '._N('group', 'groups', $count);
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
?>
<div class="pull-left" style="width:700px;padding-top:5px;">
 <h2><?php echo __('Agent Groups');?>
    <i class="help-tip icon-question-sign" href="#groups"></i>
    </h2>
 </div>
<div class="pull-right flush-right" style="padding-top:5px;padding-right:5px;">
    <b><a href="groups.php?a=add" class="Icon newgroup"><?php echo __('Add New Group');?></a></b></div>
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
            <th width="200"><a <?php echo $name_sort; ?> href="groups.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Group Name');?></a></th>
            <th width="80"><a  <?php echo $status_sort; ?> href="groups.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Status');?></a></th>
            <th width="80" style="text-align:center;"><a  <?php echo $users_sort; ?>href="groups.php?<?php echo $qstr; ?>&sort=users"><?php echo __('Members');?></a></th>
            <th width="80" style="text-align:center;"><a  <?php echo $depts_sort; ?>href="groups.php?<?php echo $qstr; ?>&sort=depts"><?php echo __('Departments');?></a></th>
            <th width="100"><a  <?php echo $created_sort; ?> href="groups.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Created On');?></a></th>
            <th width="120"><a  <?php echo $updated_sort; ?> href="groups.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids = ($errors && is_array($_POST['ids'])) ? $_POST['ids'] : null;
        if ($count) {
            $groups= Group::objects()
                ->annotate(array(
                        'members_count'=>SqlAggregate::COUNT('members__staff_id', true),
                        'depts_count'=>SqlAggregate::COUNT('depts', true),
                        'isenabled'=>new SqlExpr(array(
                                'flags__hasbit' => Group::FLAG_ENABLED))
                ))
                ->order_by(sprintf('%s%s',
                            strcasecmp($order, 'DESC') ? '' : '-',
                            $order_column))
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart());

            foreach ($groups as $group) {
                $sel=false;
                $id = $group->getId();
                if($ids && in_array($id, $ids))
                    $sel=true;
                ?>
            <tr id="<?php echo $id; ?>">
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]"
                    value="<?php echo $id; ?>"
                    <?php echo $sel?'checked="checked"':''; ?>> </td>
                <td><a href="groups.php?id=<?php echo $id; ?>"><?php echo
                $group->getName(); ?></a> &nbsp;</td>
                <td>&nbsp;<?php echo $group->isenabled ? __('Active') : '<b>'.__('Disabled').'</b>'; ?></td>
                <td style="text-align:right;padding-right:30px">&nbsp;&nbsp;
                    <?php if ($num=$group->members_count) { ?>
                        <a href="staff.php?gid=<?php echo $id; ?>"><?php echo $num; ?></a>
                    <?php } else { ?> 0
                    <?php } ?>
                    &nbsp;
                </td>
                <td style="text-align:right;padding-right:30px">&nbsp;&nbsp;
                    <?php echo $group->depts_count; ?>
                </td>
                <td><?php echo Format::date($group->getCreateDate()); ?>&nbsp;</td>
                <td><?php echo Format::datetime($group->getUpdateDate()); ?>&nbsp;</td>
            </tr>
            <?php
            } //end of while.
        } ?>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if ($count) { ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No groups found!');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count):
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
<p class="centered" id="actions">
    <input class="button" type="submit" name="enable" value="<?php echo __('Enable');?>" >
    <input class="button" type="submit" name="disable" value="<?php echo __('Disable');?>" >
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
        <?php echo sprintf(__('Are you sure want to <b>enable</b> %s?'),
            _N('selected group', 'selected groups', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>disable</b> %s?'),
            _N('selected group', 'selected groups', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected group', 'selected groups', 2));?></strong></font>
        <br><br><?php echo __("Deleted data CANNOT be recovered and might affect agents' access.");?>
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

