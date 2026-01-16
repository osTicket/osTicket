<?php if ($desc = $event->getDescription(ThreadEvent::MODE_STAFF)) { ?>
<div class="thread-event <?php if ($event->uid) echo 'action'; ?>">
        <span class="type-icon">
          <i class="faded icon-<?php echo $event->getIcon(); ?>"></i>
        </span>
        <span class="faded description">
            <?php echo $desc; ?>
        </span>
</div>
<?php } ?>
