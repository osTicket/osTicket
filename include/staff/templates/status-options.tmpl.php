<?php
global $thisstaff, $ticket, $task;

$object = $task ?: $ticket;
$objectName = $task ? 'task' : 'ticket';
$role = $object ? $object->getRole($thisstaff) : $thisstaff->getRole();
if ($role && !$role->hasPerm(Ticket::PERM_CLOSE))
    return;

// Map states to actions
$actions= array(
        'closed' => array(
            'icon'  => 'icon-ok-circle',
            'action' => 'close',
            'href' => sprintf('%ss.php', $objectName)
            ),
        'open' => array(
            'icon'  => 'icon-undo',
            'action' => 'reopen'
            ),
        );

$states = array('open');
if (!$object || $object->isCloseable())
    $states[] = 'closed';

$nextStatuses = array();
if ($objectName == 'task') {
    $statusName = ($object->getStatus() == 'Open') ? 'Closed' : 'Open';
    $status = TicketStatus::objects()
        ->filter(array('name' => $statusName))
        ->first();
    $nextStatuses[] = $status;
} else {
    $statusId = $object ? $object->getStatusId() : 0;
    foreach (TicketStatusList::getStatuses(
                array('states' => $states)) as $status) {
        if (!isset($actions[$status->getState()])
                || $statusId == $status->getId())
            continue;
        $nextStatuses[] = $status;
    }
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
                echo $object ? sprintf('%s-action', $objectName) : sprintf('%ss-action', $objectName); ?>"
                href="<?php
                    echo sprintf('#%s/status/%s/%d',
                            $object ? (sprintf('%ss/%d', $objectName, $object->getId())) : sprintf('%ss', $objectName),
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
