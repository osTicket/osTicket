<?php
/*************************************************************************
    tickets.php

    Handles all tickets related actions.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require('staff.inc.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.filter.php');
require_once(INCLUDE_DIR.'class.canned.php');
require_once(INCLUDE_DIR.'class.json.php');
require_once(INCLUDE_DIR.'class.dynamic_forms.php');
require_once(INCLUDE_DIR.'class.export.php');       // For paper sizes


$ticket = $user = null; //clean start.
$redirect = false;
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if($_REQUEST['id'] || $_REQUEST['number']) {
    if($_REQUEST['id'] && !($ticket=Ticket::lookup($_REQUEST['id'])))
         $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('ticket'));
    elseif($_REQUEST['number'] && !($ticket=Ticket::lookup(['number' => $_REQUEST['number']])))
         $errors['err']=sprintf(__('%s: Unknown or invalid number.'), __('ticket'));
    elseif(!$ticket->checkStaffPerm($thisstaff)) {
        $errors['err']=__('Access denied. Contact admin if you believe this is in error');
        $ticket=null; //Clear ticket obj.
    }
}

if ($_REQUEST['uid']) {
    $user = User::lookup($_REQUEST['uid']);
}

				
if (!$ticket) {
    $queue_key = sprintf('::Q:%s', ObjectModel::OBJECT_TYPE_TICKET);
	    $queue_name = strtolower($_GET['a'] ?: $_GET['status']); //Status is overloaded
	
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
		// Get queue id from navigation
if (isset($_REQUEST['queue'])) 
					$_SESSION['queueno'] = $_REQUEST['queue'];
				
if (isset($_REQUEST['p'])) 
					$_SESSION['pageno'] = $_REQUEST['p'];
if (isset($_REQUEST['filter'])) 
					$_SESSION['qfilter'] = $_REQUEST['filter'];				
				
		$queue_id = $_SESSION['queueno'] ?: $cfg->getDefaultTicketQueueId();
		$page_num = $_SESSION['pageno'] ?: 1;
		$q_filter = $_SESSION['qfilter'] ?: null;
//$q_filter = null;		// needs to be null to reset... 

		$_SESSION['queueno'] = $queue_id;
		$_SESSION['pageno'] = $page_num;
		$_SESSION['qfilter'] = $q_filter;

		$qurl= "&queue={$queue_id}";
		$purl= "&p={$page_num}";
		$qfurl= "&undefined&filter={$q_filter}";
		}
//$queue_id = @$_REQUEST['queue'] ?: $cfg->getDefaultTicketQueueId();
if ((int) $queue_id) {
    $queue = CustomQueue::lookup($queue_id);
}
elseif (isset($_SESSION['advsearch'])
    && strpos($queue_id, 'adhoc') === 0
) {
    list(,$key) = explode(',', $queue_id, 2);
    // XXX: De-duplicate and simplify this code
    $queue = SavedSearch::create(array(
        'title' => __("Advanced Search"),
        'root' => 'T',
    ));
    // For queue=queue, use the most recent search
    if (!$key) {
        reset($_SESSION['advsearch']);
        $key = key($_SESSION['advsearch']);
    }
    $queue->config = $_SESSION['advsearch'][$key];
    // Slight hack here to make the `adhoc` queue be selected
    $_REQUEST['queue'] = 'adhoc,'.$key;
}


// Configure form for file uploads
$response_form = new SimpleForm(array(
    'attachments' => new FileUploadField(array('id'=>'attach',
        'name'=>'attach:response',
        'configuration' => array('extensions'=>'')))
));
$note_form = new SimpleForm(array(
    'attachments' => new FileUploadField(array('id'=>'attach',
        'name'=>'attach:note',
        'configuration' => array('extensions'=>'')))
));

//At this stage we know the access status. we can process the post.
if($_POST && !$errors):

    if($ticket && $ticket->getId()) {
        //More coffee please.
        $errors=array();
        $lock = $ticket->getLock(); //Ticket lock if any
        $role = $thisstaff->getRole($ticket->getDeptId());
        switch(strtolower($_POST['a'])):
        case 'reply':
            if (!$role || !$role->hasPerm(Ticket::PERM_REPLY)) {
                $errors['err'] = __('Action denied. Contact admin for access');
            }
            else {
                $vars = $_POST;
                $vars['cannedattachments'] = $response_form->getField('attachments')->getClean();
                $vars['response'] = ThreadEntryBody::clean($vars['response']);
                if(!$vars['response'])
                    $errors['response']=__('Response required');

                if ($cfg->getLockTime()) {
                    if (!$lock) {
                        $errors['err'] = __('This action requires a lock. Please try again');
                    }
                    // Use locks to avoid double replies
                    elseif ($lock->getStaffId()!=$thisstaff->getId()) {
                        $errors['err'] = __('Action Denied. Ticket is locked by someone else!');
                    }
                    // Attempt to renew the lock if possible
                    elseif (($lock->isExpired() && !$lock->renew())
                        ||($lock->getCode() != $_POST['lockCode'])
                    ) {
                        $errors['err'] = __('Your lock has expired. Please try again');
                    }
                }

                //Make sure the email is not banned
                if(!$errors['err'] && Banlist::isBanned($ticket->getEmail()))
                    $errors['err']=__('Email is in banlist. Must be removed to reply.');
            }

            if(!$errors && ($response=$ticket->postReply($vars, $errors, $_POST['emailreply']))) {
                $msg = sprintf(__('%s: Reply posted successfully'),
                        sprintf(__('Ticket #%s'),
                            sprintf('<a href="tickets.php?queue=30&id=%d"><b>%s</b></a>',
                                $ticket->getId(), $ticket->getNumber()))
                        );

                // Clear attachment list
                $response_form->setSource(array());
                $response_form->getField('attachments')->reset();

                // Remove staff's locks
                $ticket->releaseLock($thisstaff->getId());

                // Cleanup response draft for this user
                Draft::deleteForNamespace(
                    'ticket.response.' . $ticket->getId(),
                    $thisstaff->getId());

                // Go back to the ticket listing page on reply
                $ticket = null;
				    $fl= $qurl.$purl.$qfurl;	
					$redirect = "tickets.php?{$fl}";

            } elseif(!$errors['err']) {
                $errors['err']=__('Unable to post the reply. Correct the errors below and try again!');
            }
            break;
        case 'postnote': /* Post Internal Note */
            $vars = $_POST;
            $attachments = $note_form->getField('attachments')->getClean();
            $vars['cannedattachments'] = array_merge(
                $vars['cannedattachments'] ?: array(), $attachments);
            $vars['note'] = ThreadEntryBody::clean($vars['note']);

            if ($cfg->getLockTime()) {
                if (!$lock) {
                    $errors['err'] = __('This action requires a lock. Please try again');
                }
                // Use locks to avoid double replies
                elseif ($lock->getStaffId()!=$thisstaff->getId()) {
                    $errors['err'] = __('Action Denied. Ticket is locked by someone else!');
                }
                elseif ($lock->getCode() != $_POST['lockCode']) {
                    $errors['err'] = __('Your lock has expired. Please try again');
                }
            }

            $wasOpen = ($ticket->isOpen());
            if(($note=$ticket->postNote($vars, $errors, $thisstaff))) {

                $msg=__('Internal note posted successfully');
                // Clear attachment list
                $note_form->setSource(array());
                $note_form->getField('attachments')->reset();

                // Remove staff's locks
                $ticket->releaseLock($thisstaff->getId());

                if($wasOpen && $ticket->isClosed())
                    $ticket = null; //Going back to main listing.
                else
                    // Ticket is still open -- clear draft for the note
                    Draft::deleteForNamespace('ticket.note.'.$ticket->getId(),
                        $thisstaff->getId());
					$fl= $qurl.$purl.$qfurl;
				   	$redirect = "tickets.php?queue={$fl}";
                } else {

                if(!$errors['err'])
                    $errors['err'] = __('Unable to post internal note - missing or invalid data.');

                $errors['postnote'] = __('Unable to post the note. Correct the error(s) below and try again!');
            }
            break;
        case 'edit':
        case 'update':
            if(!$ticket || !$role->hasPerm(Ticket::PERM_EDIT))
                $errors['err']=__('Permission Denied. You are not allowed to edit tickets');
            elseif($ticket->update($_POST,$errors)) {
                $msg=__('Ticket updated successfully');
                $redirect = 'tickets.php?id='.$ticket->getId();
                $_REQUEST['a'] = null; //Clear edit action - going back to view.
                //Check to make sure the staff STILL has access post-update (e.g dept change).
                if(!$ticket->checkStaffPerm($thisstaff))
                    $ticket=null;
            } elseif(!$errors['err']) {
                $errors['err']=__('Unable to update the ticket. Correct the errors below and try again!');
            }
            break;
        case 'process':
            switch(strtolower($_POST['do'])):
                case 'release':
                    if(!$ticket->isAssigned() || !($assigned=$ticket->getAssigned())) {
                        $errors['err'] = __('Ticket is not assigned!');
                    } elseif($ticket->release()) {
                        $msg=sprintf(__(
                            /* 1$ is the current assignee, 2$ is the agent removing the assignment */
                            'Ticket released (unassigned) from %1$s by %2$s'),
                            $assigned, $thisstaff->getName());
                        $ticket->logActivity(__('Ticket unassigned'),$msg);
                    } else {
                        $errors['err'] = __('Problems releasing the ticket. Try again');
                    }
                    break;
                case 'claim':
                    if(!$role->hasPerm(Ticket::PERM_EDIT)) {
                        $errors['err'] = __('Permission Denied. You are not allowed to assign/claim tickets.');
                    } elseif(!$ticket->isOpen()) {
                        $errors['err'] = __('Only open tickets can be assigned');
                    } elseif($ticket->isAssigned()) {
                        $errors['err'] = sprintf(__('Ticket is already assigned to %s'),$ticket->getAssigned());
                    } elseif ($ticket->claim()) {
                        $msg = __('Ticket is now assigned to yhjchfsou!');
					} else {
                        $errors['err'] = __('Problems assigning the ticket. Try again');
                    }
                    break;
                case 'overdue':
                    $dept = $ticket->getDept();
                    if(!$dept || !$dept->isManager($thisstaff)) {
                        $errors['err']=__('Permission Denied. You are not allowed to flag tickets overdue');
                    } elseif($ticket->markOverdue()) {
                        $msg=sprintf(__('Ticket flagged as overdue by %s'),$thisstaff->getName());
                        $ticket->logActivity(__('Ticket Marked Overdue'),$msg);
                    } else {
                        $errors['err']=__('Problems marking the the ticket overdue. Try again');
                    }
                    break;
                case 'answered':
                    $dept = $ticket->getDept();
                    if(!$dept || !$dept->isManager($thisstaff)) {
                        $errors['err']=__('Permission Denied. You are not allowed to flag tickets');
                    } elseif($ticket->markAnswered()) {
                        $msg=sprintf(__('Ticket flagged as answered by %s'),$thisstaff->getName());
                        $ticket->logActivity(__('Ticket Marked Answered'),$msg);
                    } else {
                        $errors['err']=__('Problems marking the the ticket answered. Try again');
                    }
                    break;
                case 'unanswered':
                    $dept = $ticket->getDept();
                    if(!$dept || !$dept->isManager($thisstaff)) {
                        $errors['err']=__('Permission Denied. You are not allowed to flag tickets');
                    } elseif($ticket->markUnAnswered()) {
                        $msg=sprintf(__('Ticket flagged as unanswered by %s'),$thisstaff->getName());
                        $ticket->logActivity(__('Ticket Marked Unanswered'),$msg);
                    } else {
                        $errors['err']=__('Problems marking the ticket unanswered. Try again');
                    }
                    break;
                case 'banemail':
                    if (!$thisstaff->hasPerm(Email::PERM_BANLIST)) {
                        $errors['err']=__('Permission Denied. You are not allowed to ban emails');
                    } elseif(BanList::includes($ticket->getEmail())) {
                        $errors['err']=__('Email already in banlist');
                    } elseif(Banlist::add($ticket->getEmail(),$thisstaff->getName())) {
                        $msg=sprintf(__('Email %s added to banlist'),$ticket->getEmail());
                    } else {
                        $errors['err']=__('Unable to add the email to banlist');
                    }
                    break;
                case 'unbanemail':
                    if (!$thisstaff->hasPerm(Email::PERM_BANLIST)) {
                        $errors['err'] = __('Permission Denied. You are not allowed to remove emails from banlist.');
                    } elseif(Banlist::remove($ticket->getEmail())) {
                        $msg = __('Email removed from banlist');
                    } elseif(!BanList::includes($ticket->getEmail())) {
                        $warn = __('Email is not in the banlist');
                    } else {
                        $errors['err']=__('Unable to remove the email from banlist. Try again.');
                    }
                    break;
                case 'changeuser':
                    if (!$role->hasPerm(Ticket::PERM_EDIT)) {
                        $errors['err']=__('Permission Denied. You are not allowed to edit tickets');
                    } elseif (!$_POST['user_id'] || !($user=User::lookup($_POST['user_id']))) {
                        $errors['err'] = __('Unknown user selected');
                    } elseif ($ticket->changeOwner($user)) {
                        $msg = sprintf(__('Ticket ownership changed to %s'),
                            Format::htmlchars($user->getName()));
                    } else {
                        $errors['err'] = __('Unable to change ticket ownership. Try again');
                    }
                    break;
                default:
                    $errors['err']=__('You must select action to perform');
            endswitch;
            break;
        default:
            $errors['err']=__('Unknown action');
        endswitch;
    }elseif($_POST['a']) {

        switch($_POST['a']) {
            case 'open':
                $ticket=null;
                if (!$thisstaff ||
                        !$thisstaff->hasPerm(Ticket::PERM_CREATE, false)) {
                     $errors['err'] = sprintf('%s %s',
                             sprintf(__('You do not have permission %s.'),
                                 __('to create tickets')),
                             __('Contact admin for such access'));
                } else {
                    $vars = $_POST;
                    $vars['uid'] = $user? $user->getId() : 0;

                    $vars['cannedattachments'] = $response_form->getField('attachments')->getClean();

                    if(($ticket=Ticket::open($vars, $errors))) {
                        $msg=__('Ticket created successfully');
                        $_REQUEST['a']=null;
                        if (!$ticket->checkStaffPerm($thisstaff) || $ticket->isClosed())
                            $ticket=null;
                        Draft::deleteForNamespace('ticket.staff%', $thisstaff->getId());
                        // Drop files from the response attachments widget
                        $response_form->setSource(array());
                        $response_form->getField('attachments')->reset();
                        unset($_SESSION[':form-data']);
                    } elseif(!$errors['err']) {
                        $errors['err']=__('Unable to create the ticket. Correct the error(s) and try again');
                    }
                }
                break;
        }
    }
    if(!$errors)
        $thisstaff ->resetStats(); //We'll need to reflect any changes just made!
endif;

if ($redirect) {
    if ($msg)
        Messages::success($msg);
    Http::redirect($redirect);
}

/*... Quick stats ...*/
$stats = $thisstaff->getTicketsStats();

// Clear advanced search upon request
if (isset($_GET['clear_filter']))
    unset($_SESSION['advsearch']);

//Navigation
$nav->setTabActive('tickets');
$nav->addSubNavInfo('jb-overflowmenu', 'customQ_nav');

// Fetch ticket queues organized by root and sub-queues
$queues = CustomQueue::queues()
    ->filter(Q::any(array(
        'flags__hasbit' => CustomQueue::FLAG_PUBLIC,
        'staff_id' => $thisstaff->getId(),
    )))
    ->all();

// Start with all the top-level (container) queues
foreach ($queues->findAll(array('parent_id' => 0))
as $q) {
    $nav->addSubMenu(function() use ($q, $queue) {
        // A queue is selected if it is the one being displayed. It is
        // "child" selected if its ID is in the path of the one selected
        $child_selected = $queue
            && false !== strpos($queue->getPath(), "/{$q->getId()}/");
        include STAFFINC_DIR . 'templates/queue-navigation.tmpl.php';
    });
}

// Add my advanced searches
$nav->addSubMenu(function() use ($queue, $adhoc) {
    global $thisstaff;
    // A queue is selected if it is the one being displayed. It is
    // "child" selected if its ID is in the path of the one selected
    $child_selected = $queue && !$queue->isAQueue();
    $searches = SavedSearch::objects()
        ->filter(Q::any(array(
            'flags__hasbit' => SavedSearch::FLAG_PUBLIC,
            'staff_id' => $thisstaff->getId(),
        )))
        ->exclude(array(
            'flags__hasbit' => SavedSearch::FLAG_QUEUE
        ))
        ->all();

    if (isset($adhoc)) {
        // TODO: Add "Ad Hoc Search" to the personal children
    }

    include STAFFINC_DIR . 'templates/queue-savedsearches-nav.tmpl.php';
});

if ($thisstaff->hasPerm(Ticket::PERM_CREATE, false)) {
    $nav->addSubMenu(array('desc'=>__('New Ticket'),
                           'title'=> __('Open a New Ticket'),
                           'href'=>'tickets.php?a=open',
                           'iconclass'=>'newTicket',
                           'id' => 'new-ticket'),
                        ($_REQUEST['a']=='open'));
}


$ost->addExtraHeader('<script type="text/javascript" src="js/ticket.js"></script>');
$ost->addExtraHeader('<script type="text/javascript" src="js/thread.js"></script>');
$ost->addExtraHeader('<meta name="tip-namespace" content="tickets.queue" />',
    "$('#content').data('tipNamespace', 'tickets.queue');");

if($ticket) {
    $ost->setPageTitle(sprintf(__('Ticket #%s'),$ticket->getNumber()));
    $nav->setActiveSubMenu(-1);
    $inc = 'ticket-view.inc.php';
    if ($_REQUEST['a']=='edit'
            && $ticket->checkStaffPerm($thisstaff, Ticket::PERM_EDIT)) {
        $inc = 'ticket-edit.inc.php';
        if (!$forms) $forms=DynamicFormEntry::forTicket($ticket->getId());
        // Auto add new fields to the entries
        foreach ($forms as $f) {
            $f->filterFields(function($f) { return !$f->isStorable(); });
            $f->addMissingFields();
        }
    } elseif($_REQUEST['a'] == 'print' && !$ticket->pdfExport($_REQUEST['psize'], $_REQUEST['notes']))
        $errors['err'] = __('Internal error: Unable to export the ticket to PDF for print.');
} else {
    $inc = 'templates/queue-tickets.tmpl.php';
    if ($_REQUEST['a']=='open' &&
            $thisstaff->hasPerm(Ticket::PERM_CREATE, false))
        $inc = 'ticket-open.inc.php';
    elseif($_REQUEST['a'] == 'export') {
        $ts = strftime('%Y%m%d');
        if (isset($queue) && $queue) {
            // XXX: Check staff access?
            if (!($query = $queue->getBasicQuery()))
                $errors['err'] = __('Query token not found');
            elseif (!Export::saveTickets($query, "tickets-$ts.csv", 'csv'))
                $errors['err'] = __('Internal error: Unable to dump query results');
        }
    }
    elseif ($queue) {
        // XXX: Check staff access?
        $quick_filter = $_REQUEST['filter'];
        $tickets = $queue->getQuery(false, $q_filter);//$quick_filter);
    }

    //set refresh rate if the user has it configured
    if(!$_POST && !$_REQUEST['a'] && ($min=(int)$thisstaff->getRefreshRate())) {
        $js = "+function(){ var qq = setInterval(function() { if ($.refreshTicketView === undefined) return; clearInterval(qq); $.refreshTicketView({$min}*60000); }, 200); }();";
        $ost->addExtraHeader('<script type="text/javascript">'.$js.'</script>',
            $js);
    }
}

require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
print $response_form->getMedia();
require_once(STAFFINC_DIR.'footer.inc.php');
