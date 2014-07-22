<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$qstr='';
$sql='SELECT team.*,count(m.staff_id) as members,CONCAT_WS(" ",lead.firstname,lead.lastname) as team_lead '.
     ' FROM '.TEAM_TABLE.' team '.
     ' LEFT JOIN '.TEAM_MEMBER_TABLE.' m ON(m.team_id=team.team_id) '.
     ' LEFT JOIN '.STAFF_TABLE.' lead ON(lead.staff_id=team.lead_id) ';
$sql.=' WHERE 1';
$sortOptions=array('name'=>'team.name','status'=>'team.isenabled','members'=>'members','lead'=>'team_lead','created'=>'team.created');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'team.name';

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

$query="$sql GROUP BY team.team_id ORDER BY $order_by";
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=sprintf(__('Showing 1-%1$d of %2$d teams'), $num, $num);
else
    $showing=__('No teams found!');

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
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['team_id'],$ids))
                    $sel=true;
                ?>
            <tr id="<?php echo $row['team_id']; ?>">
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['team_id']; ?>"
                            <?php echo $sel?'checked="checked"':''; ?>> </td>
                <td><a href="teams.php?id=<?php echo $row['team_id']; ?>"><?php echo $row['name']; ?></a> &nbsp;</td>
                <td>&nbsp;<?php echo $row['isenabled']?__('Active'):'<b>'.__('Disabled').'</b>'; ?></td>
                <td style="text-align:right;padding-right:25px">&nbsp;&nbsp;
                    <?php if($row['members']>0) { ?>
                        <a href="staff.php?tid=<?php echo $row['team_id']; ?>"><?php echo $row['members']; ?></a>
                    <?php }else{ ?> 0
                    <?php } ?>
                    &nbsp;
                </td>
                <td><a href="staff.php?id=<?php echo $row['lead_id']; ?>"><?php echo $row['team_lead']; ?>&nbsp;</a></td>
                <td><?php echo Format::db_date($row['created']); ?>&nbsp;</td>
                <td><?php echo Format::db_datetime($row['updated']); ?>&nbsp;</td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if($res && $num){ ?>
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
if($res && $num): //Show options..
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
        <?php echo sprintf(__('Are you sure want to <b>enable</b> %s?'),
            _N('selected team', 'selected teams', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>disable</b> %s?'),
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
