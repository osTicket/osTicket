<?php
if(!defined('OSTSCPINC') || !$thisstaff || !is_object($user)) die('Invalid path');

$account = $user->getAccount();
$org = $account ? $account->getOrganization() : null;


?>
<table width="940" cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td width="50%" class="has_bottom_border">
             <h2><a href="users.php?id=<?php echo $user->getId(); ?>"
             title="Reload"><i class="icon-refresh"></i> <?php echo $user->getName(); ?></a></h2>
        </td>
        <td width="50%" class="right_align has_bottom_border">
           <?php
            if ($account) { ?>
            <span class="action-button" data-dropdown="#action-dropdown-more">
                <span ><i class="icon-cog"></i> More</span>
                <i class="icon-caret-down"></i>
            </span>
            <?php
            } ?>
            <a id="user-delete" class="action-button user-action"
            href="#users/<?php echo $user->getId(); ?>/delete"><i class="icon-trash"></i> Delete User</a>
            <?php
            if ($account) { ?>
            <a id="user-manage" class="action-button user-action"
            href="#users/<?php echo $user->getId(); ?>/manage"><i class="icon-edit"></i> Manage Account</a>
            <?php
            } else { ?>
            <a id="user-register" class="action-button user-action"
            href="#users/<?php echo $user->getId(); ?>/register"><i class="icon-edit"></i> Register</a>
            <?php
            } ?>
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
              <ul>
                <?php
                if ($account) {
                    if (!$account->isConfirmed()) {
                        ?>
                    <li><a class="confirm-action" href="#confirmlink"><i
                        class="icon-envelope"></i> Send Activation Email</a></li>
                    <?php
                    } else { ?>
                    <li><a class="confirm-action" href="#pwreset"><i
                        class="icon-envelope"></i> Send Password Reset Email</a></li>
                    <?php
                    } ?>
                    <li><a class="user-action"
                        href="#users/<?php echo $user->getId(); ?>/manage/access"><i
                        class="icon-lock"></i> Manage Account Access</a></li>
                <?php

                } ?>
              </ul>
            </div>
        </td>
    </tr>
</table>
<table class="ticket_info" cellspacing="0" cellpadding="0" width="940" border="0">
    <tr>
        <td width="50">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <tr>
                    <th width="100">Name:</th>
                    <td><b><a href="#users/<?php echo $user->getId();
                    ?>/edit" class="user-action"><i
                    class="icon-edit"></i>&nbsp;<?php echo
                    $user->getName()->getOriginal();
                    ?></a></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>
                        <span id="user-<?php echo $user->getId(); ?>-email"><?php echo $user->getEmail(); ?></span>
                    </td>
                </tr>
                <tr>
                    <th>Organization:</th>
                    <td>
                        <span id="user-<?php echo $user->getId(); ?>-org">
                        <?php
                            if ($org)
                                echo sprintf('<a href="#users/%d/org"
                                        class="user-action">%s</a>',
                                        $user->getId(), $org->getName());
                            elseif ($account)
                                echo sprintf('<a href="#users/%d/org"
                                        class="user-action">Add Organization</a>',
                                        $user->getId());
                            else
                                echo '&nbsp;';
                        ?>
                        </span>
                    </td>
                </tr>
            </table>
        </td>
        <td width="50%" style="vertical-align:top">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <tr>
                    <th>Status:</th>
                    <td> <span id="user-<?php echo $user->getId();
                    ?>-status"><?php echo $user->getAccountStatus(); ?></span></td>
                </tr>
                <tr>
                    <th>Created:</th>
                    <td><?php echo Format::db_datetime($user->getCreateDate()); ?></td>
                </tr>
                <tr>
                    <th>Updated:</th>
                    <td><?php echo Format::db_datetime($user->getUpdateDate()); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<div class="clear"></div>
<ul class="tabs">
    <li><a class="active" id="tickets_tab" href="#tickets"><i
    class="icon-list-alt"></i>&nbsp;User Tickets</a></li>
</ul>
<div id="tickets">
<?php
//List all tickets the user

$select ='SELECT ticket.ticket_id,ticket.`number`,ticket.dept_id,ticket.staff_id,ticket.team_id '
        .' ,dept.dept_name,ticket.status,ticket.source,ticket.isoverdue,ticket.isanswered,ticket.created '
        .' ,CAST(GREATEST(IFNULL(ticket.lastmessage, 0), IFNULL(ticket.reopened, 0), ticket.created) as datetime) as effective_date '
        .' ,CONCAT_WS(" ", staff.firstname, staff.lastname) as staff, team.name as team '
        .' ,IF(staff.staff_id IS NULL,team.name,CONCAT_WS(" ", staff.lastname, staff.firstname)) as assigned '
        .' ,IF(ptopic.topic_pid IS NULL, topic.topic, CONCAT_WS(" / ", ptopic.topic, topic.topic)) as helptopic '
        .' ,cdata.priority_id, cdata.subject, pri.priority_desc, pri.priority_color';

$from =' FROM '.TICKET_TABLE.' ticket '
      .' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id '
      .' LEFT JOIN '.STAFF_TABLE.' staff ON (ticket.staff_id=staff.staff_id) '
      .' LEFT JOIN '.TEAM_TABLE.' team ON (ticket.team_id=team.team_id) '
      .' LEFT JOIN '.TOPIC_TABLE.' topic ON (ticket.topic_id=topic.topic_id) '
      .' LEFT JOIN '.TOPIC_TABLE.' ptopic ON (ptopic.topic_id=topic.topic_pid) '
      .' LEFT JOIN '.TABLE_PREFIX.'ticket__cdata cdata ON (cdata.ticket_id = ticket.ticket_id) '
      .' LEFT JOIN '.PRIORITY_TABLE.' pri ON (pri.priority_id = cdata.priority_id)';

$where = 'WHERE ticket.user_id = '.db_input($user->getId());

TicketForm::ensureDynamicDataView();

$query ="$select $from $where ORDER BY ticket.created DESC";

// Fetch the results
$results = array();
$res = db_query($query);
while ($row = db_fetch_array($res))
    $results[$row['ticket_id']] = $row;

if ($results) {
    $counts_sql = 'SELECT ticket.ticket_id,
        count(DISTINCT attach.attach_id) as attachments,
        count(DISTINCT thread.id) as thread_count,
        count(DISTINCT collab.id) as collaborators
        FROM '.TICKET_TABLE.' ticket
        LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON (ticket.ticket_id=attach.ticket_id) '
     .' LEFT JOIN '.TICKET_THREAD_TABLE.' thread ON ( ticket.ticket_id=thread.ticket_id) '
     .' LEFT JOIN '.TICKET_COLLABORATOR_TABLE.' collab
            ON ( ticket.ticket_id=collab.ticket_id) '
     .' WHERE ticket.ticket_id IN ('.implode(',', db_input(array_keys($results))).')
        GROUP BY ticket.ticket_id';
    $ids_res = db_query($counts_sql);
    while ($row = db_fetch_array($ids_res)) {
        $results[$row['ticket_id']] += $row;
    }
}
?>
<div style="width:700px; float:left;">
   <?php
    if ($results) {
        echo  sprintf('<strong>Showing 1 - %d of %s</strong>',
            count($results), count($results));
    } else {
        echo sprintf('%s does not have any tickets', $user->getName());
    }
   ?>
</div>
<div style="float:right;text-align:right;padding-right:5px;">
    <b><a class="Icon newTicket" href="tickets.php?a=open&uid=<?php echo $user->getId(); ?>"> Create New Ticket</a></b>
</div>
<br/>
<?php
if ($results) { ?>
<form action="users.php" method="POST" name='tickets' style="padding-top:10px;">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="2" width="940">
    <thead>
        <tr>
            <?php
            if (0) {?>
            <th width="8px">&nbsp;</th>
            <?php
            } ?>
            <th width="70">Ticket</th>
            <th width="100">Date</th>
            <th width="100">Status</th>
            <th width="250">Subject</th>
            <th width="100">Priority</th>
            <th width="250">Department</th>
            <th width="200">Assignee</th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach($results as $row) {
        $flag=null;
        if ($row['lock_id'])
            $flag='locked';
        elseif ($row['isoverdue'])
            $flag='overdue';

        $assigned='';
        if ($row['staff_id'])
            $assigned=sprintf('<span class="Icon staffAssigned">%s</span>',Format::truncate($row['staff'],40));
        elseif ($row['team_id'])
            $assigned=sprintf('<span class="Icon teamAssigned">%s</span>',Format::truncate($row['team'],40));
        else
            $assigned=' ';

        $status = ucfirst($row['status']);
        if(!strcasecmp($row['status'], 'open'))
            $status = "<b>$status</b>";

        $tid=$row['number'];
        $subject = Format::htmlchars(Format::truncate($row['subject'],40));
        $threadcount=$row['thread_count'];
        ?>
        <tr id="<?php echo $row['ticket_id']; ?>">
            <?php
            //Implement mass  action....if need be.
            if (0) { ?>
            <td align="center" class="nohover">
                <input class="ckb" type="checkbox" name="tids[]" value="<?php echo $row['ticket_id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
            </td>
            <?php
            } ?>
            <td align="center" nowrap>
              <a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview" title="Preview Ticket"
                href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo $tid; ?></a></td>
            <td align="center" nowrap><?php echo Format::db_datetime($row['effective_date']); ?></td>
            <td><?php echo $status; ?></td>
            <td><a <?php if ($flag) { ?> class="Icon <?php echo $flag; ?>Ticket" title="<?php echo ucfirst($flag); ?> Ticket" <?php } ?>
                href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo $subject; ?></a>
                 <?php
                    if ($threadcount>1)
                        echo "<small>($threadcount)</small>&nbsp;".'<i
                            class="icon-fixed-width icon-comments-alt"></i>&nbsp;';
                    if ($row['collaborators'])
                        echo '<i class="icon-fixed-width icon-group faded"></i>&nbsp;';
                    if ($row['attachments'])
                        echo '<i class="icon-fixed-width icon-paperclip"></i>&nbsp;';
                ?>
            </td>
            <td class="nohover" align="center" style="background-color:<?php echo $row['priority_color']; ?>;">
                <?php echo $row['priority_desc']; ?></td>
            <td><?php echo Format::truncate($row['dept_name'], 40); ?></td>
            <td>&nbsp;<?php echo $assigned; ?></td>
        </tr>
   <?php
    }
    ?>
    </tbody>
</table>
</form>
<?php
 } ?>
</div>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Please Confirm</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="banemail-confirm">
        Are you sure want to <b>ban</b> <?php echo $user->getEmail(); ?>? <br><br>
        New tickets from the email address will be auto-rejected.
    </p>
    <p class="confirm-action" style="display:none;" id="confirmlink-confirm">
        Are you sure want to send <b>Account Activation Link</b> to <em><?php echo $user->getEmail()?></em>?
    </p>
    <p class="confirm-action" style="display:none;" id="pwreset-confirm">
        Are you sure want to send <b>Password Reset Link</b> to <em><?php echo $user->getEmail()?></em>?
    </p>
    <div>Please confirm to continue.</div>
    <form action="users.php?id=<?php echo $user->getId(); ?>" method="post" id="confirm-form" name="confirm-form">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $user->getId(); ?>">
        <input type="hidden" name="a" value="process">
        <input type="hidden" name="do" id="action" value="">
        <hr style="margin-top:1em"/>
        <p class="full-width">
            <span class="buttons" style="float:left">
                <input type="button" value="Cancel" class="close">
            </span>
            <span class="buttons" style="float:right">
                <input type="submit" value="OK">
            </span>
         </p>
    </form>
    <div class="clear"></div>
</div>

<script type="text/javascript">
$(function() {
    $(document).on('click', 'a.user-action', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'+$(this).attr('href').substr(1);
        $.dialog(url, [201, 204], function (xhr) {
            if (xhr.status == 204)
                window.location.href = 'users.php';
            else
                window.location.href = window.location.href;
         }, {
            onshow: function() { $('#user-search').focus(); }
         });
        return false;
    });
});
</script>
