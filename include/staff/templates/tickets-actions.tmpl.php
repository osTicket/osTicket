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

// Mass Transfer
if ($agent->hasPerm(Ticket::PERM_TRANSFER, false)) {?>
<span class="action-button">
 <a class="tickets-action" id="tickets-transfer" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('Transfer'); ?>"
    href="#tickets/mass/transfer"><i class="icon-share"></i></a>
</span>
<?php
}


// Mass Delete
if ($agent->hasPerm(Ticket::PERM_DELETE, false)) {?>
<span class="red button action-button">
 <a class="tickets-action" id="tickets-delete" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('Delete'); ?>"
    href="#tickets/mass/delete"><i class="icon-trash"></i></a>
</span>
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
            console.log(tids);
            $.dialog(url, [201], function (xhr) {
                $.pjax.reload('#pjax-container');
             });
        }
        return false;
    });
});
</script>
