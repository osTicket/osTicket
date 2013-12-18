<?php
if (!$info['title'])
    $info['title'] = Format::htmlchars($user->getName());
?>
<h3><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<div id="user-profile" style="display:<?php echo $forms ? 'none' : 'block'; ?>;margin:5px;">
    <i class="icon-user icon-4x pull-left icon-border"></i>
    <?php
    if ($ticket) { ?>
    <a class="action-button pull-right change-user" style="overflow:inherit"
        href="#tickets/<?php echo $ticket->getId(); ?>/change-user" ><i class="icon-user"></i> Change User</a>
    <?php
    } ?>
    <div><b><a href="#" id="edituser"><i class="icon-edit"></i>&nbsp;<?php
    echo Format::htmlchars($user->getName()->getOriginal()); ?></a></b></div>
    <div>&lt;<?php echo $user->getEmail(); ?>&gt;</div>
    <table style="margin-top: 1em;">
<?php foreach ($user->getDynamicData() as $entry) {
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
    <hr>
    <div class="faded">Last updated <b><?php echo Format::db_datetime($user->getUpdateDate()); ?> </b></div>
</div>
<div id="user-form" style="display:<?php echo $forms ? 'block' : 'none'; ?>;">
<div><p id="msg_info"><i class="icon-info-sign"></i>&nbsp; Please note that updates will be reflected system-wide.</p></div>
<?php
$action = $info['action'] ? $info['action'] : ('#users/'.$user->getId());
if ($ticket && $ticket->getOwnerId() == $user->getId())
    $action = '#tickets/'.$ticket->getId().'/user';
?>
<form method="post" class="user" action="<?php echo $action; ?>">
    <input type="hidden" name="uid" value="<?php echo $user->getId(); ?>" />
    <table width="100%">
    <?php
        if (!$forms) $forms = $user->getForms();
        foreach ($forms as $form)
            $form->render();
    ?>
    </table>
    <hr>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" name="cancel" class="<?php
    echo ($ticket && $user) ? 'cancel' : 'close' ?>"  value="Cancel">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Update User">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>
<script type="text/javascript">
$(function() {
    $('a#edituser').click( function(e) {
        e.preventDefault();
        $('div#user-profile').hide();
        $('div#user-form').fadeIn();
        return false;
     });

    $(document).on('click', 'form.user input.cancel', function (e) {
        e.preventDefault();
        $('div#user-form').hide();
        $('div#user-profile').fadeIn();
        return false;
     });
});
</script>
