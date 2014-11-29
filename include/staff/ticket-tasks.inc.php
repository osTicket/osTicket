<?php

//TODO: Make it ORM based once we marge other models.
$select ='SELECT task.*, dept.dept_name '
        .' ,CONCAT_WS(" ", staff.firstname, staff.lastname) as staff, team.name as team '
        .' ,IF(staff.staff_id IS NULL,team.name,CONCAT_WS(" ", staff.lastname, staff.firstname)) as assigned ';

$from =' FROM '.TASK_TABLE.' task '
      .' LEFT JOIN '.DEPT_TABLE.' dept ON task.dept_id=dept.dept_id '
      .' LEFT JOIN '.STAFF_TABLE.' staff ON (task.staff_id=staff.staff_id) '
      .' LEFT JOIN '.TEAM_TABLE.' team ON (task.team_id=team.team_id) ';

if ($ticket)
    $where = 'WHERE task.object_type="T" AND task.object_id = '.db_input($ticket->getId());

$query ="$select $from $where ORDER BY task.created DESC";

// Fetch the results
$results = array();
$res = db_query($query);
while ($row = db_fetch_array($res))
    $results[$row['id']] = $row;

?>

<div id="tasks_content" style="display:block;">
<div style="width:700px; float:left;">
   <?php
    if ($results) {
        echo '<strong>'.sprintf(_N('Showing %d Task', 'Showing %d Tasks',
            count($results)), count($results)).'</strong>';
    } else {
        echo sprintf(__('%s does not have any tasks'), $ticket? 'Ticket' :
                'System');
    }
   ?>
</div>
<div style="float:right;text-align:right;padding-right:5px;">
    <?php
    if ($ticket) { ?>
        <a
        class="Icon newTicket ticket-action"
        data-dialog='{"size":"large"}'
        href="#tickets/<?php
            echo $ticket->getId(); ?>/add-task"> <?php
            print __('Add New Task'); ?></a>
    <?php
    } ?>
</div>
<br/>
<div>
<?php
if ($results) { ?>
<form action="tickets.php?id=<?php echo $ticket->getId(); ?>" method="POST" name='tasks' style="padding-top:10px;">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="2" width="940">
    <thead>
        <tr>
            <?php
            if (0) {?>
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
    foreach($results as $row) {
        if (!($task = Task::lookup($row['id'])))
            continue;

        $flag=null;
        if ($row['lock_id'])
            $flag='locked';
        elseif ($row['isoverdue'])
            $flag='overdue';

        $assigned='';
        if ($row['staff_id'])
            $assigned=sprintf('<span class="Icon staffAssigned">%s</span>',Format::truncate($row['staff'],40));
        elseif ($row['team_id'])
            $assigned=sprintf('<span class="Icon teamAssigned">%s</span>',Format::truncate($row['team'],40));
        else
            $assigned=' ';

        $status = $task->isOpen() ? '<strong>open</strong>': 'closed';

        $tid=$row['number'];
        $title = Format::htmlchars(Format::truncate($task->getTitle(),40));
        $threadcount= $task->getThread()->getNumEntries();
        ?>
        <tr id="<?php echo $row['id']; ?>">
            <?php
            //Implement mass  action....if need be.
            if (0) { ?>
            <td align="center" class="nohover">
                <input class="ckb" type="checkbox" name="tids[]"
                value="<?php echo $row['id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
            </td>
            <?php
            } ?>
            <td align="center" nowrap>
              <a class="Icon no-pjax preview"
                title="<?php echo __('Preview Task'); ?>"
                href="#tasks/<?php echo $task->getId(); ?>/view"
                data-preview="#tasks/<?php echo $task->getId(); ?>/preview"
                ><?php echo $task->getNumber(); ?></a></td>
            <td align="center" nowrap><?php echo
            Format::db_datetime($row['created']); ?></td>
            <td><?php echo $status; ?></td>
            <td><a <?php if ($flag) { ?> class="no-pjax"
                    title="<?php echo ucfirst($flag); ?> Task" <?php } ?>
                    href="#tasks/<?php echo $task->getId(); ?>/view"><?php
                echo $title; ?></a>
                 <?php
                    if ($threadcount>1)
                        echo "<small>($threadcount)</small>&nbsp;".'<i
                            class="icon-fixed-width icon-comments-alt"></i>&nbsp;';
                    if ($row['collaborators'])
                        echo '<i class="icon-fixed-width icon-group faded"></i>&nbsp;';
                    if ($row['attachments'])
                        echo '<i class="icon-fixed-width icon-paperclip"></i>&nbsp;';
                ?>
            </td>
            <td><?php echo Format::truncate($row['dept_name'], 40); ?></td>
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
    $(document).on('click.tasks', 'tbody.tasks a, a#reload-task', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'+$(this).attr('href').substr(1);
        var $container = $('div#task_content');
        $container.load(url, function () {
            $('.tip_box').remove();
            $('div#tasks_content').hide();
        }).show();
        return false;
     });
});
</script>
