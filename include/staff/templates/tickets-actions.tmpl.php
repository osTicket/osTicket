<?php
// Tickets mass actions based on logged in agent

// Status change
if ($agent->canManageTickets())
    echo TicketStatus::status_options();


// Mass Claim/Assignment
if ($agent->hasPerm(Ticket::PERM_ASSIGN, false)) {?>
<span
    class="action-button" data-placement="bottom"
    data-dropdown="#action-dropdown-assign" data-toggle="tooltip" title=" <?php
    echo __('Assign'); ?>">
    <i class="icon-caret-down pull-right"></i>
    <a class="tickets-action" id="tickets-assign"
        aria-label="<?php echo __('Assign'); ?>"
        href="#tickets/mass/assign"><i class="icon-user"></i></a>
</span>
<div id="action-dropdown-assign" class="action-dropdown anchor-right">
  <ul>
     <li><a class="no-pjax tickets-action"
        href="#tickets/mass/claim"><i
        class="icon-chevron-sign-down"></i> <?php echo __('Claim'); ?></a>
     <li><a class="no-pjax tickets-action"
        href="#tickets/mass/assign/agents"><i
        class="icon-user"></i> <?php echo __('Agent'); ?></a>
     <li><a class="no-pjax tickets-action"
        href="#tickets/mass/assign/teams"><i
        class="icon-group"></i> <?php echo __('Team'); ?></a>
  </ul>
</div>
<?php
}

//Mass Merge
if ($agent->hasPerm(Ticket::PERM_MERGE, false)) {?>
 <a class="button action-button tickets-action" id="tickets-merge" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('Merge'); ?>"
    href="#tickets/mass/merge"><i class="icon-code-fork"></i></a>
<?php
}

//Mass Link
if ($agent->hasPerm(Ticket::PERM_LINK, false)) {?>
 <a class="button action-button tickets-action" id="tickets-link" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('Link'); ?>"
    href="#tickets/mass/link"><i class="icon-link"></i></a>
<?php
}

// Mass Transfer
if ($agent->hasPerm(Ticket::PERM_TRANSFER, false)) {?>
 <a class="action-button tickets-action" id="tickets-transfer" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('Transfer'); ?>"
    href="#tickets/mass/transfer"><i class="icon-share"></i></a>
<?php
}


// Mass Delete
if ($agent->hasPerm(Ticket::PERM_DELETE, false)) {?>
 <a class="red button action-button tickets-action" id="tickets-delete" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('Delete'); ?>"
    href="#tickets/mass/delete"><i class="icon-trash"></i></a>
<?php
}

?>
<script type="text/javascript">
$(function() {

    $(document).off('.tickets');
    $(document).on('click.tickets', 'a.tickets-action', function(e) {
        e.preventDefault();
        var $form = $('form#tickets');
        var count = checkbox_checker($form, 1);
        if (count) {
            var tids = $('.ckb:checked', $form).map(function() {
                    return this.value;
                }).get();
            var url = 'ajax.php/'
            +$(this).attr('href').substr(1)
            +'?count='+count
            +'&tids='+tids.join(',')
            +'&_uid='+new Date().getTime();
            $.dialog(url, [201], function (xhr) {
                $.pjax.reload('#pjax-container');
             });
        }
        return false;
    });
});
</script>
