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
<div style="overflow-y: auto; height:150; margin-bottom:5px;">
<?php
if ($tickets) {
foreach ($tickets as $t) {
    list($ticket_id, $number, $ticket_pid, $sort,
        $id, $user_id, $subject, $name, $flags, $tasks, $collaborators) = $t;
    $mergeType = Ticket::getMergeTypeByFlag($flags);

    if ($mergeType == 'combine')
        $forceOptions = true;

    if ($ticket->getId() != $ticket_id && $ticket->getUserId() != $user_id)
        $showUserOption = true;
    if ($collaborators > 0)
        $showCcOption = true;
    if ($showUserOption || $showCcOption)
        $showParticipants = true;

    if ($ticket->getId() == $ticket_pid)
        $visual = true;
    $types[] = $mergeType;
?>
<li class="<?php if ($visual) echo 'sortable'; ?> row-item
    <?php
    if ($title == 'merge' &&
        (($parent && $parent instanceof Ticket && $parent->getMergeType() != 'visual' && $parent->getId() == $ticket_id) || //mass process merge
              ($ticket && $ticket_id == $ticket->getId() && $ticket->getMergeType() != 'visual'))) //ticket view merge or mass process merge w/child ticket(s)
            echo ' ui-state-disabled';
          else
            echo 'ui-sortable-handle';
    ?>" data-id="<?php echo $ticket_id; ?>">
    <input type="hidden" id="tids" name="tids[]" value="<?php echo $number; ?>" />
    <?php if ($ticket_id)
            $numberLink = sprintf('<a style="display: inline" class="preview" data-preview="#tickets/%d/preview" href="#tickets.php?id=%d">%s</a>',
                                $ticket_id, $ticket_id, $number);
            $subject = (strlen($subject) > 25) ? sprintf('%s...',substr($subject, 0, 25)) : $subject;
            $taskCount = sprintf('<a data-placement="bottom" data-toggle="tooltip" title="%s Tasks" <i class="icon-tasks"></i></a>',$tasks);
            $showMergePreview = ($mergeType != 'visual' && $children=$ticket->getChildTickets($ticket_id)) && (count($children) > 0) ? true : false;
            $mergePrev = sprintf('<a class="merge preview"href="#tickets/%d/merge"><i class="icon-code-fork"></i></a>', $ticket_id);
            $showLinkPreview = ($mergeType == 'visual' && $children=$ticket->getChildTickets($ticket_id)) && (count($children) > 0) ? true : false;
            $linkPrev = sprintf('<a class="merge preview"href="#tickets/%d/merge"><i class="icon-link"></i></a>', $ticket_id);
            $showCollaborators = ($collaborators > 0) ?
               sprintf('<a class="collaborators preview"href="#thread/%d/collaborators"><i class="icon-group"></i></a>', $id) : '';
            $nbsp = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    ?>
    <i class="icon-reorder"></i> <?php
    echo sprintf('%s %s %s %s %s %s <div style="float: right;"> %s %s %s %s %s</div>',
        $numberLink ?: $number,$nbsp, $name, $nbsp, $subject, $nbsp, $tasks ? $taskCount : '',
        $showMergePreview || $showLinkPreview ? $nbsp : '', $showMergePreview ? $mergePrev : '', $showLinkPreview ? $linkPrev : '', $showCollaborators ? $nbsp . $showCollaborators : '');
    if (!is_null($ticket_pid)) { ?>
    <div class="button-group">
    <div class="<?php if (!$parent && $visual) echo 'delete'; ?>"><a href="#" onclick="javascript:
        var value = <?php echo $ticket_id; ?>;
        $('#ticket-entries').append($('<input/>').attr({name:'dtids[]', type:'hidden'}).val(value))
        $(this).closest('li.row-item').remove();$('#delete-warning').show();">
        <?php if (!$parent && $visual) { ?><i class="icon-trash"></i><?php } ?></a></div>
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
        <?php if ($showUserOption) { ?>
            <option value='user' selected="selected"><?php echo __('User');?></option>
        <?php } if ($showCcOption) { ?>
            <option value='all'><?php echo __('User + Collaborators'); ?></option>
        <?php } ?>
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
        data = $sel.data();
        for(var key in data) {
             ticket_id = data[key]['ticket_id'];
             tasks = data[key]['tasks'];
             showTasks = (tasks > 0) ? '<a data-placement=\'bottom\' data-toggle=\'tooltip\' <i class=\'icon-tasks\'>' : '';
             subject = data[key]['subject'];
             spaces = data[key]['spaces'];
             user = data[key]['user'];
             collaborators = data[key]['collaborators'];
             thread_id = data[key]['thread_id'] ? data[key]['thread_id'] : 0;
             mergeType = data[key]['mergeType'];
             mergePrev = data[key]['mergePrev'];
             icon = (mergeType == 'visual') ? '<i class=\'icon-link\'></i>' : '<i class=\'icon-code-fork\'></i>';
             showMergePrev = mergePrev ? '<a class=\'merge preview\' href=\'#tickets/'+ticket_id+'/merge\'>'+icon+'</i></a>' : '';
             showCollaborators = (collaborators > 0) ?
                '<a class=\'collaborators preview\' href=\'#thread/'+thread_id+'/collaborators\'><i class=\'icon-group\'></i></a>' : '';
        }

    if ($sel.prop('disabled'))
        return;
    $('#ticket-entries').append($('<li></li>').addClass('sortable row-item')
        .text('  '+user)
        .data('id', id)
        .append(subject)
        .append(spaces)
        .append($('<div style=\'position: absolute; right: 50px; bottom: 9px;\'></div>')
            .append(showTasks).attr({title: tasks+' Tasks'})
            .append(showMergePrev ? spaces : '')
            .append(showMergePrev)
            .append((collaborators > 0) ? spaces : '')
            .append(showCollaborators))
        .prepend($('<a href=\'#tickets.php?id='+id+'>\' data-preview=\'#tickets/'+ticket_id+'/preview\' class=\'preview\'>\xa0'+id+'</a>'))
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
<div id="merge-options" class="hidden">
    <label class="inline checkbox">
        <input type="checkbox" id="delete-child" name="delete-child">
        <?php echo __('Delete Child Ticket') ?>
    </label>
    <br>
    <label class="inline checkbox">
        <input type="checkbox" id="move-tasks" name="move-tasks">
        <?php echo __('Move Child Tasks to Parent') ?>
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
                subject = (item.subject.length > 25) ? item.subject.substring(0, 25) + '...' : item.subject;
              return {
                text: item.id,
                ticket_id: item.ticket_id,
                user: '\xa0\xa0\xa0\xa0\xa0\xa0' + item.user + '\xa0\xa0\xa0\xa0\xa0\xa0\xa0',
                id: item.id,
                tasks: item.tasks,
                thread_id: item.thread_id,
                spaces: '\xa0\xa0\xa0\xa0\xa0\xa0\xa0',
                mergeType: item.mergeType,
                mergePrev: item.children,
                collaborators: item.collaborators,
                subject: subject,
              }
            })
          };
        }
      }
    });

    $('#combination input[type=radio]').change(function(){
      showCheckboxes(this);
    });

    $('#merge-options input[type=checkbox]').change(function(){
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
           $('#merge-options').show();
           $('#show-participants').show();
           break;
         case "2":
           $('#merge-options').hide();
           $('#show-participants').hide();
           break;
       }
     }

     function childWarning() {
         var value = $("#delete-child").prop("checked") ? 1 : 0;
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
