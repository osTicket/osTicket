<?php
if(!defined('OSTCLIENTINC') || !is_object($thisclient) || !$thisclient->isValid() || !$cfg->showRelatedTickets()) die('Access Denied');

$qstr='&'; //Query string collector
$status=null;
if(isset($_REQUEST['status'])) { //Query string status has nothing to do with the real status used below.
    $qstr.='status='.urlencode($_REQUEST['status']);
    //Status we are actually going to use on the query...making sure it is clean!
    switch(strtolower($_REQUEST['status'])) {
     case 'open':
     case 'closed':
        $status=strtolower($_REQUEST['status']);
        break;
     default:
        $status=''; //ignore
    }
} elseif($thisclient->getNumOpenTickets()) {
    $status='open'; //Defaulting to open
}

$sortOptions=array('id'=>'`number`', 'subject'=>'subject.value',
                    'status'=>'ticket.status', 'dept'=>'dept_name','date'=>'ticket.created');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
//Sorting options...
$order_by=$order=null;
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'date';
if($sort && $sortOptions[$sort])
    $order_by =$sortOptions[$sort];

$order_by=$order_by?$order_by:'ticket_created';
if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order=$orderWays[strtoupper($_REQUEST['order'])];

$order=$order?$order:'ASC';
if($order_by && strpos($order_by,','))
    $order_by=str_replace(','," $order,",$order_by);

$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';

$qselect='SELECT ticket.ticket_id,ticket.`number`,ticket.dept_id,isanswered, '
    .'dept.ispublic, subject.value as subject,'
    .'dept_name,ticket. status, ticket.source, ticket.created ';

$dynfields='(SELECT entry.object_id, value FROM '.FORM_ANSWER_TABLE.' ans '.
         'LEFT JOIN '.FORM_ENTRY_TABLE.' entry ON entry.id=ans.entry_id '.
         'LEFT JOIN '.FORM_FIELD_TABLE.' field ON field.id=ans.field_id '.
         'WHERE field.name = "%1$s" AND entry.object_type="T")';
$subject_sql = sprintf($dynfields, 'subject');

$qfrom='FROM '.TICKET_TABLE.' ticket '
      .' LEFT JOIN '.DEPT_TABLE.' dept ON (ticket.dept_id=dept.dept_id) '
      .' LEFT JOIN '.TICKET_COLLABORATOR_TABLE.' collab
        ON (collab.ticket_id = ticket.ticket_id
                AND collab.user_id ='.$thisclient->getId().' )'
      .' LEFT JOIN '.$subject_sql.' subject ON ticket.ticket_id = subject.object_id ';

$qwhere = sprintf(' WHERE ( ticket.user_id=%d OR collab.user_id=%d )',
            $thisclient->getId(), $thisclient->getId());

if($status){
    $qwhere.=' AND ticket.status='.db_input($status);
}

$search=($_REQUEST['a']=='search' && $_REQUEST['q']);
if($search) {
    $qstr.='&a='.urlencode($_REQUEST['a']).'&q='.urlencode($_REQUEST['q']);
    if(is_numeric($_REQUEST['q'])) {
        $qwhere.=" AND ticket.`number` LIKE '$queryterm%'";
    } else {//Deep search!
        $queryterm=db_real_escape($_REQUEST['q'],false); //escape the term ONLY...no quotes.
        $qwhere.=' AND ( '
                ." subject.value LIKE '%$queryterm%'"
                ." OR thread.body LIKE '%$queryterm%'"
                .' ) ';
        $deep_search=true;
        //Joins needed for search
        $qfrom.=' LEFT JOIN '.TICKET_THREAD_TABLE.' thread ON ('
               .'ticket.ticket_id=thread.ticket_id AND thread.thread_type IN ("M","R"))';
    }
}

$total=db_count('SELECT count(DISTINCT ticket.ticket_id) '.$qfrom.' '.$qwhere);
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('tickets.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));

//more stuff...
$qselect.=' ,count(attach_id) as attachments ';
$qfrom.=' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON  ticket.ticket_id=attach.ticket_id ';
$qgroup=' GROUP BY ticket.ticket_id';

$query="$qselect $qfrom $qwhere $qgroup ORDER BY $order_by $order LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
$res = db_query($query);
$showing=($res && db_num_rows($res))?$pageNav->showing():"";
$showing.=($status)?(' '.ucfirst($status).' Tickets'):' All Tickets';
if($search)
    $showing="Search Results: $showing";

$negorder=$order=='DESC'?'ASC':'DESC'; //Negate the sorting

?>
<h1>Tickets</h1>
<br>
<form action="tickets.php" method="get" id="ticketSearchForm">
    <input type="hidden" name="a"  value="search">
    <input type="text" name="q" size="20" value="<?php echo Format::htmlchars($_REQUEST['q']); ?>">
    <select name="status">
        <option value="">&mdash; Any Status &mdash;</option>
        <option value="open"
            <?php echo ($status=='open')?'selected="selected"':'';?>>Open (<?php echo $thisclient->getNumOpenTickets(); ?>)</option>
        <?php
        if($thisclient->getNumClosedTickets()) {
            ?>
        <option value="closed"
            <?php echo ($status=='closed')?'selected="selected"':'';?>>Closed (<?php echo $thisclient->getNumClosedTickets(); ?>)</option>
        <?php
        } ?>
    </select>
    <input type="submit" value="Go">
</form>
<a class="refresh" href="<?php echo $_SERVER['REQUEST_URI']; ?>">Refresh</a>
<table id="ticketTable" width="800" border="0" cellspacing="0" cellpadding="0">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="70" nowrap>
                <a href="tickets.php?sort=ID&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Ticket ID">Ticket #</a>
            </th>
            <th width="100">
                <a href="tickets.php?sort=date&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Date">Create Date</a>
            </th>
            <th width="80">
                <a href="tickets.php?sort=status&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Status">Status</a>
            </th>
            <th width="300">
                <a href="tickets.php?sort=subj&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Subject">Subject</a>
            </th>
            <th width="150">
                <a href="tickets.php?sort=dept&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Department">Department</a>
            </th>
            <th width="100">Phone Number</th>
        </tr>
    </thead>
    <tbody>
    <?php
     if($res && ($num=db_num_rows($res))) {
        $defaultDept=Dept::getDefaultDeptName(); //Default public dept.
        while ($row = db_fetch_array($res)) {
            $dept=$row['ispublic']?$row['dept_name']:$defaultDept;
            $subject=Format::htmlchars(Format::truncate($row['subject'],40));
            if($row['attachments'])
                $subject.='  &nbsp;&nbsp;<span class="Icon file"></span>';

            $ticketNumber=$row['number'];
            if($row['isanswered'] && !strcasecmp($row['status'],'open')) {
                $subject="<b>$subject</b>";
                $ticketNumber="<b>$ticketNumber</b>";
            }
            $phone=Format::phone($row['phone']);
            if($row['phone_ext'])
                $phone.=' '.$row['phone_ext'];
            ?>
            <tr id="<?php echo $row['ticket_id']; ?>">
                <td class="centered">
                <a class="Icon <?php echo strtolower($row['source']); ?>Ticket" title="<?php echo $row['email']; ?>"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo $ticketNumber; ?></a>
                </td>
                <td>&nbsp;<?php echo Format::db_date($row['created']); ?></td>
                <td>&nbsp;<?php echo ucfirst($row['status']); ?></td>
                <td>
                    <a href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo $subject; ?></a>
                </td>
                <td>&nbsp;<?php echo Format::truncate($dept,30); ?></td>
                <td><?php echo $phone; ?></td>
            </tr>
        <?php
        }

     } else {
         echo '<tr><td colspan="7">Your query did not match any records</td></tr>';
     }
    ?>
    </tbody>
</table>
<?php
if($res && $num>0) {
    echo '<div>&nbsp;Page:'.$pageNav->getPageLinks().'&nbsp;</div>';
}
?>
