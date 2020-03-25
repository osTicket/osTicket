<div class="pull-right">
   
        <a class="btn btn-sm btn-success   title="Add New Queue" data-url="queues.php?t=tickets&amp;a=add" data-dialog-config="{&quot;size&quot;:&quot;large&quot;}" href="queues.php?t=tickets&amp;a=add">
            <i class="fa fa-plus"></i>
            </a>
            
     <div class="btn-group btn-group-sm pull-right" role="group" aria-label="Button group with nested dropdown">
 

        
    
        <div class="btn-group btn-group-sm" role="group">
            <button id="btnGroupDrop1" type="button" class="btn btn-light  waves-effect  btn-nbg dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-cog" data-placement="bottom" data-toggle="tooltip" title="" data-original-title="More"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1" id="action-dropdown-change-priority">
                                   
                                    
                                    <a class="dropdown-item queue-action no-pjax" data-action="enable" href="#queues.php"><i class="icon-ok-sign icon-fixed-width style=" color:#d9534f;"=""></i> Enable</a>
          													
          													<a class="dropdown-item queue-action no-pjax" data-action="disable" href="#queues.php"><i class="icon-ban-circle icon-fixed-width style=" color:#d9534f;"=""></i> Disable</a>
           
                                    <a class="dropdown-item queue-action no-pjax" data-action="delete" href="#queues.php"><i class="icon-fixed-width icon-trash style=" color:#d9534f;"=""></i> Delete</a>
           
                    </div>
        </div>
 
</div>
        <input type="hidden" name="do" value="mass_process" />
        <h3><?php echo __('Ticket Queues');?></h3>
    </div>
    <div class="clear"></div>
 <?php csrf_token(); ?>
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
$all_queues = CustomQueue::queues()->getIterator();
$emitLevel = function($queues, $level=0) use ($all_queues, &$emitLevel) {
    $queues->sort(function($a) { return $a->sort; });
    foreach ($queues as $q) { ?>
      <tr>
<?php if ($level) { ?>
        <td colspan="<?php echo max(1, $level); ?>"></td>
<?php } ?>
        <td>
          <input type="checkbox" class="mass checkbox"  name="qids[]" value="<?php echo $q->id; ?>" />
          <input type="hidden" name="qsort[<?php echo $q->id; ?>]"
            value="<?php echo $q->sort; ?>"/>
        </td>
        <td width="63%" colspan="<?php echo max(1, 5-$level); ?>"><a
          href="queues.php?id=<?php echo $q->getId(); ?>"><?php
          echo Format::htmlchars($q->getFullName()); ?></a>
          <i class="faded-more pull-right icon-sort"></i></td>
        <td><?php echo Format::htmlchars($q->staff ? $q->staff->getName() :
        __('SYSTEM')); ?></td>
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

<script>
$(function() {
  var goBaby = function(action) {
    var ids = [], that = this,
        $form = $(this).closest('form');
    $('input:checkbox.mass:checked', $form).each(function() {
      ids.push($(this).val());
    });
    if (ids.length) {
      $.confirm(__('You sure?')).then(function() {
        $.each(ids, function() { $form.append($('<input type="hidden" name="ids[]">').val(this)); });
        $form.append($('<input type="hidden" name="a" />')
          .val($(that).data('action')));
        $form.append($('<input type="hidden" name="count" />')
          .val(ids.length));
        $form.attr('action', action);
        // I don't know why, but submitting the form doesn't work...
        $form.find('[type=submit]').trigger('click');
      });
    }
    else {
      $.sysAlert(__('Oops'),
        __('You need to select at least one item'));
    }
  };
  $(document).on('click', 'a.queue-action', function(e) {
    e.preventDefault();
    goBaby.call(this, $(this).attr('href').substr(1));
    return false;
  });
});
</script>