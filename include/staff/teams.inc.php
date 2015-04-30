<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$qs = array();
$sortOptions=array(
        'name' => 'name',
        'status' => 'isenabled',
        'members' => 'members_count',
        'lead' => 'lead__lastname',
        'created' => 'created',
        'updated' => 'updated',
        );

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
$count = Team::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('teams.php', $qs);
$showing = $pageNav->showing().' '._N('team', 'teams', $count);
$qstr .= '&amp;order='.urlencode($order=='DESC' ? 'ASC' : 'DESC');


?>
<div class="pull-left" style="width:700px;padding-top:5px;">
 <h2><?php echo __('Teams');?>
    <i class="help-tip icon-question-sign" href="#teams"></i>
    </h2>
 </div>
<div class="pull-right flush-right" style="padding-top:5px;padding-right:5px;">
    <b><a href="teams.php?a=add" class="Icon newteam"><?php echo __('Add New Team');?></a></b></div>
<div class="clear"></div>
<form action="teams.php" method="POST" name="teams">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7px">&nbsp;</th>
            <th width="250"><a <?php echo $name_sort; ?> href="teams.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Team Name');?></a></th>
            <th width="80"><a  <?php echo $status_sort; ?> href="teams.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Status');?></a></th>
            <th width="80"><a  <?php echo $members_sort; ?>href="teams.php?<?php echo $qstr; ?>&sort=members"><?php echo __('Members');?></a></th>
            <th width="200"><a  <?php echo $lead_sort; ?> href="teams.php?<?php echo $qstr; ?>&sort=lead"><?php echo __('Team Lead');?></a></th>
            <th width="100"><a  <?php echo $created_sort; ?> href="teams.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Created');?></a></th>
            <th width="130"><a  <?php echo $updated_sort; ?> href="teams.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $ids= ($errors && is_array($_POST['ids'])) ? $_POST['ids'] : null;
        if ($count) {
            $teams = Team::objects()
                ->annotate(array(
                        'members_count'=>SqlAggregate::COUNT('members', true),
                ))
                ->order_by(sprintf('%s%s',
                            strcasecmp($order, 'DESC') ? '' : '-',
                            $order_column))
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart());

            foreach ($teams as $team) {
                $id = $team->getId();
                $sel=false;
                if ($ids && in_array($id, $ids))
                    $sel=true;
                ?>
            <tr id="<?php echo $id; ?>">
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]"
                  value="<?php echo $id; ?>"
                            <?php echo $sel ? 'checked="checked"' : ''; ?>> </td>
                <td><a href="teams.php?id=<?php echo $id; ?>"><?php echo
                $team->getName(); ?></a> &nbsp;</td>
                <td>&nbsp;<?php echo $team->isActive() ? __('Active') : '<b>'.__('Disabled').'</b>'; ?></td>
                <td style="text-align:right;padding-right:25px">&nbsp;&nbsp;
                    <?php if ($team->members_count > 0) { ?>
                        <a href="staff.php?tid=<?php echo $id; ?>"><?php
                            echo $team->members_count; ?></a>
                    <?php } else { ?> 0
                    <?php } ?>
                    &nbsp;
                </td>
                <td><a href="staff.php?id=<?php
                    echo $team->getLeadId(); ?>"><?php echo $team->lead ?: ''; ?>&nbsp;</a></td>
                <td><?php echo Format::date($team->created); ?>&nbsp;</td>
                <td><?php echo Format::datetime($team->updated); ?>&nbsp;</td>
            </tr>
            <?php
            } //end of foreach
        }?>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if ($count){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No teams found!');
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
    <input class="button" type="submit" name="disable" value="<?php echo __('Disable');?>" >
    <input class="button" type="submit" name="delete" value="<?php echo __('Delete');?>" >
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
        <?php echo sprintf(__('Are you sure you want to <b>enable</b> %s?'),
            _N('selected team', 'selected teams', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> %s?'),
            _N('selected team', 'selected teams', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected team', 'selected teams', 2));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered.'); ?>
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
