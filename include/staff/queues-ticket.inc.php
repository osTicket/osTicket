<?php
require_once INCLUDE_DIR . 'class.queue.php';
?>
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
            <th width="3%">&nbsp;</th>
            <th colspan="5" width="47%"><?php echo __('Name');?></th>
            <th width="12%"><?php echo __('Creator');?></th>
            <th width="8%"><?php echo __('Status');?></th>
            <th width="10%" nowrap><?php echo __('Created');?></th>
        </tr>
    </thead>
    <tbody class="sortable-rows" data-sort="qsort">
<?php
$all_queues = CustomQueue::objects()->all();
$emitLevel = function($queues, $level=0) use ($all_queues, &$emitLevel) { 
    $queues->sort(function($a) { return $a->sort; });
    foreach ($queues as $q) { ?>
      <tr>
<?php if ($level) { ?>
        <td colspan="<?php echo max(1, $level); ?>"></td>
<?php } ?>
        <td>
          <input type="checkbox" class="checkbox" name="ckb[]">
          <input type="hidden" name="qsort[<?php echo $q->id; ?>]"
            value="<?php echo $q->sort; ?>"/>
        </td>
        <td width="63%" colspan="<?php echo max(1, 5-$level); ?>"><a
          href="queues.php?id=<?php echo $q->getId(); ?>"><?php
          echo Format::htmlchars($q->getFullName()); ?></a></td>
        <td><?php echo Format::htmlchars($q->staff->getName()); ?></td>
        <td><?php echo Format::htmlchars($q->getStatus()); ?></td>
        <td><?php echo Format::date($q->created); ?></td>
      </tr>
<?php
        $children = $all_queues->findAll(array('parent_id' => $q->id));
        if (count($children)) {
            $emitLevel($children, $level+1);
        }
    }
};

$emitLevel($all_queues->findAll(array('parent_id' => 0)));
?>
    </tbody>
</table>
