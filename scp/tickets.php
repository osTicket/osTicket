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



// Fetch ticket queues organized by root and sub-queues
$queues = CustomQueue::getHierarchicalQueues($thisstaff);

$page='';
$ticket = $user = null; //clean start.
$redirect = false;
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if(isset($_REQUEST['id']) || isset($_REQUEST['number'])) {
    if($_REQUEST['id'] && !($ticket=Ticket::lookup($_REQUEST['id'])))
         $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('ticket'));
    elseif($_REQUEST['number'] && !($ticket=Ticket::lookup(array('number' => $_REQUEST['number']))))
         $errors['err']=sprintf(__('%s: Unknown or invalid number.'), __('ticket'));
     elseif(!$ticket || !$ticket->checkStaffPerm($thisstaff)) {
         $errors['err']=__('Access denied. Contact admin if you believe this is in error');
         $ticket=null; //Clear ticket obj.
     }
}

if (!$ticket) {
    // Display a ticket queue. Decide the contents
    $queue_id = null;

    // Search for user
    if (isset($_REQUEST['uid']))
        $user = User::lookup($_REQUEST['uid']);

    if (isset($_REQUEST['email']))
        $user = User::lookupByEmail($_REQUEST['email']);

    if ($user
            && $_GET['a'] !== 'open'
    ) {
        $criteria = [
            ['user__emails__address', 'equal', $user->getDefaultEmailAddress()],
            ['user_id', 'equal', $user->id],
        ];
        if ($S = $_GET['status'])
            // The actual state is tracked by the key
            $criteria[] = ['status__state', 'includes', [$S => $S]];
        $_SESSION['advsearch']['uid'] = $criteria;
        $queue_id = "adhoc,uid";
    }
    // Search for organization tickets
    elseif (isset($_GET['orgid'])
        && ($org = Organization::lookup($_GET['orgid']))
    ) {
        $criteria = [
            ['user__org__name', 'equal', $org->name],
            ['user__org_id', 'equal', $org->id],
        ];
        if ($S = $_GET['status'])
            $criteria[] = ['status__state', 'includes', [$S => $S]];
        $_SESSION['advsearch']['orgid'] = $criteria;
        $queue_id = "adhoc,orgid";
    }
    // Basic search (click on 🔍 )
    elseif (isset($_GET['a']) && $_GET['a'] === 'search'
        && ($_GET['query'])
    ) {
        $wc = mb_str_wc($_GET['query']);
        if ($wc < 4) {
            $key = substr(md5($_GET['query']), -10);
            if ($_GET['search-type'] == 'typeahead'
                    || Validator::is_emailish($_GET['query'])) {
                // Use a faster index
                $criteria = [
                    'user__emails__address',
                    Validator::is_valid_email($_GET['query']) ? 'equal' : 'contains',
                    $_GET['query']
                ];
            } elseif (Validator::is_numeric($_GET['query'])) {
                $criteria = ['number', 'contains', $_GET['query']];
            } else {
                $criteria = [':keywords', null, $_GET['query']];
            }
            $_SESSION['advsearch'][$key] = [$criteria];
            $queue_id = "adhoc,{$key}";
        } else {
            $errors['err'] = sprintf(
                    __('Search term cannot have more than %d keywords'), 4);
        }
    }

    $queue_key = sprintf('::Q:%s', ObjectModel::OBJECT_TYPE_TICKET);
    $queue_id = $queue_id ?: @$_GET['queue'] ?: $_SESSION[$queue_key]
        ?? $thisstaff->getDefaultTicketQueueId() ?: $cfg->getDefaultTicketQueueId();

    // Recover advanced search, if requested
    if (isset($_SESSION['advsearch'])
        && strpos($queue_id, 'adhoc') === 0
    ) {
        list(,$key) = explode(',', $queue_id, 2);
        // For queue=queue, use the most recent search
        if (!$key) {
            reset($_SESSION['advsearch']);
            $key = key($_SESSION['advsearch']);
        }

        $queue = AdhocSearch::load($key);
    }

    if ((int) $queue_id && !isset($queue))
        $queue = SavedQueue::lookup($queue_id);

    if (!$queue && ($qid=$cfg->getDefaultTicketQueueId()))
        $queue = SavedQueue::lookup($qid);

    if (!$queue && $queues)
        list($queue,) = $queues[0];

    if ($queue) {
        // Set the queue_id for navigation to turn a top-level item bold
        $_REQUEST['queue'] = $queue->getId();
        // Make the current queue sticky
         $_SESSION[$queue_key] = $queue->getId();
    }
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
        $role = $ticket->getRole($thisstaff);
        $dept = $ticket->getDept();
        $isManager = $dept->isManager($thisstaff); //Check if Agent is Manager
        switch(strtolower($_POST['a'])):
        case 'reply':
            if (!$role || !$role->hasPerm(Ticket::PERM_REPLY)) {
                $errors['err'] = __('Action denied. Contact admin for access');
            } else {
                $vars = $_POST;
                $vars['files'] = $response_form->getField('attachments')->getFiles();
                $vars['response'] = ThreadEntryBody::clean($vars['response']);
                if(!$vars['response'])
                    $errors['response']=__('Response required');

                if ($cfg->isTicketLockEnabled()) {
                    if (!$lock) {
                        $errors['err'] = sprintf('%s %s', __('This action requires a lock.'), __('Please try again!'));
                    }
                    // Use locks to avoid double replies
                    elseif ($lock->getStaffId()!=$thisstaff->getId()) {
                        $errors['err'] = __('Action Denied. Ticket is locked by someone else!');
                    }
                    // Attempt to renew the lock if possible
                    elseif (($lock->isExpired() && !$lock->renew())
                        ||($lock->getCode() != $_POST['lockCode'])
                    ) {
                        $errors['err'] = sprintf('%s %s', __('Your lock has expired.'), __('Please try again!'));
                    }
                }

                //Make sure the email is not banned
                if(!$errors['err'] && Banlist::isBanned($ticket->getEmail()))
                    $errors['err']=__('Email is in banlist. Must be removed to reply.');
            }

            $alert =  strcasecmp('none', $_POST['reply-to']);
            if (!$errors) {
                // Add new collaborators (if any)
                $_errors = array();
                if (isset($vars['ccs']) && count($vars['ccs']))
                    $ticket->addCollaborators($vars['ccs'], array(), $_errors);
                // set status of collaborators
                if ($collabs = $ticket->getCollaborators()) {
                    foreach ($collabs as $collaborator) {
                        $cid = $collaborator->getUserId();
                        // Enable collaborators if they were reselected
                        if (!$collaborator->isActive() && ($vars['ccs'] && in_array($cid, $vars['ccs'])))
                            $collaborator->setFlag(Collaborator::FLAG_ACTIVE, true);
                        // Disable collaborators if they were unchecked
                        elseif ($collaborator->isActive() && (!$vars['ccs'] || !in_array($cid, $vars['ccs'])))
                            $collaborator->setFlag(Collaborator::FLAG_ACTIVE, false);
                        $collaborator->save();
                    }
                }
            }
            if (!$errors && ($response=$ticket->postReply($vars, $errors,
                            $alert))) {
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

                if ($ticket->isClosed())
                    $ticket = null;

                $redirect = 'tickets.php';
                if ($ticket && $thisstaff->getReplyRedirect() == 'Ticket')
                    $redirect = 'tickets.php?id='.$ticket->getId();

            } elseif (!$errors['err']) {
                $errors['err']=sprintf('%s %s',
                    __('Unable to post the reply.'),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'postnote': /* Post Internal Note */
            $vars = $_POST;
            $vars['files'] = $note_form->getField('attachments')->getFiles();
            $vars['note'] = ThreadEntryBody::clean($vars['note']);

            if ($cfg->isTicketLockEnabled()) {
                if (!$lock) {
                    $errors['err'] = sprintf('%s %s', __('This action requires a lock.'), __('Please try again!'));
                }
                // Use locks to avoid double replies
                elseif ($lock->getStaffId()!=$thisstaff->getId()) {
                    $errors['err'] = __('Action Denied. Ticket is locked by someone else!');
                }
                elseif ($lock->getCode() != $_POST['lockCode']) {
                    $errors['err'] = sprintf('%s %s', __('Your lock has expired.'), __('Please try again!'));
                }
            }

            $wasOpen = ($ticket->isOpen());
            if(($note=$ticket->postNote($vars, $errors, $thisstaff))) {

                $msg = sprintf(__('%s: %s posted successfully'),
                        sprintf(__('Ticket #%s'),
                            sprintf('<a href="tickets.php?id=%d"><b>%s</b></a>',
                                $ticket->getId(), $ticket->getNumber())),
                        __('Internal note')
                        );
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
                 if ($ticket)
                     $redirect ='tickets.php?id='.$ticket->getId();

            } else {

                if(!$errors['err'])
                    $errors['err'] = __('Unable to post internal note - missing or invalid data.');

                $errors['postnote'] = sprintf('%s %s',
                    __('Unable to post the note.'),
                    __('Correct any errors below and try again.'));
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
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this ticket')),
                    __('Correct any errors below and try again.')
                );
            }
            break;
        case 'process':
            switch(strtolower($_POST['do'])):
                case 'claim':
                    if(!$role->hasPerm(Ticket::PERM_EDIT)) {
                        $errors['err'] = __('Permission Denied. You are not allowed to assign/claim tickets.');
                    } elseif(!$ticket->isOpen()) {
                        $errors['err'] = __('Only open tickets can be assigned');
                    } elseif($ticket->isAssigned()) {
                        $errors['err'] = sprintf(__('Ticket is already assigned to %s'),$ticket->getAssigned());
                    } elseif ($ticket->claim()) {
                        $msg = __('Ticket is now assigned to you!');
                    } else {
                        $errors['err'] = sprintf('%s %s', __('Problems assigning the ticket.'), __('Please try again!'));
                    }
                    break;
                case 'overdue':
                    if(!$dept || !$isManager) {
                        $errors['err']=__('Permission Denied. You are not allowed to flag tickets overdue');
                    } elseif($ticket->markOverdue()) {
                        $msg=sprintf(__('Ticket flagged as overdue by %s'),$thisstaff->getName());
                        $ticket->logActivity(__('Ticket Marked Overdue'),$msg);
                    } else {
                        $errors['err']=sprintf('%s %s', __('Problems marking the the ticket overdue.'), __('Please try again!'));
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
                        $errors['err']=sprintf('%s %s', __('Unable to remove the email from banlist.'), __('Please try again!'));
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
                        $errors['err'] = sprintf('%s %s', __('Unable to change ticket ownership.'), __('Please try again!'));
                    }
                    break;
                case 'addcc':
                    if (!$role->hasPerm(Ticket::PERM_EDIT)) {
                        $errors['err']=__('Permission Denied. You are not allowed to add collaborators');
                    } elseif (!$_POST['user_id'] || !($user=User::lookup($_POST['user_id']))) {
                        $errors['err'] = __('Unknown user selected');
                  } elseif ($c2 = $ticket->addCollaborator($user, array(), $errors)) {
                        $c2->setFlag(Collaborator::FLAG_CC, true);
                        $c2->save();
                        $msg = sprintf(__('Collaborator %s added'),
                            Format::htmlchars($user->getName()));
                    }
                    else {
                      $errors['err'] = sprintf('%s %s', __('Unable to add collaborator.'), __('Please try again!'));
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
                             sprintf(__('You do not have permission %s'),
                                 __('to create tickets')),
                             __('Contact admin for such access'));
                } else {
                    $vars = $_POST;

                    if ($vars['uid'] && !($user=User::lookup($vars['uid'])))
                        $vars['uid'] = 0;

                    $vars['files'] = $response_form->getField('attachments')->getFiles();

                    if(($ticket=Ticket::open($vars, $errors))) {
                        $msg=__('Ticket created successfully');
                        $redirect = 'tickets.php?id='.$ticket->getId();
                        $_REQUEST['a']=null;
                        if (!$ticket->checkStaffPerm($thisstaff) || $ticket->isClosed())
                            $ticket=null;
                        Draft::deleteForNamespace('ticket.staff%', $thisstaff->getId());
                        // Drop files from the response attachments widget
                        $response_form->setSource(array());
                        $response_form->getField('attachments')->reset();
                        $_SESSION[':form-data'] = null;
                        // Regenerate Session ID
                        $thisstaff->regenerateSession();
                    } elseif(!$errors['err']) {
                        // ensure that we retain the tid if ticket is created from thread
                        if ($_SESSION[':form-data']['ticketId'] || $_SESSION[':form-data']['taskId'])
                            $_GET['tid'] = $_SESSION[':form-data']['ticketId'] ?: $_SESSION[':form-data']['taskId'];

                        $errors['err']=sprintf('%s %s',
                            __('Unable to create the ticket.'),
                            __('Correct any errors below and try again.'));
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

// Clear advanced search upon request
if (isset($_GET['clear_filter']))
    unset($_SESSION['advsearch']);

//Navigation
$nav->setTabActive('tickets');
$nav->addSubNavInfo('jb-overflowmenu', 'customQ_nav');

// Start with all the top-level (container) queues
foreach ($queues as $_) {
    list($q, $children) = $_;
    if ($q->isPrivate())
        continue;
    $nav->addSubMenu(function() use ($q, $queue, $children) {
        // A queue is selected if it is the one being displayed. It is
        // "child" selected if its ID is in the path of the one selected
        $_selected = ($queue && $queue->getId() == $q->getId());
        $child_selected = $queue
            && ($queue->parent_id == $q->getId()
                || false !== strpos($queue->getPath(), "/{$q->getId()}/"));
        include STAFFINC_DIR . 'templates/queue-navigation.tmpl.php';

        return ($child_selected || $_selected);
    });
}

// Add my advanced searches
$nav->addSubMenu(function() use ($queue) {
    global $thisstaff;
    $selected = false;
    // A queue is selected if it is the one being displayed. It is
    // "child" selected if its ID is in the path of the one selected
    $child_selected = $queue instanceof SavedSearch;
    include STAFFINC_DIR . 'templates/queue-savedsearches-nav.tmpl.php';
    return ($child_selected || $selected);
});


if ($thisstaff->hasPerm(Ticket::PERM_CREATE, false)) {
    $nav->addSubMenu(array('desc'=>__('New Ticket'),
                           'title'=> __('Open a New Ticket'),
                           'href'=>'tickets.php?a=open',
                           'iconclass'=>'newTicket',
                           'id' => 'new-ticket'),
                        (isset($_REQUEST['a']) && $_REQUEST['a']=='open'));
}


$ost->addExtraHeader('<script type="text/javascript" src="js/ticket.js?0375576"></script>');
$ost->addExtraHeader('<script type="text/javascript" src="js/thread.js?0375576"></script>');
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
    } elseif($_REQUEST['a'] == 'print') {
        if (!extension_loaded('mbstring'))
            $errors['err'] = sprintf('%s %s',
                'mbstring',
                __('extension required to print ticket to PDF'));
        elseif (!$ticket->pdfExport($_REQUEST['psize'], $_REQUEST['notes'], $_REQUEST['events']))
            $errors['err'] = __('Unable to export the ticket to PDF for print.')
                .' '.__('Internal error occurred');
    } elseif ($_GET['a'] == 'zip' && !$ticket->zipExport($_REQUEST['notes'], $_REQUEST['tasks'])) {
        $errors['err'] = __('Unable to export the ticket to ZIP.')
            .' '.__('Internal error occurred');
    } elseif (PluginManager::auditPlugin() && $_REQUEST['a'] == 'export' && strtolower($_REQUEST['t']) == 'audits') {
      require_once(sprintf('phar:///%s/plugins/audit.phar/class.audit.php', INCLUDE_DIR));
      $show = AuditEntry::$show_view_audits;
      $filename = sprintf('%s-audits-%s.csv',
              $ticket->getNumber(), date('Ymd'));
      $tableInfo = AuditEntry::getTableInfo($ticket, true);
      if (!Export::audits('ticket', $filename, $tableInfo, $ticket, 'csv', $show))
          $errors['err'] = __('Unable to dump query results.')
              .' '.__('Internal error occurred');
    }
} else {
    $inc = 'templates/queue-tickets.tmpl.php';
    if ((isset($_REQUEST['a']) && $_REQUEST['a']=='open') &&
            $thisstaff->hasPerm(Ticket::PERM_CREATE, false)) {
        $inc = 'ticket-open.inc.php';
    } elseif ($queue) {
        // XXX: Check staff access?
        $quick_filter = @$_REQUEST['filter'];
        $tickets = $queue->getQuery(false, $quick_filter);
    }

    //set refresh rate if the user has it configured
    if(!$_POST && !isset($_REQUEST['a']) && ($min=(int)$thisstaff->getRefreshRate())) {
        $js = "+function(){ var qq = setInterval(function() { if ($.refreshTicketView === undefined) return; clearInterval(qq); $.refreshTicketView({$min}*60000); }, 200); }();";
        $ost->addExtraHeader('<script type="text/javascript">'.$js.'</script>',
            $js);
    }
}

require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
print $response_form->getMedia();
require_once(STAFFINC_DIR.'footer.inc.php');
