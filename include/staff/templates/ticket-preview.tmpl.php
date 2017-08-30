<?php
/*
 * Ticket Preview popup template
 *
 */

$staff=$ticket->getStaff();
$lock=$ticket->getLock();
$role=$thisstaff->getRole($ticket->getDeptId());
$error=$msg=$warn=null;
$thread = $ticket->getThread();

if($lock && $lock->getStaffId()==$thisstaff->getId())
    $warn.='&nbsp;<span class="Icon lockedTicket">'
    .sprintf(__('Ticket is locked by %s'), $lock->getStaffName()).'</span>';
elseif($ticket->isOverdue())
    $warn.='&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

echo sprintf(
        '<div style="min-width:450px; padding: 2px 2px 0 5px;" id="t%s">
         <h5>'.__('Ticket #%s').': %s</h5>',
         $ticket->getNumber(),
         $ticket->getNumber(),
         Format::htmlchars($ticket->getSubject()));

if($error)
    echo sprintf('<div id="msg_error">%s</div>',$error);
elseif($msg)
    echo sprintf('<div id="msg_notice">%s</div>',$msg);
elseif($warn)
    echo sprintf('<div id="msg_warning">%s</div>',$warn);

    
$ticket_state=sprintf('<span>%s</span>',ucfirst($ticket->getStatus()));
if($ticket->isOpen()) {
    if($ticket->isOverdue())
        $ticket_state.=' &mdash; <span>'.__('Overdue').'</span>';
    else
        $ticket_state.=sprintf(' &mdash; <span>%s</span>',$ticket->getPriority());
}



$ticket_state=sprintf('<span>%s</span>',ucfirst($ticket->getStatus()));
if($ticket->isOpen()) {
    if($ticket->isOverdue())
        $ticket_state.=' &mdash; <span>'.__('Overdue').'</span>';
    else
        $ticket_state.=sprintf(' &mdash; <span>%s</span>',$ticket->getPriority());
}
?>


    
   <div class="btn-group btn-group-sm  m-b-10" role="group" aria-label="Button group with nested dropdown">

         
         <a class="btn btn-light waves-effect" href="tickets.php?id=<?php echo $tid;?>#reply" id="post-reply" data-placement="bottom" data-toggle="tooltip"data-original-title="Post Reply">
                    <i class="fa fa-reply"></i></a>
                    
                    
         <a class="btn btn-light waves-effect" href="tickets.php?id=<?php echo $tid;?>#note" id="post-note" data-placement="bottom" data-toggle="tooltip" data-original-title="Post Internal Note">
                    <i class="fa fa-pencil-square-o"></i></a>
                  
        
    <a class="btn btn-light waves-effect" href="tickets.php?id=10202#tickets/10202/assign/agents" id="post-note" data-placement="bottom" data-toggle="tooltip" title="" data-original-title="Assign">
                    <i class="fa fa-user"></i></a>                    
                    
         
        
    </div>  
         

