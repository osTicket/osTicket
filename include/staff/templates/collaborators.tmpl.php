<h3><?php echo __('Ticket Collaborators'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<?php
if($info && $info['msg']) {
    echo sprintf('<p id="msg_notice" style="padding-top:2px;">%s</p>', $info['msg']);
} ?>
<hr/>
<?php
if(($users=$ticket->getCollaborators())) {?>
<div id="manage_collaborators">
<form method="post" class="collaborators" action="#tickets/<?php echo $ticket->getId(); ?>/collaborators">
    <table border="0" cellspacing="1" cellpadding="1" width="100%">
    <?php
    foreach($users as $user) {
        $checked = $user->isActive() ? 'checked="checked"' : '';
        echo sprintf('<tr>
                        <td>
                            <input type="checkbox" name="cid[]" id="c%d" value="%d" %s>
                            <a class="collaborator" href="#collaborators/%d/view">%s</a>
                            <span class="faded"><em>%s</em></span></td>
                        <td width="10">
                            <input type="hidden" name="del[]" id="d%d" value="">
                            <a class="remove" href="#d%d">&times;</a></td>
                        <td width="30">&nbsp;</td>
                    </tr>',
                    $user->getId(),
                    $user->getId(),
                    $checked,
                    $user->getId(),
                    Format::htmlchars($user->getName()),
                    $user->getEmail(),
                    $user->getId(),
                    $user->getId());
    }
    ?>
    </table>
    <hr style="margin-top:1em"/>
    <div><a class="collaborator"
        href="#tickets/<?php echo $ticket->getId(); ?>/add-collaborator"
        ><i class="icon-plus-sign"></i> <?php echo __('Add New Collaborator'); ?></a></div>
    <div id="savewarning" style="display:none; padding-top:2px;"><p
    id="msg_warning"><?php echo __('You have made changes that you need to save.'); ?></p></div>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" value="<?php echo __('Cancel'); ?>" class="close">
        </span>
        <span class="buttons pull-right">
        <input type="submit" value="<?php echo __('Save Changes'); ?>">
        </span>
     </p>
</form>
<div class="clear"></div>
</div>
<?php
} else {
    echo __("Bro, not sure how you got here!");
}

if ($_POST && $ticket && $ticket->getNumCollaborators()) {
    $recipients = sprintf(__('Recipients (%d of %d)'),
          $ticket->getNumActiveCollaborators(),
          $ticket->getNumCollaborators());
    ?>
    <script type="text/javascript">
        $(function() {
            $('#emailcollab').show();
            $('#recipients').html('<?php echo $recipients; ?>');
            });
    </script>
<?php
}
?>

<script type="text/javascript">
$(function() {

    $(document).on('click', 'form.collaborators a#addcollaborator', function (e) {
        e.preventDefault();
        $('div#manage_collaborators').hide();
        $('div#add_collaborator').fadeIn();
        return false;
     });

    $(document).on('click', 'form.collaborators a.remove', function (e) {
        e.preventDefault();
        var fObj = $(this).closest('form');
        $('input'+$(this).attr('href'))
            .val($(this).attr('href').substr(2))
            .trigger('change');
        $(this).closest('tr').addClass('strike');

        return false;
     });

    $(document).on('change', 'form.collaborators input:checkbox, input[name="del[]"]', function (e) {
       var fObj = $(this).closest('form');
       $('div#savewarning', fObj).fadeIn();
       $('input:submit', fObj).css('color', 'red');
     });

    $(document).on('click', 'form.collaborators input:reset', function(e) {
        var fObj = $(this).closest('form');
        fObj.find('input[name="del[]"]').val('');
        fObj.find('tr').removeClass('strike');
        $('div#savewarning', fObj).hide();
        $('input:submit', fObj).removeAttr('style');
    });

    $(document).on('click', 'form.collaborators input.cancel', function (e) {
        e.preventDefault();
        var $elem = $(this);

        if($elem.attr('data-href')) {
            var href = $elem.data('href').substr(1);
            $('.dialog.collaborators .body').load('ajax.php/'+href, function () {
                });
        } else {

            $('div#manage_collaborators').show();
            $('div#add_collaborator').hide();
        }
        return false;
    });

});
</script>
