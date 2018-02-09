<?php
if(!defined('OSTCLIENTINC') || !is_object($thisclient) || !$thisclient->isValid()) die('Access Denied');
$settings = &$_SESSION['client:Q'];
// Unpack search, filter, and sort requests
if (isset($_REQUEST['clear']))
    $settings = array();
if (isset($_REQUEST['keywords'])) {
    $settings['keywords'] = $_REQUEST['keywords'];
}
if (isset($_REQUEST['topic_id'])) {
    $settings['topic_id'] = $_REQUEST['topic_id'];
}
if (isset($_REQUEST['status'])) {
    $settings['status'] = $_REQUEST['status'];
}

$org_tickets = $thisclient->canSeeOrgTickets();
if ($settings['keywords']) {
    // Don't show stat counts for searches
    $openTickets = $closedTickets = -1;
}
elseif ($settings['topic_id']) {
    $openTickets = $thisclient->getNumTopicTicketsInState($settings['topic_id'],
        'open', $org_tickets);
    $closedTickets = $thisclient->getNumTopicTicketsInState($settings['topic_id'],
        'closed', $org_tickets);
}
else {
    $openTickets = $thisclient->getNumOpenTickets($org_tickets);
    $closedTickets = $thisclient->getNumClosedTickets($org_tickets);
}


$tickets = Ticket::objects();

$qs = array();
$status=null;
$sortOptions=array('id'=>'number', 'subject'=>'cdata__subject',
                    'status'=>'status__name', 'date'=>'closed','date'=>'created','topic'=>'topic__topic','assigned'=>'staff__lastname');
$orderWays=array('DESC'=>'-','ASC'=>'');
//Sorting options...
$order_by=$order=null;
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'date';
if($sort && $sortOptions[$sort])
    $order_by =$sortOptions[$sort];
$order_by=$order_by ?: $sortOptions['date'];
if ($_REQUEST['order'])
    $order = $orderWays[strtoupper($_REQUEST['order'])];
else
    $order = $orderWays['DESC'];

$x=$sort.'_sort';
$$x=' class="'.strtolower($_REQUEST['order'] ?: 'desc').'" ';
$basic_filter = Ticket::objects();
if ($settings['topic_id']) {
    $basic_filter = $basic_filter->filter(array('topic_id' => $settings['topic_id']));
}



// Add visibility constraints — use a union query to use multiple indexes,
// use UNION without "ALL" (false as second parameter to union()) to imply
// unique values
$visibility = $basic_filter->copy()
    ->values_flat('ticket_id')
    ->filter(array('user_id' => $thisclient->getId()))
    ->union($basic_filter->copy()
        ->values_flat('ticket_id')
        ->filter(array('thread__collaborators__user_id' => $thisclient->getId()))
    , false);

if ($thisclient->canSeeOrgTickets()) {
    $visibility = $visibility->union(
        $basic_filter->copy()->values_flat('ticket_id')
            ->filter(array('user__org_id' => $thisclient->getOrgId()))
    , false);
}

// Perform basic search
if ($settings['keywords']) {
    $q = trim($settings['keywords']);
    if (is_numeric($q)) {
        $tickets->filter(array('number__startswith'=>$q));
    } elseif (strlen($q) > 2) { //Deep search!
        // Use the search engine to perform the search
        $tickets = $ost->searcher->find($q, $tickets);
    }
}


if ($settings['status'])
    $status = strtolower($settings['status']);
    switch ($status) {
    default:
        $status = 'open';
    case 'open':
        $visability = array('status__state' => $status,'user_id' => $thisclient->getId());
    case 'closed':
		$results_type = ($status == 'closed') ? __('My Tickets / Closed') : __('My Tickets / Open');
        $visability = array('status__state' => $status,'user_id' => $thisclient->getId());
        break;
}

$tickets->filter($visability);

TicketForm::ensureDynamicDataView();
$total=$tickets->count();
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('tickets.php', $qs);
$pageNav->paginate($tickets);
$showing =$total ? $pageNav->showing() : "";
if(!$results_type)
{
	$results_type=ucfirst($status).' '.__('Tickets');
}
$showing.=($status)?(' '.$results_type):' '.__('All Tickets');
if($search)
    $showing=__('Search Results').": $showing";


$negorder=$order=='-'?'ASC':'DESC'; //Negate the sorting

$tickets->order_by($order.$order_by);
$tickets->values(
    'ticket_id', 'number', 'created','closed','isanswered', 'source', 'status_id',
    'status__state', 'status__name', 'cdata__subject', 'dept_id',
    'dept__name', 'dept__ispublic', 'user__default_email__address', 'staff__firstname','staff__lastname','topic__topic'
);
?>

<div class="subnav">
    <div class="float-left subnavtitle">
    
        <span ><a href="<?php echo $refresh_url; ?>" title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> </a> &nbsp;
        <?php echo $results_type;  ?>
    </div>
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">

    <a class="btn btn-icon waves-effect waves-light btn-success" id="tickets-helptopic" data-placement="bottom" data-toggle="tooltip" title="" href="open.php" data-original-title="New Ticket">Open a New Ticket</a>
               
    </div>            
    <div class="clearfix"></div>
</div>
<div class="card-box">
<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
    <div class="float-left">
			<?php if ($settings['keywords'] || $settings['topic_id'] || $_REQUEST['sort']) { ?>
			<div style="margin-left:-5px"><strong><a href="?clear" class="btn btn-sm btn-warning"> <?php echo __('Clear Search'); ?></a></strong></div>
			<?php } ?>
			</div>
		
    <div class="float-right">
	<form class="form-inline" action="tickets.php" method="get" id="ticketSearchForm">
		
			<div class="input-group input-group-sm">   
			   <input type="hidden" name="a"  value="search">
				<input class="form-control form-control-sm rlc-search basic-search" type="search" name="keywords" size="30" value="<?php echo Format::htmlchars($settings['keywords']); ?>" placeholder="Search Tickets">
				 <span class="input-group-btn">
					<input class="btn btn-info" type="submit" value="<?php echo __('Search');?>">
				 </span>
			</div>
			
		<div class="col-md-2 text-right hidden">
			<h4 style="color: #337ab7;"><?php echo __('Help Topic'); ?>:</h4>
		</div>
		<div class="col-md-4 hidden">
			<select name="topic_id" class="nowarn form-control"  onchange="javascript: this.form.submit(); ">
				<option value="">&mdash; <?php echo __('All Help Topics');?> &mdash;</option>
		<?php foreach (Topic::getHelpTopics(true) as $id=>$name) {
				$count = $thisclient->getNumTopicTickets($id);
				if ($count == 0)
					continue;
		?>
				<option value="<?php echo $id; ?>"i
					<?php if ($settings['topic_id'] == $id) echo 'selected="selected"'; ?>
					><?php echo sprintf('%s (%d)', Format::htmlchars($name),
						$thisclient->getNumTopicTickets($id)); ?></option>
		<?php } ?>
			</select>
		</div>

	</form>
    
	</div>
</div>

<div class="row subnavspacer">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
    <div class="clear"></div>
<div>
<table id="ticketqueue" class="table table-striped table-hover table-condensed table-sm ">
   
    <thead>
        <tr>
            <th >
                <a href="tickets.php?sort=ID&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Ticket ID"><?php echo __('Ticket #');?></a>
            </th>
            <th data-breakpoints="xs">
            
<?php   if ($settings['status'])
    $status = strtolower($settings['status']);
    switch ($status) {
    default:
         $whichdate = 'Created';
    case 'open':
         $whichdate  = 'Created';
         break;
    case 'closed':
		 $whichdate  = 'Closed';
            
        break;
}?>
                <a href="tickets.php?sort=date&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Date"><?php echo $whichdate;?></a>
            </th>
            <th data-breakpoints="xs">
                <a href="tickets.php?sort=status&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Status"><?php echo __('Status');?></a>
            </th>
            <th data-breakpoints="xs">
                <a href="tickets.php?sort=topic&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Subject"><?php echo __('Help Topic');?></a>
            </th>
            <th >
                <a href="tickets.php?sort=subj&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Subject"><?php echo __('Subject');?></a>
            </th>
            <th data-breakpoints="xs">
                <a href="tickets.php?sort=assigned&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Assigned"><?php echo __('Assigned To');?></a>
            </th>            
        </tr>
    </thead>
    <tbody>
    <?php
     $subject_field = TicketForm::objects()->one()->getField('subject');
     $defaultDept=Dept::getDefaultDeptName(); //Default public dept.
     if ($tickets->exists(true)) {
         foreach ($tickets as $T) {
            
            $dept = $T['dept__ispublic']
                ? Dept::getLocalById($T['dept_id'], 'name', $T['dept__name'])
                : $defaultDept;
            $subject = $subject_field->display(
                $subject_field->to_php($T['cdata__subject']) ?: $T['cdata__subject']
            );
            $cstatus = TicketStatus::getLocalById($T['status_id'], 'value', $T['status__name']);
            
            switch ($cstatus){
            case 'Assigned':
                $whichbadge = 'bg-primary';
                break;
            case 'Awaiting Submitter Action':
                $whichbadge = 'bg-success';
                break;            
            case 'Unassigned':
                $whichbadge = 'bg-flatred';
                break;            
            case 'Awaiting Implementation':
                $whichbadge = 'bg-purple';
                break;       
            case 'Awaiting Agent Action':
                $whichbadge = 'bg-danger';
                break;                
            case 'Hold':
                $whichbadge = 'badge-warning';
                break; 
            case 'Closed':
                $whichbadge = 'bg-success';
                break;  
            case 'Auto-Closed':
                $whichbadge = ' bg-flatgreenalt2';
                break;                 
            }
                   
            
            if (false) // XXX: Reimplement attachment count support
                $subject.='  &nbsp;&nbsp;<span class="Icon file"></span>';
            $ticketNumber=$T['number'];
            if($T['isanswered'] && !strcasecmp($T['status__state'], 'open')) {
                $subject="$subject";
                $ticketNumber="$ticketNumber";
            }
if ($settings['status'])
    $status = strtolower($settings['status']);
    switch ($status) {
    default:
         $whichdate = $T['created'];
    case 'open':
         $whichdate = $T['created'];
         break;
    case 'closed':
		 $whichdate = $T['closed'];
            
        break;
}
           
            
            ?>
            <tr id="<?php echo $T['ticket_id']; ?>">
                <td >
                <a href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo $ticketNumber; ?></a>
                </td>
                <td ><?php echo Format::date($whichdate); ?></td>
                <td ><span class="badge label-table <?php echo $whichbadge ?>"><?php echo $cstatus; ?></span></td>
                <td ><?php echo $T['topic__topic']; ?></td>
                <td>
                    <a  href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo $subject; ?></a>
                </td>
                <td>
                    <a  href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo $T['staff__lastname'].', '.$T['staff__firstname']; ?></a>
                </td>
            </tr>
        <?php
        }
     } else {
         echo '<tr><td colspan="5">'.__('Your query did not match any records').'</td></tr>';
     }
    ?>
    </tbody>
</table>

</div>
<div class="row">
<div class="col">
    <div class="float-left">
    <nav>
    <ul class="pagination">   
        <?php
            echo $pageNav->getPageLinks();
        ?>
    </ul>
    </nav>
    </div>
    
   
    <div class="float-right">
          <span class="faded"><?php echo $pageNav->showing(); ?></span>
    </div>  
</div>
</div>
<script>

jQuery(function($){
	$('#ticketqueue').footable();
});
        
</script>
