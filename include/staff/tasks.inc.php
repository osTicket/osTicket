<?php
$tasks = Task::objects();
$date_header = $date_col = false;

// Make sure the cdata materialized view is available
TaskForm::ensureDynamicDataView();

// Figure out REFRESH url — which might not be accurate after posting a
// response
list($path,) = explode('?', $_SERVER['REQUEST_URI'], 2);
$args = array();
parse_str($_SERVER['QUERY_STRING'], $args);

// Remove commands from query
unset($args['id']);
unset($args['a']);

$refresh_url = $path . '?' . http_build_query($args);

$sort_options = array(
    'updated' =>            __('Most Recently Updated'),
    'created' =>            __('Most Recently Created'),
    'due' =>                __('Due Soon'),
    'number' =>             __('Task Number'),
    'closed' =>             __('Most Recently Closed'),
    'hot' =>                __('Longest Thread'),
	'ticketnumber' =>       __('Ticker Number'),
    'relevance' =>          __('Relevance'),
);

// Queues columns

$queue_columns = array(
        'number' => array(
            'width' => '8%',
            'heading' => __('Task'),
            ),
        'parent' => array(
            'width' => '8%',
            'heading' => __('Parent Ticket'),
            ),
        'date' => array(
            'width' => '20%',
            'heading' => __('Date Created'),
            'sort_col' => 'created',
            ),
        'title' => array(
            'width' => '38%',
            'heading' => __('Title'),
            'sort_col' => 'cdata__title',
            ),
        'dept' => array(
            'width' => '16%',
            'heading' => __('Department'),
            'sort_col'  => 'dept__name',
            ),
        'assignee' => array(
            'width' => '16%',
            'heading' => __('Agent'),
            ),
        );



// Queue we're viewing
$queue_key = sprintf('::Q:%s', ObjectModel::OBJECT_TYPE_TASK);
$queue_name = $_SESSION[$queue_key] ?: '';

switch ($queue_name) {
case 'closed':
    $status='closed';
    $results_type=__('Closed Tasks');
    $showassigned=true; //closed by.
    $queue_sort_options = array('closed', 'updated', 'created', 'number','ticketnumber', 'hot');

    break;
case 'overdue':
    $status='open';
    $results_type=__('Overdue Tasks');
    $tasks->filter(array('isoverdue'=>1));
    $queue_sort_options = array('updated', 'created', 'number','ticketnumber', 'hot');
    break;
case 'assigned':
    $status='open';
    $staffId=$thisstaff->getId();
    $results_type=__('My Tasks');
    $tasks->filter(array('staff_id'=>$thisstaff->getId()));
    $queue_sort_options = array('updated', 'created', 'hot', 'number','ticketnumber');
    break;
default:
case 'search':
    $queue_sort_options = array('closed', 'updated', 'created', 'number','ticketnumber', 'hot');
    // Consider basic search
    if ($_REQUEST['query']) {
        $results_type=__('Search Results');
        $tasks = $tasks->filter(Q::any(array(
            'number__startswith' => $_REQUEST['query'],
            'cdata__title__contains' => $_REQUEST['query'],
        )));
        unset($_SESSION[$queue_key]);
        break;
    } elseif (isset($_SESSION['advsearch:tasks'])) {
        // XXX: De-duplicate and simplify this code
        $form = $search->getFormFromSession('advsearch:tasks');
        $form->loadState($_SESSION['advsearch:tasks']);
        $tasks = $search->mangleQuerySet($tasks, $form);
        $results_type=__('Advanced Search')
            . '<a class="action-button" href="?clear_filter"><i class="icon-ban-circle"></i> <em>' . __('clear') . '</em></a>';
        break;
    }
    // Fall-through and show open tickets
case 'open':
    $status='open';
    $results_type=__('Open Tasks');
    $queue_sort_options = array('created', 'updated', 'due', 'number','ticketnumber', 'hot');
    break;
}

// Apply filters
$filters = array();
if ($status) {
    $SQ = new Q(array('flags__hasbit' => TaskModel::ISOPEN));
    if (!strcasecmp($status, 'closed'))
        $SQ->negate();

    $filters[] = $SQ;
}

if ($filters)
    $tasks->filter($filters);

// Impose visibility constraints
// ------------------------------------------------------------
// -- Open and assigned to me
$visibility = array(
    new Q(array('flags__hasbit' => TaskModel::ISOPEN, 'staff_id' => $thisstaff->getId()))
);
// -- Routed to a department of mine
if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
    $visibility[] = new Q(array('dept_id__in' => $depts));
// -- Open and assigned to a team of mine
if (($teams = $thisstaff->getTeams()) && count(array_filter($teams)))
    $visibility[] = new Q(array(
        'team_id__in' => array_filter($teams),
        'flags__hasbit' => TaskModel::ISOPEN
    ));
$tasks->filter(Q::any($visibility));

// Add in annotations
$tasks->annotate(array(
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

$tasks->values('id', 'number', 'created', 'staff_id', 'team_id',
        'staff__firstname', 'staff__lastname', 'team__name',
        'dept__name', 'cdata__title', 'flags','ticket','ticket__number','ticket__source');
// Apply requested quick filter

$queue_sort_key = sprintf(':Q%s:%s:sort', ObjectModel::OBJECT_TYPE_TASK, $queue_name);

if (isset($_GET['sort'])) {
    $_SESSION[$queue_sort_key] = array($_GET['sort'], $_GET['dir']);
}
elseif (!isset($_SESSION[$queue_sort_key])) {
    $_SESSION[$queue_sort_key] = array($queue_sort_options[0], 0);
}

list($sort_cols, $sort_dir) = $_SESSION[$queue_sort_key];
$orm_dir = $sort_dir ? QuerySet::ASC : QuerySet::DESC;
$orm_dir_r = $sort_dir ? QuerySet::DESC : QuerySet::ASC;

switch ($sort_cols) {
case 'number':
    $queue_columns['number']['sort_dir'] = $sort_dir;
    $tasks->extra(array(
        'order_by'=>array(
            array(SqlExpression::times(new SqlField('number'), 1), $orm_dir)
        )
    ));
    break;
case 'due':
    $queue_columns['date']['heading'] = __('Due Date');
    $queue_columns['date']['sort'] = 'due';
    $queue_columns['date']['sort_col'] = $date_col = 'duedate';
    $tasks->values('duedate');
    $tasks->order_by(SqlFunction::COALESCE(new SqlField('duedate'), 'zzz'), $orm_dir_r);
    break;
case 'closed':
    $queue_columns['date']['heading'] = __('Date Closed');
    $queue_columns['date']['sort'] = $sort_cols;
    $queue_columns['date']['sort_col'] = $date_col = 'closed';
    $queue_columns['date']['sort_dir'] = $sort_dir;
    $tasks->values('closed');
    $tasks->order_by($sort_dir ? 'closed' : '-closed');
    break;
case 'updated':
    $queue_columns['date']['heading'] = __('Last Updated');
    $queue_columns['date']['sort'] = $sort_cols;
    $queue_columns['date']['sort_col'] = $date_col = 'updated';
    $tasks->values('updated');
    $tasks->order_by($sort_dir ? 'updated' : '-updated');
    break;
case 'hot':
    $tasks->order_by('-thread_count');
    $tasks->annotate(array(
        'thread_count' => SqlAggregate::COUNT('thread__entries'),
    ));
    break;
case 'assignee':
    $tasks->order_by('staff__lastname', $orm_dir);
    $tasks->order_by('staff__firstname', $orm_dir);
    $tasks->order_by('team__name', $orm_dir);
    $queue_columns['assignee']['sort_dir'] = $sort_dir;
    break;
default:
    if ($sort_cols && isset($queue_columns[$sort_cols])) {
        $queue_columns[$sort_cols]['sort_dir'] = $sort_dir;
        if (isset($queue_columns[$sort_cols]['sort_col']))
            $sort_cols = $queue_columns[$sort_cols]['sort_col'];
        $tasks->order_by($sort_cols, $orm_dir);
        break;
    }
case 'created':
    $queue_columns['date']['heading'] = __('Date Created');
    $queue_columns['date']['sort'] = 'created';
    $queue_columns['date']['sort_col'] = $date_col = 'created';
    $tasks->order_by($sort_dir ? 'created' : '-created');
    break;
}

if (in_array($sort_cols, array('created', 'due', 'updated')))
    $queue_columns['date']['sort_dir'] = $sort_dir;

// Apply requested pagination
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$count = $tasks->count();
$pageNav=new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('tasks.php', $args);
$tasks = $pageNav->paginate($tasks);

TaskForm::ensureDynamicDataView();

// Save the query to the session for exporting
$_SESSION[':Q:tasks'] = $tasks;

// Mass actions
$actions = array();

if ($thisstaff->hasPerm(Task::PERM_ASSIGN, false)) {
    $actions += array(
            'assign' => array(
                'icon' => 'icon-user',
                'action' => __('Assign Tasks')
            ));
}

if ($thisstaff->hasPerm(Task::PERM_TRANSFER, false)) {
    $actions += array(
            'transfer' => array(
                'icon' => 'icon-share',
                'action' => __('Transfer Tasks')
            ));
}

if ($thisstaff->hasPerm(Task::PERM_DELETE, false)) {
    $actions += array(
            'delete' => array(
                'icon' => 'icon-trash',
                'action' => __('Delete Tasks')
            ));
}


?>
<form action="tasks.php" method="POST" name='tasks' id="tasks">
<div class="subnav">

    <div class="float-left subnavtitle">
                          
     <a href="<?php echo $refresh_url; ?>"
                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i></a> Tasks / <?php echo
                $results_type.$showing; ?>                          
    
    </div>
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
    <a class="btn btn-icon waves-effect waves-light btn-success newTicket new-task" href="#tasks/add" title="Open a New Task" id="new-task" data-dialog-config="{&quot;size&quot;:&quot;large&quot;}"><i class="fa fa-plus-square" data-placement="bottom"
        data-toggle="tooltip" title="<?php echo __('New Task'); ?>"></i></a>
           <?php
           if ($count)
                echo Task::getAgentActions($thisstaff, array('status' => $status));
            ?>
      </div>   
   <div class="clearfix"></div> 
</div> 




<div class="card-box">
<div class="row">
    <div class="col">
        <div class="float-right">
            <form  class="form-inline" action="users.php" method="get" style="padding-bottom: 10px; margin-top: -5px;">
                <?php csrf_token(); ?>
                
                 <div class="input-group input-group-sm">
                 <input type="hidden" name="a" value="search">
                    <input type="text" class="form-control form-control-sm basic-search" data-url="ajax.php/tasks/lookup" name="query"
                     value="<?php echo Format::htmlchars($_REQUEST['query'], true); ?>"
                   autocomplete="off" autocorrect="off" autocapitalize="off" placeholder="Search Tasks" >
                <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
                    <button type="submit"  class="input-group-addon" ><i class="fa fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class='col-sm-12 navspacer'> 
 <table id="tasks" class="table table-striped table-hover table-condensed table-sm">

<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <input type="hidden" name="status" value="<?php echo
 Format::htmlchars($_REQUEST['status'], true); ?>" >

    <thead>
        <tr>
            <?php if ($thisstaff->canManageTickets()) { ?>
	        <th width="4%">&nbsp;</th>
            <?php } ?>
            <?php
            // Query string
            unset($args['sort'], $args['dir'], $args['_pjax']);
            $qstr = Http::build_query($args);
            // Show headers
            foreach ($queue_columns as $k => $column) {
                
                $foo = 'data-breakpoints="xs sm"'; 
                switch ($column['heading']) {
                case 'Task':
                    $foo = '';
                    break;
                case 'Title':
                    $foo = '';
                    break;    
                
                }
                
                
                echo sprintf( '<th %s><a href="?sort=%s&dir=%s&%s"
                        class="%s">%s</a></th>',
                        $foo,                        
                        $column['sort'] ?: $k,
                        $column['sort_dir'] ? 0 : 1,
                        $qstr,
                        isset($column['sort_dir'])
                        ? ($column['sort_dir'] ? 'asc': 'desc') : '',
                        $column['heading']);
            }
            ?>
        </tr>
     </thead>
     <tbody>
        <?php
        // Setup Subject field for display
        $total=0;
        $title_field = TaskForm::getInstance()->getField('title');
        $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
        foreach ($tasks as $T) {
            $T['isopen'] = ($T['flags'] & TaskModel::ISOPEN != 0); //XXX:
            $total += 1;
            $tag=$T['staff_id']?'assigned':'openticket';
            $flag=null;
            if($T['lock__staff_id'] && $T['lock__staff_id'] != $thisstaff->getId())
                $flag='locked';
            elseif($T['isoverdue'])
                $flag='overdue';

            $assignee = '';
            $dept = Dept::getLocalById($T['dept_id'], 'name', $T['dept__name']);
            $assinee ='';
            if ($T['staff_id']) {
                $staff =  new AgentsName($T['staff__firstname'].' '.$T['staff__lastname']);
                $assignee = sprintf('<span>%s</span>',
                        Format::truncate((string) $staff, 40));
            } elseif($T['team_id']) {
                $assignee = sprintf('<span class="Icon teamAssigned">%s</span>',
                    Format::truncate(Team::getLocalById($T['team_id'], 'name', $T['team__name']),40));
            }

            $threadcount=$T['thread_count'];
            $number = $T['number'];
						
            if ($T['isopen'])
                $number = sprintf('<b>%s</b>', $number);

            $title = Format::truncate($title_field->display($title_field->to_php($T['cdata__title'])), 40);
            ?>
            <tr id="<?php echo $T['id']; ?>">
                <?php
                if ($thisstaff->canManageTickets()) {
                    $sel = false;
                    if ($ids && in_array($T['id'], $ids))
                        $sel = true;
                    ?>
                <td align="center" class="nohover">
                    <input class="ckb" type="checkbox" name="tids[]"
                        value="<?php echo $T['id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <?php } ?>
                <td nowrap>
                  <a class="preview"
                    href="tasks.php?id=<?php echo $T['id']; ?>"
                    data-preview="#tasks/<?php echo $T['id']; ?>/preview"
                    ><?php echo $number; ?></a></td>
				
				<?php if(empty($T['ticket__number'])) {?>				
				<td></td>
				<?php }
				else { ?>
				<td title="<?php echo $T['user__default_email__address']; ?>" nowrap>
                  <a class="Icon <?php echo strtolower($T['ticket__source']); ?>Ticket preview"
                    title="Preview Ticket"
                    href="tickets.php?id=<?php echo $T['ticket']; ?>"
                    data-preview="#tickets/<?php echo $T['ticket']; ?>/preview"
                    ><?php echo $T['ticket__number']; ?></a></td>
	
				<?php } ?>
				<td align="left" nowrap><?php echo
                Format::datetime($T[$date_col ?: 'created']); ?></td>
                <td><a <?php if ($flag) { ?> class="Icon <?php echo $flag; ?>Ticket" title="<?php echo ucfirst($flag); ?> Ticket" <?php } ?>
                    href="tasks.php?id=<?php echo $T['id']; ?>"><?php
                    echo $title; ?></a>
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
                <td nowrap>&nbsp;<?php echo Format::truncate($dept, 40); ?></td>
                <td align="left" nowrap>&nbsp;<?php echo $assignee; ?></td>
            </tr>
            <?php
            } //end of foreach
        if (!$total)
            $ferror=__('There are no tasks matching your criteria.');
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
    </tfoot></form>
    </table>
    
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
        <div class="float-left">
        
        <div class="btn btn-icon waves-effect btn-default m-b-5"> 
               <?php
                echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                       Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                        ('<i class="ti-cloud-down faded"></i>'));
                ?>
        </div>
                <i class=" hidden help-tip icon-question-sign" href="#export"></i>
        </div>
            
           
            <div class="float-right">
                  <span class="faded"><?php echo $pageNav->showing(); ?></span>
            </div>  
    </div>
</div>

   
</div>
</div>


<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="mark_overdue-confirm">
        <?php echo __('Are you sure want to flag the selected tasks as <font color="red"><b>overdue</b></font>?');?>
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
jQuery(function($){
    $('#tasks').footable();	
});

$(function() {

    $(document).off('.new-task');
    $(document).on('click.new-task', 'a.new-task', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'
        +$(this).attr('href').substr(1)
        +'?_uid='+new Date().getTime();
        var $options = $(this).data('dialogConfig');
        $.dialog(url, [201], function (xhr) {
            var tid = parseInt(xhr.responseText);
            if (tid) {
                 window.location.href = 'tasks.php?id='+tid;
            } else {
                $.pjax.reload('#pjax-container');
            }
        }, $options);

        return false;
    });

    $('[data-toggle=tooltip]').tooltip();
});
</script>
