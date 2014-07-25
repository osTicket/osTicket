<?php

$info=array();
if($plugin && $_REQUEST['a']!='add') {
    $config = $plugin->getConfig();
    if (!($page = $config->hasCustomConfig())) {
        if ($config)
            $form = $config->getForm();
        if ($form && $_POST)
            $form->isValid();
    }
    $title = __('Update Plugin');
    $action = 'update';
    $submit_text = __('Save Changes');
    $info = $plugin->ht;
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>

<form action="?id=<?php echo urlencode($_REQUEST['id']); ?>" method="post" id="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="<?php echo $action; ?>">
    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
    <h2><?php echo __('Manage Plugin'); ?>
        <br/><small><?php echo $plugin->getName(); ?></small></h2>

    <h3><?php echo __('Configuration'); ?></h3>
<?php
if ($page)
    $config->renderCustomConfig();
elseif ($form) { ?>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
<?php $form->render(); ?>
    </tbody></table>
<?php
}
else { ?>
    <tr><th><?php echo __('This plugin has no configurable settings'); ?><br>
        <em><?php echo __('Every plugin should be so easy to use.'); ?></em></th></tr>
<?php }
?>
<p class="centered">
<?php if ($page || $form) { ?>
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>">
<?php } ?>
    <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>" onclick='window.location.href="?"'>
</p>
</form>
