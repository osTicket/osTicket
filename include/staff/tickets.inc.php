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
$showoverdue=$showanswered=$showassigned=false;
$staffId=0; //Nothing for now...TODO: Allow admin and manager to limit tickets to single staff level.
//show Assigned To column, if enabled. Admins and managers can overwrite system settings!
$showassigned=(($cfg->showAssignedTickets() || $thisstaff->showAssignedTickets()) && !$search);

//Get status we are actually going to use on the query...making sure it is clean!
$status=null;
switch(strtolower($_REQUEST['status'])){ //Status is overloaded
    case 'open':
        $status='open';
        break;
    case 'closed':
        $status='closed';
        $showassigned=false;
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
        if(!$search)
            $status='open';
}

$qwhere ='';
/* 
   STRICT DEPARTMENTS BASED PERMISSION!
   User can also see tickets assigned to them regardless of the ticket's dept.
*/

$depts=$thisstaff->getDepts();    
$qwhere =' WHERE ( '
        .'  ticket.staff_id='.db_input($thisstaff->getId());

if(!$thisstaff->showAssignedOnly())
    $qwhere.=' OR ticket.dept_id IN ('.($depts?implode(',',$depts):0).')';

if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
    $qwhere.=' OR ticket.team_id IN('.implode(',',array_filter($teams)).') ';

$qwhere .= ' )';

//STATUS
if($status){
    $qwhere.=' AND status='.db_input(strtolower($status));    
}

//Overloaded sub-statuses  - you've got to just have faith!
if($staffId && ($staffId==$thisstaff->getId())) { //Staff's assigned tickets.
    $results_type='Assigned Tickets';
    $qwhere.=' AND ticket.staff_id='.db_input($staffId);
    $showassigned=false; //My tickets...already assigned to the staff.
}elseif($showoverdue) { //overdue
    $qwhere.=' AND isoverdue=1 ';
}elseif($showanswered) { ////Answered
    $qwhere.=' AND isanswered=1 ';
}elseif(!$search && !$cfg->showAnsweredTickets() && !strcasecmp($status,'open')) {
    $qwhere.=' AND isanswered=0 ';
}

//******* Showing assigned tickets? (don't confuse it with show assigned To column). F'it it's confusing - just trust me! ***/
if(!($cfg->showAssignedTickets() || $thisstaff->showAssignedTickets()) && strcasecmp($status,'closed') && !$search)
    $sql.=' AND (ticket.staff_id=0 OR ticket.staff_id='.db_input($thisstaff->getId()).') ';

//Search?? Somebody...get me some coffee 
$deep_search=false;
if($search):
    $qstr.='&a='.urlencode($_REQUEST['a']);
    $qstr.='&t='.urlencode($_REQUEST['t']);

    //query
    if($searchTerm){
        $qstr.='&query='.urlencode($searchTerm);
        $queryterm=db_real_escape($searchTerm,false); //escape the term ONLY...no quotes.
        if(is_numeric($searchTerm)){
            $qwhere.=" AND ticket.ticketID LIKE '$queryterm%'";
        }elseif(strpos($searchTerm,'@') && Validator::is_email($searchTerm)){ //pulling all tricks!
            # XXX: What about searching for email addresses in the body of
            #      the thread message
            $qwhere.=" AND ticket.email='$queryterm'";
        }else{//Deep search!
            //This sucks..mass scan! search anything that moves! 
            
            $deep_search=true;
            if($_REQUEST['stype'] && $_REQUEST['stype']=='FT') { //Using full text on big fields.
                $qwhere.=" AND ( ticket.email LIKE '%$queryterm%'".
                            " OR ticket.name LIKE '%$queryterm%'".
                            " OR ticket.subject LIKE '%$queryterm%'".
                            " OR thread.title LIKE '%$queryterm%'".
                            " OR MATCH(thread.body)   AGAINST('$queryterm')".
                            ' ) ';
            }else{
                $qwhere.=" AND ( ticket.email LIKE '%$queryterm%'".
                            " OR ticket.name LIKE '%$queryterm%'".
                            " OR ticket.subject LIKE '%$queryterm%'".
                            " OR thread.body LIKE '%$queryterm%'".
                            " OR thread.title LIKE '%$queryterm%'".
                            ' ) ';
            }
        }
    }
    //department
    if($_REQUEST['deptId'] && in_array($_REQUEST['deptId'],$thisstaff->getDepts())) {
        //This is dept based search..perm taken care above..put the sucker in.
        $qwhere.=' AND ticket.dept_id='.db_input($_REQUEST['deptId']);
        $qstr.='&deptId='.urlencode($_REQUEST['deptId']);
    }
        
    //Assignee 
    if($_REQUEST['assignee'] && strcasecmp($_REQUEST['status'], 'closed'))  {
        $id=preg_replace("/[^0-9]/", "", $_REQUEST['assignee']);
        $assignee = $_REQUEST['assignee'];
        $qstr.='&assignee='.urlencode($_REQUEST['assignee']);
        $qwhere.= ' AND ( ';
                  
        if($assignee[0]=='t')
            $qwhere.='  (ticket.team_id='.db_input($id). ' AND ticket.status="open") ';
        elseif($assignee[0]=='s')
            $qwhere.='  (ticket.staff_id='.db_input($id). ' AND ticket.status="open") ';
        else
            $qwhere.='  (ticket.staff_id='.db_input($id). ' AND ticket.status="open") ';
        
                   
        if($_REQUEST['staffId'] && !$_REQUEST['status']) { //Assigned TO + Closed By
            $qwhere.= ' OR (ticket.staff_id='.db_input($_REQUEST['staffId']). ' AND ticket.status="closed") ';
            $qstr.='&staffId='.urlencode($_REQUEST['staffId']);
        }
            
        $qwhere.= ' ) ';
    } elseif($_REQUEST['staffId']) {
        $qwhere.=' AND (ticket.staff_id='.db_input($_REQUEST['staffId']).' AND ticket.status="closed") ';
        $qstr.='&staffId='.urlencode($_REQUEST['staffId']);
    }

    //dates
    $startTime  =($_REQUEST['startDate'] && (strlen($_REQUEST['startDate'])>=8))?strtotime($_REQUEST['startDate']):0;
    $endTime    =($_REQUEST['endDate'] && (strlen($_REQUEST['endDate'])>=8))?strtotime($_REQUEST['endDate']):0;
    if( ($startTime && $startTime>time()) or ($startTime>$endTime && $endTime>0)){
        $errors['err']='Entered date span is invalid. Selection ignored.';
        $startTime=$endTime=0;
    }else{
        //Have fun with dates.
        if($startTime){
            $qwhere.=' AND ticket.created>=FROM_UNIXTIME('.$startTime.')';
            $qstr.='&startDate='.urlencode($_REQUEST['startDate']);
                        
        }
        if($endTime){
            $qwhere.=' AND ticket.created<=FROM_UNIXTIME('.$endTime.')';
            $qstr.='&endDate='.urlencode($_REQUEST['endDate']);
        }
   }

endif;

$sortOptions=array('date'=>'ticket.created','ID'=>'ticketID','pri'=>'priority_urgency','name'=>'ticket.name',
                   'subj'=>'ticket.subject','status'=>'ticket.status','assignee'=>'assigned','staff'=>'staff');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');

//Sorting options...
$order_by=$order=null;
if($_REQUEST['sort'] && $sortOptions[$_REQUEST['sort']])
    $order_by =$sortOptions[$_REQUEST['sort']];

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order=$orderWays[strtoupper($_REQUEST['order'])];

if(!$order_by && $showanswered) {
    $order_by='ticket.lastresponse, ticket.created'; //No priority sorting for answered tickets.
}elseif(!$order_by && !strcasecmp($status,'closed')){
    $order_by='ticket.closed, ticket.created'; //No priority sorting for closed tickets.
}
$order_by =$order_by?$order_by:'priority_urgency, effective_date, ticket.created';
$order=$order?$order:'ASC';

if($order_by && strpos($order_by,','))
    $order_by=str_replace(','," $order,",$order_by);

$sort=$_REQUEST['sort']?strtolower($_REQUEST['sort']):'urgency'; //Urgency is not on display table.
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';

if($_GET['limit'])
    $qstr.='&limit='.urlencode($_GET['limit']);

$qselect ='SELECT DISTINCT ticket.ticket_id,lock_id,ticketID,ticket.dept_id,ticket.staff_id,ticket.team_id '
         .' ,ticket.subject,ticket.name,ticket.email,dept_name '
         .' ,ticket.status,ticket.source,isoverdue,isanswered,ticket.created,pri.* ';

$qfrom=' FROM '.TICKET_TABLE.' ticket '.
       ' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id ';

$sjoin='';
if($search && $deep_search) {
    $sjoin=' LEFT JOIN '.TICKET_THREAD_TABLE.' thread ON (ticket.ticket_id=thread.ticket_id )';
}

$qgroup=' GROUP BY ticket.ticket_id';
//get ticket count based on the query so far..
$total=db_count("SELECT count(DISTINCT ticket.ticket_id) $qfrom $sjoin $qwhere");
//pagenate
$pagelimit=($_GET['limit'] && is_numeric($_GET['limit']))?$_GET['limit']:PAGE_LIMIT;
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('tickets.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));

//ADD attachment,priorities, lock and other crap
$qselect.=' ,count(attach.attach_id) as attachments '
         .' ,count(DISTINCT thread.id) as thread_count '
         .' ,IF(ticket.reopened is NULL,IF(ticket.lastmessage is NULL,ticket.created,ticket.lastmessage),ticket.reopened) as effective_date '
         .' ,CONCAT_WS(" ", staff.firstname, staff.lastname) as staff, team.name as team '
         .' ,IF(staff.staff_id IS NULL,team.name,CONCAT_WS(" ", staff.lastname, staff.firstname)) as assigned ';

$qfrom.=' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON (ticket.priority_id=pri.priority_id) '
       .' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON (ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW() 
               AND tlock.staff_id!='.db_input($thisstaff->getId()).') '
       .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON (ticket.ticket_id=attach.ticket_id) '
       .' LEFT JOIN '.TICKET_THREAD_TABLE.' thread ON ( ticket.ticket_id=thread.ticket_id) '
       .' LEFT JOIN '.STAFF_TABLE.' staff ON (ticket.staff_id=staff.staff_id) '
       .' LEFT JOIN '.TEAM_TABLE.' team ON (ticket.team_id=team.team_id) ';

$query="$qselect $qfrom $qwhere $qgroup ORDER BY $order_by $order LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
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
<form action="tickets.php" method="POST" name='tickets' onSubmit="return checkbox_checker(this,1,0);">
<?php csrf_token(); ?>
 <a class="refresh" href="<?php echo $_SERVER['REQUEST_URI']; ?>">Refresh</a>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="status" value="<?php echo $status; ?>" >
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

            if($showassigned){ ?>
            <th width="150">
                <a <?php echo $assignee_sort; ?> href="tickets.php?sort=assignee&order=<?php echo $negorder; ?><?php echo $qstr; ?>" 
                    title="Sort By Assignee <?php echo $negorder;?>">Assigned To</a></th>
            <?php 
            } elseif(!strcasecmp($status,'closed')) { ?>
            <th width="150">
                <a <?php echo $staff_sort; ?> href="tickets.php?sort=staff&order=<?php echo $negorder; ?><?php echo $qstr; ?>" 
                    title="Sort By Closing Staff Name <?php echo $negorder; ?>">Closed By</a></th>
            <?php 
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
        if($res && ($num=db_num_rows($res))):
            while ($row = db_fetch_array($res)) {
                $tag=$row['staff_id']?'assigned':'openticket';
                $flag=null;
                if($row['lock_id'])
                    $flag='locked';
                elseif($row['isoverdue'])
                    $flag='overdue';

                $lc='';
                if($showassigned || !strcasecmp($status,'closed')) {
                    if($row['staff_id'])
                        $lc=sprintf('<span class="Icon staffAssigned">%s</span>',Format::truncate($row['staff'],40));
                    elseif($row['team_id'])
                        $lc=sprintf('<span class="Icon teamAssigned">%s</span>',Format::truncate($row['team'],40));
                    else
                        $lc=' ';
                }else{
                    $lc=Format::truncate($row['dept_name'],40);
                }
                $tid=$row['ticketID'];
                $subject = Format::truncate($row['subject'],40);
                $threadcount=$row['thread_count'];
                if(!strcasecmp($row['status'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>
            <tr id="<?php echo $row['ticket_id']; ?>">
                <?php if($thisstaff->canManageTickets()) { ?>
                <td align="center" class="nohover">
                    <input type="checkbox" name="tids[]" value="<?php echo $row['ticket_id']; ?>" onClick="highLight(this.value,this.checked);">
                </td>
                <?php } ?>
                <td align="center" title="<?php echo $row['email']; ?>" nowrap>
                  <a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview" title="Preview Ticket" 
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo $tid; ?></a></td>
                <td align="center" nowrap><?php echo Format::db_date($row['created']); ?></td>
                <td><a <?php if($flag) { ?> class="Icon <?php echo $flag; ?>Ticket" title="<?php echo ucfirst($flag); ?> Ticket" <?php } ?> 
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo $subject; ?></a>
                     &nbsp;
                     <?php echo ($threadcount>1)?" <small>($threadcount)</small>&nbsp;":''?>
                     <?php echo $row['attachments']?"<span class='Icon file'>&nbsp;</span>":''; ?>
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
            <?php if($res && $num){ ?>
            Select:&nbsp;
            <a href="#" onclick="return select_all(document.forms['tickets'],true)">All</a>&nbsp;&nbsp;
            <a href="#" onclick="return reset_all(document.forms['tickets'])">None</a>&nbsp;&nbsp;
            <a href="#" onclick="return toogle_all(document.forms['tickets'],true)">Toggle</a>&nbsp;&nbsp;
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
           <p class="centered">  
            <?php
            $status=$_REQUEST['status']?$_REQUEST['status']:$status;
            switch (strtolower($status)) {
                case 'closed': ?>
                    <input class="button" type="submit" name="reopen" value="Reopen"
                        onClick=' return confirm("Are you sure you want to reopen selected tickets?");'>
                    <?php
                    break;
                case 'open':
                case 'answered':
                case 'assigned':
                    ?>
                    <input class="button" type="submit" name="overdue" value="Overdue"
                        onClick=' return confirm("Are you sure you want to mark selected tickets overdue/stale?");'>
                    <input class="button" type="submit" name="close" value="Close"
                        onClick=' return confirm("Are you sure you want to close selected tickets?");'>
                    <?php
                    break;
                default: //search??
                    ?>
                    <input class="button" type="submit" name="close" value="Close"
                        onClick=' return confirm("Are you sure you want to close selected tickets?");'>
                    <input class="button" type="submit" name="reopen" value="Reopen"
                        onClick=' return confirm("Are you sure you want to reopen selected tickets?");'>
            <?php
            }
            if($thisstaff->canDeleteTickets()) { ?>
                <input class="button" type="submit" name="delete" value="Delete" 
                    onClick=' return confirm("Are you sure you want to DELETE selected tickets?");'>
            <?php } ?>
        </p>
        <?php
       }
    } ?>
    </form>
</div>
<div id="search_overlay"></div>
<div style="display:none;" id="advanced-search">
    <h3>Advanced Ticket Search</h3>
    <a class="close" href="">&times;</a>
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
                <option value="0">&mdash; Anyone &mdash;</option>
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
                <?php
                if(($users=Staff::getStaffMembers())) {
                    foreach($users as $id => $name)
                        echo sprintf('<option value="%d">%s</option>', $id, $name);
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
