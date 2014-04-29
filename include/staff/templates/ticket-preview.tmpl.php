<?php
/*
 * Ticket Preview popup template
 *
 */

$staff=$ticket->getStaff();
$lock=$ticket->getLock();
$error=$msg=$warn=null;

if($lock && $lock->getStaffId()==$thisstaff->getId())
    $warn.='&nbsp;<span class="Icon lockedTicket">Ticket is locked by '.$lock->getStaffName().'</span>';
elseif($ticket->isOverdue())
    $warn.='&nbsp;<span class="Icon overdueTicket">Marked overdue!</span>';

echo sprintf(
        '<div style="width:600px; padding: 2px 2px 0 5px;" id="t%s">
         <h2>Ticket #%s: %s</h2><br>',
         $ticket->getNumber(),
         $ticket->getNumber(),
         Format::htmlchars($ticket->getSubject()));

if($error)
    echo sprintf('<div id="msg_error">%s</div>',$error);
elseif($msg)
    echo sprintf('<div id="msg_notice">%s</div>',$msg);
elseif($warn)
    echo sprintf('<div id="msg_warning">%s</div>',$warn);

echo '<ul class="tabs">';

echo '
        <li><a id="preview_tab" href="#preview" class="active"
            ><i class="icon-list-alt"></i>&nbsp;Ticket Summary</a></li>';
if ($ticket->getNumCollaborators()) {
echo sprintf('
        <li><a id="collab_tab" href="#collab"
            ><i class="icon-fixed-width icon-group
            faded"></i>&nbsp;Collaborators (%d)</a></li>',
            $ticket->getNumCollaborators());
}
echo '</ul>';

echo '<div class="tab_content" id="preview">';
echo '<table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';

$ticket_state=sprintf('<span>%s</span>',ucfirst($ticket->getStatus()));
if($ticket->isOpen()) {
    if($ticket->isOverdue())
        $ticket_state.=' &mdash; <span>Overdue</span>';
    else
        $ticket_state.=sprintf(' &mdash; <span>%s</span>',$ticket->getPriority());
}

echo sprintf('
        <tr>
            <th width="100">Ticket State:</th>
            <td>%s</td>
        </tr>
        <tr>
            <th>Create Date:</th>
            <td>%s</td>
        </tr>',$ticket_state,
        Format::db_datetime($ticket->getCreateDate()));
if($ticket->isClosed()) {
    echo sprintf('
            <tr>
                <th>Close Date:</th>
                <td>%s   <span class="faded">by %s</span></td>
            </tr>',
            Format::db_datetime($ticket->getCloseDate()),
            ($staff?$staff->getName():'staff')
            );
} elseif($ticket->getEstDueDate()) {
    echo sprintf('
            <tr>
                <th>Due Date:</th>
                <td>%s</td>
            </tr>',
            Format::db_datetime($ticket->getEstDueDate()));
}
echo '</table>';


echo '<hr>
    <table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';
if($ticket->isOpen()) {
    echo sprintf('
            <tr>
                <th width="100">Assigned To:</th>
                <td>%s</td>
            </tr>',$ticket->isAssigned()?implode('/', $ticket->getAssignees()):' <span class="faded">&mdash; Unassigned &mdash;</span>');
}
echo sprintf(
    '
        <tr>
            <th>From:</th>
            <td><a href="users.php?id=%d" class="no-pjax">%s</a> <span class="faded">%s</span></td>
        </tr>
        <tr>
            <th width="100">Department:</th>
            <td>%s</td>
        </tr>
        <tr>
            <th>Help Topic:</th>
            <td>%s</td>
        </tr>',
    $ticket->getUserId(),
    Format::htmlchars($ticket->getName()),
    $ticket->getEmail(),
    Format::htmlchars($ticket->getDeptName()),
    Format::htmlchars($ticket->getHelpTopic()));

echo '
    </table>';
echo '</div>'; // ticket preview content.
?>
<div class="tab_content" id="collab" style="display:none;">
    <table border="0" cellspacing="" cellpadding="1">
        <colgroup><col style="min-width: 250px;"></col></colgroup>
        <?php
        if (($collabs=$ticket->getCollaborators())) {?>
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
            echo "Ticket doesn't have collaborators.";
        }?>
    </table>
    <br>
    <?php
    echo sprintf('<span><a class="collaborators"
                            href="#tickets/%d/collaborators">%s</a></span>',
                            $ticket->getId(),
                            $ticket->getNumCollaborators()
                                ? 'Manage Collaborators' : 'Add Collaborator'
                                );
    ?>
</div>
<?php
$options = array();
$options[]=array('action'=>'Thread ('.$ticket->getThreadCount().')','url'=>"tickets.php?id=$tid");
if($ticket->getNumNotes())
    $options[]=array('action'=>'Notes ('.$ticket->getNumNotes().')','url'=>"tickets.php?id=$tid#notes");

if($ticket->isOpen())
    $options[]=array('action'=>'Reply','url'=>"tickets.php?id=$tid#reply");

if($thisstaff->canAssignTickets())
    $options[]=array('action'=>($ticket->isAssigned()?'Reassign':'Assign'),'url'=>"tickets.php?id=$tid#assign");

if($thisstaff->canTransferTickets())
    $options[]=array('action'=>'Transfer','url'=>"tickets.php?id=$tid#transfer");

$options[]=array('action'=>'Post Note','url'=>"tickets.php?id=$tid#note");

if($thisstaff->canEditTickets())
    $options[]=array('action'=>'Edit Ticket','url'=>"tickets.php?id=$tid&a=edit");

if($options) {
    echo '<ul class="tip_menu">';
    foreach($options as $option)
        echo sprintf('<li><a href="%s">%s</a></li>',$option['url'],$option['action']);
    echo '</ul>';
}

echo '</div>';
?>
