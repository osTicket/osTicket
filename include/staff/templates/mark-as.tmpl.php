<?php
global $cfg;

$form = MarkAsForm::instantiate($_POST);
?>
<h3 class="drag-handle"><?php echo $info[':title'] ?:  __('Please Confirm'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warn']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warn']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} elseif ($info['notice']) {
   echo sprintf('<p id="msg_info"><i class="icon-info-sign"></i> %s</p>',
           $info['notice']);
}
?>
<form class="mass-action" method="post"
    action="#tickets/<?php echo $ticket->getId(); ?>/mark/<?php echo $action; ?>"
    name="markAs">
    <table width="100%">
        <tbody>
            <tr><td>
                <p>
                <?php echo sprintf(
                            __('Are you sure you want to mark ticket as <b>%s</b>?'),
                            $action); ?>
                </p>
                <p>
                <?php echo __('Please confirm to continue.'); ?>
                </p>
            </td></tr>
            <tr><td>
                <p>
                <?php print $form->getField('comments')->render(); ?>
                </p>
            </td></tr>
        </tbody>
    </table>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="close"
                value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php
            echo __('OK'); ?>">
        </span>
    </p>
</form>
<div class="clear"></div>
