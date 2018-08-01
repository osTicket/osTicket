<?php
/*************************************************************************
    tasks.php

    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require('staff.inc.php');
require_once(INCLUDE_DIR.'class.task.php');
require_once(INCLUDE_DIR.'class.export.php');

$page = '';
$task = null; //clean start.
if ($_REQUEST['id']) {
    if (!($task=Task::lookup($_REQUEST['id'])))
         $errors['err'] = sprintf(__('%s: Unknown or invalid ID.'), __('task'));
    elseif (!$task->checkStaffPerm($thisstaff)) {
        $errors['err'] = __('Access denied. Contact admin if you believe this is in error');
        $task = null;
    }
}

// Configure form for file uploads
$note_attachments_form = new SimpleForm(array(
    'attachments' => new FileUploadField(array('id'=>'attach',
        'name'=>'attach:note',
        'configuration' => array('extensions'=>'')))
));

$reply_attachments_form = new SimpleForm(array(
    'attachments' => new FileUploadField(array('id'=>'attach',
        'name'=>'attach:reply',
        'configuration' => array('extensions'=>'')))
));

//At this stage we know the access status. we can process the post.
if($_POST && !$errors):

    if ($task) {
        //More coffee please.
        $errors=array();
        $role = $thisstaff->getRole($task->getDeptId());
        switch(strtolower($_POST['a'])):
        case 'postnote': /* Post Internal Note */
            $vars = $_POST;
            $attachments = $note_attachments_form->getField('attachments')->getClean();
            $vars['cannedattachments'] = array_merge(
                $vars['cannedattachments'] ?: array(), $attachments);

            $wasOpen = ($task->isOpen());
            if(($note=$task->postNote($vars, $errors, $thisstaff))) {

                $msg=__('Internal note posted successfully');
                // Clear attachment list
                $note_attachments_form->setSource(array());
                $note_attachments_form->getField('attachments')->reset();

                if($wasOpen && $task->isClosed())
                    $task = null; //Going back to main listing.
                else
                    // Task is still open -- clear draft for the note
                    Draft::deleteForNamespace('task.note.'.$task->getId(),
                        $thisstaff->getId());

            } else {
                if(!$errors['err'])
                    $errors['err'] = __('Unable to post internal note - missing or invalid data.');

                $errors['postnote'] = sprintf('%s %s',
                    __('Unable to post the note.'),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'postreply': /* Post an update */
            $vars = $_POST;
            $attachments = $reply_attachments_form->getField('attachments')->getClean();
            $vars['cannedattachments'] = array_merge(
                $vars['cannedattachments'] ?: array(), $attachments);

            $wasOpen = ($task->isOpen());
            if (($response=$task->postReply($vars, $errors))) {

                $msg=__('Reply posted successfully');
                // Clear attachment list
                $reply_attachments_form->setSource(array());
                $reply_attachments_form->getField('attachments')->reset();

                if ($wasOpen && $task->isClosed())
                    $task = null; //Going back to main listing.
                else
                    // Task is still open -- clear draft for the note
                    Draft::deleteForNamespace('task.reply.'.$task->getId(),
                        $thisstaff->getId());

            } else {
                if (!$errors['err'])
                    $errors['err'] = __('Unable to post the reply - missing or invalid data.');

                $errors['postreply'] = sprintf('%s %s',
                    __('Unable to post the reply.'),
                    __('Correct any errors below and try again.'));
            }
            break;
        default:
            $errors['err']=__('Unknown action');
        endswitch;
    }
    if(!$errors)
        $thisstaff->resetStats(); //We'll need to reflect any changes just made!
endif;

/*... Quick stats ...*/
$stats= $thisstaff->getTasksStats();

// Clear advanced search upon request
if (isset($_GET['clear_filter']))
    unset($_SESSION['advsearch:tasks']);


if (!$task) {
    $queue_key = sprintf('::Q:%s', ObjectModel::OBJECT_TYPE_TASK);
    $queue_name = strtolower($_GET['status'] ?: $_GET['a']);
    if (!$queue_name && isset($_SESSION[$queue_key]))
        $queue_name = $_SESSION[$queue_key];

    // Stash current queue view
    $_SESSION[$queue_key] = $queue_name;

    // Set queue as status
    if (@!isset($_REQUEST['advanced'])
            && @$_REQUEST['a'] != 'search'
            && !isset($_GET['status'])
            && $queue_name)
        $_GET['status'] = $_REQUEST['status'] = $queue_name;
}

//Navigation
$nav->setTabActive('tasks');
$open_name = _P('queue-name',
    /* This is the name of the open tasks queue */
    'Open');

$nav->addSubMenu(array('desc'=>$open_name.' ('.number_format($stats['open']).')',
                       'title'=>__('Open Tasks'),
                       'href'=>'tasks.php?status=open',
                       'iconclass'=>'Ticket'),
                    ((!$_REQUEST['status'] && !isset($_SESSION['advsearch:tasks'])) || $_REQUEST['status']=='open'));

if ($stats['assigned']) {

    $nav->addSubMenu(array('desc'=>__('My Tasks').' ('.number_format($stats['assigned']).')',
                           'title'=>__('Assigned Tasks'),
                           'href'=>'tasks.php?status=assigned',
                           'iconclass'=>'assignedTickets'),
                        ($_REQUEST['status']=='assigned'));
}

if ($stats['overdue']) {
    $nav->addSubMenu(array('desc'=>__('Overdue').' ('.number_format($stats['overdue']).')',
                           'title'=>__('Stale Tasks'),
                           'href'=>'tasks.php?status=overdue',
                           'iconclass'=>'overdueTickets'),
                        ($_REQUEST['status']=='overdue'));

    if(!$sysnotice && $stats['overdue']>10)
        $sysnotice=sprintf(__('%d overdue tasks!'), $stats['overdue']);
}

if ($stats['closed']) {
    $nav->addSubMenu(array('desc' => __('Completed').' ('.number_format($stats['closed']).')',
                           'title'=>__('Completed Tasks'),
                           'href'=>'tasks.php?status=closed',
                           'iconclass'=>'closedTickets'),
                        ($_REQUEST['status']=='closed'));
}

if (isset($_SESSION['advsearch:tasks'])) {
    // XXX: De-duplicate and simplify this code
    $search = SavedSearch::create();
    $form = $search->getFormFromSession('advsearch:tasks');
    $form->loadState($_SESSION['advsearch:tasks']);
    $tasks = Task::objects();
    $tasks = $search->mangleQuerySet($tasks, $form);
    $count = $tasks->count();
    $nav->addSubMenu(array('desc' => __('Search').' ('.number_format($count).')',
                           'title'=>__('Advanced Task Search'),
                           'href'=>'tasks.php?status=search',
                           'iconclass'=>'Ticket'),
                        (!$_REQUEST['status'] || $_REQUEST['status']=='search'));
}

if ($thisstaff->hasPerm(TaskModel::PERM_CREATE, false)) {
    $nav->addSubMenu(array('desc'=>__('New Task'),
                           'title'=> __('Open a New Task'),
                           'href'=>'#tasks/add',
                           'iconclass'=>'newTicket new-task',
                           'id' => 'new-task',
                           'attr' => array(
                               'data-dialog-config' => '{"size":"large"}'
                               )
                           ),
                        ($_REQUEST['a']=='open'));
}


$ost->addExtraHeader('<script type="text/javascript" src="js/ticket.js"></script>');
$ost->addExtraHeader('<script type="text/javascript" src="js/thread.js"></script>');
$ost->addExtraHeader('<meta name="tip-namespace" content="tasks.queue" />',
    "$('#content').data('tipNamespace', 'tasks.queue');");

if($task) {
    $ost->setPageTitle(sprintf(__('Task #%s'),$task->getNumber()));
    $nav->setActiveSubMenu(-1);
    $inc = 'task-view.inc.php';
    if ($_REQUEST['a']=='edit'
            && $task->checkStaffPerm($thisstaff, TaskModel::PERM_EDIT)) {
        $inc = 'task-edit.inc.php';
        if (!$forms) $forms=DynamicFormEntry::forObject($task->getId(), 'A');
        // Auto add new fields to the entries
        foreach ($forms as $f) $f->addMissingFields();
    } elseif($_REQUEST['a'] == 'print' && !$task->pdfExport($_REQUEST['psize']))
        $errors['err'] = __('Internal error: Unable to print to PDF');
} else {
	$inc = 'tasks.inc.php';
    if ($_REQUEST['a']=='open' &&
            $thisstaff->hasPerm(Task::PERM_CREATE, false))
        $inc = 'task-open.inc.php';
    elseif($_REQUEST['a'] == 'export') {
        $ts = strftime('%Y%m%d');
        if (!($query=$_SESSION[':Q:tasks']))
            $errors['err'] = __('Query token not found');
        elseif (!Export::saveTasks($query, "tasks-$ts.csv", 'csv'))
            $errors['err'] = __('Internal error: Unable to dump query results');
    }

    //Clear active submenu on search with no status
    if($_REQUEST['a']=='search' && !$_REQUEST['status'])
        $nav->setActiveSubMenu(-1);

    //set refresh rate if the user has it configured
    if(!$_POST && !$_REQUEST['a'] && ($min=$thisstaff->getRefreshRate())) {
        $js = "clearTimeout(window.task_refresh);
               window.task_refresh = setTimeout($.refreshTaskView,"
            .($min*60000).");";
        $ost->addExtraHeader('<script type="text/javascript">'.$js.'</script>',
            $js);
    }
}

require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
