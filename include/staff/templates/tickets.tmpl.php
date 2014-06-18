<?php

$select ='SELECT ticket.ticket_id,ticket.`number`,ticket.dept_id,ticket.staff_id,ticket.team_id, ticket.user_id '
        .' ,dept.dept_name,ticket.status,ticket.source,ticket.isoverdue,ticket.isanswered,ticket.created '
        .' ,CAST(GREATEST(IFNULL(ticket.lastmessage, 0), IFNULL(ticket.reopened, 0), ticket.created) as datetime) as effective_date '
        .' ,CONCAT_WS(" ", staff.firstname, staff.lastname) as staff, team.name as team '
        .' ,IF(staff.staff_id IS NULL,team.name,CONCAT_WS(" ", staff.lastname, staff.firstname)) as assigned '
        .' ,IF(ptopic.topic_pid IS NULL, topic.topic, CONCAT_WS(" / ", ptopic.topic, topic.topic)) as helptopic '
        .' ,cdata.priority_id, cdata.subject, user.name, email.address as email';

$from =' FROM '.TICKET_TABLE.' ticket '
      .' LEFT JOIN '.USER_TABLE.' user ON user.id = ticket.user_id '
      .' LEFT JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id '
      .' LEFT JOIN '.USER_ACCOUNT_TABLE.' account ON (ticket.user_id=account.user_id) '
      .' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id '
      .' LEFT JOIN '.STAFF_TABLE.' staff ON (ticket.staff_id=staff.staff_id) '
      .' LEFT JOIN '.TEAM_TABLE.' team ON (ticket.team_id=team.team_id) '
      .' LEFT JOIN '.TOPIC_TABLE.' topic ON (ticket.topic_id=topic.topic_id) '
      .' LEFT JOIN '.TOPIC_TABLE.' ptopic ON (ptopic.topic_id=topic.topic_pid) '
      .' LEFT JOIN '.TABLE_PREFIX.'ticket__cdata cdata ON (cdata.ticket_id = ticket.ticket_id) '
      .' LEFT JOIN '.PRIORITY_TABLE.' pri ON (pri.priority_id = cdata.priority_id)';

if ($user)
    $where = 'WHERE ticket.user_id = '.db_input($user->getId());
elseif ($org)
    $where = 'WHERE user.org_id = '.db_input($org->getId());


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
        echo  sprintf('<strong>Showing 1 - %d of %s</strong>', count($results), count($results));
    } else {
        echo sprintf('%s does not have any tickets', $user? 'User' : 'Organization');
    }
   ?>
</div>
<div style="float:right;text-align:right;padding-right:5px;">
    <?php
    if ($user) { ?>
    <b><a class="Icon newTicket" href="tickets.php?a=open&uid=<?php echo $user->getId(); ?>"> Create New Ticket</a></b>
    <?php
    } ?>
</div>
<br/>
<div>
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
            <th width="300">Subject</th>
            <?php
            if ($user) { ?>
            <th width="200">Department</th>
            <th width="200">Assignee</th>
            <?php
            } else { ?>
            <th width="400">User</th>
            <?php
            } ?>
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
            <?php
            if ($user) { ?>
            <td><?php echo Format::truncate($row['dept_name'], 40); ?></td>
            <td>&nbsp;<?php echo $assigned; ?></td>
            <?php
            } else { ?>
            <td>&nbsp;<?php echo sprintf('<a href="users.php?id=%d">%s <em> &lt;%s&gt;</em></a>',
                    $row['user_id'], $row['name'], $row['email']); ?></td>
            <?php
            } ?>
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
