<?php

if (!$info['title'])
    $info['title'] = sprintf(__('Delete %s'), Format::htmlchars($org->getName()));

$info['warn'] = __('Deleted organization CANNOT be recovered');

?>
<h3><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<?php

if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warn']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warn']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>

<div id="org-profile" style="margin:5px;">
    <i class="icon-group icon-4x pull-left icon-border"></i>
    <div><b> <?php echo Format::htmlchars($org->getName()); ?></b></div>
    <table style="margin-top: 1em;">
<?php foreach ($org->getDynamicData() as $entry) {
?>
    <tr><td colspan="2" style="border-bottom: 1px dotted black"><strong><?php
         echo $entry->getForm()->get('title'); ?></strong></td></tr>
<?php foreach ($entry->getAnswers() as $a) { ?>
    <tr style="vertical-align:top"><td style="width:30%;border-bottom: 1px dotted #ccc"><?php echo Format::htmlchars($a->getField()->get('label'));
         ?>:</td>
    <td style="border-bottom: 1px dotted #ccc"><?php echo $a->display(); ?></td>
    </tr>
<?php }
}
?>
    </table>
    <div class="clear"></div>
    <?php
    if (($users=$org->users->count())) { ?>
    <hr>
    <div>&nbsp;<strong><?php echo sprintf(__(
            '%s assigned to this organization will be orphaned.'),
            sprintf(_N('One user', '%d users', $users), $users)); ?></strong></div>
    <?php
    } ?>
    <hr>
    <form method="delete" class="org"
        action="#orgs/<?php echo $org->getId(); ?>/delete">
        <input type="hidden" name="id" value="<?php echo $org->getId(); ?>" />
        <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="close"
                value="<?php echo __('No, Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Yes, Delete'); ?>">
        </span>
        </p>
    </form>
</div>
<div class="clear"></div>
