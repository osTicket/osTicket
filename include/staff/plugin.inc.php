<?php

$info=array();
if($plugin && $_REQUEST['a']!='add') {
    $config = $plugin->getConfig();
    if ($config)
        $form = $config->getForm();
    if ($_POST)
        $form->isValid();
    $title = 'Update Plugin';
    $action = 'update';
    $submit_text='Save Changes';
    $info = $plugin->ht;
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>

<form action="?id=<?php echo urlencode($_REQUEST['id']); ?>" method="post" id="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="<?php echo $action; ?>">
    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
    <h2>Manage Plugin
        <br/><small><?php echo $plugin->getName(); ?></small></h2>

    <h3>Configuration</h3>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
<?php
if ($form)
    $form->render();
else { ?>
    <tr><th>This plugin has no configurable settings<br>
        <em>Every plugin should be so easy to use</em></th></tr>
<?php }
?>
    </tbody></table>
<p class="centered">
<?php if ($form) { ?>
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Reset">
<?php } ?>
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="?"'>
</p>
</form>
