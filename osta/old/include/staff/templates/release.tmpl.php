<?php
global $cfg;

$assignees = array();
if (($staff = $ticket->getStaff()))
    $assignees[] = $staff;
if (($team = $ticket->getTeam()))
    $assignees[] = $team;

$form = ReleaseForm::instantiate($_POST);
?>
<h3 class="drag-handle"><?php echo $info[':title'] ?:  __('Release Confirmation'); ?></h3>
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
    action="#tickets/<?php echo $ticket->getId(); ?>/release"
    name="release">
    <input type='hidden' name='do' value='release'>
    <table width="100%">
        <tbody>
        <?php if ($staff && $team) { ?>
            <tr><td>
                <p>
                <?php echo __('Please check assignee(s) to release assignment.'); ?>
                </p>
            </td></tr>
        <?php } ?>
        <?php if(count($assignees) > 1) { ?>
            <?php foreach($assignees as $assignee) { ?>
                <tr><td>
                    <label class="inline checkbox">
                        <?php echo sprintf(
                            ($isStaff = $assignee instanceof Staff)
                                ? '<input type="checkbox" name="sid[]" id="s%d" value="%d">'
                                : '<input type="checkbox" name="tid[]" id="t%d" value="%d">',
                            $assignee->getId(),
                            $assignee->getId()); ?>
                    </label>
                    <?php echo '<i class="icon-'.(($isStaff) ? 'user' : 'group').'"></i>'; ?>
                    <?php echo $assignee->getName(); ?>
                </td></tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td>
                <input type="hidden" name="<?php echo (($staff)?'s':'t').'id[]'; ?>" value="()">
                <p>
                <?php echo __('Please confirm to continue.'); ?>
                </p>
                <p>
                <?php echo sprintf(
                            __('Are you sure you want to <b>unassign</b> ticket from <b>%s</b>?'),
                            ($staff) ?: $team); ?>
                </p>
            </td></tr>
        <?php } ?>
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
            echo __('Release'); ?>">
        </span>
    </p>
</form>
<div class="clear"></div>
