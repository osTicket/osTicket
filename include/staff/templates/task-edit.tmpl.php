<?php
global $cfg;

if (!$info['title'])
    $info['title'] = sprintf(__('%s Tasks #%s'),
            __('Edit'), $task->getNumber()
            );

$action = $info['action'] ?: ('#tasks/'.$task->getId().'/edit');

?>
<div id="task-form">
<h3><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<?php

if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warning']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warning']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<div id="edit-task-form" style="display:block;">
<form method="post" class="task" action="<?php echo $action; ?>">

    <table class="form_table dynamic-forms" width="100%" border="0" cellspacing="0" cellpadding="2">
            <?php if ($forms)
                foreach ($forms as $form) {
                    $form->render(true, false, array('mode'=>'edit','width'=>160,'entry'=>$form));
                    print $form->getForm()->getMedia();
            } ?>
    </table>
    <table class="form_table dynamic-forms" width="100%" border="0" cellspacing="0" cellpadding="2">
        <tr><th colspan=2><em><?php
             echo __('Task Visibility & Assignment'); ?></em></th></tr>
    <?php
        $iform = $iform ?: TaskForm::getInternalForm();
        foreach ($iform->getFields()  as $name=>$field) {
            if (!$field->isEditable()) continue;
         ?>
        <tr>
            <td class="multi-line <?php if ($field->get('required')) echo 'required';
            ?>" style="min-width:120px;" width="160">
            <?php echo Format::htmlchars($field->get('label')); ?>:</td>
            <td>
            <fieldset id="field<?php echo $field->getWidget()->id;
                ?>" <?php if (!$field->isVisible()) echo 'style="display:none;"'; ?>>
                <?php echo $field->render(); ?>
                <?php if ($field->get('required')) { ?>
                <span class="error">*</span>
                <?php
                }
                foreach ($field->errors() as $E) {
                    ?><div class="error"><?php echo $E; ?></div><?php
                } ?>
            </fieldset>
          </td>
        </tr>
        <?php
        }
       ?>
    </table>
    <table class="form_table" width="100%" border="0" cellspacing="0" cellpadding="2">
        <tbody>
            <tr>
                <th colspan="2">
                    <em><strong><?php echo __('Internal Note');?></strong>: <?php
                     echo __('Reason for editing the task (optional');?> <font class="error">&nbsp;<?php echo $errors['note'];?></font></em>
                </th>
            </tr>
            <tr>
                <td colspan="2">
                    <textarea class="richtext no-bar" name="note" cols="21"
                        rows="6" style="width:80%;"><?php echo $info['note'];
                        ?></textarea>
                </td>
            </tr>
        </tbody>
    </table>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="close"
                value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Update'); ?>">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>
</div>
