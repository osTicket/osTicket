<?php
if (!defined('OSTADMININC') || !$thisstaff->isAdmin())
    die('Access Denied');

$qs = array();
$sortOptions=array(
    'name' => 'name',
    'status' => 'flags',
    'type' => 'ispublic',
    'members'=> 'members_count',
    'email'=> 'email__name',
    'manager'=>'manager__lastname'
    );

$orderWays = array('DESC'=>'DESC', 'ASC'=>'ASC');
$sort = ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';
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
$count = Dept::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qstr .= '&amp;order='.($order=='DESC' ? 'ASC' : 'DESC');
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('departments.php', $qs);
$showing = $pageNav->showing().' '._N('department', 'departments', $count);
?>
<form action="departments.php" method="POST" name="depts">
<div class="sticky bar">
    <div class="content">
        <div class="pull-left">
            <h2><?php echo __('Departments');?></h2>
        </div>
        <div class="pull-right flush-right">
            <a href="departments.php?a=add" class="green button action-button"><i class="icon-plus-sign"></i> <?php echo __('Add New Department');?></a>
            <span class="action-button" data-dropdown="#action-dropdown-more">
                <i class="icon-caret-down pull-right"></i>
                <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
            </span>
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
                <ul id="actions">
                    <li>
                        <a class="confirm" data-name="enable" href="departments.php?a=enable">
                            <i class="icon-ok-sign icon-fixed-width"></i>
                            <?php echo __( 'Enable'); ?>
                        </a>
                    </li>
                    <li>
                        <a class="confirm" data-name="disable" href="departments.php?a=disable">
                            <i class="icon-ban-circle icon-fixed-width"></i>
                            <?php echo __( 'Disable'); ?>
                        </a>
                    </li>
                    <li>
                        <a class="confirm" data-name="archive" href="departments.php?a=archive">
                            <i class="icon-folder-close icon-fixed-width"></i>
                            <?php echo __( 'Archive'); ?>
                        </a>
                    </li>
                    <li class="danger"><a class="confirm" data-name="delete" href="departments.php?a=delete">
                        <i class="icon-trash icon-fixed-width"></i>
                        <?php echo __('Delete'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="clear"></div>
    </div>
</div>
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th width="28%"><a <?php echo $name_sort; ?> href="departments.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name');?></a></th>
            <th width="8%"><a <?php echo $status_sort; ?> href="departments.php?<?php echo $qstr;?>&sort=status"><?php echo __('Status');?></a></th>
            <!-- <th style="padding-left:4px;vertical-align:middle" width="8%"><?php echo __('Status'); ?></th> -->
            <th width="8%"><a  <?php echo $type_sort; ?> href="departments.php?<?php echo $qstr; ?>&sort=type"><?php echo __('Type');?></a></th>
            <!-- <th width="8%"><a  <?php echo $users_sort; ?>href="departments.php?<?php echo $qstr; ?>&sort=users"><?php echo __('Agents');?></a></th> -->
            <th width="8%"><a  <?php echo $users_sort; ?>href="departments.php?<?php echo $qstr; ?>&sort=members"><?php echo __('Agents');?></a></th>
            <th width="30%"><a  <?php echo $email_sort; ?> href="departments.php?<?php echo $qstr; ?>&sort=email"><?php echo __('Email Address');?></a></th>
            <th width="22%"><a  <?php echo $manager_sort; ?> href="departments.php?<?php echo $qstr; ?>&sort=manager"><?php echo __('Manager');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $ids= ($errors && is_array($_POST['ids'])) ? $_POST['ids'] : null;
        if ($count) {
            $depts = Dept::objects()
                ->annotate(array(
                        'members_count' => SqlAggregate::COUNT('members', true),
                ))
                ->order_by(sprintf('%s%s',
                            strcasecmp($order, 'DESC') ? '' : '-',
                            $order_column))
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart());
            $defaultId=$cfg->getDefaultDeptId();
            $defaultEmailId = $cfg->getDefaultEmailId();
            $defaultEmailAddress = (string) $cfg->getDefaultEmail();
            foreach ($depts as $dept) {
                $id = $dept->getId();
                $sel=false;
                if($ids && in_array($dept->getId(), $ids))
                    $sel=true;

                if ($dept->email) {
                    $email = (string) $dept->email;
                    $emailId = $dept->email->getId();
                } else {
                    $emailId = $defaultEmailId;
                    $email = $defaultEmailAddress;
                }

                $default= ($defaultId == $dept->getId()) ?' <small>'.__('(Default)').'</small>' : '';
                ?>
            <tr id="<?php echo $id; ?>">
                <td align="center">
                  <input type="checkbox" class="ckb" name="ids[]"
                  value="<?php echo $id; ?>"
                  <?php echo $sel? 'checked="checked"' : ''; ?>
                  <?php echo $default? 'disabled="disabled"' : ''; ?> >
                </td>
                <td>
                  <a href="departments.php?id=<?php echo $id; ?>"><?php
                echo Dept::getNameById($id); ?></a>&nbsp;<?php echo $default; ?>
                </td>
                <td><?php
                  if(!strcasecmp($dept->getStatus(), 'Active'))
                    echo $dept->getStatus();
                  else
                    echo '<b>'.$dept->getStatus();
                  ?>
                </td>
                <td><?php echo $dept->isPublic() ? __('Public') :'<b>'.__('Private').'</b>'; ?></td>
                <td>&nbsp;&nbsp;
                    <b>
                    <?php if ($dept->members_count) { ?>
                        <a href="staff.php?did=<?php echo $id; ?>"><?php echo $dept->members_count; ?></a>
                    <?php }else{ ?> 0
                    <?php } ?>
                    </b>
                </td>
                <td><span class="ltr"><a href="emails.php?id=<?php echo $emailId; ?>"><?php
                    echo Format::htmlchars($email); ?></a></span></td>
                <td><a href="staff.php?id=<?php echo $dept->manager_id; ?>"><?php
                    echo $dept->manager_id ? $dept->manager : ''; ?>&nbsp;</a></td>
            </tr>
            <?php
            } //end of foreach.
        } ?>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php
            if ($count) { ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No departments found!');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count):
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
<?php
endif;
?>
</form>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="make_public-confirm">
        <?php echo sprintf(__('Are you sure you want to make %s <b>public</b>?'),
            _N('selected department', 'selected departments', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="make_private-confirm">
        <?php echo sprintf(__('Are you sure you want to make %s <b>private</b> (internal)?'),
            _N('selected department', 'selected departments', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>enable</b> %s?'),
            _N('selected department', 'selected departments', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> %s?'),
            _N('selected department', 'selected departments', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="archive-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>archive</b> %s?'),
            _N('selected department', 'selected departments', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected department', 'selected departments', 2));?></strong></font>
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
