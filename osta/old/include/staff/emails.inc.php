<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qs = array();
$sortOptions = array(
        'email' => 'email',
        'dept' => 'dept__name',
        'priority' => 'priority__priority_desc',
        'created' => 'created',
        'updated' => 'updated');


$orderWays = array('DESC'=>'DESC', 'ASC'=>'ASC');
$sort = ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ?  strtolower($_REQUEST['sort']) : 'email';
if ($sort && $sortOptions[$sort]) {
        $order_column = $sortOptions[$sort];
}

$order_column = $order_column ? $order_column : 'email';

if ($_REQUEST['order'] && isset($orderWays[strtoupper($_REQUEST['order'])]))
{
        $order = $orderWays[strtoupper($_REQUEST['order'])];
} else {
        $order = 'ASC';
}

$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = Email::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('emails.php', $qs);
$showing = $pageNav->showing().' '._N('email', 'emails', $count);
$qstr = '&amp;order='.($order=='DESC' ? 'ASC' : 'DESC');

$def_dept_id = $cfg->getDefaultDeptId();
$def_dept_name = ($d = $cfg->getDefaultDept()) ? $d->getName() : '';
$def_priority = ($c = $cfg->getDefaultPriority()) ? $c->getDesc() : '';
?>
<form action="emails.php" method="POST" name="emails">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Email Addresses');?></h2>
            </div>
            <div class="pull-right flush-right">
                <a href="emails.php?a=add" class="green button action-button"><i class="icon-plus-sign"></i> <?php echo __('Add New Email');?></a>
                <span class="action-button" data-dropdown="#action-dropdown-more">
                            <i class="icon-caret-down pull-right"></i>
                            <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                </span>
                <div id="action-dropdown-more" class="action-dropdown anchor-right">
                    <ul id="actions">
                        <li class="danger">
                            <a class="confirm" data-name="delete" href="emails.php?a=delete">
                                <i class="icon-trash icon-fixed-width"></i>
                                <?php echo __( 'Delete'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="clear"></div>
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th width="38%"><a <?php echo $email_sort; ?> href="emails.php?<?php echo $qstr; ?>&sort=email"><?php echo __('Email');?></a></th>
            <th width="8%"><a  <?php echo $priority_sort; ?> href="emails.php?<?php echo $qstr; ?>&sort=priority"><?php echo __('Priority');?></a></th>
            <th width="15%"><a  <?php echo $dept_sort; ?> href="emails.php?<?php echo $qstr; ?>&sort=dept"><?php echo __('Department');?></a></th>
            <th width="15%" nowrap><a  <?php echo $created_sort; ?>href="emails.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Created');?></a></th>
            <th width="20%" nowrap><a  <?php echo $updated_sort; ?>href="emails.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $ids = ($errors && is_array($_POST['ids'])) ? $_POST['ids'] : null;
        if ($count):
            $defaultId=$cfg->getDefaultEmailId();
            $emails = Email::objects()
                ->order_by(sprintf('%s%s',
                            strcasecmp($order, 'DESC') ? '' : '-',
                            $order_column))
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart());

            foreach ($emails as $email) {
                $id = $email->getId();
                $sel=false;
                if ($ids && in_array($id, $ids))
                    $sel=true;
                $default=($id==$defaultId);
                ?>
            <tr id="<?php echo $id; ?>">
                <td align="center">
                  <input type="checkbox" class="ckb" name="ids[]"
                    value="<?php echo $id; ?>"
                    <?php echo $sel ? 'checked="checked" ' : ''; ?>
                    <?php echo $default?'disabled="disabled" ':''; ?>>
                </td>
                <td><span class="ltr"><a href="emails.php?id=<?php echo $id; ?>"><?php
                    echo Format::htmlchars((string) $email); ?></a></span>
                <?php echo ($default) ?' <small>'.__('(Default)').'</small>' : ''; ?>
                </td>
                <td><?php echo $email->priority ?: $def_priority; ?></td>
                <td><a href="departments.php?id=<?php $email->dept_id ?: $def_dept_id; ?>"><?php
                    echo $email->dept ?: $def_dept_name; ?></a></td>
                <td>&nbsp;<?php echo Format::date($email->created); ?></td>
                <td>&nbsp;<?php echo Format::datetime($email->updated); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if ($count){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No emails found!');
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
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected email', 'selected emails', 2)) ;?></strong></font>
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
