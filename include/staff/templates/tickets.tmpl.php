<?php
$args = array();
parse_str($_SERVER['QUERY_STRING'], $args);
$args['t'] = 'tickets';
unset($args['p'], $args['_pjax']);

$tickets = TicketModel::objects();

if ($user) {
    $filter = $tickets->copy()
        ->values_flat('ticket_id')
        ->filter(array('user_id' => $user->getId()))
        ->union($tickets->copy()
            ->values_flat('ticket_id')
            ->filter(array('thread__collaborators__user_id' => $user->getId()))
        , false);
} elseif ($org) {
    $filter = $tickets->copy()
        ->values_flat('ticket_id')
        ->filter(array('user__org' => $org));
}

// Apply filter
$tickets->filter(array('ticket_id__in' => $filter));

// Apply staff visibility
if (!$thisstaff->hasPerm(SearchBackend::PERM_EVERYTHING)) {
    // -- Open and assigned to me
    $visibility = array(
        new Q(array('status__state'=>'open', 'staff_id' => $thisstaff->getId()))
    );
    // -- Routed to a department of mine
    if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
        $visibility[] = new Q(array('dept_id__in' => $depts));
    // -- Open and assigned to a team of mine
    if (($teams = $thisstaff->getTeams()) && count(array_filter($teams)))
        $visibility[] = new Q(array(
            'team_id__in' => array_filter($teams), 'status__state'=>'open'
        ));
    $tickets->filter(Q::any($visibility));
}

$tickets->constrain(array('lock' => array(
                'lock__expire__gt' => SqlFunction::NOW())));

// Group by ticket_id.
$tickets->distinct('ticket_id');

// Save the query to the session for exporting
$queue = sprintf(':%s:tickets', $user ? 'U' : 'O');
$_SESSION[$queue] = $tickets;

// Apply pagination
$total = $tickets->count();
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$pageNav = new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL(($user ? 'users.php' : 'orgs.php'), $args);
$tickets = $pageNav->paginate($tickets);

$tickets->annotate(array(
    'collab_count' => SqlAggregate::COUNT('thread__collaborators', true),
    'attachment_count' => SqlAggregate::COUNT(SqlCase::N()
       ->when(new SqlField('thread__entries__attachments__inline'), null)
       ->otherwise(new SqlField('thread__entries__attachments')),
        true
    ),
    'thread_count' => SqlAggregate::COUNT(SqlCase::N()
        ->when(
            new Q(array('thread__entries__flags__hasbit'=>ThreadEntry::FLAG_HIDDEN)),
            null)
        ->otherwise(new SqlField('thread__entries__id')),
       true
    ),
));

$tickets->values('staff_id', 'staff__firstname', 'staff__lastname', 'team__name', 'team_id', 'lock__lock_id', 'lock__staff_id', 'isoverdue', 'status_id', 'status__name', 'status__state', 'number', 'cdata__subject', 'ticket_id', 'source', 'dept_id', 'dept__name', 'user_id', 'user__default_email__address', 'user__name', 'lastupdate');

$tickets->order_by('-created');

TicketForm::ensureDynamicDataView();
// Fetch the results
?>
<div class="pull-left" style="margin-top:5px;">
   <?php
    if ($total) {
        echo '<strong>'.$pageNav->showing().'</strong>';
    } else {
        echo sprintf(__('%s does not have any tickets'), $user? 'User' : 'Organization');
    }
   ?>
</div>
<div style="margin-bottom:10px;">
    <div class="pull-right flush-right">
        <?php
        if ($user) { ?>
            <a class="green button action-button" href="tickets.php?a=open&uid=<?php echo $user->getId(); ?>">
                <i class="icon-plus"></i> <?php print __('Create New Ticket'); ?></a>
        <?php
        } ?>
    </div>
</div>
<br/>
<div>
<?php
if ($total) { ?>
<form action="users.php" method="POST" name='tickets' style="padding-top:10px;">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="2" width="940">
    <thead>
        <tr>
            <?php
            if (0) {?>
            <th width="4%">&nbsp;</th>
            <?php
            } ?>
            <th width="10%"><?php echo __('Ticket'); ?></th>
            <th width="18%"><?php echo __('Last Updated'); ?></th>
            <th width="8%"><?php echo __('Status'); ?></th>
            <th width="30%"><?php echo __('Subject'); ?></th>
            <?php
            if ($user) { ?>
            <th width="15%"><?php echo __('Department'); ?></th>
            <th width="15%"><?php echo __('Assignee'); ?></th>
            <?php
            } else { ?>
            <th width="30%"><?php echo __('User'); ?></th>
            <?php
            } ?>
        </tr>
    </thead>
    <tbody>
    <?php
    $subject_field = TicketForm::objects()->one()->getField('subject');
    $user_id = $user ? $user->getId() : 0;
    foreach($tickets as $T) {
        $flag=null;
        if ($T['lock__lock_id'] && $T['lock__staff_id'] != $thisstaff->getId())
            $flag='locked';
        elseif ($T['isoverdue'])
            $flag='overdue';

        $assigned='';
        if ($T['staff_id'])
            $assigned = new AgentsName(array(
                'first' => $T['staff__firstname'],
                'last' => $T['staff__lastname']
            ));
        elseif ($T['team_id'])
            $assigned = Team::getLocalById($T['team_id'], 'name', $T['team__name']);
        else
            $assigned=' ';

        $status = TicketStatus::getLocalById($T['status_id'], 'value', $T['status__name']);
        $tid = $T['number'];
        $subject = $subject_field->display($subject_field->to_php($T['cdata__subject']));
        $threadcount = $T['thread_count'];
        ?>
        <tr id="<?php echo $T['ticket_id']; ?>">
            <?php
            //Implement mass  action....if need be.
            if (0) { ?>
            <td align="center" class="nohover">
                <input class="ckb" type="checkbox" name="tids[]" value="<?php echo $T['ticket_id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
            </td>
            <?php
            } ?>
            <td nowrap>
              <a class="Icon <?php
                echo strtolower($T['source']); ?>Ticket preview"
                title="<?php echo __('Preview Ticket'); ?>"
                href="tickets.php?id=<?php echo $T['ticket_id']; ?>"
                data-preview="#tickets/<?php echo $T['ticket_id']; ?>/preview"><?php
                echo $tid; ?></a>
               <?php
                if ($user_id && $user_id != $T['user_id'])
                    echo '<span class="pull-right faded-more" data-toggle="tooltip" title="'
                            .__('Collaborator').'"><i class="icon-eye-open"></i></span>';
            ?></td>
            <td nowrap><?php echo Format::datetime($T['lastupdate']); ?></td>
            <td><?php echo $status; ?></td>
            <td><a class="truncate <?php if ($flag) { ?> Icon <?php echo $flag; ?>Ticket" title="<?php echo ucfirst($flag); ?> Ticket<?php } ?>"
                style="max-width: 230px;"
                href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo $subject; ?></a>
                 <?php
                    if ($T['attachment_count'])
                        echo '<i class="small icon-paperclip icon-flip-horizontal" data-toggle="tooltip" title="'
                            .$T['attachment_count'].'"></i>';
                    if ($threadcount > 1) { ?>
                            <span class="pull-right faded-more"><i class="icon-comments-alt"></i>
                            <small><?php echo $threadcount; ?></small></span>
<?php               }
                    if ($T['attachments'])
                        echo '<i class="small icon-paperclip icon-flip-horizontal"></i>';
                    if ($T['collab_count'])
                        echo '<span class="faded-more" data-toggle="tooltip" title="'
                            .$T['collab_count'].'"><i class="icon-group"></i></span>';
                ?>
            </td>
            <?php
            if ($user) {
                $dept = Dept::getLocalById($T['dept_id'], 'name', $T['dept__name']); ?>
            <td><span class="truncate" style="max-wdith:125px"><?php
                echo Format::htmlchars($dept); ?></span></td>
            <td><span class="truncate" style="max-width:125px"><?php
                echo Format::htmlchars($assigned); ?></span></td>
            <?php
            } else { ?>
            <td><a class="truncate" style="max-width:250px" href="users.php?id="<?php
                echo $T['user_id']; ?>><?php echo Format::htmlchars($T['user__name']);
                    ?> <em>&lt;<?php echo Format::htmlchars($T['user__default_email__address']);
                ?>&gt;</em></a>
            </td>
            <?php
            } ?>
        </tr>
   <?php
    }
    ?>
    </tbody>
</table>
<?php
if ($total>0) {
    echo '<div>';
    echo __('Page').':'.$pageNav->getPageLinks('tickets', '#tickets').'&nbsp;';
    echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
            Http::build_query(array(
                    'id' => $user ? $user->getId(): $org->getId(),
                    'a' => 'export',
                    't' => 'tickets')),
            __('Export'));
    echo '</div>';
} ?>
</form>
<?php
 } ?>
</div>
