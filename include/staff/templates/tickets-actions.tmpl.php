<?php
// Tickets mass actions based on logged in agent

// Mass merge
if ($agent->hasPerm(Ticket::PERM_MERGE, false)) {?>
<form action="tickets.php" method="post" style="display: inline-block;" id="merge-form">
    <?php csrf_token(); ?>
    <input type="hidden" name="a" value="merge">
    <input type="hidden" name="tids_merge" value="">
    <div class="attached input" data-toggle="tooltip" title=" <?php echo __('Merge'); ?>" style="height: 26px;text-align:left">
        <select id="masterid" name="masterid" style="width: 250px" class="js-example-basic-single">
            <?php
                $mysqli = new MySQLi(DBHOST,DBUSER,DBPASS,DBNAME);
                if($mysqli->connect_error) {
                  echo 'Database connection failed...' . 'Error: ' . $mysqli->connect_errno . ' ' . $mysqli->connect_error;
                  exit;
                } else {
                  $mysqli->set_charset('utf8');
                }
                if ($data = $mysqli->query("SELECT t2.`ticket_id`, CONCAT(t2.`number`, ' | ', t3.`subject`) AS 'row' FROM 
                    (SELECT `ticket_id`, `number`, `status_id`, `dept_id`, `staff_id` FROM `" . TICKET_TABLE . "`) t2
                    LEFT JOIN
                    (SELECT `id`, `state` FROM `" . TICKET_STATUS_TABLE . "` WHERE `state` = 'open') t1
                    ON t1.`id` = t2.`status_id`
                    LEFT JOIN
                    (SELECT `ticket_id`, `subject` FROM `" . TICKET_CDATA_TABLE . "`) t3
                    ON t2.`ticket_id` = t3.`ticket_id`
                    WHERE t1.`state` IS NOT NULL")) {
                    while($row = mysqli_fetch_array($data)) {
                        if(($temp = Ticket::lookup($row['ticket_id']))){
                            if($temp->checkStaffPerm($agent)){
                                echo "<option value='" . $row['ticket_id'] . "'>" . $row['row'] . "</option>";
                            }
                        }
                    }
                }
                flush();
                 
                $mysqli->close();
            ?>
        </select>
        <button type="submit" class="attached button"><i class="icon-code-fork"></i>
        </button>
    </div>
</form>
<form action="tickets.php" method="post" style="display: inline-block;" id="split-form">
    <?php csrf_token(); ?>
    <input type="hidden" name="a" value="split">
    <input type="hidden" name="tids_split" value="">
</form>
<button type="submit" form="split-form" class="button"><i class="icon-code-fork"></i>
</button>
<?php
}

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
    $('form#merge-form').submit(function(e) {
        var $form = $('form#tickets');
        var count = checkbox_checker($form, 1);
        if (count) {
            var tids = $('.ckb:checked', $form).map(function() {
                return this.value;
            }).get();
            $(this).find("input[name='tids_merge']").val(tids);
            return true;
        }
        e.preventDefault();
    });
    $('form#split-form').submit(function(e) {
        var $form = $('form#tickets');
        var count = checkbox_checker($form, 1);
        if (count) {
            var tids = $('.ckb:checked', $form).map(function() {
                return this.value;
            }).get();
            $(this).find("input[name='tids_split']").val(tids);
            return true;
        }
        e.preventDefault();
     });
    $(document).ready(function() {
        $("#masterid").select2({
            placeholder: "<?php echo __('Select a ticket'); ?>"
        });
    });
    $('#masterid').val('');
});
</script>
