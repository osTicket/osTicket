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
unset($args['a']);

$refresh_url = $path . '?' . http_build_query($args);

$queue_name = strtolower($_GET['status'] ?: $_GET['a']); //Status is overloaded
switch ($queue_name) {
case 'closed':
    $status='closed';
    $results_type=__('Closed Tickets');
    $showassigned=true; //closed by.
    $tickets->values('staff__firstname', 'staff__lastname', 'team__name', 'team_id');
    break;
case 'overdue':
    $status='open';
    $results_type=__('Overdue Tickets');
    $tickets->filter(array('isoverdue'=>1));
    break;
case 'assigned':
    $status='open';
    $staffId=$thisstaff->getId();
    $results_type=__('My Tickets');
    $tickets->filter(array('staff_id'=>$thisstaff->getId()));
    break;
case 'answered':
    $status='open';
    $showanswered=true;
    $results_type=__('Answered Tickets');
    $tickets->filter(array('isanswered'=>1));
    break;
default:
case 'search':
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
                'user__emails__address__contains' => $_REQUEST['query'],
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
            . '<a class="action-button" href="?clear_filter"><i class="icon-ban-circle"></i> <em>' . __('clear') . '</em></a>';
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
    break;
}

// Apply user filter
if (isset($_GET['uid'])) {
    $tickets->filter(array('user__id'=>$_GET['uid']));
}


// Apply primary ticket status
if ($status)
    $tickets->filter(array('status__state'=>$status));

// Impose visibility constraints
// ------------------------------------------------------------
if (!$view_all_tickets) {
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

// Add in annotations
$tickets->annotate(array(
    'collab_count' => SqlAggregate::COUNT('thread__collaborators'),
    'attachment_count' => SqlAggregate::COUNT('thread__entries__attachments'),
    'thread_count' => SqlAggregate::COUNT('thread__entries'),
));

// Select pertinent columns
// ------------------------------------------------------------
$tickets->values('lock__staff_id', 'staff_id', 'isoverdue', 'team_id', 'ticket_id', 'number', 'cdata__subject', 'user__default_email__address', 'source', 'cdata__:priority__priority_color', 'cdata__:priority__priority_desc', 'status_id', 'status__name', 'status__state', 'dept_id', 'dept__name', 'user__name', 'lastupdate');

// Apply requested quick filter

// Apply requested sorting
$queue_sort_key = sprintf(':Q:%s:sort', $queue_name);

if (isset($_GET['sort']))
    $_SESSION[$queue_sort_key] = $_GET['sort'];
switch ($_SESSION[$queue_sort_key]) {
case 'number':
    $tickets->extra(array(
        'order_by'=>array(SqlExpression::times(new SqlField('number'), 1))
    ));
    break;
case 'created':
    $tickets->order_by('-created');
    break;

case 'priority,due':
    $tickets->order_by('cdata__:priority__priority_urgency');
    // Fall through to add in due date filter
case 'due':
    $date_header = __('Due Date');
    $date_col = 'est_duedate';
    $tickets->values('est_duedate');
    $tickets->filter(array('est_duedate__isnull'=>false));
    $tickets->order_by('est_duedate');
    break;

default:
case 'updated':
    $tickets->order_by('cdata__:priority__priority_urgency', '-lastupdate');
    break;
}

// Apply requested pagination
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($tickets->count(), $page, PAGE_LIMIT);
$pageNav->setURL('tickets.php', $args);
$tickets = $pageNav->paginate($tickets);

TicketForm::ensureDynamicDataView();

// Save the query to the session for exporting
$_SESSION[':Q:tickets'] = $tickets;

?>

<!-- SEARCH FORM START -->
<div id='basic_search'>
    <form action="tickets.php" method="get">
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
<div>
        <div class="pull-left flush-left">
            <h2><a href="<?php echo $refresh_url; ?>"
                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> <?php echo
                $results_type.$showing; ?></a></h2>
        </div>
        <div class="pull-right flush-right">
            <span style="display:inline-block">
                <span style="vertical-align: baseline">Sort:</span>
            <select name="sort" onchange="javascript:addSearchParam('sort', $(this).val());">
<?php foreach (array(
    'updated' =>    __('Most Recently Updated'),
    'created' =>    __('Most Recently Created'),
    'due' =>        __('Due Soon'),
    'priority,due' => __('Priority + Due Soon'),
    'number' =>     __('Ticket Number'),
) as $mode => $desc) { ?>
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
<div class="clear" style="margin-bottom:10px;"></div>
<form action="tickets.php" method="POST" name='tickets' id="tickets">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <input type="hidden" name="status" value="<?php echo
 Format::htmlchars($_REQUEST['status'], true); ?>" >
 <table class="list fixed" border="0" cellspacing="1" cellpadding="2" width="940">
    <thead>
        <tr>
            <?php if ($thisstaff->canManageTickets()) { ?>
	        <th width="12px">&nbsp;</th>
            <?php } ?>
	        <th width="70">
                <?php echo __('Ticket'); ?></th>
	        <th width="100">
                <?php echo $date_header ?: __('Date'); ?></th>
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
        $subject_field = TicketForm::objects()->one()->getField('subject');
        $class = "row1";
        $total=0;
        $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
        $subject_field = TicketForm::objects()->one()->getField('subject');
        foreach ($tickets as $T) {
            $total += 1;
                $tag=$T['staff_id']?'assigned':'openticket';
                $flag=null;
                if($T['lock__staff_id'] && $T['lock__staff_id'] != $thisstaff->getId())
                    $flag='locked';
                elseif($T['isoverdue'])
                    $flag='overdue';

                $lc='';
                $dept = Dept::getLocalById($T['dept_id'], 'name', $T['dept__name']);
                if($showassigned) {
                    if($T['staff_id'])
                        $lc=sprintf('<span class="Icon staffAssigned truncate">%s</span>',(string) new PersonsName($T['staff__firstname'].' '.$T['staff__lastname']));
                    elseif($T['team_id'])
                        $lc=sprintf('<span class="Icon teamAssigned">%s</span>',
                            Team::getLocalById($T['team_id'], 'name', $T['team__name']));
                    else
                        $lc=' ';
                }else{
                    $lc='<span class="truncate">'.Format::htmlchars($dept).'</span>';
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
                <td align="center" nowrap><?php echo Format::datetime($T[$date_col ?: 'lastupdate']); ?></td>
                <td><a <?php if ($flag) { ?> class="Icon <?php echo $flag; ?>Ticket" title="<?php echo ucfirst($flag); ?> Ticket" <?php } ?>
                    style="max-width: 80%; max-width: calc(100% - 86px);"
                    href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><span
                    class="truncate"><?php echo $subject; ?></span></a>
                     <?php
                        if ($threadcount>1)
                            echo "<small>($threadcount)</small>&nbsp;".'<i
                                class="icon-fixed-width icon-comments-alt"></i>&nbsp;';
                        if ($T['collab_count'])
                            echo '<i class="icon-fixed-width icon-group faded"></i>&nbsp;';
                        if ($T['attachment_count'])
                            echo '<i class="icon-fixed-width icon-paperclip"></i>&nbsp;';
                    ?>
                </td>
                <td nowrap><span class="truncate"><?php
                    $un = new PersonsName($T['user__name']);
                        echo Format::htmlchars($un);
                ?></td>
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
                <td nowrap>&nbsp;<?php echo $lc; ?></td>
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
        <?php echo __('Are you sure want to flag the selected tickets as <font color="red"><b>overdue</b></font>?');?>
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

