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
    $title = strpos($_SERVER['PATH_INFO'], 'link') !== false ? 'link' : 'merge';
?>
<h3 class="drag-handle"><i class="<?php echo $title == 'link' ? 'icon-link' : 'icon-code-fork' ?>"></i> <?php echo sprintf(__('%s Tickets'), ucfirst($title)); ?></i></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/><?php echo sprintf(__(
'Choose which Tickets to %s. The Ticket on top will be the Parent Ticket. Sort the order of the Tickets by clicking and dragging them.'
), ($title == 'merge' ? 'merge into this one' : 'link'));
?>
<br/>
<br/>
<form method="post" onsubmit="refreshAndClose();" action="<?php echo $info['action']; ?>">
<input type="hidden" name="title" value="<?php echo $title; ?>" />
<ul id="ticket-entries">
<div style="overflow-y: auto; height:200px; margin-bottom:5px;">
<?php
if ($tickets) {
foreach ($tickets as $t) {
    $ticket_id = $t['ticket_id'];
    $user_id = $t['user_id'];
    $number = $t['number'];
    $id = $t['id'];
    $subject = $t['subject'];
    if (!$type && $title == 'merge' && $t['type'] == 'visual') {
        $type = 'combine';
        $forceOptions = true;
    }
    else
        $type = $t['type'];

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
    <?php if ($id)
            $numberLink = sprintf('<a class="collaborators preview"
                     href="#thread/%d/collaborators">%s
                    </a>', $id, $number);
    if (($parent && $parent instanceof Ticket && $ticket_id != $parent->getId()) ||
              ($parent_id && $ticket_id != $parent_id) || !$parent) {?>
        <i class="icon-reorder"></i> <?php echo sprintf('%s &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; %s', $numberLink ?: $number, $subject);
    }
    else
        echo sprintf('%s &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; %s', $numberLink ?: $number, $subject);
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
</div>
</ul>
<?php
    if ($showParticipants && $title == 'merge') { //get user/collab merge options here
?>

<div id="participant-options">
&nbsp;&nbsp;&nbsp;
    <label class="inline checkbox">
        <?php echo __('Participants') ?>&nbsp;
    <select id="participants" name="participants">
        <option value='user' selected="selected"><?php echo __('User');?></option>
        <option value='all'><?php echo __('User + Collaborators'); ?></option>
    </select>
    </label>
</div>

<br/><br/>
<?php } ?>
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
    <?php echo ($title == 'merge') ? __('Merge Type: ') : ''?>
    <?php echo $title == 'merge' ? '<i class="help-tip icon-question-sign" href="#merge_types"></i>' : '';?>
    <fieldset id="combination">
        <?php if ($title == 'merge') { ?>
            <label for="combine" style="display:none"><input type="radio" name="combine" id="combine" value="1"<?php echo $ticket->getMergeType() == 'combine' || $title == 'merge'?'checked="checked"':''; ?>><?php echo __('Combine Threads');?></label>
            <label for="separate" style="display:none"><input type="radio" name="combine" id="separate" value="0"<?php echo $ticket->getMergeType() == 'separate'?'checked="checked"':''; ?>><?php echo __('Separate Threads');?></label>
        <?php } else { ?>
            <label for="visual" style="display:none"><input type="radio" name="combine" id="visual" value="2"<?php echo $ticket->getMergeType() == 'visual'?'checked="checked"':''; ?>><?php echo __('Visual Merge');?></label>
        <?php } ?>
    </fieldset>
</div>
<?php if ($title == 'merge') { ?>
<hr>
<div id="delete-child" class="hidden">
    <label class="inline checkbox">
        <input type="checkbox" id="delete-child2" name="delete-child2">
        <?php echo __('Delete Child Ticket') ?>
    </label>
</div>
<div id="savewarning" style="display:none; padding-top:2px;"><p
id="msg_warning"><?php echo __('Are you sure you want to delete the child ticket(s)?'); ?></p></div>
<?php } ?>

<div id="delete-warning" style="display:none">
    <div id="msg_warning"><?php echo __(
    'Clicking <strong>Save Changes</strong> will unmerge this Ticket'
    ); ?>
    </div>
</div>

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

function refreshAndClose() {
    setTimeout(function () {
        location.reload();
    }, 1000);
}

$(document).ready(function() {
    showCheckboxes($("#combination :radio:checked"));
    showMergeOptions();

    function showMergeOptions() {
        var jArray = <?php echo json_encode($types); ?>;
        for(var i=0; i<jArray.length; i++){
            switch (jArray[0]) {
                case 'visual':
                    $('#combine').parent().show();
                    $('#separate').parent().show();
                    break;
                case 'combine':
                    <?php if ($forceOptions == true) { ?>
                        $('#combine').parent().show();
                        $('input:radio[id=combine]').attr('checked',true);
                        $('#separate').parent().show();
                    <?php } else { ?>
                        $('#combine').parent().show();
                        $('input:radio[id=combine]').attr('checked',true);
                        $('#separate').parent().hide();
                    <?php } ?>
                    break;
                case 'separate':
                    $('#combine').parent().hide();
                    $('#separate').parent().show();
                    $('input:radio[id=separate]').attr('checked',true);
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
      showCheckboxes(this);
    });

    $('#delete-child input[type=checkbox]').change(function(){
        childWarning();
    });

    $('#show-participants input[type=checkbox]').change(function(){
        participantOptions();
    });

    function showCheckboxes(combine) {
       var value = $(combine).val();
       switch (value) {
         case "0":
         case "1":
           $('#delete-child').show();
           $('#show-participants').show();
           break;
         case "2":
           $('#delete-child').hide();
           $('#show-participants').hide();
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
#participant-options{
    padding: 0px;
    display: block;
    float: left;
}
</style>
