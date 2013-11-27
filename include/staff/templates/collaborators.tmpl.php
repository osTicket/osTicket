<h3>Ticket Collaborators</h3>
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
                    $user->getName(),
                    $user->getEmail(),
                    $user->getId(),
                    $user->getId());
    }
    ?>
    </table>
    <hr style="margin-top:1em"/>
    <div><a class="collaborator"
        href="#tickets/<?php echo $ticket->getId(); ?>/add-collaborator" >Add New Collaborator</a></div>
    <div id="savewarning" style="display:none; padding-top:2px;"><p id="msg_warning">You have made changes that you need to save.</p></div>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" value="Done" class="close">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Save Changes">
        </span>
     </p>
</form>
<div class="clear"></div>
</div>
<?php
} else {
    echo "Bro, not sure how you got here!";
}
?>
