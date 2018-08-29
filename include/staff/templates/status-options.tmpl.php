<?php
global $thisstaff, $ticket;

$role = $ticket ? $ticket->getRole($thisstaff) : $thisstaff->getRole();
if ($role && !$role->hasPerm(Ticket::PERM_CLOSE))
    return;

// Map states to actions
$actions= array(
        'closed' => array(
            'icon'  => 'icon-ok-circle',
            'action' => 'close',
            'href' => 'tickets.php'
            ),
        'open' => array(
            'icon'  => 'icon-undo',
            'action' => 'reopen'
            ),
        );

$states = array('open');
<<<<<<< HEAD
<<<<<<< HEAD
if ($thisstaff->getRole($ticket ? $ticket->getDeptId() : null)->hasPerm(Ticket::PERM_CLOSE)
        && (!$ticket || !$ticket->getMissingRequiredFields()))
=======
if ($thisstaff->getRole($ticket ? $ticket->getDeptId() : null)->hasPerm(TicketModel::PERM_CLOSE)
        && (!$ticket || !Ticket::getMissingRequiredFields($ticket)))
>>>>>>> issue: Check Missing Required Fields
    $states = array_merge($states, array('closed'));
=======
if (!$ticket || $ticket->isCloseable())
    $states[] = 'closed';
>>>>>>> Roles: Cleanup how roles are fetched and checked

$statusId = $ticket ? $ticket->getStatusId() : 0;
$nextStatuses = array();
foreach (TicketStatusList::getStatuses(
            array('states' => $states)) as $status) {
    if (!isset($actions[$status->getState()])
            || $statusId == $status->getId())
        continue;
    $nextStatuses[] = $status;
}

if (!$nextStatuses)
    return;
?>

<span
    class="action-button"
    data-dropdown="#action-dropdown-statuses" data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Change Status'); ?>">
    <i class="icon-caret-down pull-right"></i>
    <a class="tickets-action"
        aria-label="<?php echo __('Change Status'); ?>"
        href="#statuses"><i
        class="icon-flag"></i></a>
</span>
<div id="action-dropdown-statuses"
    class="action-dropdown anchor-right">
    <ul>
<?php foreach ($nextStatuses as $status) { ?>
        <li>
            <a class="no-pjax <?php
                echo $ticket? 'ticket-action' : 'tickets-action'; ?>"
                href="<?php
                    echo sprintf('#%s/status/%s/%d',
                            $ticket ? ('tickets/'.$ticket->getId()) : 'tickets',
                            $actions[$status->getState()]['action'],
                            $status->getId()); ?>"
                <?php
                if (isset($actions[$status->getState()]['href']))
                    echo sprintf('data-redirect="%s"',
                            $actions[$status->getState()]['href']);

                ?>
                ><i class="<?php
                        echo $actions[$status->getState()]['icon'] ?: 'icon-tag';
                    ?>"></i> <?php
                        echo __($status->getName()); ?></a>
        </li>
    <?php
    } ?>
    </ul>
</div>
