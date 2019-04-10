<?php
if (!$_REQUEST['mode']) { ?>
<h3 class="drag-handle"><?php echo __('Email Recipients'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<?php
} ?>
<p>
<table>
<?php
$recipients = Format::htmlchars($recipients);
 foreach ($recipients as $k => $v) {
    echo sprintf('<tr><td nowrap width="5" valign="top"><b>%s</b>:</td><td>%s</td></tr>',
            ucfirst($k),
            is_array($v) ? implode('<br>', $v) : $v
             );
 }
 ?>
</table>
<?php
if (!$_REQUEST['mode']) {?>
<hr>
<p class="full-width">
    <span class="buttons pull-right">
        <input type="button" name="cancel" class="close"
            value="<?php echo __('Close'); ?>">
    </span>
</p>
<?php
} ?>
