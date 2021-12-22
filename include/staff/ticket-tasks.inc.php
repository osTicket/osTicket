<?php
global $thisstaff;

$role = $ticket->getRole($thisstaff);

$tasks = Task::objects()
    ->select_related('dept', 'staff', 'team')
    ->order_by('-created');

$tasks->filter(array(
            'object_id' => $ticket->getId(),
            'object_type' => 'T'));

$count = $tasks->count();
$pageNav = new Pagenate($count,1, 100000); //TODO: support ajax based pages
$showing = $pageNav->showing().' '._N('task', 'tasks', $count);

?>
<div id="tasks_content" style="display:block;">
<div class="pull-left">
   <?php
    if ($count) {
        echo '<strong>'.$showing.'</strong>';
    } else {
        echo sprintf(__('%s does not have any tasks'), $ticket? 'This ticket' :
                'System');
    }
   ?>
</div>
<div class="pull-right">
    <?php
    if ($role && $role->hasPerm(Task::PERM_CREATE)) { ?>
        <a
        class="green button action-button ticket-task-action"
        data-url="tickets.php?id=<?php echo $ticket->getId(); ?>#tasks"
        data-dialog-config='{"size":"large"}'
        href="#tickets/<?php
            echo $ticket->getId(); ?>/add-task">
            <i class="icon-plus-sign"></i> <?php
            print __('Add New Task'); ?></a>
    <?php
    }
    foreach ($tasks as $task)
        $taskStatus .= $task->isOpen() ? 'open' : 'closed';

    if ($count)
        Task::getAgentActions($thisstaff, array(
                    'container' => '#tasks_content',
                    'callback_url' => sprintf('ajax.php/tickets/%d/tasks',
                        $ticket->getId()),
                    'morelabel' => __('Options'),
                    'status' => $taskStatus ? $taskStatus : '')
                );
    ?>
</div>
<div class="clear"></div>
<div>
<?php
if ($count) { ?>
<form action="#tickets/<?php echo $ticket->getId(); ?>/tasks" method="POST"
    name='tasks' id="tasks" style="padding-top:7px;">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="2" width="940">
    <thead>
        <tr>
            <?php
            if (1) {?>
            <th width="8px">&nbsp;</th>
            <?php
            } ?>
            <th width="70"><?php echo __('Number'); ?></th>
            <th width="100"><?php echo __('Date'); ?></th>
            <th width="100"><?php echo __('Status'); ?></th>
            <th width="300"><?php echo __('Title'); ?></th>
            <th width="200"><?php echo __('Department'); ?></th>
            <th width="200"><?php echo __('Assignee'); ?></th>
        </tr>
    </thead>
    <tbody class="tasks">
    <?php
    foreach($tasks as $task) {
        $id = $task->getId();
        $access = $task->checkStaffPerm($thisstaff);
        $assigned='';
        if ($task->staff || $task->team) {
            $assigneeType = $task->staff ? 'staff' : 'team';
            $icon = $assigneeType == 'staff' ? 'staffAssigned' : 'teamAssigned';
            $assigned=sprintf('<span class="Icon %s">%s</span>',
                    $icon,
                    Format::truncate($task->getAssigned(),40));
        }

        $status = $task->isOpen() ? '<strong>open</strong>': 'closed';

        $title = Format::htmlchars(Format::truncate($task->getTitle(),40));
        $threadcount = $task->getThread() ?
            $task->getThread()->getNumEntries() : 0;

        if ($access)
            $viewhref = sprintf('#tickets/%d/tasks/%d/view', $ticket->getId(), $id);
        else
            $viewhref = '#';

        ?>
        <tr id="<?php echo $id; ?>">
            <td align="center" class="nohover">
                <input class="ckb" type="checkbox" name="tids[]"
                value="<?php echo $id; ?>" <?php echo $sel?'checked="checked"':''; ?>>
            </td>
            <td align="center" nowrap>
              <a class="Icon no-pjax preview"
                title="<?php echo __('Preview Task'); ?>"
                href="<?php echo $viewhref; ?>"
                data-preview="#tasks/<?php echo $id; ?>/preview"
                ><?php echo $task->getNumber(); ?></a></td>
            <td align="center" nowrap><?php echo
            Format::datetime($task->created); ?></td>
            <td><?php echo $status; ?></td>
            <td>
                <?php
                if ($access) { ?>
                    <a <?php if ($flag) { ?> class="no-pjax"
                        title="<?php echo ucfirst($flag); ?> Task" <?php } ?>
                        href="<?php echo $viewhref; ?>"><?php
                    echo $title; ?></a>
                 <?php
                } else {
                     echo $title;
                }
                    if ($threadcount>1)
                        echo "<small>($threadcount)</small>&nbsp;".'<i
                            class="icon-fixed-width icon-comments-alt"></i>&nbsp;';
                    if ($row['collaborators'])
                        echo '<i class="icon-fixed-width icon-group faded"></i>&nbsp;';
                    if ($row['attachments'])
                        echo '<i class="icon-fixed-width icon-paperclip"></i>&nbsp;';
                ?>
            </td>
            <td><?php echo Format::truncate($task->dept->getName(), 40); ?></td>
            <td>&nbsp;<?php echo $assigned; ?></td>
        </tr>
   <?php
    }
    ?>
    </tbody>
</table>
</form>
<?php
 } ?>
</div>
</div>
<div id="task_content" style="display:none;">
</div>
<script type="text/javascript">
$(function() {

    $(document).off('click.taskv');
    $(document).on('click.taskv', 'tbody.tasks a, a#reload-task', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        if ($(this).attr('href').length > 1) {
            var url = 'ajax.php/'+$(this).attr('href').substr(1);
            var $container = $('div#task_content');
            var $stop = $('ul#ticket_tabs').offset().top;
            $.pjax({url: url, container: 'div#task_content', push: false, scrollTo: $stop})
            .done(
                function() {
                $container.show();
                $('.tip_box').remove();
                $('div#tasks_content').hide();
                });
        } else {
            $(this).trigger('mouseenter');
        }

        return false;
     });
    // Ticket Tasks
    $(document).off('.ticket-task-action');
    $(document).on('click.ticket-task-action', 'a.ticket-task-action', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'
        +$(this).attr('href').substr(1)
        +'?_uid='+new Date().getTime();
        var $redirect = $(this).data('href');
        var $options = $(this).data('dialogConfig');
        $.dialog(url, [201], function (xhr) {
            var tid = parseInt(xhr.responseText);
            if (tid) {
                var url = 'ajax.php/tickets/'+<?php echo $ticket->getId();
                ?>+'/tasks';
                var $container = $('div#task_content');
                $container.load(url+'/'+tid+'/view', function () {
                    $('.tip_box').remove();
                    $('div#tasks_content').hide();
                    $.pjax({url: url, container: '#tasks_content', timeout: 30000, push: false});
                }).show();
            } else {
                window.location.href = $redirect ? $redirect : window.location.href;
            }
        }, $options);
        return false;
    });

    $('#ticket-tasks-count').html(<?php echo $count; ?>);
});
</script>
