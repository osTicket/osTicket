<?php
$error=$msg=$warn=null;

if($lock && $lock->getStaffId()==$thisstaff->getId())
    $warn.='&nbsp;<span class="Icon lockedTicket">'
    .sprintf(__('Ticket is locked by %s'), $lock->getStaffName()).'</span>';
elseif($task->isOverdue())
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

echo '<ul class="tabs" id="ticket-preview">';

echo '
        <li><a id="preview_tab" href="#preview" class="active"
            ><i class="icon-list-alt"></i>&nbsp;'.__('Task Summary').'</a></li>';
if (0 && $task->getNumCollaborators()) {
echo sprintf('
        <li><a id="collab_tab" href="#collab"
            ><i class="icon-fixed-width icon-group
            faded"></i>&nbsp;'.__('Collaborators (%d)').'</a></li>',
            $task->getNumCollaborators());
}
echo '</ul>';
echo '<div id="ticket-preview_container">';
echo '<div class="tab_content" id="preview_tab_content">';
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
        Format::db_datetime($task->getCreateDate()));

if (0 && $task->isOpen() && $task->getEstDueDate()) {
    echo sprintf('
            <tr>
                <th>'.__('Due Date').':</th>
                <td>%s</td>
            </tr>',
            Format::db_datetime($task->getEstDueDate()));
}
echo '</table>';


echo '<hr>
    <table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';
if(0 && $ticket->isOpen()) {
    echo sprintf('
            <tr>
                <th width="100">'.__('Assigned To').':</th>
                <td>%s</td>
            </tr>',$ticket->isAssigned()?implode('/', $ticket->getAssignees()):' <span class="faded">&mdash; '.__('Unassigned').' &mdash;</span>');
}
echo sprintf(
    '
        <tr>
            <th width="100">'.__('Department').':</th>
            <td>%s</td>
        </tr>',
    Format::htmlchars('Dept. HERE')
    );

echo '
    </table>';
echo '</div>';
?>
<div class="tab_content" id="collab_tab_content" style="display:none;">
    <table border="0" cellspacing="" cellpadding="1">
        <colgroup><col style="min-width: 250px;"></col></colgroup>
        <?php
        if (0 && ($collabs=$task->getCollaborators())) {?>
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
                            href="#tasks/%d/collaborators">%s</a></span>',
                            $task->getId(),
                            0
                                ? __('Manage Collaborators') : __('Add Collaborator')
                                );
    ?>
</div>
</div>
<?php
$options = array();
$options[]=array('action'=>sprintf(__('Thread (%d)'),
            $task->getThread()->getNumEntries()),
        'url'=>"tickets.php?id=$tid");
if ($thisstaff->canAssignTickets())
    $options[]=array('action'=>($task->isAssigned()?__('Reassign'):__('Assign')),'url'=>"tickets.php?id=$tid#assign");

if ($thisstaff->canTransferTickets())
    $options[]=array('action'=>'Transfer','url'=>"tickets.php?id=$tid#transfer");

if ($thisstaff->canEditTickets())
    $options[]=array('action'=>'Edit Task','url'=>"tickets.php?id=$tid&a=edit");

if ($options) {
    echo '<ul class="tip_menu">';
    foreach($options as $option)
        echo sprintf('<li><a href="%s">%s</a></li>',$option['url'],$option['action']);
    echo '</ul>';
}

echo '</div>';
?>
