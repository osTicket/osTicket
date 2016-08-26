<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info = $qs = array();

if ($_REQUEST['a'] == 'add-tpl') {
    if (!$template) {
        $template = new TaskTemplate(array(
            'flags' => TaskTemplate::FLAG_ENABLED,
            'group_id' => $set->getId(),
        ));
    }
    $title=__('Add New Task Template');
    $action='add-template';
    $submit_text=__('Create');
    $info['group_id'] = $set->getId();
    $qs += array('group_id' => $set->getId());
}
else {
    $title=__('Manage Task Template');
    $action='update';
    $submit_text=__('Save Changes');
    $info['id'] = $template->getId();
    $qs += array('id' => $template->getId());
}
?>

<form action="task-templates.php?<?php echo Http::build_query($qs); ?>" method="post" id="save" autocomplete="off">
  <?php csrf_token(); ?>
  <input type="hidden" name="do" value="<?php echo $action; ?>">
  <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
  <input type="hidden" name="group_id" value="<?php echo Format::htmlchars($_REQUEST['group_id']); ?>">
  <input type="hidden" name="tpl_id" value="<?php echo $info['id']; ?>">

  <h2><?php echo $title;
if (isset($template->id)) { ?><small>
      —
<?php if (isset($set)) { ?>
  <a href="task-templates.php?group_id=<?php echo $set->getId(); ?>"
    ><?php echo Format::htmlchars($set->getName()); ?></a> /
<?php
    } ?>
    <?php echo $template->getName(); ?></small>
<?php
} ?>
  </h2>

<?php

// Basic task information (configurable)
$form = $template->getTaskForm($_POST);
if ($_POST) $form->isValid();
echo $form->asTable();

// Visibility and assignment information
$form = $template->getBasicForm($_POST);
if ($_POST) $form->isValid();
echo $form->asTable();

// Extra forms with enable/disable ability
?>
<table class="grid form">
  <caption>
    <?php echo __('Data'); ?>
    <div><small><?php echo __('Attach extra forms to this task'); ?></small></div>
  </caption>
</table>
<?php
$forms = $template->getForms();
include STAFFINC_DIR . 'templates/manage-custom-fields.tmpl.php';
?>

  <p style="text-align:center;">
      <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
      <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
      <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick="window.history.go(-1);">
  </p>
</form>
