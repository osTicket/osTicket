
    
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">

   <a class="btn btn-icon waves-effect waves-light btn-success" id="tickets-helptopic" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('New Ticket'); ?>"
   href="<?php echo ROOT_PATH ?>scp/tickets.php?a=open"><i class="fa fa-plus-square"></i></a>

<?php
if (!$count) {
// Status change
if ($agent->canManageTickets())
    echo TicketStatus::status_options();




// Mass Priority Change
if ($agent->hasPerm(Ticket::PERM_EDIT, false)) { ?>


<div class="btn-group btn-group-sm" role="group">
        <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false data-placement="bottom" data-toggle="tooltip" 
         title="<?php echo __('Change Priority'); ?>"><i class="icon-exclamation"></i>
        </button>
    <div class="dropdown-menu " aria-labelledby="btnGroupDrop1" id="action-dropdown-change-priority">
                
           <?php foreach (Priority::getPriorities() as $Pid => $Pname) { ?>
     <a class="dropdown-item no-pjax tickets-action"
        href="#tickets/mass/priority/<?php echo $Pid; ?>"><i
        class="icon-level-up"></i> <?php echo $Pname; ?></a>
<?php } ?>
           
        <?php } ?>   
    </div>
</div>

<?php
// Mass Topic Change
if ($agent->hasPerm(Ticket::PERM_EDIT, false)) {?>

        <a class="btn btn-light tickets-action" id="tickets-helptopic" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('Change Help Topic'); ?>"
   href="#tickets/mass/topic"><i class="icon-bookmark"></i></a>

<?php } ?>

<?php
// Mass Claim/Assignment
if ($agent->hasPerm(Ticket::PERM_ASSIGN, false)) {?>

<div class="btn-group btn-group-sm" role="group">
        <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-placement="bottom" data-toggle="tooltip" 
         title="<?php echo __('Assign'); ?>"><i class="icon-user"></i>
        </button>
    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="btnGroupDrop1" id="action-dropdown-change-priority">

   <a class="dropdown-item no-pjax tickets-action"
        href="#tickets/mass/claim"><i
        class="icon-chevron-sign-down"></i> <?php echo __('Claim'); ?></a>
     <a class="dropdown-item no-pjax tickets-action"
        href="#tickets/mass/assign/agents"><i
        class="icon-user"></i> <?php echo __('Agent'); ?></a>
      <a class="dropdown-item no-pjax tickets-action"
        href="#tickets/mass/assign/teams"><i
        class="icon-group"></i> <?php echo __('Team'); ?></a>
    
    </div>
</div>

<?php
}

// Mass Transfer
if ($agent->hasPerm(Ticket::PERM_TRANSFER, false)) {?>

 <a class="btn btn-light tickets-action" id="tickets-transfer" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('Transfer'); ?>"
    href="#tickets/mass/transfer"><i class="icon-share"></i></a>

<?php
}

// Mass Delete
if ($agent->hasPerm(Ticket::PERM_DELETE, false)) {?>

 <a class="btn btn-icon waves-effect waves-light btn-danger tickets-action" id="tickets-delete" data-placement="bottom"
    data-toggle="tooltip" title="<?php echo __('Delete'); ?>"
    href="#tickets/mass/delete"><i class="icon-trash"></i></a>

<?php
}
}
?>

</div>
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
                //$.pjax.reload('#pjax-container');
                location.reload();
             });
        }
        return false;
    });
});

</script>
