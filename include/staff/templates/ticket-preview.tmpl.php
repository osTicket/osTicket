<?php
/*
 * Ticket Preview popup template
 *
 */

$staff=$ticket->getStaff();
$lock=$ticket->getLock();
$role=$ticket->getRole($thisstaff);
$error=$msg=$warn=null;
$thread = $ticket->getThread();

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
if ($thread && $thread->getNumCollaborators()) {
echo sprintf('
        <li><a id="collab_tab" href="#collab"
            ><i class="icon-fixed-width icon-group
            faded"></i>&nbsp;'.__('Collaborators (%d)').'</a></li>',
            $thread->getNumCollaborators());
}
echo '<li><a id="thread_tab" href="#threadPreview"
            ><i class="icon-fixed-width icon-list
            faded"></i>&nbsp;'.__('Thread Preview').'</a></li>';

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
?>
<?php
foreach (DynamicFormEntry::forTicket($ticket->getId()) as $form) {
    // Skip core fields shown earlier in the ticket preview
    $answers = $form->getAnswers()->exclude(Q::any(array(
        'field__flags__hasbit' => DynamicFormField::FLAG_EXT_STORED,
        'field__name__in' => array('subject', 'priority')
    )));
    $displayed = array();
    foreach($answers as $a) {
        if (!($v = $a->display()))
            continue;
        $displayed[] = array($a->getLocal('label'), $v);
    }
    if (count($displayed) == 0)
        continue;

    echo '<hr>';
    echo '<table border="0" cellspacing="" cellpadding="1" width="100%" style="margin-bottom:0px;" class="ticket_info">';
    echo '<tbody>';

    foreach ($displayed as $stuff) {
        list($label, $v) = $stuff;
        echo '<tr>';
        echo '<th width="20%" style="white-space: nowrap;">'.Format::htmlchars($label).':</th>';
        echo '<td>'.$v.'</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
echo '</div>'; // ticket preview content.
?>

<div class="hidden tab_content" id="collab">
    <table border="0" cellspacing="" cellpadding="1">
        <colgroup><col style="min-width: 250px;"></col></colgroup>
        <?php
        if ($thread && ($collabs=$thread->getCollaborators())) {?>
        <?php
            foreach($collabs as $collab) {
                echo sprintf('<tr><td %s>%s
                        <a href="users.php?id=%d" class="no-pjax">%s</a> <em>&lt;%s&gt;</em></td></tr>',
                        ($collab->isActive()? '' : 'class="faded"'),
                        (($U = $collab->getUser()) && ($A = $U->getAvatar()))
                            ? $A->getImageTag(20) : sprintf('<i class="icon-%s"></i>',
                                $collab->isActive() ? 'comments' :  'comment-alt'),
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
                            href="#thread/%d/collaborators/1">%s</a></span>',
                            $thread->getId(),
                            $thread && $thread->getNumCollaborators()
                                ? __('Manage Collaborators') : __('Add Collaborator')
                                );
    ?>
</div>
<div class="hidden tab_content thread-preview" id="threadPreview">
    <div id="ticketThread">
        <div id="thread-items">
        <?php
        include STAFFINC_DIR.'templates/thread-entries-preview.tmpl.php';
        ?>
        </div>
    </div>
</div>
<?php
$options = array();
$options[]=array('action'=>sprintf(__('Thread (%d)'),$ticket->getThreadCount()),'url'=>"tickets.php?id=$tid");
if($ticket->getNumNotes())
    $options[]=array('action'=>sprintf(__('Notes (%d)'),$ticket->getNumNotes()),'url'=>"tickets.php?id=$tid#notes");

if($ticket->isOpen())
    $options[]=array('action'=>__('Reply'),'url'=>"tickets.php?id=$tid#reply");

if ($role->hasPerm(Ticket::PERM_ASSIGN))
    $options[]=array('action'=>($ticket->isAssigned()?__('Reassign'):__('Assign')),'url'=>"tickets.php?id=$tid#assign");

if ($role->hasPerm(Ticket::PERM_TRANSFER))
    $options[]=array('action'=>__('Transfer'),'url'=>"tickets.php?id=$tid#transfer");

$options[]=array('action'=>__('Post Note'),'url'=>"tickets.php?id=$tid#note");

if ($role->hasPerm(Ticket::PERM_EDIT))
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
    $('.thread-preview-entry').on('click', function(){
        if($(this).hasClass('collapsed')) {
            $(this).removeClass('collapsed', 500);
        }
    });

    $('.header').on('click', function(){
        if(!$(this).closest('.thread-preview-entry').hasClass('collapsed')) {
            $(this).closest('.thread-preview-entry').addClass('collapsed', 500);
        }
    });
 </script>
