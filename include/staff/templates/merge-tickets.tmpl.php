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
<form method="post" action="<?php echo $info['action']; ?>">
<ul id="ticket-entries">
<?php
if ($tickets) {
foreach ($tickets as $t) {
    if ($tickets instanceof QuerySet)
        list($ticket_id, $number, $ticket_pid, $sort) = $t;
    else {
        $ticket_id = $t['ticket_id'];
        $user_id = $t['user_id'];
        $number = $t['number'];
        $type = $t['type'];
    }

    if ($ticket->getId() != $ticket_id && $ticket->getUserId() != $user_id) {
        $showParticipants = true;
    }
    if ($ticket->getId() == $t['ticket_pid'])
        $visual = true;
    $types[] = $type;
?>
<li class="<?php if ($visual) echo 'sortable'; ?> row-item
    <?php if (($parent && $parent instanceof Ticket && $parent->getMergeType() != 'visual' && $parent->getId() == $ticket_id) || //mass process merge
              ($ticket && $ticket_id == $ticket->getId() && $ticket->getMergeType() != 'visual')) //ticket view merge or mass process merge w/child ticket(s)
            echo ' ui-state-disabled';
          else
            echo 'ui-sortable-handle';
    ?>" data-id="<?php echo $ticket_id; ?>">
    <input type="hidden" id="tids" name="tids[]" value="<?php echo $number; ?>" />
    <?php if (($parent && $parent instanceof Ticket && $ticket_id != $parent->getId()) ||
              ($parent_id && $ticket_id != $parent_id) || !$parent) {?>
        <i class="icon-reorder"></i> <?php echo $number;
    }
    else
        echo $number;
    if (!is_null($t['ticket_pid'])) { ?>
    <div class="button-group">
    <div class="<?php if ($visual) echo 'delete'; ?>"><a href="#" onclick="javascript:
        var value = <?php echo $ticket_id; ?>;
        $('#ticket-entries').append($('<input/>').attr({name:'dtids[]', type:'hidden'}).val(value))
        $(this).closest('li.row-item').remove();$('#delete-warning').show();">
        <?php if ($visual) { ?><i class="icon-trash"></i><?php } ?></a></div>
    </div>
    <?php } ?>
</li>
<?php } } ?>
</ul>
<br/>
<label class="inline checkbox">
    <?php echo __('Show Children Threads') ?>
    <input type="checkbox" name="show_children" value="1" <?php echo $ticket ?: $ticket->hasFlag(Ticket::FLAG_SHOW_CHILDREN)?'checked="checked"':''; ?> >
</label>

<div id="delete-child" class="hidden">
    <label class="inline checkbox">
        <?php echo __('Delete Child Ticket') ?>
        <input type="checkbox" id="delete-child2" name="delete-child2">
    </label>
</div>

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
    $('#ticket-entries').append($('<li></li>').addClass('sortable row-item')
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
                $(this).closest('li.row-item').remove();
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
    <fieldset id="combination">
        <label for="combine" style="display:none"><input type="radio" name="combine" id="combine" value="1"<?php echo $ticket->getMergeType() == 'combine'?'checked="checked"':''; ?>><?php echo __('Combine Threads');?></label>
        <label for="separate" style="display:none"><input type="radio" name="combine" id="separate" value="0"<?php echo $ticket->getMergeType() == 'separate'?'checked="checked"':''; ?>><?php echo __('Separate Threads');?></label>
        <label for="visual" style="display:none"><input type="radio" name="combine" id="visual" value="2"<?php echo $ticket->getMergeType() == 'visual'?'checked="checked"':''; ?>><?php echo __('Visual Merge');?></label>
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
    $('#ticket-entries').sortable({items: "li:not(.ui-state-disabled)"});
    $( "#ticket-entries li" ).disableSelection();
});

function refreshAndClose(tid, type) {
  location.reload();
}

$(document).ready(function() {
    showMergeOptions();

    function showMergeOptions() {
        var jArray = <?php echo json_encode($types); ?>;
        for(var i=0; i<jArray.length; i++){
            switch (jArray[0]) {
                case 'visual':
                    $('#combine').parent().show();
                    $('#separate').parent().show();
                    $('#visual').parent().show();
                    break;
                case 'combine':
                    $('#combine').parent().show();
                    $('input:radio[id=combine]').attr('checked',true);
                    $('#separate').parent().hide();
                    $('#visual').parent().hide();
                    deleteChild($('#combine'));
                    break;
                case 'separate':
                    $('#combine').parent().hide();
                    $('#separate').parent().show();
                    $('input:radio[id=separate]').attr('checked',true);
                    $('#visual').parent().hide();
                    deleteChild($('#separate'));
                    break;
                default:

            }
        }
    }


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

    $('#combination input[type=radio]').change(function(){
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

<style>
  #ticket-entries { list-style-type: none;}
  #ticket-entries { padding: 0px; }
</style>
