<?php
global $thisstaff, $ticket;
// Map states to actions
$actions= array(
        'closed' => array(
            'icon'  => 'icon-repeat',
            'action' => 'close'
            ),
        'resolved' => array(
            'icon'  => 'icon-ok-circle',
            'action' => 'resolve'
            ),
        'open' => array(
            'icon'  => 'icon-undo',
            'action' => 'reopen'
            ),
        );
?>

<span
    class="action-button"
    data-dropdown="#action-dropdown-statuses">
    <a class="tickets-action"
        href="#statuses"><i
        class="icon-flag"></i> <?php
        echo __('Change Status'); ?></a>
    <i class="icon-caret-down"></i>
</span>
<div id="action-dropdown-statuses"
    class="action-dropdown anchor-right">
    <ul>
    <?php
    $states = array('open');
    if ($thisstaff->canCloseTickets())
        $states = array_merge($states,
                array('resolved', 'closed'));

    $statusId = $ticket ? $ticket->getStatusId() : 0;
    foreach (TicketStatusList::getStatuses(
                array('states' => $states))->all() as $status) {
        if (!isset($actions[$status->getState()])
                || $statusId == $status->getId())
            continue;
        ?>
        <li>
            <a class="no-pjax <?php
                echo $ticket? 'ticket-action' : 'tickets-action'; ?>"
                href="<?php
                    echo sprintf('#%s/status/%s/%d',
                            $ticket ? ('tickets/'.$ticket->getId()) : 'tickets',
                            $actions[$status->getState()]['action'],
                            $status->getId()); ?>"><i class=" aaa <?php
                        echo $actions[$status->getState()]['icon'] ?: 'icon-tag';
                    ?>"></i> <?php
                        echo __($status->getName()); ?></a>
        </li>
    <?php
    } ?>
    </ul>
</div>
