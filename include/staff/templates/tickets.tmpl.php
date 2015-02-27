<?php

$select ='SELECT ticket.ticket_id,ticket.`number`,ticket.dept_id,ticket.staff_id,ticket.team_id, ticket.user_id '
        .' ,dept.name as department, status.name as status,ticket.source,ticket.isoverdue,ticket.isanswered,ticket.created '
        .' ,CAST(GREATEST(IFNULL(ticket.lastmessage, 0), IFNULL(ticket.reopened, 0), ticket.created) as datetime) as effective_date '
        .' ,CONCAT_WS(" ", staff.firstname, staff.lastname) as staff, team.name as team '
        .' ,IF(staff.staff_id IS NULL,team.name,CONCAT_WS(" ", staff.lastname, staff.firstname)) as assigned '
        .' ,IF(ptopic.topic_pid IS NULL, topic.topic, CONCAT_WS(" / ", ptopic.topic, topic.topic)) as helptopic '
        .' ,cdata.priority as priority_id, cdata.subject, user.name, email.address as email';

$from =' FROM '.TICKET_TABLE.' ticket '
      .' LEFT JOIN '.TICKET_STATUS_TABLE.' status
        ON status.id = ticket.status_id '
      .' LEFT JOIN '.USER_TABLE.' user ON user.id = ticket.user_id '
      .' LEFT JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id '
      .' LEFT JOIN '.USER_ACCOUNT_TABLE.' account ON (ticket.user_id=account.user_id) '
      .' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.id '
      .' LEFT JOIN '.STAFF_TABLE.' staff ON (ticket.staff_id=staff.staff_id) '
      .' LEFT JOIN '.TEAM_TABLE.' team ON (ticket.team_id=team.team_id) '
      .' LEFT JOIN '.TOPIC_TABLE.' topic ON (ticket.topic_id=topic.topic_id) '
      .' LEFT JOIN '.TOPIC_TABLE.' ptopic ON (ptopic.topic_id=topic.topic_pid) '
      .' LEFT JOIN '.TABLE_PREFIX.'ticket__cdata cdata ON (cdata.ticket_id = ticket.ticket_id) '
      .' LEFT JOIN '.PRIORITY_TABLE.' pri ON (pri.priority_id = cdata.priority)';

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
        count(DISTINCT attach.id) as attachments,
        count(DISTINCT entry.id) as thread_count,
        count(DISTINCT collab.id) as collaborators
        FROM '.TICKET_TABLE.' ticket '
     .' LEFT JOIN '.THREAD_TABLE.' thread
            ON (thread.object_id=ticket.ticket_id AND thread.object_type="T") '
     .' LEFT JOIN '.THREAD_ENTRY_TABLE.' entry ON (entry.thread_id=thread.id) '
     .' LEFT JOIN '.ATTACHMENT_TABLE.' attach
            ON (attach.object_id=entry.id AND attach.`type` = "H") '
     .' LEFT JOIN '.THREAD_COLLABORATOR_TABLE.' collab
            ON ( thread.id=collab.thread_id) '
     .' WHERE ticket.ticket_id IN ('.implode(',', db_input(array_keys($results))).')
        GROUP BY ticket.ticket_id';
    $ids_res = db_query($counts_sql);
    while ($row = db_fetch_array($ids_res)) {
        $results[$row['ticket_id']] += $row;
    }
}
?>
<div style="width:700px;" class="pull-left">
   <?php
    if ($results) {
        echo '<strong>'.sprintf(_N('Showing %d ticket', 'Showing %d tickets',
            count($results)), count($results)).'</strong>';
    } else {
        echo sprintf(__('%s does not have any tickets'), $user? 'User' : 'Organization');
    }
   ?>
</div>
<div class="pull-right flush-right" style="padding-right:5px;">
    <?php
    if ($user) { ?>
    <b><a class="Icon newTicket" href="tickets.php?a=open&uid=<?php echo $user->getId(); ?>">
    <?php print __('Create New Ticket'); ?></a></b>
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
 <table class="list fixed" border="0" cellspacing="1" cellpadding="2" width="940">
    <thead>
        <tr>
            <?php
            if (0) {?>
            <th width="8px">&nbsp;</th>
            <?php
            } ?>
            <th width="70"><?php echo __('Ticket'); ?></th>
            <th width="100"><?php echo __('Date'); ?></th>
            <th width="100"><?php echo __('Status'); ?></th>
            <th width="300"><?php echo __('Subject'); ?></th>
            <?php
            if ($user) { ?>
            <th width="100"><?php echo __('Department'); ?></th>
            <th width="100"><?php echo __('Assignee'); ?></th>
            <?php
            } else { ?>
            <th width="200"><?php echo __('User'); ?></th>
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
            $assigned=sprintf('<span class="truncate Icon staffAssigned">%s</span>',$row['staff']);
        elseif ($row['team_id'])
            $assigned=sprintf('<span class="truncate Icon teamAssigned">%s</span>',$row['team']);
        else
            $assigned=' ';

        $status = ucfirst($row['status']);
        $tid=$row['number'];
        $subject = Format::htmlchars($row['subject']);
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
              <a class="Icon <?php
                echo strtolower($row['source']); ?>Ticket preview"
                title="<?php echo __('Preview Ticket'); ?>"
                href="tickets.php?id=<?php echo $row['ticket_id']; ?>"
                data-preview="#tickets/<?php echo $row['ticket_id']; ?>/preview"><?php echo $tid; ?></a></td>
            <td align="center" nowrap><?php echo Format::datetime($row['effective_date']); ?></td>
            <td><?php echo $status; ?></td>
            <td><a class="truncate <?php if ($flag) { ?> Icon <?php echo $flag; ?>Ticket" title="<?php echo ucfirst($flag); ?> Ticket<?php } ?>"
                style="max-width: 80%; max-width: calc(100% - 86px);"
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
            <td><span class="truncate"><?php echo $row['department']; ?></td>
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
