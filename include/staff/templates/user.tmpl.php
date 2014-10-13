<?php
if (!isset($info['title']))
    $info['title'] = Format::htmlchars($user->getName());

if ($info['title']) { ?>
<h3><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr>
<?php
} else {
    echo '<div class="clear"></div>';
}
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
        href="#tickets/<?php echo $ticket->getId(); ?>/change-user" ><i class="icon-user"></i>
        <?php echo __('Change User'); ?></a>
    <?php
    } ?>
    <div><b><?php
    echo Format::htmlchars($user->getName()->getOriginal()); ?></b></div>
    <div class="faded">&lt;<?php echo $user->getEmail(); ?>&gt;</div>
    <?php
    if (($org=$user->getOrganization())) { ?>
    <div style="margin-top: 7px;"><?php echo $org->getName(); ?></div>
    <?php
    } ?>

<div class="clear"></div>
<ul class="tabs" style="margin-top:5px">
    <li><a href="#info-tab" class="active"
        ><i class="icon-info-sign"></i>&nbsp;<?php echo __('User'); ?></a></li>
<?php if ($org) { ?>
    <li><a href="#organization-tab"
        ><i class="icon-fixed-width icon-building"></i>&nbsp;<?php echo __('Organization'); ?></a></li>
<?php }
    $ext_id = "U".$user->getId();
    $notes = QuickNote::forUser($user, $org)->all(); ?>
    <li><a href="#notes-tab"
        ><i class="icon-fixed-width icon-pushpin"></i>&nbsp;<?php echo __('Notes'); ?></a></li>
</ul>

<div class="tab_content" id="info-tab">
<div class="floating-options">
    <a href="<?php echo $info['useredit'] ?: '#'; ?>" id="edituser" class="action" title="<?php echo __('Edit'); ?>"><i class="icon-edit"></i></a>
    <a href="users.php?id=<?php echo $user->getId(); ?>" title="<?php
        echo __('Manage User'); ?>" class="action"><i class="icon-share"></i></a>
</div>
    <table class="custom-info" width="100%">
<?php foreach ($user->getDynamicData() as $entry) {
?>
    <tr><th colspan="2"><strong><?php
         echo $entry->getForm()->get('title'); ?></strong></td></tr>
<?php foreach ($entry->getAnswers() as $a) { ?>
    <tr><td style="width:30%;"><?php echo Format::htmlchars($a->getField()->get('label'));
         ?>:</td>
    <td><?php echo $a->display(); ?></td>
    </tr>
<?php }
}
?>
    </table>
</div>

<?php if ($org) { ?>
<div class="tab_content" id="organization-tab" style="display:none">
<div class="floating-options">
    <a href="orgs.php?id=<?php echo $org->getId(); ?>" title="<?php
    echo __('Manage Organization'); ?>" class="action"><i class="icon-share"></i></a>
</div>
    <table class="custom-info" width="100%">
<?php foreach ($org->getDynamicData() as $entry) {
?>
    <tr><th colspan="2"><strong><?php
         echo $entry->getForm()->get('title'); ?></strong></td></tr>
<?php foreach ($entry->getAnswers() as $a) { ?>
    <tr><td style="width:30%"><?php echo Format::htmlchars($a->getField()->get('label'));
         ?>:</td>
    <td><?php echo $a->display(); ?></td>
    </tr>
<?php }
}
?>
    </table>
</div>
<?php } # endif ($org) ?>

<div class="tab_content" id="notes-tab" style="display:none">
<?php $show_options = true;
foreach ($notes as $note)
    include STAFFINC_DIR . 'templates/note.tmpl.php';
?>
<div id="new-note-box">
<div class="quicknote no-options" id="new-note"
    data-url="users/<?php echo $user->getId(); ?>/note">
<div class="body">
    <a href="#"><i class="icon-plus icon-large"></i> &nbsp;
    <?php echo __('Click to create a new note'); ?></a>
</div>
</div>
</div>
</div>

</div>
<div id="user-form" style="display:<?php echo $forms ? 'block' : 'none'; ?>;">
<div><p id="msg_info"><i class="icon-info-sign"></i>&nbsp; <?php echo __(
'Please note that updates will be reflected system-wide.'
); ?></p></div>
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
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="<?php
    echo ($ticket && $user) ? 'cancel' : 'close' ?>"  value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Update User'); ?>">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>
<script type="text/javascript">
$(function() {
    $('a#edituser').click( function(e) {
        e.preventDefault();
        if ($(this).attr('href').length > 1) {
            var url = 'ajax.php/'+$(this).attr('href').substr(1);
            $.dialog(url, [201, 204], function (xhr) {
                window.location.href = window.location.href;
            }, {
                onshow: function() { $('#user-search').focus(); }
            });
        } else {
            $('div#user-profile').hide();
            $('div#user-form').fadeIn();
        }

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
