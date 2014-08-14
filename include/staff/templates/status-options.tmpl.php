<?php
$actions= array(
        'delete' => array(
            'action'  => __('Delete'),
            'icon'  => 'icon-trash',
            'states' => array('deleted'),
            ),
        'archive' => array(
            'action'  => __('Archive'),
            'icon' => 'icon-archive',
            'states' => array('archived')
            ),
        'close' => array(
            'action' => __('Close'),
            'icon'  => 'icon-repeat',
            'states' => array('closed')
            ),
        'resolve' => array(
            'action' => __('Resolve'),
            'icon'  => 'icon-ok-circle',
            'states' => array('resolved')
            ),
        'reopen' => array(
            'action' =>  __('Reopen'),
            'icon'  => 'icon-undo',
            'states' => array('open')
            ),
        );

foreach($actions as $k => $v) {
    $criteria = array('states' => $v['states']);
    if (!($statuses = TicketStatusList::getStatuses($criteria)->all()))
        continue;

    if ($statuses && count($statuses) > 1) {
    ?>
        <span
            class="action-button"
            data-dropdown="#action-dropdown-<?php echo $k; ?>">
            <a id="tickets-<?php echo $k; ?>"
                class="tickets-action"
                href="#tickets/status/<?php echo $k; ?>"><i
                class="<?php echo $v['icon']; ?>"></i> <?php echo $v['action']; ?></a>
            <i class="icon-caret-down"></i>
        </span>
        <div id="action-dropdown-<?php echo $k; ?>"
            class="action-dropdown anchor-right">
          <ul>
            <?php
            foreach ($statuses as $s) {
                ?>

             <li>
                 <a class="no-pjax tickets-action"
                    href="#tickets/status/<?php echo $k; ?>/<?php
                    echo $s->getId(); ?>"> <i
                        class="icon-tag"></i> <?php echo $s->getName(); ?></a> </li>
            <?php
            } ?>
          </ul>
        </div>
    <?php
    } else {
    ?>
        <a id="tickets-<?php echo $k; ?>" class="action-button tickets-action"
            href="#tickets/status/<?php echo $k; ?>"><i
            class="<?php echo $v['icon']; ?>"></i> <?php echo $v['action']; ?></a>
<?php
    }
}
?>
