<?php
/*
 * Ticket Preview popup template
 *
 */

$staff=$ticket->getStaff();
$lock=$ticket->getLock();
$role=$thisstaff->getRole($ticket->getDeptId());
$error=$msg=$warn=null;

if($lock && $lock->getStaffId()==$thisstaff->getId())
    $warn.='&nbsp;<span class="Icon lockedTicket">'
    .sprintf(__('Ticket is locked by %s'), $lock->getStaffName()).'</span>';
elseif($ticket->isOverdue())
    $warn.='&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

echo sprintf(
        '<div style="width:600px; padding: 2px 2px 0 5px;" id="t%s">
         <h2>'.__('Ticket #%s').': %s</h2>',
         $ticket->getNumber(),
         $ticket->getNumber(),
         Format::htmlchars($ticket->getSubject()));

if($error)
    echo sprintf('<div id="msg_error">%s</div>',$error);
elseif($msg)
    echo sprintf('<div id="msg_notice">%s</div>',$msg);
elseif($warn)
    echo sprintf('<div id="msg_warning">%s</div>',$warn);

echo '<ul class="tabs" id="ticket-preview">';

echo '
        <li class="active"><a id="preview_tab" href="#preview"
            ><i class="icon-list-alt"></i>&nbsp;'.__('Ticket Summary').'</a></li>';
if ($ticket->getThread()->getNumCollaborators()) {
echo sprintf('
        <li><a id="collab_tab" href="#collab"
            ><i class="icon-fixed-width icon-group
            faded"></i>&nbsp;'.__('Collaborators (%d)').'</a></li>',
            $ticket->getThread()->getNumCollaborators());
}
echo '<li><a id="thread_tab" href="#threadPreview"
            ><i class="icon-fixed-width icon-list
            faded"></i>&nbsp;'.__('Thread Preview (6)').'</a></li>';

echo '</ul>';
echo '<div id="ticket-preview_container">';
echo '<div class="tab_content" id="preview">';
echo '<table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';

$ticket_state=sprintf('<span>%s</span>',ucfirst($ticket->getStatus()));
if($ticket->isOpen()) {
    if($ticket->isOverdue())
        $ticket_state.=' &mdash; <span>'.__('Overdue').'</span>';
    else
        $ticket_state.=sprintf(' &mdash; <span>%s</span>',$ticket->getPriority());
}

echo sprintf('
        <tr>
            <th width="100">'.__('Ticket State').':</th>
            <td>%s</td>
        </tr>
        <tr>
            <th>'.__('Created').':</th>
            <td>%s</td>
        </tr>',$ticket_state,
        Format::datetime($ticket->getCreateDate()));
if($ticket->isClosed()) {
    echo sprintf('
            <tr>
                <th>'.__('Closed').':</th>
                <td>%s   <span class="faded">by %s</span></td>
            </tr>',
            Format::datetime($ticket->getCloseDate()),
            ($staff?$staff->getName():'staff')
            );
} elseif($ticket->getEstDueDate()) {
    echo sprintf('
            <tr>
                <th>'.__('Due Date').':</th>
                <td>%s</td>
            </tr>',
            Format::datetime($ticket->getEstDueDate()));
}
echo '</table>';


echo '<hr>
    <table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';
if($ticket->isOpen()) {
    echo sprintf('
            <tr>
                <th width="100">'.__('Assigned To').':</th>
                <td>%s</td>
            </tr>',$ticket->isAssigned()?implode('/', $ticket->getAssignees()):' <span class="faded">&mdash; '.__('Unassigned').' &mdash;</span>');
}
echo sprintf(
    '
        <tr>
            <th>'.__('From').':</th>
            <td><a href="users.php?id=%d" class="no-pjax">%s</a> <span class="faded">%s</span></td>
        </tr>
        <tr>
            <th width="100">'.__('Department').':</th>
            <td>%s</td>
        </tr>
        <tr>
            <th>'.__('Help Topic').':</th>
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

<div class="hidden tab_content" id="collab">
    <table border="0" cellspacing="" cellpadding="1">
        <colgroup><col style="min-width: 250px;"></col></colgroup>
        <?php
        if (($collabs=$ticket->getThread()->getCollaborators())) {?>
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
            echo __("Ticket doesn't have any collaborators.");
        }?>
    </table>
    <br>
    <?php
    echo sprintf('<span><a class="collaborators"
                            href="#tickets/%d/collaborators">%s</a></span>',
                            $ticket->getId(),
                            $ticket->getThread()->getNumCollaborators()
                                ? __('Manage Collaborators') : __('Add Collaborator')
                                );
    ?>
</div>

<!-- Thread Preview HTML Start -->

<div class="hidden tab_content thread-preview" id="threadPreview">
    <div id="ticketThread">
        <div id="thread-items">

<!-- First three entries full visibility -->


            <div id="thread-entry-1">
                <div class="thread-preview-entry collapsed message">

                    <div class="header">
                       <div class="thread-info">

                            <div class="thread-name"><span>John Doe</span>&nbsp;<span>Oct 24, 2015 4:35pm</span></div>
                        </div>
                    </div>

                    <div class="thread-body no-pjax">
                        <div class="thread-teaser">Etiam ligula ex, facilisis eget nisl id, egestas blandit mi. Sed ut lacinia erat, a facilisis ligula. Praesent mollis erat et magna ultricies, cursus vulputate lacus imperdiet. Sed ligula metus, iaculis at malesuada in, aliquet sed erat. Suspendisse ut bibendum magna. Nam vel dolor erat. Donec sagittis diam quis orci hendrerit dapibus. Praesent elementum lectus et imperdiet venenatis. Aliquam quis leo in mi maximus venenatis et nec ipsum. Integer quis tincidunt libero, id varius erat. Sed tempus odio sit amet euismod scelerisque.</div>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>

             <div id="thread-entry-2">
                <div class="thread-preview-entry collapsed response">

                    <div class="header">
                       <div class="thread-info">

                            <div class="thread-name"><span>John Doe</span>&nbsp;<span>Oct 24, 2015 4:35pm</span></div>
                        </div>
                    </div>

                    <div class="thread-body no-pjax">
                        <div class="thread-teaser truncate">Etiam ligula ex, facilisis eget nisl id, egestas blandit mi. Sed ut lacinia erat, a facilisis ligula. Praesent mollis erat et magna ultricies, cursus vulputate lacus imperdiet. Sed ligula metus, iaculis at malesuada in, aliquet sed erat. Suspendisse ut bibendum magna. Nam vel dolor erat. Donec sagittis diam quis orci hendrerit dapibus. Praesent elementum lectus et imperdiet venenatis. Aliquam quis leo in mi maximus venenatis et nec ipsum. Integer quis tincidunt libero, id varius erat. Sed tempus odio sit amet euismod scelerisque.</div>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- End Thread Preview HTML -->

<?php
$options = array();
$options[]=array('action'=>sprintf(__('Thread (%d)'),$ticket->getThreadCount()),'url'=>"tickets.php?id=$tid");
if($ticket->getNumNotes())
    $options[]=array('action'=>sprintf(__('Notes (%d)'),$ticket->getNumNotes()),'url'=>"tickets.php?id=$tid#notes");

if($ticket->isOpen())
    $options[]=array('action'=>__('Reply'),'url'=>"tickets.php?id=$tid#reply");

if ($role->hasPerm(TicketModel::PERM_ASSIGN))
    $options[]=array('action'=>($ticket->isAssigned()?__('Reassign'):__('Assign')),'url'=>"tickets.php?id=$tid#assign");

if ($role->hasPerm(TicketModel::PERM_TRANSFER))
    $options[]=array('action'=>__('Transfer'),'url'=>"tickets.php?id=$tid#transfer");

$options[]=array('action'=>__('Post Note'),'url'=>"tickets.php?id=$tid#note");

if ($role->hasPerm(TicketModel::PERM_EDIT))
    $options[]=array('action'=>__('Edit Ticket'),'url'=>"tickets.php?id=$tid&a=edit");

if($options) {
    echo '<ul class="tip_menu">';
    foreach($options as $option)
        echo sprintf('<li><a href="%s">%s</a></li>',$option['url'],$option['action']);
    echo '</ul>';
}

echo '</div>';
?>
<script type="text/javascript">
    $('.thread-preview-entry').click(function (){
        if ($(this).hasClass('collapsed')) {
            $(this).removeClass('collapsed',500);
        } else {
            $('.header').click(function () {
                $(this).closest('.thread-preview-entry').addClass('collapsed',500);
            });
        }
    })


 </script>
