<form action="schedules.php" method="POST" name="schedules">

<div class="sticky bar opaque">
    <div class="content">
        <div class="pull-left flush-left">
            <h2><?php echo __('Schedules'); ?></h2>
        </div>
        <div class="pull-right flush-right">
            <a class="green button action-button" id="new-schedule"
                href="#schedule/add">
                <i class="icon-plus-sign"></i>
                <?php echo __('Add New Schedule'); ?>
            </a>
            <span class="action-button" data-dropdown="#action-dropdown-more">
                    <i class="icon-caret-down pull-right"></i>
                    <span ><i class="icon-cog"></i> <?php echo __('Actions');?></span>
            </span>
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
                <ul id="actions">
                    <li class="danger">
                        <a class="confirm" data-name="delete"
                        href="schedules.php?a=delete">
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
$count = Schedule::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('schedules.php');
$showing=$pageNav->showing().' '._N('schedule', 'schedules', $count);

?>
<?php csrf_token(); ?>
<input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="25">&nbsp;</th>
            <th><?php echo __('Name'); ?></th>
            <th width="120"><?php echo __('Type'); ?></th>
            <th width="180"><?php echo __('Created') ?></th>
            <th width="180"><?php echo __('Last Updated'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach (Schedule::objects()->order_by('name')
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart()) as $schedule) {
            $sel = false;
            if ($ids && in_array($form->get('id'),$ids))
                $sel = true; ?>
        <tr>
            <td align="center">
                <input width="7" type="checkbox" class="ckb" name="ids[]"
                value="<?php echo $schedule->getId(); ?>"
                    <?php echo $sel?'checked="checked"':''; ?>>
            </td>
            <td><a href="?id=<?php echo $schedule->getId(); ?>"><?php echo
            $schedule->getName(); ?></a><span class="pull-right"><small
            class="faded-more"><i class="icon-calendar"></i> <?php
            echo $schedule->getNumEntries(); ?></small></span>
            </td>
            <td><?php echo Format::htmlchars($schedule->getTypeDesc()); ?></td>
            <td><?php echo Format::date($schedule->getCreated()); ?></td>
            <td><?php echo Format::datetime($schedule->getUpdated()); ?></td>
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
                echo sprintf(__('No schedules defined yet &mdash; %s add one %s!'),
                    '<a href="schedules.php?a=add">','</a>');
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
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(
        __('Are you sure you want to DELETE %s?'),
        _N('selected schedule', 'selected schedules', 2)); ?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered.'); ?>
    </p>
    <div><?php echo __('Please confirm to continue.'); ?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="No, Cancel" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="Yes, Do it!" class="confirm">
        </span>
    </p>
    <div class="clear"></div>
</div>
<script type="text/javascript">
$(function() {
    $(document).on('click', 'a#new-schedule', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'
        +$(this).attr('href').substr(1)
        var $options = $(this).data('dialog');
        $.dialog(url, [201], function (xhr) {
            var id = parseInt(xhr.responseText);
            if (id)
                window.location.href = 'schedules.php?id='+id;
            $.pjax.reload('#pjax-container');
        }, $options);
        return false;
    });
});
</script>
