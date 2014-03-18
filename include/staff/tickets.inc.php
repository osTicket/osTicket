<?php
if(!defined('OSTSCPINC') || !$thisstaff || !@$thisstaff->isStaff()) die('Access Denied');

$qstr='&'; //Query string collector
if($_REQUEST['status']) { //Query string status has nothing to do with the real status used below; gets overloaded.
    $qstr.='status='.urlencode($_REQUEST['status']);
}

//See if this is a search
$search=($_REQUEST['a']=='search');
$searchTerm='';
//make sure the search query is 3 chars min...defaults to no query with warning message
if($search) {
  $searchTerm=$_REQUEST['query'];
  if( ($_REQUEST['query'] && strlen($_REQUEST['query'])<3)
      || (!$_REQUEST['query'] && isset($_REQUEST['basic_search'])) ){ //Why do I care about this crap...
      $search=false; //Instead of an error page...default back to regular query..with no search.
      $errors['err']='Search term must be more than 3 chars';
      $searchTerm='';
  }
}
$showoverdue=$showanswered=false;
$staffId=0; //Nothing for now...TODO: Allow admin and manager to limit tickets to single staff level.
$showassigned= true; //show Assigned To column - defaults to true

//Get status we are actually going to use on the query...making sure it is clean!
$status=null;
switch(strtolower($_REQUEST['status'])){ //Status is overloaded
    case 'open':
        $status='open';
        break;
    case 'closed':
        $status='closed';
        $showassigned=true; //closed by.
        break;
    case 'overdue':
        $status='open';
        $showoverdue=true;
        $results_type='Overdue Tickets';
        break;
    case 'assigned':
        $status='open';
        $staffId=$thisstaff->getId();
        $results_type='My Tickets';
        break;
    case 'answered':
        $status='open';
        $showanswered=true;
        $results_type='Answered Tickets';
        break;
    default:
        if(!$search && !isset($_REQUEST['advsid']))
            $_REQUEST['status']=$status='open';
}

$qwhere ='';
/*
   STRICT DEPARTMENTS BASED PERMISSION!
   User can also see tickets assigned to them regardless of the ticket's dept.
*/

$depts=$thisstaff->getDepts();
$qwhere =' WHERE ( '
        .'  ( ticket.staff_id='.db_input($thisstaff->getId())
        .' AND ticket.status="open")';

if(!$thisstaff->showAssignedOnly())
    $qwhere.=' OR ticket.dept_id IN ('.($depts?implode(',', db_input($depts)):0).')';

if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
    $qwhere.=' OR (ticket.team_id IN ('.implode(',', db_input(array_filter($teams)))
            .') AND ticket.status="open")';

$qwhere .= ' )';

//STATUS
if($status) {
    $qwhere.=' AND ticket.status='.db_input(strtolower($status));
}

if (isset($_REQUEST['uid']) && $_REQUEST['uid']) {
    $qwhere .= ' AND (ticket.user_id='.db_input($_REQUEST['uid'])
            .' OR collab.user_id='.db_input($_REQUEST['uid']).') ';
    $qstr .= '&uid='.urlencode($_REQUEST['uid']);
}

//Queues: Overloaded sub-statuses  - you've got to just have faith!
if($staffId && ($staffId==$thisstaff->getId())) { //My tickets
    $results_type='Assigned Tickets';
    $qwhere.=' AND ticket.staff_id='.db_input($staffId);
    $showassigned=false; //My tickets...already assigned to the staff.
}elseif($showoverdue) { //overdue
    $qwhere.=' AND ticket.isoverdue=1 ';
}elseif($showanswered) { ////Answered
    $qwhere.=' AND ticket.isanswered=1 ';
}elseif(!strcasecmp($status, 'open') && !$search) { //Open queue (on search OPEN means all open tickets - regardless of state).
    //Showing answered tickets on open queue??
    if(!$cfg->showAnsweredTickets())
        $qwhere.=' AND ticket.isanswered=0 ';

    /* Showing assigned tickets on open queue?
       Don't confuse it with show assigned To column -> F'it it's confusing - just trust me!
     */
    if(!($cfg->showAssignedTickets() || $thisstaff->showAssignedTickets())) {
        $qwhere.=' AND ticket.staff_id=0 '; //XXX: NOT factoring in team assignments - only staff assignments.
        $showassigned=false; //Not showing Assigned To column since assigned tickets are not part of open queue
    }
}

//Search?? Somebody...get me some coffee
$deep_search=false;
if($search):
    $qstr.='&a='.urlencode($_REQUEST['a']);
    $qstr.='&t='.urlencode($_REQUEST['t']);

    //query
    if($searchTerm){
        $qstr.='&query='.urlencode($searchTerm);
        $queryterm=db_real_escape($searchTerm,false); //escape the term ONLY...no quotes.
        if (is_numeric($searchTerm)) {
            $qwhere.=" AND ticket.`number` LIKE '$queryterm%'";
        } elseif (strpos($searchTerm,'@') && Validator::is_email($searchTerm)) {
            //pulling all tricks!
            # XXX: What about searching for email addresses in the body of
            #      the thread message
            $qwhere.=" AND email.address='$queryterm'";
        } else {//Deep search!
            //This sucks..mass scan! search anything that moves!
            require_once(INCLUDE_DIR.'ajax.tickets.php');

            $tickets = TicketsAjaxApi::_search(array('query'=>$queryterm));
            if (count($tickets))
                $qwhere .= ' AND ticket.ticket_id IN ('.
                    implode(',',db_input($tickets)).')';
            else
                // No hits -- there should be an empty list of results
                $qwhere .= ' AND false';
        }
   }

endif;

if ($_REQUEST['advsid'] && isset($_SESSION['adv_'.$_REQUEST['advsid']])) {
    $qstr.='advsid='.$_REQUEST['advsid'];
    $qwhere .= ' AND ticket.ticket_id IN ('. implode(',',
        db_input($_SESSION['adv_'.$_REQUEST['advsid']])).')';
}

$sortOptions=array('date'=>'effective_date','ID'=>'ticket.`number`',
    'pri'=>'pri.priority_urgency','name'=>'user.name','subj'=>'cdata.subject',
    'status'=>'ticket.status','assignee'=>'assigned','staff'=>'staff',
    'dept'=>'dept.dept_name');

$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');

//Sorting options...
$queue = isset($_REQUEST['status'])?strtolower($_REQUEST['status']):$status;
$order_by=$order=null;
if($_REQUEST['sort'] && $sortOptions[$_REQUEST['sort']])
    $order_by =$sortOptions[$_REQUEST['sort']];
elseif($sortOptions[$_SESSION[$queue.'_tickets']['sort']]) {
    $_REQUEST['sort'] = $_SESSION[$queue.'_tickets']['sort'];
    $_REQUEST['order'] = $_SESSION[$queue.'_tickets']['order'];

    $order_by = $sortOptions[$_SESSION[$queue.'_tickets']['sort']];
    $order = $_SESSION[$queue.'_tickets']['order'];
}

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order=$orderWays[strtoupper($_REQUEST['order'])];

//Save sort order for sticky sorting.
if($_REQUEST['sort'] && $queue) {
    $_SESSION[$queue.'_tickets']['sort'] = $_REQUEST['sort'];
    $_SESSION[$queue.'_tickets']['order'] = $_REQUEST['order'];
}

//Set default sort by columns.
if(!$order_by ) {
    if($showanswered)
        $order_by='ticket.lastresponse, ticket.created'; //No priority sorting for answered tickets.
    elseif(!strcasecmp($status,'closed'))
        $order_by='ticket.closed, ticket.created'; //No priority sorting for closed tickets.
    elseif($showoverdue) //priority> duedate > age in ASC order.
        $order_by='pri.priority_urgency ASC, ISNULL(ticket.duedate) ASC, ticket.duedate ASC, effective_date ASC, ticket.created';
    else //XXX: Add due date here?? No -
        $order_by='pri.priority_urgency ASC, effective_date DESC, ticket.created';
}

$order=$order?$order:'DESC';
if($order_by && strpos($order_by,',') && $order)
    $order_by=preg_replace('/(?<!ASC|DESC),/', " $order,", $order_by);

$sort=$_REQUEST['sort']?strtolower($_REQUEST['sort']):'pri.priority_urgency'; //Urgency is not on display table.
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';

if($_GET['limit'])
    $qstr.='&limit='.urlencode($_GET['limit']);

$qselect ='SELECT ticket.ticket_id,tlock.lock_id,ticket.`number`,ticket.dept_id,ticket.staff_id,ticket.team_id '
    .' ,user.name'
    .' ,email.address as email, dept.dept_name '
         .' ,ticket.status,ticket.source,ticket.isoverdue,ticket.isanswered,ticket.created ';

$qfrom=' FROM '.TICKET_TABLE.' ticket '.
       ' LEFT JOIN '.USER_TABLE.' user ON user.id = ticket.user_id'.
       ' LEFT JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id'.
       ' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id ';

if ($_REQUEST['uid'])
    $qfrom.=' LEFT JOIN '.TICKET_COLLABORATOR_TABLE.' collab
        ON (ticket.ticket_id = collab.ticket_id )';


$sjoin='';

if($search && $deep_search) {
    $sjoin.=' LEFT JOIN '.TICKET_THREAD_TABLE.' thread ON (ticket.ticket_id=thread.ticket_id )';
}

//get ticket count based on the query so far..
$total=db_count("SELECT count(DISTINCT ticket.ticket_id) $qfrom $sjoin $qwhere");
//pagenate
$pagelimit=($_GET['limit'] && is_numeric($_GET['limit']))?$_GET['limit']:PAGE_LIMIT;
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('tickets.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));

//ADD attachment,priorities, lock and other crap
$qselect.=' ,IF(ticket.duedate IS NULL,IF(sla.id IS NULL, NULL, DATE_ADD(ticket.created, INTERVAL sla.grace_period HOUR)), ticket.duedate) as duedate '
         .' ,CAST(GREATEST(IFNULL(ticket.lastmessage, 0), IFNULL(ticket.reopened, 0), ticket.created) as datetime) as effective_date '
         .' ,CONCAT_WS(" ", staff.firstname, staff.lastname) as staff, team.name as team '
         .' ,IF(staff.staff_id IS NULL,team.name,CONCAT_WS(" ", staff.lastname, staff.firstname)) as assigned '
         .' ,IF(ptopic.topic_pid IS NULL, topic.topic, CONCAT_WS(" / ", ptopic.topic, topic.topic)) as helptopic '
         .' ,cdata.priority_id, cdata.subject, pri.priority_desc, pri.priority_color';

$qfrom.=' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON (ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW()
               AND tlock.staff_id!='.db_input($thisstaff->getId()).') '
       .' LEFT JOIN '.STAFF_TABLE.' staff ON (ticket.staff_id=staff.staff_id) '
       .' LEFT JOIN '.TEAM_TABLE.' team ON (ticket.team_id=team.team_id) '
       .' LEFT JOIN '.SLA_TABLE.' sla ON (ticket.sla_id=sla.id AND sla.isactive=1) '
       .' LEFT JOIN '.TOPIC_TABLE.' topic ON (ticket.topic_id=topic.topic_id) '
       .' LEFT JOIN '.TOPIC_TABLE.' ptopic ON (ptopic.topic_id=topic.topic_pid) '
       .' LEFT JOIN '.TABLE_PREFIX.'ticket__cdata cdata ON (cdata.ticket_id = ticket.ticket_id) '
       .' LEFT JOIN '.PRIORITY_TABLE.' pri ON (pri.priority_id = cdata.priority_id)';

TicketForm::ensureDynamicDataView();

$query="$qselect $qfrom $qwhere ORDER BY $order_by $order LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
$hash = md5($query);
$_SESSION['search_'.$hash] = $query;
$res = db_query($query);
$showing=db_num_rows($res)?$pageNav->showing():"";
if(!$results_type)
    $results_type = ucfirst($status).' Tickets';

if($search)
    $results_type.= ' (Search Results)';

$negorder=$order=='DESC'?'ASC':'DESC'; //Negate the sorting..

// Fetch the results
$results = array();
while ($row = db_fetch_array($res)) {
    $results[$row['ticket_id']] = $row;
}

// Fetch attachment and thread entry counts
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

//YOU BREAK IT YOU FIX IT.
?>
<!-- SEARCH FORM START -->
<div id='basic_search'>
    <form action="tickets.php" method="get">
    <?php csrf_token(); ?>
    <input type="hidden" name="a" value="search">
    <table>
        <tr>
            <td><input type="text" id="basic-ticket-search" name="query" size=30 value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off"></td>
            <td><input type="submit" name="basic_search" class="button" value="Search"></td>
            <td>&nbsp;&nbsp;<a href="" id="go-advanced">[advanced]</a></td>
        </tr>
    </table>
    </form>
</div>
<!-- SEARCH FORM END -->
<div class="clear"></div>
<div style="margin-bottom:20px">
<form action="tickets.php" method="POST" name='tickets'>
<?php csrf_token(); ?>
 <a class="refresh" href="<?php echo $_SERVER['REQUEST_URI']; ?>">Refresh</a>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <input type="hidden" name="status" value="<?php echo Format::htmlchars($_REQUEST['status']); ?>" >
 <table class="list" border="0" cellspacing="1" cellpadding="2" width="940">
    <caption><?php echo $showing; ?>&nbsp;&nbsp;&nbsp;<?php echo $results_type; ?></caption>
    <thead>
        <tr>
            <?php if($thisstaff->canManageTickets()) { ?>
	        <th width="8px">&nbsp;</th>
            <?php } ?>
	        <th width="70">
                <a <?php echo $id_sort; ?> href="tickets.php?sort=ID&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                    title="Sort By Ticket ID <?php echo $negorder; ?>">Ticket</a></th>
	        <th width="70">
                <a  <?php echo $date_sort; ?> href="tickets.php?sort=date&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                    title="Sort By Date <?php echo $negorder; ?>">Date</a></th>
	        <th width="280">
                 <a <?php echo $subj_sort; ?> href="tickets.php?sort=subj&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                    title="Sort By Subject <?php echo $negorder; ?>">Subject</a></th>
            <th width="170">
                <a <?php echo $name_sort; ?> href="tickets.php?sort=name&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                     title="Sort By Name <?php echo $negorder; ?>">From</a></th>
            <?php
            if($search && !$status) { ?>
                <th width="60">
                    <a <?php echo $status_sort; ?> href="tickets.php?sort=status&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                        title="Sort By Status <?php echo $negorder; ?>">Status</a></th>
            <?php
            } else { ?>
                <th width="60" <?php echo $pri_sort;?>>
                    <a <?php echo $pri_sort; ?> href="tickets.php?sort=pri&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                        title="Sort By Priority <?php echo $negorder; ?>">Priority</a></th>
            <?php
            }

            if($showassigned ) {
                //Closed by
                if(!strcasecmp($status,'closed')) { ?>
                    <th width="150">
                        <a <?php echo $staff_sort; ?> href="tickets.php?sort=staff&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                            title="Sort By Closing Staff Name <?php echo $negorder; ?>">Closed By</a></th>
                <?php
                } else { //assigned to ?>
                    <th width="150">
                        <a <?php echo $assignee_sort; ?> href="tickets.php?sort=assignee&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                            title="Sort By Assignee <?php echo $negorder;?>">Assigned To</a></th>
                <?php
                }
            } else { ?>
                <th width="150">
                    <a <?php echo $dept_sort; ?> href="tickets.php?sort=dept&order=<?php echo $negorder;?><?php echo $qstr; ?>"
                        title="Sort By Department <?php echo $negorder; ?>">Department</a></th>
            <?php
            } ?>
        </tr>
     </thead>
     <tbody>
        <?php
        $class = "row1";
        $total=0;
        if($res && ($num=count($results))):
            $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
            foreach ($results as $row) {
                $tag=$row['staff_id']?'assigned':'openticket';
                $flag=null;
                if($row['lock_id'])
                    $flag='locked';
                elseif($row['isoverdue'])
                    $flag='overdue';

                $lc='';
                if($showassigned) {
                    if($row['staff_id'])
                        $lc=sprintf('<span class="Icon staffAssigned">%s</span>',Format::truncate($row['staff'],40));
                    elseif($row['team_id'])
                        $lc=sprintf('<span class="Icon teamAssigned">%s</span>',Format::truncate($row['team'],40));
                    else
                        $lc=' ';
                }else{
                    $lc=Format::truncate($row['dept_name'],40);
                }
                $tid=$row['number'];
                $subject = Format::htmlchars(Format::truncate($row['subject'],40));
                $threadcount=$row['thread_count'];
                if(!strcasecmp($row['status'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>
            <tr id="<?php echo $row['ticket_id']; ?>">
                <?php if($thisstaff->canManageTickets()) {

                    $sel=false;
                    if($ids && in_array($row['ticket_id'], $ids))
                        $sel=true;
                    ?>
                <td align="center" class="nohover">
                    <input class="ckb" type="checkbox" name="tids[]" value="<?php echo $row['ticket_id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <?php } ?>
                <td align="center" title="<?php echo $row['email']; ?>" nowrap>
                  <a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview" title="Preview Ticket"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo $tid; ?></a></td>
                <td align="center" nowrap><?php echo Format::db_datetime($row['effective_date']); ?></td>
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
                <td nowrap>&nbsp;<?php echo Format::truncate($row['name'],22,strpos($row['name'],'@')); ?>&nbsp;</td>
                <?php
                if($search && !$status){
                    $displaystatus=ucfirst($row['status']);
                    if(!strcasecmp($row['status'],'open'))
                        $displaystatus="<b>$displaystatus</b>";
                    echo "<td>$displaystatus</td>";
                } else { ?>
                <td class="nohover" align="center" style="background-color:<?php echo $row['priority_color']; ?>;">
                    <?php echo $row['priority_desc']; ?></td>
                <?php
                }
                ?>
                <td nowrap>&nbsp;<?php echo $lc; ?></td>
            </tr>
            <?php
            } //end of while.
        else: //not tickets found!! set fetch error.
            $ferror='There are no tickets here. (Leave a little early today).';
        endif; ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if($res && $num && $thisstaff->canManageTickets()){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">All</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">None</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo $ferror?Format::htmlchars($ferror):'Query returned 0 results.';
                echo '</i>';
            } ?>
        </td>
     </tr>
    </tfoot>
    </table>
    <?php
    if($num>0){ //if we actually had any tickets returned.
        echo '<div>&nbsp;Page:'.$pageNav->getPageLinks().'&nbsp;';
        echo '<a class="export-csv" href="?a=export&h='
            .$hash.'&status='.$_REQUEST['status'] .'">Export</a></div>';
    ?>
        <?php
         if($thisstaff->canManageTickets()) { ?>
           <p class="centered" id="actions">
            <?php
            $status=$_REQUEST['status']?$_REQUEST['status']:$status;
            switch (strtolower($status)) {
                case 'closed': ?>
                    <input class="button" type="submit" name="reopen" value="Reopen" >
                    <?php
                    break;
                case 'open':
                case 'answered':
                case 'assigned':
                    ?>
                    <input class="button" type="submit" name="mark_overdue" value="Overdue" >
                    <input class="button" type="submit" name="close" value="Close">
                    <?php
                    break;
                case 'overdue':
                    ?>
                    <input class="button" type="submit" name="close" value="Close">
                    <?php
                    break;
                default: //search??
                    ?>
                    <input class="button" type="submit" name="close" value="Close" >
                    <input class="button" type="submit" name="reopen" value="Reopen">
            <?php
            }
            if($thisstaff->canDeleteTickets()) { ?>
                <input class="button" type="submit" name="delete" value="Delete">
            <?php } ?>
        </p>
        <?php
       }
    } ?>
    </form>
</div>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Please Confirm</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="close-confirm">
        Are you sure want to <b>close</b> selected open tickets?
    </p>
    <p class="confirm-action" style="display:none;" id="reopen-confirm">
        Are you sure want to <b>reopen</b> selected closed tickets?
    </p>
    <p class="confirm-action" style="display:none;" id="mark_overdue-confirm">
        Are you sure want to flag the selected tickets as <font color="red"><b>overdue</b></font>?
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Are you sure you want to DELETE selected tickets?</strong></font>
        <br><br>Deleted tickets CANNOT be recovered, including any associated attachments.
    </p>
    <div>Please confirm to continue.</div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="button" value="No, Cancel" class="close">
        </span>
        <span class="buttons" style="float:right">
            <input type="button" value="Yes, Do it!" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>

<div class="dialog" style="display:none;" id="advanced-search">
    <h3>Advanced Ticket Search</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <form action="tickets.php" method="post" id="search" name="search">
        <input type="hidden" name="a" value="search">
        <fieldset class="query">
            <label for="query">Keyword:</label>
            <input type="input" id="query" name="query" size="20"> <em>Optional</em>
        </fieldset>
        <fieldset>
            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="">&mdash; Any Status &mdash;</option>
                <option value="open">Open</option>
                <?php
                if(!$cfg->showAnsweredTickets()) {?>
                <option value="answered">Answered</option>
                <?php
                } ?>
                <option value="overdue">Overdue</option>
                <option value="closed">Closed</option>
            </select>
            <label for="deptId">Dept:</label>
            <select id="deptId" name="deptId">
                <option value="">&mdash; All Departments &mdash;</option>
                <?php
                if(($mydepts = $thisstaff->getDepts()) && ($depts=Dept::getDepartments())) {
                    foreach($depts as $id =>$name) {
                        if(!in_array($id, $mydepts)) continue;
                        echo sprintf('<option value="%d">%s</option>', $id, $name);
                    }
                }
                ?>
            </select>
        </fieldset>
        <fieldset class="owner">
            <label for="assignee">Assigned To:</label>
            <select id="assignee" name="assignee">
                <option value="">&mdash; Anyone &mdash;</option>
                <option value="0">&mdash; Unassigned &mdash;</option>
                <option value="<?php echo $thisstaff->getId(); ?>">Me</option>
                <?php
                if(($users=Staff::getStaffMembers())) {
                    echo '<OPTGROUP label="Staff Members ('.count($users).')">';
                    foreach($users as $id => $name) {
                        $k="s$id";
                        echo sprintf('<option value="%s">%s</option>', $k, $name);
                    }
                    echo '</OPTGROUP>';
                }

                if(($teams=Team::getTeams())) {
                    echo '<OPTGROUP label="Teams ('.count($teams).')">';
                    foreach($teams as $id => $name) {
                        $k="t$id";
                        echo sprintf('<option value="%s">%s</option>', $k, $name);
                    }
                    echo '</OPTGROUP>';
                }
                ?>
            </select>
            <label for="staffId">Closed By:</label>
            <select id="staffId" name="staffId">
                <option value="0">&mdash; Anyone &mdash;</option>
                <option value="<?php echo $thisstaff->getId(); ?>">Me</option>
                <?php
                if(($users=Staff::getStaffMembers())) {
                    foreach($users as $id => $name)
                        echo sprintf('<option value="%d">%s</option>', $id, $name);
                }
                ?>
            </select>
        </fieldset>
        <fieldset>
            <label for="topicId">Help Topic:</label>
            <select id="topicId" name="topicId">
                <option value="" selected >&mdash; All Help Topics &mdash;</option>
                <?php
                if($topics=Topic::getHelpTopics()) {
                    foreach($topics as $id =>$name)
                        echo sprintf('<option value="%d" >%s</option>', $id, $name);
                }
                ?>
            </select>
        </fieldset>
        <fieldset class="date_range">
            <label>Date Range:</label>
            <input class="dp" type="input" size="20" name="startDate">
            <span>TO</span>
            <input class="dp" type="input" size="20" name="endDate">
        </fieldset>
        <fieldset>
        <?php
        foreach (TicketForm::getInstance()->getFields() as $f) {
            if (in_array($f->get('type'), array('text', 'memo', 'phone', 'thread')))
                continue;
            elseif (!$f->hasData())
                continue;
            ?><label><?php echo $f->getLabel(); ?>:</label>
                <div style="display:inline-block;width: 12.5em;"><?php
                     $f->render('search'); ?></div>
        <?php } ?>
        </fieldset>
        <p>
            <span class="buttons">
                <input type="submit" value="Search">
                <input type="reset" value="Reset">
                <input type="button" value="Cancel" class="close">
            </span>
            <span class="spinner">
                <img src="./images/ajax-loader.gif" width="16" height="16">
            </span>
        </p>
    </form>
    <div id="result-count">
    </div>
</div>
