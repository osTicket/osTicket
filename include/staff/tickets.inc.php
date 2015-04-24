<?php
$search = SavedSearch::create();
$tickets = TicketModel::objects();
$clear_button = false;
$view_all_tickets = $date_header = $date_col = false;

// Figure out REFRESH url — which might not be accurate after posting a
// response
list($path,) = explode('?', $_SERVER['REQUEST_URI'], 2);
$args = array();
parse_str($_SERVER['QUERY_STRING'], $args);

// Remove commands from query
unset($args['id']);
if ($args['a'] !== 'search') unset($args['a']);

$refresh_url = $path . '?' . http_build_query($args);

$sort_options = array(
    'priority,updated' =>   __('Priority + Most Recently Updated'),
    'updated' =>            __('Most Recently Updated'),
    'priority,created' =>   __('Priority + Most Recently Created'),
    'due' =>                __('Due Soon'),
    'priority,due' =>       __('Priority + Due Soon'),
    'number' =>             __('Ticket Number'),
    'answered' =>           __('Most Recently Answered'),
    'closed' =>             __('Most Recently Closed'),
    'hot' =>                __('Longest Thread'),
    'relevance' =>          __('Relevance'),
);
$use_subquery = true;

$queue_name = strtolower($_GET['a'] ?: $_GET['status']); //Status is overloaded
// Stash current queue view
$_SESSION['::Q'] = $queue_name;

switch ($queue_name) {
case 'closed':
    $status='closed';
    $results_type=__('Closed Tickets');
    $showassigned=true; //closed by.
    $tickets->values('staff__firstname', 'staff__lastname', 'team__name', 'team_id');
    $queue_sort_options = array('closed', 'priority,due', 'due',
        'priority,updated', 'priority,created', 'answered', 'number', 'hot');
    break;
case 'overdue':
    $status='open';
    $results_type=__('Overdue Tickets');
    $tickets->filter(array('isoverdue'=>1));
    $queue_sort_options = array('priority,due', 'due', 'priority,updated',
        'updated', 'answered', 'priority,created', 'number', 'hot');
    break;
case 'assigned':
    $status='open';
    $staffId=$thisstaff->getId();
    $results_type=__('My Tickets');
    $tickets->filter(array('staff_id'=>$thisstaff->getId()));
    $queue_sort_options = array('updated', 'priority,updated',
        'priority,created', 'priority,due', 'due', 'answered', 'number',
        'hot');
    break;
case 'answered':
    $status='open';
    $showanswered=true;
    $results_type=__('Answered Tickets');
    $tickets->filter(array('isanswered'=>1));
    $queue_sort_options = array('answered', 'priority,updated', 'updated',
        'priority,created', 'priority,due', 'due', 'number', 'hot');
    break;
default:
case 'search':
    $queue_sort_options = array('priority,updated', 'priority,created',
        'priority,due', 'due', 'updated', 'answered',
        'closed', 'number', 'hot');
    // Consider basic search
    if ($_REQUEST['query']) {
        $results_type=__('Search Results');
        // Use an index if possible
        if (Validator::is_email($_REQUEST['query'])) {
            $tickets = $tickets->filter(array(
                'user__emails__address' => $_REQUEST['query'],
            ));
        }
        else {
            $tickets = $tickets->filter(Q::any(array(
                'number__startswith' => $_REQUEST['query'],
                'user__name__contains' => $_REQUEST['query'],
                'user__emails__address__contains' => $_REQUEST['query'],
                'user__org__name__contains' => $_REQUEST['query'],
            )));
        }
        break;
    } elseif (isset($_SESSION['advsearch'])) {
        // XXX: De-duplicate and simplify this code
        $form = $search->getFormFromSession('advsearch');
        $form->loadState($_SESSION['advsearch']);
        $tickets = $search->mangleQuerySet($tickets, $form);
        $view_all_tickets = $thisstaff->getRole()->hasPerm(SearchBackend::PERM_EVERYTHING);
        $results_type=__('Advanced Search')
            . '<a class="action-button" href="?clear_filter"><i style="top:0" class="icon-ban-circle"></i> <em>' . __('clear') . '</em></a>';
        $has_relevance = false;
        foreach ($tickets->getSortFields() as $sf) {
            if ($sf instanceof SqlCode && $sf->code == '`relevance`') {
                $has_relevance = true;
                break;
            }
        }
        if ($has_relevance) {
            $use_subquery = false;
            array_unshift($queue_sort_options, 'relevance');
        }
        elseif ($_SESSION[$queue_sort_key] == 'relevance') {
            unset($_SESSION[$queue_sort_key]);
        }

        break;
    }
    // Apply user filter
    elseif (isset($_GET['uid']) && ($user = User::lookup($_GET['uid']))) {
        $tickets->filter(array('user__id'=>$_GET['uid']));
        $results_type = sprintf('%s — %s', __('Search Results'),
            $user->getName());
        // Don't apply normal open ticket
        break;
    }
    elseif (isset($_GET['orgid']) && ($org = Organization::lookup($_GET['orgid']))) {
        $tickets->filter(array('user__org_id'=>$_GET['orgid']));
        $results_type = sprintf('%s — %s', __('Search Results'),
            $org->getName());
        // Don't apply normal open ticket
        break;
    }
    // Fall-through and show open tickets
case 'open':
    $status='open';
    $results_type=__('Open Tickets');
    $showassigned = ($cfg && $cfg->showAssignedTickets()) || $thisstaff->showAssignedTickets();
    if (!$cfg->showAnsweredTickets())
        $tickets->filter(array('isanswered'=>0));
    if (!$showassigned)
        $tickets->filter(Q::any(array('staff_id'=>0, 'team_id'=>0)));
    else
        $tickets->values('staff__firstname', 'staff__lastname', 'team__name');
    $queue_sort_options = array('priority,updated', 'updated',
        'priority,due', 'due', 'priority,created', 'answered', 'number',
        'hot');
    break;
}

// Apply primary ticket status
if (!isset($status) && isset($_GET['status']))
    $status = $_GET['status'];
if ($status)
    $tickets->filter(array('status__state'=>$status));

// Impose visibility constraints
// ------------------------------------------------------------
if (!$view_all_tickets) {
    // -- Open and assigned to me
    $assigned = Q::any(array(
        'staff_id' => $thisstaff->getId(),
    ));
    // -- Open and assigned to a team of mine
    if ($teams = array_filter($thisstaff->getTeams()))
        $assigned->add(array('team_id__in' => $teams));

    $visibility = Q::any(array('status__state'=>'open', $assigned));

    // -- Routed to a department of mine
    if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
        $visibility->add(array('dept_id__in' => $depts));

    $tickets->filter(Q::any($visibility));
}

// TODO :: Apply requested quick filter

// Apply requested pagination
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav = new Pagenate($tickets->count(), $page, PAGE_LIMIT);
$pageNav->setURL('tickets.php', $args);
$tickets = $pageNav->paginate($tickets);

// Apply requested sorting
$queue_sort_key = sprintf(':Q:%s:sort', $queue_name);

if (isset($_GET['sort'])) {
    $_SESSION[$queue_sort_key] = $_GET['sort'];
}
elseif (!isset($_SESSION[$queue_sort_key])) {
    $_SESSION[$queue_sort_key] = $queue_sort_options[0];
}

switch ($_SESSION[$queue_sort_key]) {
case 'number':
    $tickets->extra(array(
        'order_by'=>array(SqlExpression::times(new SqlField('number'), 1))
    ));
    break;

case 'priority,created':
    $tickets->order_by('cdata__:priority__priority_urgency');
    // Fall through to columns for `created`
case 'created':
    $date_header = __('Date Created');
    $date_col = 'created';
    $tickets->values('created');
    $tickets->order_by('-created');
    break;

case 'priority,due':
    $tickets->order_by('cdata__:priority__priority_urgency');
    // Fall through to add in due date filter
case 'due':
    $date_header = __('Due Date');
    $date_col = 'est_duedate';
    $tickets->values('est_duedate');
    $tickets->order_by(SqlFunction::COALESCE(new SqlField('est_duedate'), 'zzz'));
    break;

case 'closed':
    $date_header = __('Date Closed');
    $date_col = 'closed';
    $tickets->values('closed');
    $tickets->order_by('-closed');
    break;

case 'answered':
    $date_header = __('Last Response');
    $date_col = 'lastresponse';
    $date_fallback = '<em class="faded">'.__('unanswered').'</em>';
    $tickets->order_by('-lastresponse');
    $tickets->values('lastresponse');
    break;

case 'hot':
    $tickets->order_by('-thread_count');
    $tickets->annotate(array(
        'thread_count' => SqlAggregate::COUNT('thread__entries'),
    ));
    break;

case 'relevance':
    $tickets->order_by(new SqlCode('relevance'));
    break;

default:
case 'priority,updated':
    $tickets->order_by('cdata__:priority__priority_urgency');
    // Fall through for columns defined for `updated`
case 'updated':
    $date_header = __('Last Updated');
    $date_col = 'lastupdate';
    $tickets->order_by('-lastupdate');
    break;
}


// Rewrite $tickets to use a nested query, which will include the LIMIT part
// in order to speed the result
//
// ATM, advanced search with keywords doesn't support the subquery approach
if ($use_subquery) {
    $orig_tickets = clone $tickets;
    $tickets2 = TicketModel::objects();
    $tickets2->values = $tickets->values;
    $tickets2->filter(array('ticket_id__in' => $tickets->values_flat('ticket_id')));

    // Transfer the order_by from the original tickets
    $tickets2->order_by($tickets->getSortFields());
    $tickets = $tickets2;
}

TicketForm::ensureDynamicDataView();

// Select pertinent columns
// ------------------------------------------------------------
$tickets->values('lock__staff_id', 'staff_id', 'isoverdue', 'team_id', 'ticket_id', 'number', 'cdata__subject', 'user__default_email__address', 'source', 'cdata__:priority__priority_color', 'cdata__:priority__priority_desc', 'status_id', 'status__name', 'status__state', 'dept_id', 'dept__name', 'user__name', 'lastupdate');

// Add in annotations
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

// Save the query to the session for exporting
$_SESSION[':Q:tickets'] = $orig_tickets;

?>

<!-- SEARCH FORM START -->
<div id='basic_search'>
    <form action="tickets.php" method="get" onsubmit="javascript:
  $.pjax({
    url:$(this).attr('action') + '?' + $(this).serialize(),
    container:'#pjax-container',
    timeout: 2000
  });
return false;">
    <input type="hidden" name="a" value="search">
    <table>
        <tr>
            <td><input type="text" id="basic-ticket-search" name="query"
            size=30 value="<?php echo Format::htmlchars($_REQUEST['query'],
            true); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off"></td>
            <td><input type="submit" class="button" value="<?php echo __('Search'); ?>"></td>
            <td>&nbsp;&nbsp;<a href="#" onclick="javascript:
                $.dialog('ajax.php/tickets/search', 201);"
                >[<?php echo __('advanced'); ?>]</a>&nbsp;<i class="help-tip icon-question-sign" href="#advanced"></i></td>
        </tr>
    </table>
    </form>
</div>
<!-- SEARCH FORM END -->
<div class="clear"></div>
<div style="margin-bottom:20px; padding-top:10px;">
<div class="sticky bar">
    <div class="content">
        <div class="pull-left flush-left">
            <h2><a href="<?php echo $refresh_url; ?>"
                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> <?php echo
                $results_type.$showing; ?></a></h2>
        </div>
        <div class="pull-right flush-right">
            <span style="display:inline-block">
                <span style="vertical-align: baseline">Sort:</span>
            <select name="sort" onchange="javascript: $.pjax({
                url:'?' + addSearchParam('sort', $(this).val()),
                timeout: 2000,
                container: '#pjax-container'});">
<?php foreach ($queue_sort_options as $mode) {
    $desc = $sort_options[$mode]; ?>
            <option value="<?php echo $mode; ?>" <?php if ($mode == $_SESSION[$queue_sort_key]) echo 'selected="selected"'; ?>><?php echo $desc; ?></option>
<?php } ?>
            </select>
            </span>
            <?php
            if ($thisstaff->canManageTickets()) {
                echo TicketStatus::status_options();
            }
            if ($thisstaff->hasPerm(TicketModel::PERM_DELETE)) { ?>
            <a id="tickets-delete" class="action-button tickets-action"
                href="#tickets/status/delete"><i
            class="icon-trash"></i> <?php echo __('Delete'); ?></a>
            <?php
            } ?>
        </div>
    </div>
</div>
<div class="clear" style="margin-bottom:10px;"></div>
<form action="tickets.php" method="POST" name='tickets' id="tickets">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <input type="hidden" name="status" value="<?php echo
 Format::htmlchars($_REQUEST['status'], true); ?>" >
 <table class="list" border="0" cellspacing="1" cellpadding="2" width="940">
    <thead>
        <tr>
            <?php if ($thisstaff->canManageTickets()) { ?>
	        <th width="12px">&nbsp;</th>
            <?php } ?>
	        <th width="70">
                <?php echo __('Ticket'); ?></th>
	        <th width="100">
                <?php echo $date_header ?: __('Date Created'); ?></th>
	        <th width="280">
                <?php echo __('Subject'); ?></th>
            <th width="170">
                <?php echo __('From');?></th>
            <?php
            if($search && !$status) { ?>
                <th width="60">
                    <?php echo __('Status');?></th>
            <?php
            } else { ?>
                <th width="60" <?php echo $pri_sort;?>>
                    <?php echo __('Priority');?></th>
            <?php
            }

            if($showassigned ) {
                //Closed by
                if(!strcasecmp($status,'closed')) { ?>
                    <th width="150">
                        <?php echo __('Closed By'); ?></th>
                <?php
                } else { //assigned to ?>
                    <th width="150">
                        <?php echo __('Assigned To'); ?></th>
                <?php
                }
            } else { ?>
                <th width="150">
                    <?php echo __('Department');?></th>
            <?php
            } ?>
        </tr>
     </thead>
     <tbody>
        <?php
        // Setup Subject field for display
        $subject_field = TicketForm::getInstance()->getField('subject');
        $class = "row1";
        $total=0;
        $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
        foreach ($tickets as $T) {
            $total += 1;
                $tag=$T['staff_id']?'assigned':'openticket';
                $flag=null;
                if($T['lock__staff_id'] && $T['lock__staff_id'] != $thisstaff->getId())
                    $flag='locked';
                elseif($T['isoverdue'])
                    $flag='overdue';

                $lc='';
                if ($showassigned) {
                    if ($T['staff_id'])
                        $lc = new PersonsName($T['staff__firstname'].' '.$T['staff__lastname']);
                    elseif ($T['team_id'])
                        $lc = Team::getLocalById($T['team_id'], 'name', $T['team__name']);
                }
                else {
                    $lc = Dept::getLocalById($T['dept_id'], 'name', $T['dept__name']);
                }
                $tid=$T['number'];
                $subject = $subject_field->display($subject_field->to_php($T['cdata__subject']));
                $threadcount=$T['thread_count'];
                if(!strcasecmp($T['status__state'],'open') && !$T['isanswered'] && !$T['lock__staff_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>
            <tr id="<?php echo $T['ticket_id']; ?>">
                <?php if($thisstaff->canManageTickets()) {

                    $sel=false;
                    if($ids && in_array($T['ticket_id'], $ids))
                        $sel=true;
                    ?>
                <td align="center" class="nohover">
                    <input class="ckb" type="checkbox" name="tids[]"
                        value="<?php echo $T['ticket_id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <?php } ?>
                <td title="<?php echo $T['user__default_email__address']; ?>" nowrap>
                  <a class="Icon <?php echo strtolower($T['source']); ?>Ticket preview"
                    title="Preview Ticket"
                    href="tickets.php?id=<?php echo $T['ticket_id']; ?>"
                    data-preview="#tickets/<?php echo $T['ticket_id']; ?>/preview"
                    ><?php echo $tid; ?></a></td>
                <td align="center" nowrap><?php echo Format::datetime($T[$date_col ?: 'lastupdate']) ?: $date_fallback; ?></td>
                <td><a <?php if ($flag) { ?> class="Icon <?php echo $flag; ?>Ticket" title="<?php echo ucfirst($flag); ?> Ticket" <?php } ?>
                    style="max-width: 210px;"
                    href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><span
                    class="truncate"><?php echo $subject; ?></span></a>
<?php               if ($T['attachment_count'])
                        echo '<i class="small icon-paperclip icon-flip-horizontal"></i>';
                    if ($threadcount > 1) { ?>
                        <span class="pull-right faded-more"><i class="icon-comments-alt"></i>
                            <small><?php echo $threadcount; ?></small>
                        </span>
                    <?php } ?>
                </td>
                <td nowrap><div><?php
                    if ($T['collab_count'])
                        echo '<span class="pull-right faded-more"><i class="icon-group"></i></span>';
                    ?><span class="truncate" style="max-width:<?php
                        echo $T['collab_count'] ? '150px' : '170px'; ?>"><?php
                    $un = new PersonsName($T['user__name']);
                        echo Format::htmlchars($un);
                    ?></span></div></td>
                <?php
                if($search && !$status){
                    $displaystatus=TicketStatus::getLocalById($T['status_id'], 'value', $T['status__name']);
                    if(!strcasecmp($T['status__state'],'open'))
                        $displaystatus="<b>$displaystatus</b>";
                    echo "<td>$displaystatus</td>";
                } else { ?>
                <td class="nohover" align="center" style="background-color:<?php echo $T['cdata__:priority__priority_color']; ?>;">
                    <?php echo $T['cdata__:priority__priority_desc']; ?></td>
                <?php
                }
                ?>
                <td nowrap>&nbsp;<?php echo Format::htmlchars($lc); ?></td>
            </tr>
            <?php
            } //end of foreach
        if (!$total)
            $ferror=__('There are no tickets matching your criteria.');
        ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if($total && $thisstaff->canManageTickets()){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo $ferror?Format::htmlchars($ferror):__('Query returned 0 results.');
                echo '</i>';
            } ?>
        </td>
     </tr>
    </tfoot>
    </table>
    <?php
    if ($total>0) { //if we actually had any tickets returned.
        echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;';
        echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                __('Export'));
        echo '&nbsp;<i class="help-tip icon-question-sign" href="#export"></i></div>';
    } ?>
    </form>
</div>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="mark_overdue-confirm">
        <?php echo __('Are you sure you want to flag the selected tickets as <font color="red"><b>overdue</b></font>?');?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel');?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!');?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
<script type="text/javascript">
$(function() {
    $(document).off('.tickets');
    $(document).on('click.tickets', 'a.tickets-action', function(e) {
        e.preventDefault();
        var count = checkbox_checker($('form#tickets'), 1);
        if (count) {
            var url = 'ajax.php/'
            +$(this).attr('href').substr(1)
            +'?count='+count
            +'&_uid='+new Date().getTime();
            $.dialog(url, [201], function (xhr) {
                window.location.href = window.location.href;
             });
        }
        return false;
    });
});
</script>

