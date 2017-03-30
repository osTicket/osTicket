<?php
if (!defined('OSTSCPINC')
    || !$thisstaff || !$task
    || !($role = $thisstaff->getRole($task->getDeptId())))
    die('Invalid path');

global $cfg;

$id = $task->getId();
$dept = $task->getDept();
$thread = $task->getThread();

$iscloseable = $task->isCloseable();
$canClose = ($role->hasPerm(TaskModel::PERM_CLOSE) && $iscloseable === true);
$actions = array();

if ($task->isOpen() && $role->hasPerm(Task::PERM_ASSIGN)) {

    if ($task->getStaffId() != $thisstaff->getId()
            && (!$dept->assignMembersOnly()
                || $dept->isMember($thisstaff))) {
        $actions += array(
                'claim' => array(
                    'href' => sprintf('#tasks/%d/claim', $task->getId()),
                    'icon' => 'icon-user',
                    'label' => __('Claim'),
                    'redirect' => 'tasks.php'
                ));
    }

    $actions += array(
            'assign/agents' => array(
                'href' => sprintf('#tasks/%d/assign/agents', $task->getId()),
                'icon' => 'icon-user',
                'label' => __('Assign to Agent'),
                'redirect' => 'tasks.php'
            ));

    $actions += array(
            'assign/teams' => array(
                'href' => sprintf('#tasks/%d/assign/teams', $task->getId()),
                'icon' => 'icon-user',
                'label' => __('Assign to Team'),
                'redirect' => 'tasks.php'
            ));
}

if ($role->hasPerm(Task::PERM_TRANSFER)) {
    $actions += array(
            'transfer' => array(
                'href' => sprintf('#tasks/%d/transfer', $task->getId()),
                'icon' => 'icon-share',
                'label' => __('Transfer'),
                'redirect' => 'tasks.php'
            ));
}

$actions += array(
        'print' => array(
            'href' => sprintf('tasks.php?id=%d&a=print', $task->getId()),
            'class' => 'no-pjax',
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

if ($role->hasPerm(Task::PERM_DELETE)) {
    $actions += array(
            'delete' => array(
                'href' => sprintf('#tasks/%d/delete', $task->getId()),
                'icon' => 'icon-trash',
                'class' => 'red button',
                'label' => __('Delete'),
                'redirect' => 'tasks.php'
            ));
}

$info=($_POST && $errors)?Format::input($_POST):array();

if ($task->isOverdue())
    $warn.='&nbsp;&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

?>
<div>
    <div class="sticky bar">
       <div class="content">
        <div class="pull-left flush-left">
            <?php
            if ($ticket) { ?>
                <strong>
                <a id="all-ticket-tasks" href="#">
                <?php
                    echo sprintf(__('All Tasks (%s)'),
                            $ticket->getNumTasks());
                 ?></a>
                &nbsp;/&nbsp;
                <a id="reload-task" class="preview"
                    <?php
                    echo ' class="preview" ';
                    echo sprintf('data-preview="#tasks/%d/preview" ', $task->getId());
                    echo sprintf('href="#tickets/%s/tasks/%d/view" ',
                            $ticket->getId(), $task->getId()
                            );
                    ?>><?php echo sprintf(__('Task #%s'), $task->getNumber()); ?></a>
                </strong>
            <?php
            } else { ?>
               <h2>
                <a  id="reload-task"
                    href="tasks.php?id=<?php echo $task->getId(); ?>"><i
                    class="icon-refresh"></i>&nbsp;<?php
                    echo sprintf(__('Task #%s'), $task->getNumber()); ?></a>
                </h2>
            <?php
            } ?>
        </div>
        <div class="flush-right">
            <?php
            if ($ticket) { ?>
            <a  id="task-view"
                target="_blank"
                class="action-button"
                href="tasks.php?id=<?php
                 echo $task->getId(); ?>"><i class="icon-share"></i> <?php
                            echo __('View Task'); ?></a>
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

                    <?php
                    if ($task->isOpen()) { ?>
                    <li>
                        <a class="no-pjax task-action"
                            href="#tasks/<?php echo $task->getId(); ?>/reopen"><i
                            class="icon-fixed-width icon-undo"></i> <?php
                            echo __('Reopen');?> </a>
                    </li>
                    <?php
                    } else {
                    ?>
                    <li>
                        <a class="no-pjax task-action"
                            href="#tasks/<?php echo $task->getId(); ?>/close"><i
                            class="icon-fixed-width icon-ok-circle"></i> <?php
                            echo __('Close');?> </a>
                    </li>
                    <?php
                    } ?>
                    <?php
                    foreach ($actions as $a => $action) { ?>
                    <li <?php if ($action['class']) echo sprintf("class='%s'", $action['class']); ?> >
                        <a class="no-pjax task-action" <?php
                            if ($action['dialog'])
                                echo sprintf("data-dialog-config='%s'", $action['dialog']);
                            if ($action['redirect'])
                                echo sprintf("data-redirect='%s'", $action['redirect']);
                            ?>
                            href="<?php echo $action['href']; ?>"
                            <?php
                            if (isset($action['href']) &&
                                    $action['href'][0] != '#') {
                                echo 'target="blank"';
                            } ?>
                            ><i class="<?php
                            echo $action['icon'] ?: 'icon-tag'; ?>"></i> <?php
                            echo  $action['label']; ?></a>
                    </li>
                <?php
                } ?>
                </ul>
            </div>
            <?php
           } else { ?>
                <span
                    class="action-button"
                    data-dropdown="#action-dropdown-tasks-status">
                    <i class="icon-caret-down pull-right"></i>
                    <a class="tasks-status-action"
                        href="#statuses"
                        data-placement="bottom"
                        data-toggle="tooltip"
                        title="<?php echo __('Change Status'); ?>"><i
                        class="icon-flag"></i></a>
                </span>
                <div id="action-dropdown-tasks-status"
                    class="action-dropdown anchor-right">
                    <ul>
                        <?php
                        if ($task->isClosed()) { ?>
                        <li>
                            <a class="no-pjax task-action"
                                href="#tasks/<?php echo $task->getId(); ?>/reopen"><i
                                class="icon-fixed-width icon-undo"></i> <?php
                                echo __('Reopen');?> </a>
                        </li>
                        <?php
                        } else {
                        ?>
                        <li>
                            <a class="no-pjax task-action"
                                href="#tasks/<?php echo $task->getId(); ?>/close"><i
                                class="icon-fixed-width icon-ok-circle"></i> <?php
                                echo __('Close');?> </a>
                        </li>
                        <?php
                        } ?>
                    </ul>
                </div>
                <?php
                // Assign
                unset($actions['claim'], $actions['assign/agents'], $actions['assign/teams']);
                if ($task->isOpen() && $role->hasPerm(Task::PERM_ASSIGN)) {?>
                <span class="action-button"
                    data-dropdown="#action-dropdown-assign"
                    data-placement="bottom"
                    data-toggle="tooltip"
                    title=" <?php echo $task->isAssigned() ? __('Reassign') : __('Assign'); ?>"
                    >
                    <i class="icon-caret-down pull-right"></i>
                    <a class="task-action" id="task-assign"
                        data-redirect="tasks.php"
                        href="#tasks/<?php echo $task->getId(); ?>/assign"><i class="icon-user"></i></a>
                </span>
                <div id="action-dropdown-assign" class="action-dropdown anchor-right">
                  <ul>
                    <?php
                    // Agent can claim team assigned ticket
                    if ($task->getStaffId() != $thisstaff->getId()
                            && (!$dept->assignMembersOnly()
                                || $dept->isMember($thisstaff))
                            ) { ?>
                     <li><a class="no-pjax task-action"
                        data-redirect="tasks.php"
                        href="#tasks/<?php echo $task->getId(); ?>/claim"><i
                        class="icon-chevron-sign-down"></i> <?php echo __('Claim'); ?></a>
                    <?php
                    } ?>
                     <li><a class="no-pjax task-action"
                        data-redirect="tasks.php"
                        href="#tasks/<?php echo $task->getId(); ?>/assign/agents"><i
                        class="icon-user"></i> <?php echo __('Agent'); ?></a>
                     <li><a class="no-pjax task-action"
                        data-redirect="tasks.php"
                        href="#tasks/<?php echo $task->getId(); ?>/assign/teams"><i
                        class="icon-group"></i> <?php echo __('Team'); ?></a>
                  </ul>
                </div>
                <?php
                } ?>
                <?php
                foreach ($actions as $action) {?>
                <span class="action-button <?php echo $action['class'] ?: ''; ?>">
                    <a class="task-action"
                        <?php
                        if ($action['dialog'])
                            echo sprintf("data-dialog-config='%s'", $action['dialog']);
                        if ($action['redirect'])
                            echo sprintf("data-redirect='%s'", $action['redirect']);
                        ?>
                        href="<?php echo $action['href']; ?>"
                        data-placement="bottom"
                        data-toggle="tooltip"
                        title="<?php echo $action['label']; ?>">
                        <i class="<?php
                        echo $action['icon'] ?: 'icon-tag'; ?>"></i>
                    </a>
                </span>
           <?php
                }
           } ?>
        </div>
    </div>
   </div>
</div>

<div class="clear tixTitle has_bottom_border">
    <h3>
    <?php
        $title = TaskForm::getInstance()->getField('title');
        echo $title->display($task->getTitle());
    ?>
    </h3>
</div>
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
                        <th><?php echo __('Created');?>:</th>
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
                        <th><?php echo __('Completed');?>:</th>
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
            'field__flags__hasbit' => DynamicFormField::FLAG_EXT_STORED,
            'field__name__in' => array('title')
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
    <div id="task_thread_content" class="tab_content">
     <?php
     $task->getThread()->render(array('M', 'R', 'N'),
             array(
                 'mode' => Thread::MODE_STAFF,
                 'container' => 'taskThread',
                 'sort' => $thisstaff->thread_view_order
                 )
             );
     ?>
   </div>
</div>
<div class="clear"></div>
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
<div id="task_response_options" class="<?php echo $ticket ? 'ticket_task_actions' : ''; ?> sticky bar stop actions">
    <ul class="tabs">
        <?php
        if ($role->hasPerm(TaskModel::PERM_REPLY)) { ?>
        <li class="active"><a href="#task_reply"><?php echo __('Post Update');?></a></li>
        <li><a href="#task_note"><?php echo __('Post Internal Note');?></a></li>
        <?php
        }?>
    </ul>
    <?php
    if ($role->hasPerm(TaskModel::PERM_REPLY)) { ?>
    <form id="task_reply" class="tab_content spellcheck save"
        action="<?php echo $action; ?>"
        name="task_reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $task->getId(); ?>">
        <input type="hidden" name="a" value="postreply">
        <input type="hidden" name="lockCode" value="<?php echo ($mylock) ? $mylock->getCode() : ''; ?>">
        <span class="error"></span>
        <table style="width:100%" border="0" cellspacing="0" cellpadding="3">
            <tbody id="collab_sec" style="display:table-row-group">
             <tr>
                <td>
                    <input type='checkbox' value='1' name="emailcollab" id="emailcollab"
                        <?php echo ((!$info['emailcollab'] && !$errors) || isset($info['emailcollab']))?'checked="checked"':''; ?>
                        style="display:<?php echo $thread->getNumCollaborators() ? 'inline-block': 'none'; ?>;"
                        >
                    <?php
                    $recipients = __('Add Participants');
                    if ($thread->getNumCollaborators())
                        $recipients = sprintf(__('Recipients (%d of %d)'),
                                $thread->getNumActiveCollaborators(),
                                $thread->getNumCollaborators());

                    echo sprintf('<span><a class="collaborators preview"
                            href="#thread/%d/collaborators"><span id="t%d-recipients">%s</span></a></span>',
                            $thread->getId(),
                            $thread->getId(),
                            $recipients);
                   ?>
                </td>
             </tr>
            </tbody>
            <tbody id="update_sec">
            <tr>
                <td>
                    <div class="error"><?php echo $errors['response']; ?></div>
                    <input type="hidden" name="draft_id" value=""/>
                    <textarea name="response" id="task-response" cols="50"
                        data-signature-field="signature" data-dept-id="<?php echo $dept->getId(); ?>"
                        data-signature="<?php
                            echo Format::htmlchars(Format::viewableImages($signature)); ?>"
                        placeholder="<?php echo __( 'Start writing your update here.'); ?>"
                        rows="9" wrap="soft"
                        class="<?php if ($cfg->isRichTextEnabled()) echo 'richtext';
                            ?> draft draft-delete" <?php
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('task.response', $task->getId(), $info['task.response']);
    echo $attrs; ?>><?php echo $draft ?: $info['task.response'];
                    ?></textarea>
                <div id="task_response_form_attachments" class="attachments">
                <?php
                    if ($reply_attachments_form)
                        print $reply_attachments_form->getField('attachments')->render();
                ?>
                </div>
               </td>
            </tr>
            <tr>
                <td>
                    <div><?php echo __('Status');?>
                        <span class="faded"> - </span>
                        <select  name="task:status">
                            <option value="open" <?php
                                echo $task->isOpen() ?
                                'selected="selected"': ''; ?>> <?php
                                echo __('Open'); ?></option>
                            <?php
                            if ($task->isClosed() || $canClose) {
                                ?>
                            <option value="closed" <?php
                                echo $task->isClosed() ?
                                'selected="selected"': ''; ?>> <?php
                                echo __('Closed'); ?></option>
                            <?php
                            } ?>
                        </select>
                        &nbsp;<span class='error'><?php echo
                        $errors['task:status']; ?></span>
                    </div>
                </td>
            </tr>
        </table>
       <p  style="text-align:center;">
           <input class="save pending" type="submit" value="<?php echo __('Post Update');?>">
           <input type="reset" value="<?php echo __('Reset');?>">
       </p>
    </form>
    <?php
    } ?>
    <form id="task_note"
        action="<?php echo $action; ?>"
        class="tab_content spellcheck save <?php
            echo $role->hasPerm(TaskModel::PERM_REPLY) ? 'hidden' : ''; ?>"
        name="task_note"
        method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $task->getId(); ?>">
        <input type="hidden" name="a" value="postnote">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td>
                    <div><span class='error'><?php echo $errors['note']; ?></span></div>
                    <textarea name="note" id="task-note" cols="80"
                        placeholder="<?php echo __('Internal Note details'); ?>"
                        rows="9" wrap="soft" data-draft-namespace="task.note"
                        data-draft-object-id="<?php echo $task->getId(); ?>"
                        class="richtext ifhtml draft draft-delete"><?php
                        echo $info['note'];
                        ?></textarea>
                    <div class="attachments">
                    <?php
                        if ($note_attachments_form)
                            print $note_attachments_form->getField('attachments')->render();
                    ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div><?php echo __('Status');?>
                        <span class="faded"> - </span>
                        <select  name="task:status">
                            <option value="open" <?php
                                echo $task->isOpen() ?
                                'selected="selected"': ''; ?>> <?php
                                echo __('Open'); ?></option>
                            <?php
                            if ($task->isClosed() || $canClose) {
                                ?>
                            <option value="closed" <?php
                                echo $task->isClosed() ?
                                'selected="selected"': ''; ?>> <?php
                                echo __('Closed'); ?></option>
                            <?php
                            } ?>
                        </select>
                        &nbsp;<span class='error'><?php echo
                        $errors['task:status']; ?></span>
                    </div>
                </td>
            </tr>
        </table>
       <p  style="text-align:center;">
           <input class="save pending" type="submit" value="<?php echo __('Post Note');?>">
           <input type="reset" value="<?php echo __('Reset');?>">
       </p>
    </form>
 </div>
<?php
echo $reply_attachments_form->getMedia();
?>

<script type="text/javascript">
$(function() {
    $(document).off('.tasks-content');
    $(document).on('click.tasks-content', '#all-ticket-tasks', function(e) {
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
        var $options = $(this).data('dialogConfig');
        var $redirect = $(this).data('redirect');
        $.dialog(url, [201], function (xhr) {
            if (!!$redirect)
                window.location.href = $redirect;
            else
                $.pjax.reload('#pjax-container');
        }, $options);

        return false;
    });

    $(document).off('.tf');
    $(document).on('submit.tf', '.ticket_task_actions form', function(e) {
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
    <?php
    if ($ticket) { ?>
    $('#ticket-tasks-count').html(<?php echo $ticket->getNumTasks(); ?>);
   <?php
    } ?>
});
</script>
