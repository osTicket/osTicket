<?php

if (!$info['title'])
    $info['title'] = __('New Task');

$namespace = 'task.add';
if ($ticket)
    $namespace = sprintf('ticket.%d.task', $ticket->getId());

?>
<div id="task-form">
<h3 class="drag-handle"><?php echo $info['title']; ?></h3>
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
<div id="new-task-form" style="display:block;">
<form method="post" class="org" action="<?php echo $info['action'] ?: '#tasks/add'; ?>">
    <table width="100%" class="fixed">
    <?php
        $form = $form ?: TaskForm::getInstance();
        $form->render(true,
                __('Create New Task'),
                array('draft-namespace' => $namespace)
                );
    ?>
        <tr><th colspan=2><em><?php
             echo __('Task Visibility & Assignment'); ?></em></th></tr>
    <?php
        $iform = $iform ?: TaskForm::getInternalForm();
        foreach ($iform->getFields()  as $name=>$field) { ?>
        <tr>
            <td class="multi-line <?php if ($field->get('required')) echo 'required';
            ?>" style="min-width:120px;" >
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
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="close"
                value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Create Task'); ?>">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>
</div>
