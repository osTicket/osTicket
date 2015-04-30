<?php
$error=$msg=$warn=null;

if($task->isOverdue())
    $warn.='&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

echo sprintf(
        '<div style="width:600px; padding: 2px 2px 0 5px;" id="t%s">
         <h2>'.__('Task #%s').': %s</h2><br>',
         $task->getNumber(),
         $task->getNumber(),
         Format::htmlchars($task->getTitle()));

if($error)
    echo sprintf('<div id="msg_error">%s</div>',$error);
elseif($msg)
    echo sprintf('<div id="msg_notice">%s</div>',$msg);
elseif($warn)
    echo sprintf('<div id="msg_warning">%s</div>',$warn);

echo '<ul class="tabs" id="task-preview">';

echo '
        <li class="active"><a href="#summary"
            ><i class="icon-list-alt"></i>&nbsp;'.__('Task Summary').'</a></li>';
echo '</ul>';
echo '<div id="task-preview_container">';
echo '<div class="tab_content" id="summary">';
echo '<table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';
$status=sprintf('<span>%s</span>',ucfirst($task->getStatus()));
echo sprintf('
        <tr>
            <th width="100">'.__('Status').':</th>
            <td>%s</td>
        </tr>
        <tr>
            <th>'.__('Created').':</th>
            <td>%s</td>
        </tr>',$status,
        Format::datetime($task->getCreateDate()));

if ($task->isOpen() && $task->duedate) {
    echo sprintf('
            <tr>
                <th>'.__('Due Date').':</th>
                <td>%s</td>
            </tr>',
            Format::datetime($task->duedate));
}
echo '</table>';


echo '<hr>
    <table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';
if ($task->isOpen()) {
    echo sprintf('
            <tr>
                <th width="100">'.__('Assigned To').':</th>
                <td>%s</td>
            </tr>', $task->getAssigned() ?: ' <span class="faded">&mdash; '.__('Unassigned').' &mdash;</span>');
}
echo sprintf(
    '
        <tr>
            <th width="100">'.__('Department').':</th>
            <td>%s</td>
        </tr>',
    Format::htmlchars($task->dept->getName())
    );

echo '
    </table>';
echo '</div>';
?>
</div>
<?php
//TODO: add link to view if the user has permission

echo '</div>';
?>
