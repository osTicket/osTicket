<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qs = array();
$sortOptions=array(
        'name' => 'name',
        'status' => 'isactive',
        'period' => 'grace_period',
        'created' => 'created',
        'updated' => 'updated'
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
$count = SLA::objects()->count();
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);

$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('slas.php', $qs);
$showing = $pageNav->showing().' '._N('Service Level Agreement', 'Service Level Agreements', $count);
$qstr .= '&amp;order='.($order=='DESC' ? 'ASC' : 'DESC');
?>
<form action="slas.php" method="POST" name="slas">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Service Level Agreements');?></h2>
            </div>
            <div class="pull-right flush-right">
                <a href="slas.php?a=add" class="green button action-button"><i class="icon-plus-sign"></i> <?php echo __('Add New SLA Plan');?></a>
                <span class="action-button" data-dropdown="#action-dropdown-more">
                    <i class="icon-caret-down pull-right"></i>
                    <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                </span>
                <div id="action-dropdown-more" class="action-dropdown anchor-right">
                    <ul id="actions">
                        <li>
                            <a class="confirm" data-name="enable" href="slas.php?a=enable">
                                <i class="icon-ok-sign icon-fixed-width"></i>
                                <?php echo __( 'Enable'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="confirm" data-name="disable" href="slas.php?a=disable">
                                <i class="icon-ban-circle icon-fixed-width"></i>
                                <?php echo __( 'Disable'); ?>
                            </a>
                        </li>
                        <li class="danger">
                            <a class="confirm" data-name="delete" href="slas.php?a=delete">
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
            <th width="38%"><a <?php echo $name_sort; ?> href="slas.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name');?></a></th>
            <th width="8%"><a <?php echo $status_sort; ?> href="slas.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Status');?></a></th>
            <th><a <?php echo $period_sort; ?> href="slas.php?<?php echo $qstr; ?>&sort=period"><?php echo __('Grace Period (hrs)');?></a></th>
            <th width="15%" nowrap><a <?php echo $created_sort; ?>href="slas.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Date Added');?></a></th>
            <th width="20%" nowrap><a <?php echo $updated_sort; ?>href="slas.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids = ($errors && is_array($_POST['ids'])) ? $_POST['ids'] : null;
        if ($count) {
            $slas = SLA::objects()
                ->order_by(sprintf('%s%s',
                            strcasecmp($order, 'DESC') ? '' : '-',
                            $order_column))
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart());

            $defaultId = $cfg->getDefaultSLAId();
            foreach ($slas as $sla) {
                $sel=false;
                $id = $sla->getId();
                if($ids && in_array($id, $ids))
                    $sel=true;

                $default = '';
                if ($id == $defaultId)
                    $default = '<small><em>(Default)</em></small>';
                ?>
            <tr id="<?php echo $id; ?>">
                <td align="center">
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $id; ?>"
                    <?php echo $sel ? 'checked="checked"' :'' ; ?>>
                </td>
                <td>&nbsp;<a href="slas.php?id=<?php echo $id;
                    ?>"><?php echo Format::htmlchars($sla->getName());
                    ?></a>&nbsp;<?php echo $default; ?></td>
                <td><?php echo $sla->isActive() ? __('Active') : '<b>'.__('Disabled').'</b>'; ?></td>
                <td style="text-align:right;padding-right:35px;"><?php echo $sla->getGracePeriod(); ?>&nbsp;</td>
                <td>&nbsp;<?php echo Format::date($sla->getCreateDate()); ?></td>
                <td>&nbsp;<?php echo Format::datetime($sla->getUpdateDate()); ?></td>
            </tr>
            <?php
            } //end of foreach.
        } ?>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if ($count) { ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No SLA plans found' /* SLA is abbreviation for Service Level Agreement */);
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count): //Show options..
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
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>enable</b> %s?'),
            _N('selected SLA plan', 'selected SLA plans', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> %s?'),
            _N('selected SLA plan', 'selected SLA plans', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected SLA plan', 'selected SLA plans', 2)); ?></strong></font>
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
