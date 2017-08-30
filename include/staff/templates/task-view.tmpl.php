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

    
            <?php
            if ($ticket) { ?>
         <div class="row">
         <div style="width:100%">
          <div class="pull-left subnavtitle">
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
                </strong> - 
                <?php
        $title = TaskForm::getInstance()->getField('title');
        echo $title->display($task->getTitle());
    ?>
            </div>
            <?php
            
            
            } else { ?>
            
            
            <div class="subnav">
          
            <div class="float-left subnavtitle">
               
                <a  id="reload-task"
                    href="tasks.php?id=<?php echo $task->getId(); ?>"><i
                    class="icon-refresh"></i>&nbsp;<?php
                    echo sprintf(__('Task #%s'), $task->getNumber()); ?></a> -
                    <?php
        $title = TaskForm::getInstance()->getField('title');
        echo $title->display($task->getTitle());
    ?>
                </div>
            <?php
            } ?>
        
        
        <!-- buttons -->
      
      
            <?php
            if ($ticket) { ?>
            <div class="btn-group btn-group-sm  float-right subnavbuttons" role="group" aria-label="Button group with nested dropdown">
            <a  id="task-view"
                target="_blank"
                class="btn btn-light btn-nbg"
                href="tasks.php?id=<?php
                 echo $task->getId(); ?>"><i class="icon-share"  data-placement="bottom" data-toggle="tooltip" 
                 title="<?php echo __('View Task'); ?>"></i></a>
           
                <div class="btn-group btn-group-sm" role="group">
                
                <button id="btnGroupDrop1" type="button" class="btn btn-light btn-nbg dropdown-toggle" 
                data-toggle="dropdown"><i class="fa fa-cog" data-placement="bottom" data-toggle="tooltip" 
                 title="<?php echo __('more'); ?>"></i>
                </button>
                    <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1">

                    <?php
                    if ($task->isOpen()) { ?>
                    
                        <a class="dropdown-item no-pjax task-action"
                            href="#tasks/<?php echo $task->getId(); ?>/reopen"><i
                            class="icon-fixed-width icon-undo"></i> <?php
                            echo __('Reopen');?> </a>
                   
                    <?php
                    } else {
                    ?>
                   
                        <a class="dropdown-item  no-pjax task-action"
                            href="#tasks/<?php echo $task->getId(); ?>/close"><i
                            class="icon-fixed-width icon-ok-circle"></i> <?php
                            echo __('Close');?> </a>
                   
                    <?php
                    } ?>
                    <?php
                    foreach ($actions as $a => $action) { ?>
                    
                        <a class="dropdown-item no-pjax task-action" <?php
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
                    
                <?php
                } ?>
                
            </div>
           </div>
           
                <a class="btn btn-light btn-nbg" id="all-ticket-tasks" href="#" >
                <i class="fa fa-list-alt"  data-placement="bottom" data-toggle="tooltip" 
                 title="<?php echo __('All Tasks'); ?>"></i></a>

                 
           </div>
           </div>
           </div>
            <?php
           } else { 
           
           // standalone task ?> 
          <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
                    
                <div class="btn-group btn-group-sm" role="group">
                
                <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
                data-toggle="dropdown"><i class="fa fa-flag" data-placement="bottom" data-toggle="tooltip" 
                 title="<?php echo __('Change Status'); ?>"></i>
                </button>
                    <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1">
                
                       <?php
                        if ($task->isClosed()) { ?>
                                <a class="dropdown-item no-pjax task-action"
                                href="#tasks/<?php echo $task->getId(); ?>/reopen"><i
                                class="icon-fixed-width icon-undo"></i> <?php
                                echo __('Reopen');?> </a>
                      
                        <?php
                        } else {
                        ?>
                       
                            <a class="dropdown-item no-pjax task-action"
                                href="#tasks/<?php echo $task->getId(); ?>/close"><i
                                class="icon-fixed-width icon-ok-circle"></i> <?php
                                echo __('Close');?> </a>
                       
                        <?php
                        } ?>
                    
                </div>
                </div>
                <?php
                // Assign
                unset($actions['claim'], $actions['assign/agents'], $actions['assign/teams']);
                if ($task->isOpen() && $role->hasPerm(Task::PERM_ASSIGN)) {?>
                
                <div class="btn-group btn-group-sm" role="group">
                
                <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
                data-toggle="dropdown"><i class="fa fa-user" data-placement="bottom" data-toggle="tooltip" 
                 title="<?php echo __('Assign'); ?>"></i>
                </button>
                    <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1">
                
                    <?php
                    // Agent can claim team assigned ticket
                    if ($task->getStaffId() != $thisstaff->getId()
                            && (!$dept->assignMembersOnly()
                                || $dept->isMember($thisstaff))
                            ) { ?>
                     <a class="dropdown-item no-pjax task-action"
                        data-redirect="tasks.php"
                        href="#tasks/<?php echo $task->getId(); ?>/claim"><i
                        class="icon-chevron-sign-down"></i> <?php echo __('Claim'); ?></a>
                    <?php
                    } ?>
                     <a class="dropdown-item no-pjax task-action"
                        data-redirect="tasks.php"
                        href="#tasks/<?php echo $task->getId(); ?>/assign/agents"><i
                        class="icon-user"></i> <?php echo __('Agent'); ?></a>
                     <a class="dropdown-item no-pjax task-action"
                        data-redirect="tasks.php"
                        href="#tasks/<?php echo $task->getId(); ?>/assign/teams"><i
                        class="icon-group"></i> <?php echo __('Team'); ?></a>
                 
                </div>
                </div>
                <?php
                } ?>
                <?php
                foreach ($actions as $action) {?>
                
                    <a class="btn btn-light task-action"
                        <?php
                        if ($action['dialog'])
                            echo sprintf("data-dialog-config='%s'", $action['dialog']);
                        if ($action['redirect'])
                            echo sprintf("data-redirect='%s'", $action['redirect']);
                        ?>
                        href="<?php echo $action['href']; ?>"
                        >
                        <i class="<?php
                        echo $action['icon'] ?: 'icon-tag'; ?>" data-placement="bottom"
                        data-toggle="tooltip"
                        title="<?php echo $action['label']; ?>"></i>
                    </a>
                
           <?php
                }
                ?>
                 <a class="btn btn-light" href="tasks.php" ><i class="fa fa-list-alt" data-placement="bottom"
                        data-toggle="tooltip"
                        title="<?php echo __('Tasks'); ?>"></i></a>
                
                </div>
                <div class="clearfix"></div>
            </div>
            
            <?php
                
           } ?>
           
<?php
if (!$ticket) { ?>


<div class="card-box">
<div class="row boldlabels">
    <div class="col-md-3">
     <div><label><?php echo __('Status');?>:</label>
                       <?php echo $task->getStatus(); ?></div>
    
    <div><label><?php echo __('Created');?>:</label>
                        <td><?php echo Format::datetime($task->getCreateDate()); ?></div>
    
    <?php if($task->isOpen()){ ?>
                    
                         <div><label><?php echo __('Due Date');?>:</label>
                        <?php echo $task->duedate ?
                        Format::datetime($task->duedate) : '<span
                        class="faded">&mdash; '.__('None').' &mdash;</span>'; ?></div>
                    
                    <?php
                    }else { ?>
                   
                         <div><label><?php echo __('Completed');?>:</label>
                        <?php echo Format::datetime($task->getCloseDate()); ?></div>
                    
                    <?php
                    }
                    ?>
    
    </div>
    <div class="col-md-3">
    
    <div><label><?php echo __('Department');?>:</label>
                        <?php echo Format::htmlchars($task->dept->getName()); ?></div>
    
    <?php
                    if ($task->isOpen()) { ?>
                    
                        <div><label><?php echo __('Assigned To');?>:</label>
                        
                            <?php
                            if ($assigned=$task->getAssigned())
                                echo Format::htmlchars($assigned);
                            else
                                echo '<span class="faded">&mdash; '.__('Unassigned').' &mdash;</span>';
                            ?>
                        </div>
                    
                    <?php
                    } else { ?>
                    <div><label><?php echo __('Closed By');?>:</label>
                        
                            <?php
                            if (($staff = $task->getStaff()))
                                echo Format::htmlchars($staff->getName());
                            else
                                echo '<span class="faded">&mdash; '.__('Unknown').' &mdash;</span>';
                            ?>
                        </div>
                    
                    <?php
                    } ?>
                    
                    <div><label><?php echo __('Collaborators');?>:</label>
                        
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
                        </div>
                    
    </div>


    <div class="col-md-6">
  
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
            
                <?php foreach($answers as $a) {
                    if (!($v = $a->display())) continue; ?>
                    <div>
                        <label><?php
                            echo $a->getField()->get('label');
                        ?>:</label>
                        <?php
                            echo $v;
                        ?>
                    </div>
                    <?php
                } ?>
                       
        
        <?php
        $idx++;
    } ?>
   </div>   
</div></div>

<?php
} ?>

<div class="card-box" >
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
<div class="card-box <?php echo $ticket ? 'ticket_task_actions' : ''; ?> ">
<div id="ReponseTabs" >
    <ul class="nav nav-pills">
        <?php
        if ($role->hasPerm(TaskModel::PERM_REPLY)) { ?>
        <li class="nav-item"><a class="nav-link active" href="#task_reply" data-toggle="tab" ><?php echo __('Post Update');?></a></li>
        <li class="nav-item"><a class="nav-link" href="#task_note" data-toggle="tab" ><?php echo __('Post Internal Note');?></a></li>
        <?php
        }?>
    </ul>
    <?php
    
    
    if ($role->hasPerm(TaskModel::PERM_REPLY)) { ?>
    <div class="tab-content clearfix">
    <div class="tab-pane active" id="task_reply">
    <form  class="spellcheck save"
        action="<?php echo $action; ?>"
        name="task_reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        
        
        <input type="hidden" name="id" value="<?php echo $task->getId(); ?>">
        <input type="hidden" name="a" value="postreply">
        <input type="hidden" name="lockCode" value="<?php echo ($mylock) ? $mylock->getCode() : ''; ?>">
        <span class="error"></span>
         <div  class="form-group">
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
         </div>
        <div  class="form-group">
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
            </div>
            <div  class="form-group">
            <label><?php echo __('Status');?>
                        <span class="faded"> - </span></label>
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
           
      
       <div>
           <input class="btn btn-primary btn-sm" type="submit" value="<?php echo __('Post Update');?>">
           <input class="btn btn-warning btn-sm" type="reset" value="<?php echo __('Reset');?>">
       </div>
    </form>
    </div>
    <?php
    } ?>
    
    
   <div class="tab-pane" id="task_note">
    
    <form action="<?php echo $action; ?>"class="spellcheck save"
        name="task_note"
        method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        
        <div class="form-group">
        <input type="hidden" name="id" value="<?php echo $task->getId(); ?>">
        <input type="hidden" name="a" value="postnote">
        <div>    
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
        </div>
        </div>
        <div class="form-group">
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
        </div>
        <div>
           <input class="save pending" type="submit" value="<?php echo __('Post Note');?>">
           <input type="reset" value="<?php echo __('Reset');?>">
        </div>
       
    </form>
    </div>
    </div>
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
