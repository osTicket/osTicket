<h3>User Information &mdash; <?php echo $user->getFullName() ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<form method="post" action="ajax.php/form/user-info/<?php
        echo $user->get('id'); ?>" onsubmit="javascript:
        var form = $(this);
        $.post(this.action, form.serialize(), function(data, status, xhr) {
            if (!data.length) {
                form.closest('.dialog').hide();
                $('#overlay').hide();
                location.reload();
            } else {
                form.closest('.dialog').empty().append(data);
            }
        });
        return false;
        ">
    <table width="100%">
    <?php
        echo csrf_token();
        foreach ($custom as $form)
            $form->render();
    ?>
    </table>
    <hr style="margin-top:3em"/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" value="Cancel" class="close">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Save">
        </span>
     </p>
</form>
<div class="clear"></div>
