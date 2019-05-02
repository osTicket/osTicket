<?php
    if ($info['error']) {
        echo sprintf('<p id="msg_error">%s</p>', $info['error']);
    } elseif ($info['warn']) {
        echo sprintf('<p id="msg_warning">%s</p>', $info['warn']);
    } elseif ($info['msg']) {
        echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
    } elseif ($info['notice']) {
       echo sprintf('<p id="msg_info"><i class="icon-info-sign"></i> %s</p>',
               $info['notice']);
    }
?>
<h3 class="drag-handle"><i class="icon-code-fork"></i> <?php echo __('Merge Tickets'); ?></i></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/><?php echo __(
'Choose which Tickets to merge into this one. The Ticket on top will be the Parent Ticket. Sort the order of the Tickets by clicking and dragging them.'
);
?>
<br/>
<br/>
<form method="post" action="<?php echo $info['action']; ?>" onsubmit="refreshAndClose();">
<div id="ticket-entries">
<?php

foreach ($tickets as $t) {
    if ($ticket->getId() == $t['ticket_pid'])
        $visual = true;
?>
<div class="<?php if ($visual) echo 'sortable'; ?> row-item" data-id="<?php echo $t['ticket_id']; ?>">
    <input type="hidden" name="tids[]" value="<?php echo $t['number']; ?>" />
    <i class="icon-reorder"></i> <?php echo $t['number'];
    if (!is_null($t['ticket_pid'])) { ?>
    <div class="button-group">
    <div class="<?php if ($visual) echo 'delete'; ?>"><a href="#" onclick="javascript:
        var value = <?php echo $t['ticket_id']; ?>;
        $('#ticket-entries').append($('<input/>').attr({name:'dtids[]', type:'hidden'}).val(value))
        $(this).closest('div.row-item').remove();$('#delete-warning').show();">
        <?php if ($visual) { ?><i class="icon-trash"></i><?php } ?></a></div>
    </div>
    <?php } ?>
</div>
<?php } ?>
</div>
<br/>
<label class="inline checkbox">
    <?php echo __('Show Children Threads') ?>
    <input type="checkbox" name="show_children" value="1" <?php echo $ticket->hasFlag(Ticket::FLAG_SHOW_CHILDREN)?'checked="checked"':''; ?> >
</label>
<?php
if (!$ticket->isMerged()) {  ?>
<hr/>
<div>
<i class="icon-plus"></i>&nbsp;
<span>
    <select class="ticketSelection" name="name" id="ticket-number"
    data-placeholder="<?php echo __('Select Ticket'); ?>">
  </select>
</span>
<button type="button" class="inline green button" onclick="javascript:
    var select = $(this).parent().find('select'),
        $sel = select.find('option:selected'),
        id = $sel.val();

    if ($sel.prop('disabled'))
        return;
    $('#ticket-entries').append($('<div></div>').addClass('sortable row-item')
        .text(' '+$sel.text())
        .data('id', id)
        .prepend($('<i>').addClass('icon-reorder'))
        .append($('<input/>').attr({name:'tids[]', type:'hidden'}).val(id))
        .append($('<div></div>').addClass('button-group')
          .append($('<div></div>').addClass('delete')
            .append($('<a href=\'#\'>')
              .append($('<i>').addClass('icon-trash'))
              .click(function() {
                $sel.prop('disabled',false);
                $(this).closest('div.row-item').remove();
                $('#delete-warning').show();
                return false;
              })
            )
        ))
    );
    $sel.prop('disabled',true);"><i class="icon-plus-sign"></i>
<?php echo __('Add a Ticket'); ?></button>
</div>

<div>
    <hr>
    <?php echo __('Merge Type: '); ?><i class="help-tip icon-question-sign" href="#merge_types"></i>
    <fieldset id="combine">
        <input type="radio" name="combine" value="1" <?php echo $ticket->getMergeType() == 'combine'?'checked="checked"':''; ?>><?php echo __('Combine Threads');?>
        <input type="radio" name="combine" value="0" <?php echo $ticket->getMergeType() == 'separate'?'checked="checked"':''; ?>><?php echo __('Separate Threads');?>
        <input type="radio" name="combine" value="2" <?php echo $ticket->getMergeType() == 'visual'?'checked="checked"':''; ?>><?php echo __('Visual Merge');?>
    </fieldset>
</div>
<div id="savewarning" style="display:none; padding-top:2px;"><p
id="msg_warning"><?php echo __('Are you sure you want to delete the child ticket(s)?'); ?></p></div>

<div id="delete-warning" style="display:none">
<hr>
    <div id="msg_warning"><?php echo __(
    'Clicking <strong>Save Changes</strong> will unmerge this Ticket'
    ); ?>
    </div>
</div>

<?php } ?>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" name="cancel" class="<?php
                echo $user ? 'cancel' : 'close' ?>" value="<?php echo __('Cancel'); ?>">
        </span>
        <?php if (!$info['error']) { ?>
            <span class="buttons pull-right">
                <input type="submit" value="<?php echo __('Save Changes'); ?>">
            </span>
        <?php } ?>
     </p>

<script type="text/javascript">
$(function() {
    $('#ticket-entries').sortable({containment:'parent',tolerance:'pointer'});
});

function refreshAndClose(tid, type) {
  location.reload();
}
</script>
<script type="text/javascript">
$(function() {
    $('.ticketSelection').select2({
      width: '450px',
      minimumInputLength: 3,
      ajax: {
        url: "ajax.php/tickets/lookup",
        dataType: 'json',
        data: function (params) {
          return {
            q: params.term,
          };
        },
        processResults: function (data) {
          return {
            results: $.map(data, function (item) {
              return {
                text: item.id,
                slug: item.slug,
                id: item.id
              }
            })
          };
        }
      }
    });

    $('#combine input[type=radio]').change(function(){
      deleteChild(this);
    })

    $('#delete-child input[type=checkbox]').change(function(){
        childWarning();
    })

    function deleteChild(combine) {
       var value = $(combine).val();
       switch (value) {
         case "0":
         case "1":
           $('#delete-child').show();
           break;
         case "2":
           $('#delete-child').hide();
           break;
       }
     }

     function childWarning() {
         var value = $("#delete-child2").prop("checked") ? 1 : 0;
         (value == 1) ? $('#savewarning').show() : $('#savewarning').hide();
     }
});

</script>
