<?php
global $cfg, $thisstaff;

if (!$info['title'])
    $info['title'] = __('Change Tickets Status');

if ($info['ticket_id'])
    $ticket = Ticket::lookup($info['ticket_id']);

if ($ticket) {
    $dept = $ticket->getDept();
    $role = $ticket->getRole($thisstaff);
    $isManager = $dept->isManager($thisstaff); //Check if Agent is Manager
    $canAnswer = ($isManager || $role->hasPerm(Ticket::PERM_REPLY)); //Check if Agent can mark as answered/unanswered
}
?>
<h3 class="drag-handle"><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
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


$action = $info['action'] ?: ('#tickets/status/'. $state);
?>
<div id="ticket-status" style="display:block; margin:5px;">
    <form method="post" name="status" id="status"
        action="<?php echo $action; ?>">
        <table width="100%">
            <?php
            if ($info['extra']) {
                ?>
            <tbody>
                <tr><td colspan="2"><strong><?php echo $info['extra'];
                ?></strong></td> </tr>
            </tbody>
            <?php
            }

            $verb = '';
            if ($state) {
                $statuses = TicketStatusList::getStatuses(array('states'=>array($state)))->all();
                $verb = TicketStateField::getVerb($state);
            }

            if ($statuses) {
            ?>
            <tbody>
                <tr>
                    <td colspan=2>
                        <span>
                        <?php
                        if (count($statuses) > 1) { ?>
                            <strong><?php echo __('Status') ?>:&nbsp;</strong>
                            <select name="status_id">
                            <?php
                            foreach ($statuses as $s) {
                                echo sprintf('<option value="%d" data-state="%s" %s>%s</option>',
                                        $s->getId(),
                                        $s->getState(),
                                        ($info['status_id'] == $s->getId())
                                         ? 'selected="selected"' : '',
                                        $s->getName()
                                        );
                            }
                            ?>
                            </select>
                            <font class="error">*&nbsp;<?php echo $errors['status_id']; ?></font>
                        <?php
                        } elseif ($statuses[0]) {
                            echo  "<input type='hidden' name='status_id' value={$statuses[0]->getId()} />";
                        } ?>
                        </span>
                    </td>
                </tr>
            </tbody>
            <?php
            }
            if ($ticket && !$ticket->isAnswered() && $canAnswer) { ?>
            <tbody>
                <tr>
                    <td>
                        <label class="checkbox inline" id="status_answered_container">
                            <input type="checkbox" name="status_answered" id="status_answered"></i>
                            <?php echo __('Mark as Answered'); ?>
                        </label>
                    </td>
                </tr>
            </tbody>
            <?php } ?>
            <tbody>
                <tr>
                    <td colspan="2">
                        <?php
                        $placeholder = $info['placeholder'] ?: __('Optional reason for status change (internal note)');
                        ?>
                        <textarea name="comments" id="comments"
                            cols="50" rows="3" wrap="soft" style="width:100%"
                            class="<?php if ($cfg->isRichTextEnabled()) echo 'richtext';
                            ?> no-bar small"
                            placeholder="<?php echo $placeholder; ?>"><?php
                            echo $info['comments']; ?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
        <hr>
        <p class="full-width">
            <span class="buttons pull-left">
                <input type="reset" value="<?php echo __('Reset'); ?>">
                <input type="button" name="cancel" class="close"
                value="<?php echo __('Cancel'); ?>">
            </span>
            <span class="buttons pull-right">
                <input type="submit" value="<?php
                echo $verb ?: __('Submit'); ?>">
            </span>
         </p>
    </form>
</div>
<div class="clear"></div>
<script type="text/javascript">
$(function() {
    // Copy checked tickets to status form.
    $('form#tickets input[name="tids[]"]:checkbox:checked')
    .each(function() {
        $('<input>')
        .prop('type', 'hidden')
        .attr('name', 'tids[]')
        .val($(this).val())
        .appendTo('form#status');
    });

   // Toggle Internal Note "answered" checkbox visibility
   function toggleAnswered() {
       var state = $('select[name=status_id]').find(':selected').data('state');
       if ($.inArray(state, ['closed', 'resolved']) == -1)
           $('#status_answered_container').show();
       else {
           $('#status_answered_container').hide();
           $('#status_answered').removeAttr('checked');
       }
   }

   toggleAnswered();

   $('select[name=status_id]').change(function() {
       toggleAnswered();
   });
});
</script>
