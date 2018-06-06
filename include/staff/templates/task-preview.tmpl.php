<?php
$error=$msg=$warn=null;

if (!$task->checkStaffPerm($thisstaff))
     $warn.= sprintf(__('You do not have access to %s'), __('this task'));
elseif ($task->isOverdue())
    $warn.='&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

echo sprintf(
        '<div style="width:600px; padding: 2px 2px 0 5px;" id="t%s">
         <h2>'.__('Task #%s').': %s</h2>',
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
if ($task->getThread()->getNumCollaborators()) {
    echo sprintf('
        <li><a id="collab_tab" href="#collab"
            ><i class="icon-fixed-width icon-group
            faded"></i>&nbsp;'.__('Collaborators (%d)').'</a></li>',
            $task->getThread()->getNumCollaborators());
}
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

if ($task->isClosed()) {

    echo sprintf('
            <tr>
                <th>'.__('Completed').':</th>
                <td>%s</td>
            </tr>',
            Format::datetime($task->getCloseDate()));

} elseif ($task->isOpen() && $task->duedate) {
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
<?php
//TODO: add link to view if the user has permission
?>
<div class="hidden tab_content" id="collab">
    <table border="0" cellspacing="" cellpadding="1">
        <colgroup><col style="min-width: 250px;"></col></colgroup>
        <?php
        if (($collabs=$task->getThread()->getCollaborators())) {?>
        <?php
            foreach($collabs as $collab) {
                echo sprintf('<tr><td %s><i class="icon-%s"></i>
                        <a href="users.php?id=%d" class="no-pjax">%s</a> <em>&lt;%s&gt;</em></td></tr>',
                        ($collab->isActive()? '' : 'class="faded"'),
                        ($collab->isActive()? 'comments' :  'comment-alt'),
                        $collab->getUserId(),
                        $collab->getName(),
                        $collab->getEmail());
            }
        }  else {
            echo __("Task doesn't have any collaborators.");
        }?>
    </table>
    <br>
    <?php
    echo sprintf('<span><a class="collaborators"
                            href="#thread/%d/collaborators">%s</a></span>',
                            $task->getThreadId(),
                            $task->getThread()->getNumCollaborators()
                                ? __('Manage Collaborators') : __('Add Collaborator')
                                );
    ?>
</div>
</div>
</div>
