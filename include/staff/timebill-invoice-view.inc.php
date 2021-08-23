<?php

if ( isset( $_POST['submit'] ) ) {
    //actions to take on updating ticket box
    $ticket=Ticket::lookup($_REQUEST['id']);
    foreach ($ticket->getThread()->getEntries()
            ->exclude([
                'poster' => 'SYSTEM',
                'time_spent' => 0,
            ])
            ->filter([
                'time_bill' => 1,
                'type__in' => ['R', 'N'],
                //'time_invoice' => 0,
            ])
        as $entry) {
        if (isset($_POST['invoiced'][$entry->getId()])) {
            $entry->setTimeInvoice($_POST['invoiced'][$entry->getId()]);    
        }
    }
}

$ticket = $user = null; //clean start.
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if($_REQUEST['id']) {
	if (isset($_GET['search'])) {
		$TicketID=Ticket::getIdByNumber($_REQUEST['id']);
	} else {
		$TicketID=$_REQUEST['id'];
	}
    if(!($ticket=Ticket::lookup($TicketID)))
         $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('ticket'));
    elseif(!$thisstaff->canAccess($ticket)) {
        $errors['err']=__('Access denied. Contact admin if you believe this is in error');
        $ticket=null; //Clear ticket obj.
    }
}



#if($ticket) {
#    $ost->setPageTitle(sprintf(__('Ticket #%s Invoice'),$ticket->getNumber()));
#}



if(!$errors) {
    // Retrieve Ticket Information
    $Subject = $ticket->getSubject();
    $TicketNo = $ticket->getNumber();
}


if(!$errors) {
?>

    <h1><?php echo __('Time and Billing Invoice'); ?></h1>
    
    <h2><?php echo __('Ticket Information'); ?></h2>
    <p><b><?php echo __('Ticket'); ?>:</b> <a href="tickets.php?id=<?php echo $ticket->getId() ?>">#<?php echo $TicketNo; ?></a><br />
        <b><?php echo __('Subject'); ?>:</b> <?php echo $Subject; ?> <br />
        <b><?php echo __('Generated'); ?>:</b> <?php echo Format::datetime(Misc::gmtime(), false); ?>
    </p>
    <p>&nbsp;</p>
    
    <h2><?php echo __('Time Summary'); ?></h2>
    <p>
<?php   foreach ($ticket->getTimeTotalsByType() as $name=>$total) {
            echo sprintf('%s %s<br/>', Ticket::formatTime($total), $name);
        } ?>
    </p>
    <p>&nbsp;</p>
    
    <h2><?php echo __('Time History / Detail'); ?></h2>
    <form action="timebill.php?id=<?php echo $ticket->getId() ?>&view=invoice" method="post" id="save">
    <?php csrf_token(); ?>
    <table class="list" border="0" cellspacing="1" cellpadding="2" width="940">
        <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Post Type'); ?></th>
            <th><?php echo __('Poster'); ?></th>
            <th><?php echo __('Time Spent'); ?></th>
            <th><?php echo __('Time Type'); ?></th>
            <th><?php echo __('Invoiced'); ?></th>
        </tr>
<?php   foreach ($ticket->getThread()->getEntries()
            ->exclude([
                'poster' => 'SYSTEM',
                'time_spent' => 0,
            ])
            ->filter([
                'time_bill' => 1,
                'type__in' => ['R', 'N'],
                //'time_invoice' => 0,
            ])
        as $entry) {
            echo '<tr>';
                echo "<td>" . Format::datetime($entry->created) . "</td>";
                if ($entry->type=="R") {
                    echo "<td>Response to Customer</td>";
                }
                if ($entry->type=="N") {
                    echo "<td>Internal Note</td>";
                }
                echo "<td>" . Format::htmlchars($entry->poster) . "</td>";
                echo "<td>" . Ticket::formatTime($entry->time_spent) . "</td>";
                echo "<td>" . $time_types[$entry->time_type] . "</td>";
                ?>
                <td>
                    <input type="hidden" name="invoiced[<?php echo $entry->id; ?>]" value="0" />
                    <input type="checkbox" name="invoiced[<?php echo $entry->id; ?>]" value="1" <?php echo $entry->time_invoice?'checked="checked"':''; ?>>
                </td>
                <?php
            echo '</tr>';
        }
        ?>
    </table>
    
    <p style="padding-left:210px;">
        <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes'); ?>">
        <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes'); ?>">
    </p>
    </form>
    
<?php
} else {
?>
    <h1><?php echo __('Billing Report'); ?></h1>
    <p><?php echo __('You do not have access to this report.'); ?></p>
<?php
}
?>