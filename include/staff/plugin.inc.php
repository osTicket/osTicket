<?php

$info=array();
if($plugin && $_REQUEST['a']!='add') {
    $form = $plugin->getConfig()->getForm();
    if ($_POST)
        $form->isValid();
    $title = 'Update Plugin';
    $action = 'update';
    $submit_text='Save Changes';
    $info = $plugin->ht;
    $newcount=2;
} else {
    $title = 'Install plugin';
    $action = 'add';
    $submit_text='Install';
    $newcount=4;
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
$form->render();
?>
    </tbody></table>
<p class="centered">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="?"'>
</p>
</form>
