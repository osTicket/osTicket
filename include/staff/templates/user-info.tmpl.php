<h3><?php echo $user->getFullName() ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<br>
<div><p id="msg_info"><i class="icon-info-sign"></i> Please note that updates will be reflected system-wide.</p></div>
<hr/>
<form method="post" action="ajax.php/form/user-info/<?php
        echo $user->get('id'); ?>" onsubmit="javascript:
        var form = $(this);
        var dialog = form.closest('.dialog');
        $.post(this.action, form.serialize(), function(data, status, xhr) {
            if(xhr && xhr.status == 201) {
                var user = $.parseJSON(xhr.responseText);
                $('#user-'+user.id+'-name').html(user.name);
                $('div.body', dialog).empty();
                dialog.hide();
                $('#overlay').hide();
            } else {
                $('div.body', dialog).html(data);
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
