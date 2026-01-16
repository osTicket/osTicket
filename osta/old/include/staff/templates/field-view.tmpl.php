<?php
global $cfg;

$options = array('template' => 'simple', 'form_id' => $form->getId());

?>
<div id="inline-edit-p1">
<h3 class="drag-handle"><?php echo $info[':title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warn']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warn']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} elseif ($info['notice']) {
   echo sprintf('<p id="msg_info"><i class="icon-info-sign"></i> %s</p>',
           $info['notice']);
}

$action = $info[':action'] ?: ('#');
?>
<div style="display:block; margin:5px;">
<form method="post" name="inline_update" id="inline_update"
    class="mass-action"
    action="<?php echo $action; ?>">
    <div>
        <?php echo $field->getAnswer()->display(); ?>
    </div>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" name="cancel" class="close"
            value="<?php echo __('Cancel'); ?>">
        </span>
        <?php if ($ticket->checkStaffPerm($thisstaff, Ticket::PERM_EDIT)) { ?>
        <span class="buttons pull-right">
            <input id="edit" type="submit" value="<?php
            echo $verb ?: __('Edit'); ?>">
        </span>
        <?php } ?>
     </p>
</form>
</div>
</div>
<div id="inline-edit-next" style="display:none;">
</div>
<div class="clear"></div>
<script type="text/javascript">
$(function() {
    $(document).on('click.inline-edit', 'form#inline_update input#edit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        thisHref = $('form#inline_update').attr('action');
        $('div#inline-edit-next').empty();
        var url = thisHref.replace("#", "ajax.php/");
        var $container = $('div#inline-edit-next');
        $container.load(url, function () {
            $('div#inline-edit-p1').hide();
        }).show();
        return false;
     });
});
</script>
