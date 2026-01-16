<?php
// Calling convention (assumed global scope):
// $tickets - <QuerySet> with all columns and annotations necessary to
//      render the full page


// Impose visibility constraints
// ------------------------------------------------------------
//filter if limited visibility or if unlimited visibility and in a queue
$ignoreVisibility = $queue->ignoreVisibilityConstraints($thisstaff);
if (!$ignoreVisibility || //limited visibility
   ($ignoreVisibility && ($queue->isAQueue() || $queue->isASubQueue())) //unlimited visibility + not a search
)
    $tickets->filter($thisstaff->getTicketsVisibility());

// do not show children tickets unless agent is doing a search
if ($queue->isAQueue() || $queue->isASubQueue())
    $tickets->filter(Q::any(
            array('ticket_pid' => null, 'flags__hasbit' => TICKET::FLAG_LINKED)));

// Make sure the cdata materialized view is available
TicketForm::ensureDynamicDataView();

// Identify columns of output
$columns = $queue->getColumns();

// Figure out REFRESH url — which might not be accurate after posting a
// response
// Remove some variables from query string.
$qsFilter = ['id'];
if (isset($_REQUEST['a']) && ($_REQUEST['a'] !== 'search'))
    $qsFilter[] = 'a';
$refresh_url = Http::refresh_url($qsFilter);

// Establish the selected or default sorting mechanism
if (isset($_GET['sort']) && is_numeric($_GET['sort'])) {
    $sort = $_SESSION['sort'][$queue->getId()] = array(
        'col' => (int) $_GET['sort'],
        'dir' => (int) $_GET['dir'],
    );
}
elseif (isset($_GET['sort'])
    // Drop the leading `qs-`
    && (strpos($_GET['sort'], 'qs-') === 0)
    && ($sort_id = substr($_GET['sort'], 3))
    && is_numeric($sort_id)
    && ($sort = QueueSort::lookup($sort_id))
) {
    $sort = $_SESSION['sort'][$queue->getId()] = array(
        'queuesort' => $sort,
        'dir' => (int) $_GET['dir'],
    );
}
elseif (isset($_SESSION['sort'][$queue->getId()])) {
    $sort = $_SESSION['sort'][$queue->getId()];
}
elseif ($queue_sort = $queue->getDefaultSort()) {
    $sort = $_SESSION['sort'][$queue->getId()] = array(
        'queuesort' => $queue_sort,
        'dir' => (int) $_GET['dir'] ?? 0,
    );
}

// Handle current sorting preferences

$sorted = false;
foreach ($columns as $C) {
    // Sort by this column ?
    if (isset($sort['col']) && $sort['col'] == $C->id) {
        $tickets = $C->applySort($tickets, $sort['dir']);
        $sorted = true;
    }
}

// Apply queue sort if it's not already sorted by a column
if (!$sorted) {
    // Apply queue sort-dropdown selected preference
    if (isset($sort['queuesort']))
    $sort['queuesort']->applySort($tickets, $sort['dir']);
    else // otherwise sort by created DESC
        $tickets->order_by('-created');
}

// Apply pagination

$page = (isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav = new Pagenate(PHP_INT_MAX, $page, PAGE_LIMIT);
$tickets = $pageNav->paginateSimple($tickets);

if (isset($tickets->extra['tables'])) {
    // Creative twist here. Create a new query copying the query criteria, sort, limit,
    // and offset. Then join this new query to the $tickets query and clear the
    // criteria, sort, limit, and offset from the outer query.
    $criteria = clone $tickets;
    $criteria->limit(500);
    $criteria->annotations = $criteria->related = $criteria->aggregated =
        $criteria->annotations = $criteria->ordering = [];
    $tickets->constraints = $tickets->extra = [];
    $criteria->extra(array('select' => array('relevance' => 'Z1.relevance')));
    $tickets = $tickets->filter(['ticket_id__in' =>
            $criteria->values_flat('ticket_id')]);
    $tickets->order_by(new SqlCode('relevance'), QuerySet::DESC);
    # Index hint should be used on the $criteria query only
    $tickets->clearOption(QuerySet::OPT_INDEX_HINT);
}

$tickets->distinct('ticket_id');
$Q = $queue->getBasicQuery();

if ($Q->constraints) {
    if (count($Q->constraints) > 1) {
        foreach ($Q->constraints as $value) {
            if (!$value->constraints)
                $empty = true;
        }
    }
}

if (($Q->extra && isset($Q->extra['tables'])) || !$Q->constraints || $empty) {
    $skipCount = true;
    $count = '-';
}

$count = $count ?? $queue->getCount($thisstaff);
$pageNav->setTotal($count, true);
$pageNav->setURL('tickets.php', $args);
?>

<!--osta-->
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">

            <div class="pull-right flush-right page-top">            
            
				<form action="tickets.php" method="get" onsubmit="javascript:
					  $.pjax({
						url:$(this).attr('action') + '?' + $(this).serialize(),
						container:'#pjax-container',
						timeout: 2000
					  });
return false;">
					<input type="hidden" name="a" value="search">
					<input type="hidden" name="search-type" value=""/>
					<div class="attached input">
						<input type="text" class="basic-search" data-url="ajax.php/tickets/lookup" name="query"  placeholder="<?php echo __('Search Tickets'); ?>"
							size="30" value="<?php echo Format::htmlchars($_REQUEST['query'], true); ?>"
							autocomplete="off" autocorrect="off" autocapitalize="off">		
					  <button type="submit" class="attached button"><i class="icon-search"></i>
						</button>
					</div>
					<a href="#" onclick="javascript:$.dialog('ajax.php/tickets/search', 201);">
						<div class="action-button advanced-search gray-light2" data-placement="bottom" data-toggle="tooltip" title="<?php echo __('Advanced Search'); ?>">
							<div class="button-icon">
							</div>
							<div class="button-text advanced-search">
								<?php echo __('Advanced'); ?>	
								<svg style="width:20px;height:20px" viewBox="0 0 20 20">
									<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
								</svg>							
							</div>
						</div>
					</a>
				</form>            

            </div>
			
            <div class="pull-left flush-left">
                <h2><a href="<?php echo $refresh_url; ?>"
                    title="<?php echo __('Refresh'); ?>"> <?php echo
                    $queue->getName(); ?> <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="24" height="24" viewBox="0 0 24 24"><path d="M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z" /></svg></a>
                    <?php
                    if (($crit=$queue->getSupplementalCriteria()))
                        echo sprintf('<i class="icon-filter"
                                data-placement="bottom" data-toggle="tooltip"
                                title="%s"></i>&nbsp;',
                                Format::htmlchars($queue->describeCriteria($crit)));
                    ?>
                </h2>
            </div>
            <div class="configureQ">
                <i class="icon-cog"></i>
                <div class="noclick-dropdown anchor-left">
                    <ul>
                        <li>
                            <a class="no-pjax" href="#"
                              data-dialog="ajax.php/tickets/search/<?php echo
                              urlencode($queue->getId()); ?>"><i
                            class="icon-fixed-width icon-pencil"></i>
                            <?php echo __('Edit'); ?></a>
                        </li>
                        <li>
                            <a class="no-pjax" href="#"
                              data-dialog="ajax.php/tickets/search/create?pid=<?php
                              echo $queue->getId(); ?>"><i
                            class="icon-fixed-width icon-plus-sign"></i>
                            <?php echo __('Add Sub Queue'); ?></a>
                        </li>
<?php

if ($queue->id > 0 && $queue->isOwner($thisstaff)) { ?>
                        <li class="danger">
                            <a class="no-pjax confirm-action" href="#"
                                data-dialog="ajax.php/queue/<?php
                                echo $queue->id; ?>/delete"><i
                            class="icon-fixed-width icon-trash"></i>
                            <?php echo __('Delete'); ?></a>
                        </li>
<?php } ?>
                    </ul>
                </div>
            </div>

          <div class="pull-right flush-right">
            <?php
            // TODO: Respect queue root and corresponding actions
            if ($count) {
                Ticket::agentActions($thisstaff, array('status' => $status ?? null));
            }?>
<!--osta-->
<?php
require 'queue-quickfilter.tmpl.php';
if ($queue->getSortOptions())
	require 'queue-sort.tmpl.php';
?>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>

<form action="?" method="POST" name='tickets' id="tickets">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
<!--osta-->
<table class="list queue tickets font-reg" border="0" cellspacing="1" cellpadding="2" width="940">
  <thead>
    <tr>
<?php
$canManageTickets = $thisstaff->canManageTickets();
if ($canManageTickets) { ?>
<!--osta--><th style="width:12px"><a id="selectToggle" href="#ckb"></a></th>
<?php
}

foreach ($columns as $C) {
    $heading = Format::htmlchars($C->getLocalHeading());
    if ($C->isSortable()) {
        $args = $_GET;
        $dir = $sort['col'] != $C->id ?: ($sort['dir'] ? 'desc' : 'asc');
        $args['dir'] = $sort['col'] != $C->id ?: (int) !$sort['dir'];
        $args['sort'] = $C->id;
        $heading = sprintf('<a href="?%s" class="%s">%s</a>',
            Http::build_query($args), $dir, $heading);
    }
    $classname = "osta_"  . str_replace(" ", "", strtolower(  preg_replace("/[^A-Za-z0-9 ]/", "", isset( $C->ht["name"] ) ? $C->ht["name"]  : $C->ht["heading"] )));
    echo sprintf('<th width="%s" data-id="%d" class="%s">%s</th>',
        $C->getWidth(), $C->id, $classname, $heading);
}
?>
    </tr>
  </thead>
  <tbody>
<?php
foreach ($tickets as $T) {
    echo '<tr data-redirect="' . $_SERVER['REQUEST_URI'] . '" rel="#tickets/' . $T['ticket_id'] . '/field/priority/edit">';
    if ($canManageTickets) { ?>
        <!-- osta -->
        <td class="checkbox-cell"><p class="checkbox"><input type="checkbox" class="ckb" name="tids[]"
            value="<?php echo $T['ticket_id']; ?>" /><label></label></p></td>
<?php
    }
 //  echo print_r( $columns, true );
//die();
    foreach ($columns as $C) {
        list($contents, $styles) = $C->render($T);

        //osta
        $fieldname = isset( $C->ht["name"] ) ? $C->ht["name"]  : $C->ht["heading"];
	$classname = "osta_"  . str_replace(" ", "", strtolower(  preg_replace("/[^A-Za-z0-9 ]/", "", $fieldname )));
    // 2020 - DEC - 10 -- Ted one line below - Added new variable extra for inline update  ticket priority on osta_priority column
    $extra = "";
if( $fieldname  == "Priority" ) {
   
 $classname .= " osta_priority_" . str_replace(" ", "", strtolower(  preg_replace("/[^A-Za-z0-9 ]/", "", strip_tags($contents) )));
 // 2020 - DEC - 10 -- Ted added below 2 lines to inline update  ticket priority on osta_priority column
 $extra = ' data-redirect="' . $_SERVER['REQUEST_URI'] . '" rel="#tickets/' . $T['ticket_id'] . '/field/priority/edit" ';
 
 $classname = " osta-ticket-action "  . $classname; 
}
        
        if ( preg_match("/:([0-5][0-9]) ([AaPp][Mm])$/", $contents ) ) {
            $contents = substr($contents,0,-3) . "&nbsp;" . substr($contents,-2);
        }
        if ($style = $styles ? 'style="'.$styles.'"' : '') {
            // 2020 - DEC - 10 -- Ted one line below- use new variable extra for inline update  ticket priority on osta_priority column
            echo "<td $style $extra class=\"" . $classname . "\"><div $style>$contents</div></td>";
        }
        else {
             // 2020 - DEC - 10 -- Ted  1 line below - use new variable extra for inline update  ticket priority on osta_priority column
            echo "<td $extra class=\"" . $classname . "\">$contents</td>";
        }
    }
    echo '</tr>';
}
?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="<?php echo count($columns)+1; ?>">
        <?php if ($count && $canManageTickets) {
        echo __('Select');?>:&nbsp;
        <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
        <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
        <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
        <?php }else{
            echo '<i>';
            echo $ferror?Format::htmlchars($ferror):__('Query returned 0 results.');
            echo '</i>';
        } ?>
		<!--osta-->
		<div class="padding-slider-container">
		  <input type="range" min="5" max="30" value="18" class="padding-slider" id="myRange"><span id="padding-slider"></span>
		</div>		
		<div id="resize-buttons-container">
		  <a class="resize-buttons" id="text-down" href="#">A-</a>
		  <a class="resize-buttons" id="text-reset" href="#">A</a>
		  <a class="resize-buttons" id="text-up" href="#">A+</a>
		</div>
		
      </td>
    </tr>
  </tfoot>
</table>
<!-- osta -->
<script>
if ( ($("#msg_info" ).length) || ($("#msg_notice" ).length) || ($("#msg_warning" ).length) || ($("#msg_error" ).length) ) {
	$(".attached.input").addClass("move-search"); //move search when msg displayed
}
$( "th.osta_ticket a" ).text( "#" );
$( "div[style='font-weight:bold']" ).closest( "td.osta_ticket" ).addClass( "new-reply-waiting" );
$( "td.osta_ticket" ).prepend( "<div id='new-reply-icon' data-placement='top' data-toggle='tooltip' data-original-title='<?php echo __('New Reply'); ?>'><span class='dot'></span></div>" );
$( "table.list.queue.tickets a.preview" ).wrap( "<div class='ticket-num'></div>" );
$( "td.osta_lastupdated" ).wrapInner( "<div class='date-text'></div>" );
$( "td.osta_priority.osta_priority_low" ).closest( "tr" ).addClass( "priority-low osta-ticket-action " );
$( "td.osta_priority.osta_priority_normal" ).closest( "tr" ).addClass( "priority-normal osta-ticket-action " );
$( "td.osta_priority.osta_priority_high" ).closest( "tr" ).addClass( "priority-high osta-ticket-action ");
$( "td.osta_priority.osta_priority_emergency" ).closest( "tr" ).addClass( "priority-emerency osta-ticket-action ");
$( "td:not(.osta_ticket) .icon-link" ).closest( "tr" ).addClass( "osta-ticket-linked");
$( "td:not(.osta_ticket) .icon-code-link" ).closest( "tr" ).addClass( "osta-ticket-code-linked");
$( "td:not(.osta_ticket) .icon-code-fork" ).closest( "tr" ).addClass( "osta-ticket-merged");
$( ".tickets td:not(.osta_ticket) .icon-link,.tickets td:not(.osta_ticket) .icon-code-fork,.tickets td:not(.osta_ticket) .icon-code-link" ).remove();
$( ".lockedTicket" ).closest( "tr" ).addClass( "locked" );
if ($("td:contains('Query returned 0 results')").length) {
	$("tfoot td").addClass("empty");
}
jQuery( ".truncate a" ).each(function(i, value) {
   var $link = jQuery(value);
   var text = $link.text();
   if(text.length > 55) {
      $link.text(text.substring(0, 55) + "...");
   }
});
function myFunction(x) {
  if (x.matches) { // If media query matches
	$( "tbody tr" ).wrapInner( "<label class='wrapper'></label>" );
	$( ".overdueTicket" ).closest( "tr" ).addClass( "overdue" );
	$( ".overdue td.osta_ticket" ).append( "<div class='overdue-ticket' data-placement='top' data-toggle='tooltip' data-original-title='<?php echo __('Ticket is Overdue!'); ?>'>&nbsp;</div>" );
	$( ".paperclip" ).closest( "tr" ).addClass( "paperclip-icon" );
	$( ".paperclip-icon td.osta_ticket" ).append( "<div class='ticket-has-attachement' data-placement='top' data-toggle='tooltip' data-original-title='<?php echo __('Ticket Has Attachment'); ?>'>&nbsp;</div>" );
	$( ".lockedTicket" ).closest( "div" ).addClass( "locked" );
	$('div[style="font-weight:bold"] .icon-code-link').each(function() {$(this).parent().after(this);});
	$('div[style="font-weight:bold"] .icon-code-fork').each(function() {$(this).parent().after(this);});	
	$( "tr" ).each(function() {
		var $test = $(this),
			$href = $test.find( ".pull-right"),
			$target = $test.find( "td.osta_ticket" );
		$href.appendTo($target);
	});
  } else {
	$( "tbody tr > .wrapper" ).contents().unwrap();
	$( ".overdueTicket" ).closest( "tr" ).removeClass( "overdue" );
	$( "td.osta_ticket .overdue-ticket" ).remove();
	$( "td.osta_ticket .pull-right" ).remove();
	$( ".paperclip" ).closest( "tr" ).removeClass( "paperclip-icon" );
  }
}
var x = window.matchMedia( "(max-width: 760px)" )
myFunction(x) // Call listener function at run time
x.addListener(myFunction) // Attach listener function on state changes

// Padding Slider
var slider = document.getElementById("myRange");
var output = document.getElementById("padding-slider");
var thisTable = document.querySelector('table');
var thisDiv = document.querySelector('input#myRange');
output.innerHTML = slider.value;
slider.oninput = function() {
 output.innerHTML = this.value;
  var thisVal = parseInt(this.value);
  setCookie("padding-slider", thisVal,999999);
  padItems(thisVal);
}
function padItems(thisVal) { 
    if (thisVal > 21 && thisVal < 26) {
        thisTable.setAttribute('class', 'list queue tickets font-med');
    } else if (thisVal > 25 && thisVal < 30) {
        thisTable.setAttribute('class', 'list queue tickets font-lrg');
    } else if (thisVal === 30) {
        thisTable.setAttribute('class', 'list queue tickets font-x-lrg');
    } else {
        thisTable.setAttribute('class', 'list queue tickets font-reg');
    }
    if (thisVal > 4 && thisVal < 18) { 
        thisDiv.setAttribute('class', 'padding-slider left');
    } else if (thisVal > 18 && thisVal < 31) { 
        thisDiv.setAttribute('class', 'padding-slider right');  
    } else {
        thisDiv.setAttribute('class', 'padding-slider');
    }  
    var items = document.getElementsByClassName("checkbox-cell");
    for (var i=0; i < items.length; i++) {
    items[i].style.padding =  thisVal + "px 0";
    }
}
var val = getCookie("padding-slider");
if(val) { slider.value = val; padItems(val);} 

//jfontsize
  $('.list th, .list th a, .list td, .list td a:not(#resize-buttons-container a)').jfontsize({
    btnMinusClasseId: '#text-down', // Defines the class or id of the decrease button
    btnDefaultClasseId: '#text-reset', // Defines the class or id of default size button
    btnPlusClasseId: '#text-up', // Defines the class or id of the increase button
    btnMinusMaxHits: 2, // How many times the size can be decreased
    btnPlusMaxHits: 4, // How many times the size can be increased
    sizeChange: 1 // Defines the range of change in pixels
  });
</script>
	
<?php
    if ($count > 0 || $skipCount) { //if we actually had any tickets returned.
?>  <div>
      <span class="faded pull-right"><?php echo $pageNav->showing(); ?></span>
<?php
        echo __('Page').':'.$pageNav->getPageLinks().'&nbsp;';
        ?>
        <a href="#tickets/export/<?php echo $queue->getId(); ?>"
        id="queue-export" class="no-pjax export"
            ><?php echo __('Export'); ?></a>
        <i class="help-tip icon-question-sign" href="#export"></i>
    </div>
<?php
    } ?>
</form>
