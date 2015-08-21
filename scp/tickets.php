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

$page='';
$ticket = $user = null; //clean start.
$redirect = false;
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if($_REQUEST['id']) {
    if(!($ticket=Ticket::lookup($_REQUEST['id'])))
         $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('ticket'));
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
            if (!$role || !$role->hasPerm(TicketModel::PERM_REPLY)) {
                $errors['err'] = __('Action denied. Contact admin for access');
            }
            else {

                if(!$_POST['response'])
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

            //If no error...do the do.
            $vars = $_POST;
            $vars['cannedattachments'] = $response_form->getField('attachments')->getClean();

            if(!$errors && ($response=$ticket->postReply($vars, $errors, $_POST['emailreply']))) {
                $msg = sprintf(__('%s: Reply posted successfully'),
                        sprintf(__('Ticket #%s'),
                            sprintf('<a href="tickets.php?id=%d"><b>%s</b></a>',
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
                $redirect = 'tickets.php';

            } elseif(!$errors['err']) {
                $errors['err']=__('Unable to post the reply. Correct the errors below and try again!');
            }
            break;
        case 'postnote': /* Post Internal Note */
            $vars = $_POST;
            $attachments = $note_form->getField('attachments')->getClean();
            $vars['cannedattachments'] = array_merge(
                $vars['cannedattachments'] ?: array(), $attachments);

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

                 $redirect = 'tickets.php';
            } else {

                if(!$errors['err'])
                    $errors['err'] = __('Unable to post internal note - missing or invalid data.');

                $errors['postnote'] = __('Unable to post the note. Correct the error(s) below and try again!');
            }
            break;
        case 'edit':
        case 'update':
            if(!$ticket || !$role->hasPerm(TicketModel::PERM_EDIT))
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
                    if(!$role->hasPerm(TicketModel::PERM_EDIT)) {
                        $errors['err'] = __('Permission Denied. You are not allowed to assign/claim tickets.');
                    } elseif(!$ticket->isOpen()) {
                        $errors['err'] = __('Only open tickets can be assigned');
                    } elseif($ticket->isAssigned()) {
                        $errors['err'] = sprintf(__('Ticket is already assigned to %s'),$ticket->getAssigned());
                    } elseif ($ticket->claim()) {
                        $msg = __('Ticket is now assigned to you!');
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
                    if (!$role->hasPerm(TicketModel::PERM_EDIT)) {
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
                        !$thisstaff->hasPerm(TicketModel::PERM_CREATE, false)) {
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
$stats= $thisstaff->getTicketsStats();

// Clear advanced search upon request
if (isset($_GET['clear_filter']))
    unset($_SESSION['advsearch']);

//Navigation
$nav->setTabActive('tickets');
$open_name = _P('queue-name',
    /* This is the name of the open ticket queue */
    'Open');
if($cfg->showAnsweredTickets()) {
    $nav->addSubMenu(array('desc'=>$open_name.' ('.number_format($stats['open']+$stats['answered']).')',
                            'title'=>__('Open Tickets'),
                            'href'=>'tickets.php?status=open',
                            'iconclass'=>'Ticket'),
                        ((!$_REQUEST['status'] && !isset($_SESSION['advsearch'])) || $_REQUEST['status']=='open'));
} else {

    if ($stats) {

        $nav->addSubMenu(array('desc'=>$open_name.' ('.number_format($stats['open']).')',
                               'title'=>__('Open Tickets'),
                               'href'=>'tickets.php?status=open',
                               'iconclass'=>'Ticket'),
                            ((!$_REQUEST['status'] && !isset($_SESSION['advsearch'])) || $_REQUEST['status']=='open'));
    }

    if($stats['answered']) {
        $nav->addSubMenu(array('desc'=>__('Answered').' ('.number_format($stats['answered']).')',
                               'title'=>__('Answered Tickets'),
                               'href'=>'tickets.php?status=answered',
                               'iconclass'=>'answeredTickets'),
                            ($_REQUEST['status']=='answered'));
    }
}

if($stats['assigned']) {

    $nav->addSubMenu(array('desc'=>__('My Tickets').' ('.number_format($stats['assigned']).')',
                           'title'=>__('Assigned Tickets'),
                           'href'=>'tickets.php?status=assigned',
                           'iconclass'=>'assignedTickets'),
                        ($_REQUEST['status']=='assigned'));
}

if($stats['overdue']) {
    $nav->addSubMenu(array('desc'=>__('Overdue').' ('.number_format($stats['overdue']).')',
                           'title'=>__('Stale Tickets'),
                           'href'=>'tickets.php?status=overdue',
                           'iconclass'=>'overdueTickets'),
                        ($_REQUEST['status']=='overdue'));

    if(!$sysnotice && $stats['overdue']>10)
        $sysnotice=sprintf(__('%d overdue tickets!'),$stats['overdue']);
}

if (isset($_SESSION['advsearch'])) {
    // XXX: De-duplicate and simplify this code
    $search = SavedSearch::create();
    $form = $search->getFormFromSession('advsearch');
    $tickets = TicketModel::objects();
    $tickets = $search->mangleQuerySet($tickets, $form);
    $count = $tickets->count();
    $nav->addSubMenu(array('desc' => __('Search').' ('.number_format($count).')',
                           'title'=>__('Advanced Ticket Search'),
                           'href'=>'tickets.php?status=search',
                           'iconclass'=>'Ticket'),
                        (!$_REQUEST['status'] || $_REQUEST['status']=='search'));
}

$nav->addSubMenu(array('desc' => __('Closed'),
                       'title'=>__('Closed Tickets'),
                       'href'=>'tickets.php?status=closed',
                       'iconclass'=>'closedTickets'),
                    ($_REQUEST['status']=='closed'));

if ($thisstaff->hasPerm(TicketModel::PERM_CREATE, false)) {
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
            && $ticket->checkStaffPerm($thisstaff, TicketModel::PERM_EDIT)) {
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
	$inc = 'tickets.inc.php';
    if ($_REQUEST['a']=='open' &&
            $thisstaff->hasPerm(TicketModel::PERM_CREATE, false))
        $inc = 'ticket-open.inc.php';
    elseif($_REQUEST['a'] == 'export') {
        $ts = strftime('%Y%m%d');
        if (!($query=$_SESSION[':Q:tickets']))
            $errors['err'] = __('Query token not found');
        elseif (!Export::saveTickets($query, "tickets-$ts.csv", 'csv'))
            $errors['err'] = __('Internal error: Unable to dump query results');
    }

    //Clear active submenu on search with no status
    if($_REQUEST['a']=='search' && !$_REQUEST['status'])
        $nav->setActiveSubMenu(-1);

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
