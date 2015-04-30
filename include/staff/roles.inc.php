<div class="pull-left" style="width:700;padding-top:5px;">
 <h2><?php echo __('Roles'); ?></h2>
</div>
<div class="pull-right flush-right" style="padding-top:5px;padding-right:5px;">
 <b><a href="roles.php?a=add" class="Icon list-add"><?php
 echo __('Add New Role'); ?></a></b></div>
<div class="clear"></div>

<?php
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = Role::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('roles.php');
$showing=$pageNav->showing().' '._N('role', 'roles', $count);

?>
<form action="roles.php" method="POST" name="roles">
<?php csrf_token(); ?>
<input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th><?php echo __('Name'); ?></th>
            <th width="100"><?php echo __('Status'); ?></th>
            <th width="200"><?php echo __('Created On') ?></th>
            <th width="250"><?php echo __('Last Updated'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach (Role::objects()->order_by('name')
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart()) as $role) {
            $id = $role->getId();
            $sel = false;
            if ($ids && in_array($id, $ids))
                $sel = true; ?>
        <tr>
            <td>
                <?php
                if ($role->isDeleteable()) { ?>
                <input width="7" type="checkbox" class="ckb" name="ids[]"
                value="<?php echo $id; ?>"
                    <?php echo $sel?'checked="checked"':''; ?>>
                <?php
                } else {
                    echo '&nbsp;';
                }
                ?>
            </td>
            <td><a href="?id=<?php echo $id; ?>"><?php echo
            $role->getLocal('name'); ?></a></td>
            <td>&nbsp;<?php echo $role->isEnabled() ? __('Active') :
            '<b>'.__('Disabled').'</b>'; ?></td>
            <td><?php echo Format::date($role->getCreateDate()); ?></td>
            <td><?php echo Format::datetime($role->getUpdateDate()); ?></td>
        </tr>
    <?php }
    ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="5">
            <?php if($count){ ?>
            <?php echo __('Select'); ?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All'); ?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None'); ?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle'); ?></a>&nbsp;&nbsp;
            <?php } else {
                echo sprintf(__('No roles defined yet &mdash; %s add one %s!'),
                    '<a href="roles.php?a=add">','</a>');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count) //Show options..
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>

<p class="centered" id="actions">
    <input class="button" type="submit" name="enable" value="<?php echo
    __('Enable'); ?>">
    &nbsp;&nbsp;
    <input class="button" type="submit" name="disable" value="<?php echo
    __('Disable'); ?>">
    &nbsp;&nbsp;
    <input class="button" type="submit" name="delete" value="<?php echo
    __('Delete'); ?>">
</p>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm'); ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>enable</b> %s?'),
            _N('selected role', 'selected roles', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>disable</b> %s?'),
            _N('selected role', 'selected roles', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(
        __('Are you sure you want to DELETE %s?'),
        _N('selected role', 'selected roles', 2)); ?></strong></font>
        <br><br><?php echo __('Deleted roles CANNOT be recovered.'); ?>
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
