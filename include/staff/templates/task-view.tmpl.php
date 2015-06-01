<?php
if (!defined('OSTSCPINC')
    || !$thisstaff || !$task
    || !($role = $thisstaff->getRole($task->getDeptId())))
    die('Invalid path');

$actions = array();

$actions += array(
        'print' => array(
            'href' => sprintf('tasks.php?id=%d&a=print', $task->getId()),
            'class' => 'none',
            'icon' => 'icon-print',
            'label' => __('Print')
        ));

if ($role->hasPerm(Task::PERM_EDIT)) {
    $actions += array(
            'edit' => array(
                'href' => sprintf('#tasks/%d/edit', $task->getId()),
                'icon' => 'icon-edit',
                'dialog' => '{"size":"large"}',
                'label' => __('Edit')
            ));
}

if ($role->hasPerm(Task::PERM_ASSIGN)) {
    $actions += array(
            'assign' => array(
                'href' => sprintf('#tasks/%d/assign', $task->getId()),
                'icon' => 'icon-user',
                'label' => $task->isAssigned() ? __('Reassign') : __('Assign')
            ));
}

if ($role->hasPerm(Task::PERM_TRANSFER)) {
    $actions += array(
            'transfer' => array(
                'href' => sprintf('#tasks/%d/transfer', $task->getId()),
                'icon' => 'icon-share',
                'label' => __('Transfer')
            ));
}


if ($role->hasPerm(Task::PERM_DELETE)) {
    $actions += array(
            'delete' => array(
                'href' => sprintf('#tasks/%d/delete', $task->getId()),
                'icon' => 'icon-trash',
                'label' => __('Delete')
            ));
}

$info=($_POST && $errors)?Format::input($_POST):array();

$id    = $task->getId();
if ($task->isOverdue())
    $warn.='&nbsp;&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

?>
<table width="940" cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td width="<?php echo $ticket ? '70%' : '20%'; ?>" class="has_bottom_border">
            <?php
            if ($ticket) { ?>
                <strong>
                <a id="tasks" href="#"> All Tasks (<?php echo $ticket->getNumTasks(); ?>)</a>
                &nbsp;/&nbsp;
                <?php echo $task->getTitle(); ?>
                &nbsp;&mdash;&nbsp;
                <a
                    id="reload-task" class="preview"
                    <?php
                    echo ' class="preview" ';
                    echo sprintf('data-preview="#tasks/%d/preview" ', $task->getId());
                    echo sprintf('href="#tasks/%d" ', $task->getId());
                    ?>
                ><?php
                echo sprintf('#%s', $task->getNumber()); ?></a>
                </strong>
            <?php
            } else { ?>
               <h2>
                <a
                id="reload-task"
                href="tasks.php?id=<?php echo $task->getId(); ?>"
                href="tasks.php?id=<?php echo $task->getId(); ?>"
                ><i class="icon-refresh"></i> <?php
                echo sprintf(__('Task #%s'), $task->getNumber()); ?></a>
               </h2>
            <?php
            } ?>
        </td>
        <td width="auto" class="flush-right has_bottom_border">
            <?php
            if ($ticket) { ?>
            <span
                class="action-button"
                data-dropdown="#action-dropdown-task-options">
                <i class="icon-caret-down pull-right"></i>
                <a class="task-action"
                    href="#task-options"><i
                    class="icon-reorder"></i> <?php
                    echo __('Actions'); ?></a>
            </span>
            <div id="action-dropdown-task-options"
                class="action-dropdown anchor-right">
                <ul>
            <?php foreach ($actions as $a => $action) { ?>
                    <li>
                        <a class="no-pjax <?php
                            echo $action['class'] ?: 'task-action'; ?>"
                            <?php
                            if ($action['dialog'])
                                echo sprintf("data-dialog='%s'", $action['dialog']);
                            if ($action['redirect'])
                                echo sprintf("data-redirect='%s'", $action['redirect']);
                            ?>
                            href="<?php echo $action['href']; ?>"
                            ><i class="<?php
                            echo $action['icon'] ?: 'icon-tag'; ?>"></i> <?php
                            echo  $action['label']; ?></a>
                    </li>
                <?php
                } ?>
                </ul>
            </div>
            <?php
           } else {
                foreach ($actions as $action) {?>
                <a class="action-button <?php
                    echo $action['class'] ?: 'task-action'; ?>"
                    <?php
                    if ($action['dialog'])
                        echo sprintf("data-dialog='%s'", $action['dialog']);
                    ?>
                    href="<?php echo $action['href']; ?>"><i
                    class="<?php
                    echo $action['icon'] ?: 'icon-tag'; ?>"></i> <?php
                    echo $action['label'];
                ?></a>
           <?php
                }
           } ?>
        </td>
    </tr>
</table>

<?php
if (!$ticket) { ?>
    <table class="ticket_info" cellspacing="0" cellpadding="0" width="940" border="0">
        <tr>
            <td width="50%">
                <table border="0" cellspacing="" cellpadding="4" width="100%">
                    <tr>
                        <th width="100"><?php echo __('Status');?>:</th>
                        <td><?php echo $task->getStatus(); ?></td>
                    </tr>

                    <tr>
                        <th><?php echo __('Create Date');?>:</th>
                        <td><?php echo Format::datetime($task->getCreateDate()); ?></td>
                    </tr>
                    <?php
                    if($task->isOpen()){ ?>
                    <tr>
                        <th><?php echo __('Due Date');?>:</th>
                        <td><?php echo $task->duedate ?
                        Format::datetime($task->duedate) : '<span
                        class="faded">&mdash; '.__('None').' &mdash;</span>'; ?></td>
                    </tr>
                    <?php
                    }else { ?>
                    <tr>
                        <th><?php echo __('Close Date');?>:</th>
                        <td><?php echo Format::datetime($task->getCloseDate()); ?></td>
                    </tr>
                    <?php
                    }
                    ?>
                </table>
            </td>
            <td width="50%" style="vertical-align:top">
                <table cellspacing="0" cellpadding="4" width="100%" border="0">

                    <tr>
                        <th><?php echo __('Department');?>:</th>
                        <td><?php echo Format::htmlchars($task->dept->getName()); ?></td>
                    </tr>
                    <?php
                    if ($task->isOpen()) { ?>
                    <tr>
                        <th width="100"><?php echo __('Assigned To');?>:</th>
                        <td>
                            <?php
                            if ($assigned=$task->getAssigned())
                                echo Format::htmlchars($assigned);
                            else
                                echo '<span class="faded">&mdash; '.__('Unassigned').' &mdash;</span>';
                            ?>
                        </td>
                    </tr>
                    <?php
                    } else { ?>
                    <tr>
                        <th width="100"><?php echo __('Closed By');?>:</th>
                        <td>
                            <?php
                            if (($staff = $task->getStaff()))
                                echo Format::htmlchars($staff->getName());
                            else
                                echo '<span class="faded">&mdash; '.__('Unknown').' &mdash;</span>';
                            ?>
                        </td>
                    </tr>
                    <?php
                    } ?>
                    <tr>
                        <th><?php echo __('Collaborators');?>:</th>
                        <td>
                            <?php
                            $collaborators = __('Add Participants');
                            if ($task->getThread()->getNumCollaborators())
                                $collaborators = sprintf(__('Participants (%d)'),
                                        $task->getThread()->getNumCollaborators());

                            echo sprintf('<span><a class="collaborators preview"
                                    href="#thread/%d/collaborators"><span
                                    id="t%d-collaborators">%s</span></a></span>',
                                    $task->getThreadId(),
                                    $task->getThreadId(),
                                    $collaborators);
                           ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br>
    <br>
    <table class="ticket_info" cellspacing="0" cellpadding="0" width="940" border="0">
    <?php
    $idx = 0;
    foreach (DynamicFormEntry::forObject($task->getId(),
                ObjectModel::OBJECT_TYPE_TASK) as $form) {
        $answers = $form->getAnswers()->exclude(Q::any(array(
            'field__flags__hasbit' => DynamicFormField::FLAG_EXT_STORED
        )));
        if (!$answers || count($answers) == 0)
            continue;

        ?>
            <tr>
            <td colspan="2">
                <table cellspacing="0" cellpadding="4" width="100%" border="0">
                <?php foreach($answers as $a) {
                    if (!($v = $a->display())) continue; ?>
                    <tr>
                        <th width="100"><?php
                            echo $a->getField()->get('label');
                        ?>:</th>
                        <td><?php
                            echo $v;
                        ?></td>
                    </tr>
                    <?php
                } ?>
                </table>
            </td>
            </tr>
        <?php
        $idx++;
    } ?>
    </table>
<?php
} ?>
<div class="clear"></div>
<div id="task_thread_container">
    <div id="task_thread_content" data-thread-id="<?php echo
    $task->getThread()->getId(); ?>" class="tab_content">
    <?php
    $task->getThread()->render(array('M', 'R', 'N'));
    ?>
   </div>
</div>
<div class="clear" style="padding-bottom:10px;"></div>
<?php if($errors['err']) { ?>
    <div id="msg_error"><?php echo $errors['err']; ?></div>
<?php }elseif($msg) { ?>
    <div id="msg_notice"><?php echo $msg; ?></div>
<?php }elseif($warn) { ?>
    <div id="msg_warning"><?php echo $warn; ?></div>
<?php }

if ($ticket)
    $action = sprintf('#tickets/%d/tasks/%d',
            $ticket->getId(), $task->getId());
else
    $action = 'tasks.php?id='.$task->getId();
?>
<div id="response_options">
    <ul class="tabs"></ul>
    <form id="<?php echo $ticket? 'ticket_task_note': 'task_note'; ?>"
        action="<?php echo $action; ?>"
        name="task_note"
        method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $task->getId(); ?>">
        <input type="hidden" name="a" value="postnote">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td>
                    <div>
                        <div class="faded" style="padding-left:0.15em"><?php
                        echo __('Note title - summary of the note (optional)'); ?></div>
                        <input type="text" name="title" id="title" size="60" value="<?php echo $info['title']; ?>" >
                        <br/>
                        <span class="error">&nbsp;<?php echo $errors['title']; ?></span>
                    </div>
                    <div>
                        <label><strong><?php echo __('Internal Note'); ?></strong><span class='error'>&nbsp;* <?php echo $errors['note']; ?></span></label>
                    </div>
                    <textarea name="note" id="internal_note" cols="80"
                        placeholder="<?php echo __('Note details'); ?>"
                        rows="9" wrap="soft" data-draft-namespace="task.note"
                        data-draft-object-id="<?php echo $task->getId(); ?>"
                        class="richtext ifhtml draft draft-delete"><?php
                        echo $info['note'];
                        ?></textarea>
                    <div class="attachments">
                    <?php
                        if ($note_form)
                            print $note_form->getField('attachments')->render();
                    ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div><?php echo __('Task Status');?>
                        <span class="faded"> - </span>
                        <select  name="task_status">
                            <option value="1" <?php
                                echo $task->isOpen() ?
                                'selected="selected"': ''; ?>> <?php
                                echo _('Open'); ?></option>
                            <option value="0" <?php
                                echo $task->isClosed() ?
                                'selected="selected"': ''; ?>> <?php
                                echo _('Closed'); ?></option>
                        </select>
                        &nbsp;<span class='error'><?php echo
                        $errors['task_status']; ?></span>
                    </div>
                </td>
            </tr>
        </table>
       <p  style="padding-left:165px;">
           <input class="btn_sm" type="submit" value="<?php echo __('Post Note');?>">
           <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
       </p>
    </form>
 </div>

<script type="text/javascript">
$(function() {
    $(document).on('click', 'li.active a#ticket_tasks, a#tasks', function(e) {
        e.preventDefault();
        $('div#task_content').hide().empty();
        $('div#tasks_content').show();
        return false;
     });

    $(document).off('.task-action');
    $(document).on('click.task-action', 'a.task-action', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'
        +$(this).attr('href').substr(1)
        +'?_uid='+new Date().getTime();
        var $options = $(this).data('dialog');
        var $redirect = $(this).data('redirect');
        $.dialog(url, [201], function (xhr) {
            if ($redirect)
                window.location.href = $redirect;
            else
                $.pjax.reload('#pjax-container');
        }, $options);

        return false;
    });

    $(document).off('.tf');
    $(document).on('submit.tf', 'form#ticket_task_note', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $container = $('div#task_content');
        $.ajax({
            type:  $form.attr('method'),
            url: 'ajax.php/'+$form.attr('action').substr(1),
            data: $form.serialize(),
            cache: false,
            success: function(resp, status, xhr) {
                $container.html(resp);
                $('#msg_notice, #msg_error',$container)
                .delay(5000)
                .slideUp();
            }
        })
        .done(function() { })
        .fail(function() { });
     });
});
</script>
