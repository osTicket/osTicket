<div id="the-lookup-form">
<h3><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<div><p id="msg_info"><i class="icon-info-sign"></i>&nbsp; Search existing users or add a new user.</p></div>
<div style="margin-bottom:10px;"><input type="text" class="search-input" style="width:100%;" placeholder="Search by email, phone or name" id="user-search" autocorrect="off" autocomplete="off"/></div>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<div id="selected-user-info" style="display:<?php echo $user ? 'block' :'none'; ?>;margin:5px;">
<form method="post" class="user" action="<?php echo $info['action'] ?  $info['action'] : '#users/lookup'; ?>">
    <input type="hidden" id="user-id" name="id" value="<?php echo $user ? $user->getId() : 0; ?>"/>
    <i class="icon-user icon-4x pull-left icon-border"></i>
    <a class="action-button pull-right" style="overflow:inherit"
        id="unselect-user"  href="#"><i class="icon-remove"></i> Add New User</a>
    <div><strong id="user-name"><?php echo $user ? Format::htmlchars($user->getName()->getOriginal()) : ''; ?></strong></div>
    <div>&lt;<span id="user-email"><?php echo $user ? $user->getEmail() : ''; ?></span>&gt;</div>
<?php if ($user) { ?>
    <table style="margin-top: 1em;">
<?php foreach ($user->getDynamicData() as $entry) { ?>
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
<?php } ?>
    <div class="clear"></div>
    <hr>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="button" name="cancel" class="close"  value="Cancel">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Continue">
        </span>
     </p>
</form>
</div>
<div id="new-user-form" style="display:<?php echo $user ? 'none' :'block'; ?>;">
<form method="post" class="user" action="<?php echo $info['action'] ?: '#users/lookup/form'; ?>">
    <table width="100%" class="fixed">
    <?php
        if(!$form) $form = UserForm::getInstance();
        $form->render(true, 'Create New User'); ?>
    </table>
    <hr>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" name="cancel" class="<?php echo $user ? 'cancel' : 'close' ?>"  value="Cancel">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Add User">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>
</div>
<script type="text/javascript">
$(function() {
    var last_req;
    $('#user-search').typeahead({
        source: function (typeahead, query) {
            if (last_req) last_req.abort();
            last_req = $.ajax({
                url: "ajax.php/users?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            $('#the-lookup-form').load(
                '<?php echo $info['onselect']? $info['onselect']: "ajax.php/users/select/"; ?>'+encodeURIComponent(obj.id)
            );
        },
        property: "/bin/true"
    });

    $('a#unselect-user').click( function(e) {
        e.preventDefault();
        $('div#selected-user-info').hide();
        $('div#new-user-form').fadeIn({start: function(){ $('#user-search').focus(); }});
        return false;
     });

    $(document).on('click', 'form.user input.cancel', function (e) {
        e.preventDefault();
        $('div#new-user-form').hide();
        $('div#selected-user-info').fadeIn({start: function(){ $('#user-search').focus(); }});
        return false;
     });
});
</script>
