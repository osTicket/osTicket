<?php
$desc = $event->getDescription(ThreadEvent::MODE_CLIENT);
if (!$desc)
    return;
?>

<div class="timeline-2"><div class="time-item">
                                    <div class="item-info <?php if ($event->uid) echo 'action'; ?>">
        <span class="type-icon">
          <i class="faded icon-<?php echo $event->getIcon(); ?>"></i>
        </span>
        <span class="faded description">
            <?php echo $desc; ?>
        </span>
</div>
</div></div>