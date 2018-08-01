<?php
global $cfg;

if (!$info['title'])
    $info['title'] = sprintf(__('%s Tasks #%s'),
            __('Edit'), $task->getNumber()
            );

$action = $info['action'] ?: ('#tasks/'.$task->getId().'/edit');

$namespace = sprintf('task.%d.edit', $task->getId());

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
<div id="edit-task-form" style="display:block;">
<form method="post" class="task" action="<?php echo $action; ?>">
    <div>
    <?php
    if ($forms) {
        foreach ($forms as $form)
            echo $form->getForm(false, array('mode' => 'edit'))->asTable(
                    __('Task Information'),
                    array(
                        'draft-namespace' => $namespace,
                        )
                    );
    }
    ?>
    </div>
    <div><strong><?php echo __('Internal Note');?></strong>:
     <font class="error">&nbsp;<?php echo $errors['note'];?></font></div>
    <div>
        <textarea class="richtext no-bar" name="note" cols="21" rows="6"
            style="width:80%;"
            placeholder="<?php echo __('Reason for editing the task (optional)'); ?>"
            >
            <?php echo $info['note'];
            ?></textarea>
    </div>
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
