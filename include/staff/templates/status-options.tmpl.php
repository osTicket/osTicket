<?php
global $thisstaff, $ticket;
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
?>

<span
    class="action-button pull-right"
    data-dropdown="#action-dropdown-statuses">
    <i class="icon-caret-down pull-right"></i>
    <a class="tickets-action"
        href="#statuses"><i
        class="icon-flag"></i> <?php
        echo __('Change Status'); ?></a>
</span>
<div id="action-dropdown-statuses"
    class="action-dropdown anchor-right">
    <ul>
    <?php
    $states = array('open');
    if ($thisstaff->canCloseTickets())
        $states = array_merge($states, array('closed'));

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
                            $status->getId()); ?>"
                <?php
                if (isset($actions[$status->getState()]['href']))
                    echo sprintf('data-href="%s"',
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
