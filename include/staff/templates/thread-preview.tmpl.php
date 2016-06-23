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
        '<div style="min-width:400px; padding: 2px 2px 0 5px;" id="t%s"> 
         <h2>'.__('<a href="%s">Ticket #%s').': %s</a></h2>',
         $ticket->getNumber(),
         Ticket::getLink( $ticket->getId()),
         $ticket->getNumber(),
         Format::htmlchars($ticket->getSubject()));

if($error)
    echo sprintf('<div id="msg_error">%s</div>',$error);
elseif($msg)
    echo sprintf('<div id="msg_notice">%s</div>',$msg);
elseif($warn)
    echo sprintf('<div id="msg_warning">%s</div>',$warn);

echo '<ul class="tabs" id="thread-preview">';


echo '<li ><a id="thread_preview_tab" href="#threadPreview"
            ><i class="icon-fixed-width icon-list
            faded"></i>&nbsp;'.__('Thread Preview').'</a></li>';

echo '</ul>';
echo '<div id="ticket-preview_container">';
echo '<div class="tab_content" id="preview">';
?>
        <?php
        include STAFFINC_DIR.'templates/thread-entries-preview.tmpl.php';
        ?>

</div>
<!-- End Thread Preview -->

<script type="text/javascript">
    $('.thread-preview-entry').on('click', function(){
        if($(this).hasClass('collapsed')) {
            $(this).removeClass('collapsed', 100);
        }
    });

    $('.header').on('click', function(){
        if(!$(this).closest('.thread-preview-entry').hasClass('collapsed')) {
            $(this).closest('.thread-preview-entry').addClass('collapsed', 100);
        }
    });


 </script>
