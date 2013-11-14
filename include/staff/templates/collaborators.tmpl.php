<h3>Ticket Collaborators</h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<?php
if($info && $info['msg']) {
    echo sprintf('<p id="msg_notice" style="padding-top:2px;">%s</p>', $info['msg']);
} ?>
<hr/>
<?php
if(($users=$ticket->getCollaborators())) {?>
<div id="manage_collaborators" <?php echo $form?  'style="display:none;"' : ''; ?>>
<form method="post" class="collaborators" action="#tickets/<?php echo $ticket->getId(); ?>/collaborators">
    <table border="0" cellspacing="1" cellpadding="1" width="100%">
    <?php
    foreach($users as $user) {
        $checked = $user->isActive() ? 'checked="checked"' : '';
        echo sprintf('<tr>
                        <td>
                            <input type="checkbox" name="cid[]" id="c%d" value="%d" %s>
                            <a class="editcollaborator" href="#collaborators/%d/view">%s</a>
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
    <div><a id="addcollaborator" href="#" >Add New Collaborator</a></div>
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
}
?>
<div id="add_collaborator" <?php echo ($users && !$form)? 'style="display:none;"' : ''; ?>>
<?php
if($info && $info['add_error']) { ?>
<p id="msg_error"><?php echo $info['add_error']; ?></p>
<?php
} ?>
<div>Please complete the form below to add a new collaborator.</div>
<form method="post" class="collaborators" action="#tickets/<?php echo $ticket->getId(); ?>/collaborators/add">
    <table width="100%">
    <?php
        if(!$form) $form = UserForm::getInstance();
        $form->render(); ?>
    </table>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" name="cancel" class="<?php echo !$users ?  'close': 'cancel'; ?>"  value="Cancel">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Add">
        </span>
     </p>
</form>
<div class="clear"></div>
</div>
