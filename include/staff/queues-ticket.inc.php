<?php
require_once INCLUDE_DIR . 'class.queue.php';
?>
<form action="queues.php?t=tickets" method="POST" name="keys">
    <div>
        <div class="pull-right">
            <a href="queues.php?t=tickets&amp;a=add" class="green button action-button"><i class="icon-plus-sign"></i> <?php echo __('Add New Queue');?></a>
            <span class="action-button" data-dropdown="#action-dropdown-more">
                        <i class="icon-caret-down pull-right"></i>
                        <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
            </span>
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
                <ul id="actions">
                    <li>
                        <a class="confirm" data-name="enable" href="queues.php?t=tickets&amp;a=enable">
                            <i class="icon-ok-sign icon-fixed-width"></i>
                            <?php echo __( 'Enable'); ?>
                        </a>
                    </li>
                    <li>
                        <a class="confirm" data-name="disable" href="queues.php?t=tickets&amp;a=disable">
                            <i class="icon-ban-circle icon-fixed-width"></i>
                            <?php echo __( 'Disable'); ?>
                        </a>
                    </li>
                    <li class="danger">
                        <a class="confirm" data-name="delete" href="queues.php?t=tickets&amp;a=delete#queues">
                            <i class="icon-trash icon-fixed-width"></i>
                            <?php echo __( 'Delete'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <h3><?php echo __('Ticket Queues');?></h3>
    </div>
    <div class="clear"></div>
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th width="46%"><a <?php echo $key_sort; ?> href="queues.php?t=tickets&amp;<?php echo $qstr; ?>&sort=name#queues"><?php echo __('Name');?></a></th>
            <th width="12%"><a <?php echo $ip_sort; ?> href="queues.php?t=tickets&amp;<?php echo $qstr; ?>&sort=creator#queues"><?php echo __('Creator');?></a></th>
            <th width="8%"><a  <?php echo $status_sort; ?> href="queues.php?t=tickets&amp;<?php echo $qstr; ?>&sort=status#queues"><?php echo __('Status');?></a></th>
            <th width="10%" nowrap><a  <?php echo $date_sort; ?>href="queues.php?t=tickets&amp;<?php echo $qstr; ?>&sort=date#queues"><?php echo __('Created');?></a></th>
        </tr>
    </thead>
    <tbody>
<?php foreach (CustomQueue::objects() as $q) { ?>
    <tr>
      <td><input type="checkbox" class="checkbox" name="ckb[]"></td>
      <td><a href="queues.php?id=<?php echo $q->getId(); ?>"><?php
        echo Format::htmlchars($q->getFullName()); ?></a></td>
      <td><?php echo Format::htmlchars($q->staff->getName()); ?></td>
      <td><?php echo Format::htmlchars($q->getStatus()); ?></td>
      <td><?php echo Format::date($q->created); ?></td>
    </tr>
<?php } ?>
    </tbody>
</table>
</form>
