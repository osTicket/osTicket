<?php
if (!defined('OSTSCPINC')
        || !$thisstaff
        || !$task
        || !($role = $thisstaff->getRole($task->getId())))
    die('Invalid path');


$actions = array();

if ($role->hasPerm(Task::PERM_EDIT)) {
    $actions += array(
            'edit' => array(
                'icon' => 'icon-edit',
                'dialog' => '{"size":"large"}',
                'action' => __('Edit')
            ));
}

if ($role->hasPerm(Task::PERM_ASSIGN)) {
    $actions += array(
            'assign' => array(
                'icon' => 'icon-user',
                'action' => $task->isAssigned() ? __('Reassign') : __('Assign')
            ));
}

if ($role->hasPerm(Task::PERM_TRANSFER)) {
    $actions += array(
            'transfer' => array(
                'icon' => 'icon-share',
                'action' => __('Transfer')
            ));
}

if ($role->hasPerm(Task::PERM_DELETE)) {
    $actions += array(
            'delete' => array(
                'icon' => 'icon-trash',
                'action' => __('Delete')
            ));
}


$info=($_POST && $errors)?Format::input($_POST):array();

$id    = $task->getId();    //Ticket ID.
if ($task->isOverdue())
    $warn.='&nbsp;&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

?>
<table width="940" cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td width="20%" class="has_bottom_border">
            <h3><a href="#tasks/<?php echo $task->getId(); ?>/view"
                    id="reload-task"><i class="icon-refresh"></i> <?php
                echo sprintf(__('Task #%s'), $task->getNumber()); ?></a>
            </h3>
        </td>
        <td width="auto" class="flush-right has_bottom_border">
        <?php
           if ($actions) { ?>
            <span
                class="action-button"
                data-dropdown="#action-dropdown-taskoptions">
                <i class="icon-caret-down pull-right"></i>
                <a class="task-action"
                    href="#taskoptions"><i
                    class="icon-reorder"></i> <?php
                    echo __('Task Options'); ?></a>
            </span>
            <div id="action-dropdown-taskoptions"
                class="action-dropdown anchor-right">
                <ul>
            <?php foreach ($actions as $a => $action) { ?>
                    <li>
                        <a class="no-pjax task-action"
                            <?php
                            if ($action['dialog'])
                                echo sprintf("data-dialog='%s'", $action['dialog']);
                            ?>
                            href="<?php
                            echo sprintf('#tasks/%d/%s', $task->getId(), $a); ?>"
                            ><i class="<?php
                            echo $action['icon'] ?: 'icon-tag'; ?>"></i> <?php
                            echo $action['action']; ?></a>
                    </li>
                <?php
                } ?>
                </ul>
            </div>
            <?php
           } ?>
        </td>
    </tr>
</table>
<table class="ticket_info" cellspacing="0" cellpadding="0" width="940" border="0">
    <tr>
        <td width="50%">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <tr>
                    <th width="100"><?php echo __('Status');?>:</th>
                    <td><?php echo $task->getStatus(); ?></td>
                </tr>
                <tr>
                    <th><?php echo __('Department');?>:</th>
                    <td><?php echo Format::htmlchars($task->dept->getName()); ?></td>
                </tr>
                <tr>
                    <th><?php echo __('Create Date');?>:</th>
                    <td><?php echo Format::datetime($task->getCreateDate()); ?></td>
                </tr>
            </table>
        </td>
        <td width="50%" style="vertical-align:top">
            <table cellspacing="0" cellpadding="4" width="100%" border="0">
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
                        if (0 && ($staff = $task->getStaff()))
                            echo Format::htmlchars($staff->getName());
                        else
                            echo '<span class="faded">&mdash; '.__('Unknown').' &mdash;</span>';
                        ?>
                    </td>
                </tr>
                <?php
                } ?>
                <tr>
                    <th><?php echo __('SLA Plan');?>:</th>
                    <td><?php echo $sla?Format::htmlchars($sla->getName()):'<span class="faded">&mdash; '.__('None').' &mdash;</span>'; ?></td>
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
                    <td><?php echo 0 ?
                    Format::datetime($task->getCloseDate()) : ''; ?></td>
                </tr>
                <?php
                }
                ?>
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
    $answers = array_filter($form->getAnswers(), function ($a) {
            return $a->getField()->isStorable();
        });
    if (count($answers) == 0)
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
<div class="clear"></div>
<div id="task_thread_container">
    <div id="task_thread_content">
    <?php
    $threadTypes=array('M'=>'message','R'=>'response', 'N'=>'note');
    /* -------- Messages & Responses & Notes (if inline)-------------*/
    $types = array('M', 'R', 'N');
    if(($thread=$task->getThreadEntries($types))) {
       foreach($thread as $entry) { ?>
        <table class="thread-entry <?php echo $threadTypes[$entry['type']]; ?>" cellspacing="0" cellpadding="1" width="940" border="0">
            <tr>
                <th colspan="4" width="100%">
                <div>
                    <span class="pull-left">
                    <span style="display:inline-block"><?php
                        echo Format::datetime($entry['created']);?></span>
                    <span style="display:inline-block;padding:0 1em" class="faded title"><?php
                        echo Format::truncate($entry['title'], 100); ?></span>
                    </span>
                    <span class="pull-right" style="white-space:no-wrap;display:inline-block">
                        <span style="vertical-align:middle;" class="textra"></span>
                        <span style="vertical-align:middle;"
                            class="tmeta faded title"><?php
                            echo Format::htmlchars($entry['name'] ?: $entry['poster']); ?></span>
                    </span>
                </div>
                </th>
            </tr>
            <tr><td colspan="4" class="thread-body" id="thread-id-<?php
                echo $entry['id']; ?>"><div><?php
                echo $entry['body']->toHtml(); ?></div></td></tr>
            <?php
            $urls = null;
            if($entry['attachments']
                    && ($tentry = $task->getThreadEntry($entry['id']))
                    && ($urls = $tentry->getAttachmentUrls())
                    && ($links = $tentry->getAttachmentsLinks())) {?>
            <tr>
                <td class="info" colspan="4"><?php echo $links; ?></td>
            </tr> <?php
            }
            if ($urls) { ?>
                <script type="text/javascript">
                    $('#thread-id-<?php echo $entry['id']; ?>')
                        .data('urls', <?php
                            echo JsonDataEncoder::encode($urls); ?>)
                        .data('id', <?php echo $entry['id']; ?>);
                </script>
<?php
            } ?>
        </table>
        <?php
        if ($entry['type'] == 'M')
            $msgId = $entry['id'];
       }
    } else {
        echo '<p>'.__('Error fetching thread - get technical help.').'</p>';
    }?>
   </div>
</div>
<div class="clear" style="padding-bottom:10px;"></div>
<?php if($errors['err']) { ?>
    <div id="msg_error"><?php echo $errors['err']; ?></div>
<?php }elseif($msg) { ?>
    <div id="msg_notice"><?php echo $msg; ?></div>
<?php }elseif($warn) { ?>
    <div id="msg_warning"><?php echo $warn; ?></div>
<?php } ?>
<div id="response_options">
    <ul class="tabs"></ul>
    <form id="task_note"
        action="#tasks/<?php echo $task->getId(); ?>"
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
                        if ($task_note_form)
                            print $task_note_form->getField('attachments')->render();
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
    $(document).on('click', 'li.active a#ticket_tasks', function(e) {
        e.preventDefault();
        $('div#task_content').hide().empty();
        $('div#tasks_content').show();
        return false;
     });
    $(document).off('.tf');
    $(document).on('submit.tf', 'form#task_note', function(e) {
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
