<h3>Update Collaborator: <?php echo $collaborator->getName(); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<?php
if($errors && $errors['err']) {
    echo sprintf('<div><p id="msg_error">%s</p></div>', $errors['err']);
} ?>
<hr/>
<div>
<div><p id="msg_info"><i class="icon-info-sign"></i> Please note that updates will be reflected system-wide.</p></div>
<form method="post" class="collaborators" action="#collaborators/<?php echo $collaborator->getId(); ?>">
    <table width="100%">
    <?php
        if(!$forms) $forms =  $collaborator->getForms();
        foreach($forms as$form)
            $form->render(); ?>
    </table>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" name="cancel" class="cancel"
                data-href="#tickets/<?php echo $collaborator->getTicketId(); ?>/collaborators/manage" value="Cancel">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Update">
        </span>
     </p>
</form>
<div class="clear"></div>
</div>
