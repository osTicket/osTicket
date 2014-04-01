<?php

if (!$info['title'])
    $info['title'] = 'Delete User: '.Format::htmlchars($user->getName());

$info['warn'] = 'Deleted users and tickets CANNOT be recovered';

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

<div id="user-profile" style="margin:5px;">
    <i class="icon-user icon-4x pull-left icon-border"></i>
    <?php
    // TODO: Implement change of ownership
    if (0 && $user->getNumTickets()) { ?>
    <a class="action-button pull-right change-user" style="overflow:inherit"
        href="#users/<?php echo $user->getId(); ?>/replace" ><i
        class="icon-user"></i> Change Tickets Ownership</a>
    <?php
    } ?>
    <div><b> <?php echo Format::htmlchars($user->getName()->getOriginal()); ?></b></div>
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
    <form method="post" class="user"
        action="#users/<?php echo $user->getId(); ?>/delete">
        <input type="hidden" name="id" value="<?php echo $user->getId(); ?>" />

    <?php
    if (($num=$user->tickets->count())) {
        echo sprintf('<div><input type="checkbox" name="deletetickets" value="1" >
            <strong>Delete <a href="tickets.php?a=search&uid=%d" target="_blank">%d
            %s</a> and any associated attachments and data.</strong></div><hr>',
            $user->getId(),
            $num,
            ($num >1) ? 'tickets' : 'ticket'
            );
    }
    ?>
        <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" name="cancel" class="close"
                value="No, Cancel">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Yes, Delete User">
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
