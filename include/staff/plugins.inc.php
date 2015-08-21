<form action="plugins.php" method="POST" name="forms">

<div class="sticky bar opaque">
    <div class="content">
        <div class="pull-left flush-left">
            <h2><?php echo __('Currently Installed Plugins'); ?></h2>
        </div>
        <div class="pull-right flush-right">
            <a href="plugins.php?a=add" class="green button action-button"><i class="icon-plus-sign"></i> <?php
                echo __('Add New Plugin'); ?></a>
            <span class="action-button" data-dropdown="#action-dropdown-more">
                <i class="icon-caret-down pull-right"></i>
                <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
            </span>
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
                <ul id="actions">
                    <li>
                        <a class="confirm" data-name="enable" href="plugins.php?a=enable">
                            <i class="icon-ok-sign icon-fixed-width"></i>
                            <?php echo __( 'Enable'); ?>
                        </a>
                    </li>
                    <li>
                        <a class="confirm" data-name="disable" href="plugins.php?a=disable">
                            <i class="icon-ban-circle icon-fixed-width"></i>
                            <?php echo __( 'Disable'); ?>
                        </a>
                    </li>
                    <li class="danger">
                        <a class="confirm" data-name="delete" href="plugins.php?a=delete">
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

<?php
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = count($ost->plugins->allInstalled());
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('forms.php');
$showing=$pageNav->showing().' '._N('plugin', 'plugins', $count);
?>

<form action="plugins.php" method="POST" name="forms">
<?php csrf_token(); ?>
<input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th width="66%"><?php echo __('Plugin Name'); ?></th>
            <th width="10%"><?php echo __('Status'); ?></th>
            <th width="20%"><?php echo __('Date Installed'); ?></th>
        </tr>
    </thead>
    <tbody>
<?php
foreach ($ost->plugins->allInstalled() as $p) {
    if ($p instanceof Plugin) { ?>
    <tr>
        <td align="center"><input type="checkbox" class="ckb" name="ids[]" value="<?php echo $p->getId(); ?>"
                <?php echo $sel?'checked="checked"':''; ?>></td>
        <td><a href="plugins.php?id=<?php echo $p->getId(); ?>"
            ><?php echo $p->getName(); ?></a></td>
        <td><?php echo ($p->isActive())
            ? 'Enabled' : '<strong>Disabled</strong>'; ?></td>
        <td><?php echo Format::datetime($p->getInstallDate()); ?></td>
    </tr>
    <?php } else {} ?>
<?php } ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="4">
            <?php if($count){ ?>
            <?php echo __('Select'); ?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All'); ?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None'); ?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle'); ?></a>&nbsp;&nbsp;
            <?php }else{
                echo sprintf(__('No plugins installed yet &mdash; %s add one %s!'),
                    '<a href="?a=add">','</a>');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count) //Show options..
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>

</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm'); ?></h3>
    <a class="close" href="">&times;</a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(
        __('Are you sure you want to DELETE %s?'),
        _N('selected plugin', 'selected plugins', 2)); ?></strong></font>
        <br><br><?php echo __(
        'Configuration for deleted plugins CANNOT be recovered.'); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <font color="green"><?php echo sprintf(
        __('Are you sure you want to <b>enable</b> %s?'),
        _N('selected plugin', 'selected plugins', 2)); ?></font>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <font color="red"><?php echo sprintf(
        __('Are you sure you want to <b>disable</b> %s?'),
        _N('selected plugin', 'selected plugins', 2)); ?></font>
    </p>
    <div><?php echo __('Please confirm to continue.'); ?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel'); ?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!'); ?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
