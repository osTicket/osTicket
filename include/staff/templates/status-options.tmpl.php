<?php
$actions= array(
        'close' => array(
            'icon'  => 'icon-repeat',
            'state' => 'closed'
            ),
        'resolve' => array(
            'icon'  => 'icon-ok-circle',
            'state' => 'resolved'
            ),
        'reopen' => array(
            'icon'  => 'icon-undo',
            'state' => 'open'
            ),
        );

foreach($actions as $k => $v) {
    $criteria = array('states' => array($v['state']));
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
                class="<?php echo $v['icon']; ?>"></i> <?php
                echo TicketStateField::getVerb($v['state']); ?></a>
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
                        class="icon-tag"></i> <?php echo __($s->getName()); ?></a> </li>
            <?php
            } ?>
          </ul>
        </div>
    <?php
    } else {
    ?>
        <a id="tickets-<?php echo $k; ?>" class="action-button tickets-action"
            href="#tickets/status/<?php echo $k; ?>"><i
            class="<?php echo $v['icon']; ?>"></i> <?php
            echo TicketStateField::getVerb($v['state']); ?></a>
<?php
    }
}
?>
