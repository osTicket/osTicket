<?php
/*********************************************************************
    ajax.tickets.php

    AJAX interface for tickets

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('403');

include_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.ajax.php');
require_once(INCLUDE_DIR.'class.note.php');
include_once INCLUDE_DIR . 'class.thread_actions.php';

class TicketsAjaxAPI extends AjaxController {

    function lookup() {
        global $thisstaff;

        if(!is_numeric($_REQUEST['q']))
            return self::lookupByEmail();


        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $tickets=array();

        $visibility = Q::any(array(
            'staff_id' => $thisstaff->getId(),
            'team_id__in' => $thisstaff->teams->values_flat('team_id'),
        ));
        if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts())) {
            $visibility->add(array('dept_id__in' => $depts));
        }


        $hits = TicketModel::objects()
            ->filter(Q::any(array(
                'number__startswith' => $_REQUEST['q'],
            )))
            ->filter($visibility)
            ->values('number', 'user__emails__address')
            ->annotate(array('tickets' => SqlAggregate::COUNT('ticket_id')))
            ->order_by('-created')
            ->limit($limit);

        foreach ($hits as $T) {
            $tickets[] = array('id'=>$T['number'], 'value'=>$T['number'],
                'info'=>"{$T['number']} — {$T['user__emails__address']}",
                'matches'=>$_REQUEST['q']);
        }
        if (!$tickets)
            return self::lookupByEmail();

        return $this->json_encode($tickets);
    }

    function lookupByEmail() {
        global $thisstaff;


        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $tickets=array();

        $visibility = Q::any(array(
            'staff_id' => $thisstaff->getId(),
            'team_id__in' => $thisstaff->teams->values_flat('team_id'),
        ));
        if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts())) {
            $visibility->add(array('dept_id__in' => $depts));
        }

        $hits = TicketModel::objects()
            ->filter(Q::any(array(
                'user__emails__address__contains' => $_REQUEST['q'],
                'user__name__contains' => $_REQUEST['q'],
                'user__account__username' => $_REQUEST['q'],
                'user__org__name__contains' => $_REQUEST['q'],
            )))
            ->filter($visibility)
            ->values('user__emails__address')
            ->annotate(array('tickets' => SqlAggregate::COUNT('ticket_id')))
            ->limit($limit);

        foreach ($hits as $T) {
            $email = $T['user__emails__address'];
            $count = $T['tickets'];
            $tickets[] = array('email'=>$email, 'value'=>$email,
                'info'=>"$email ($count)", 'matches'=>$_REQUEST['q']);
        }

        return $this->json_encode($tickets);
    }

    function acquireLock($tid) {
        global $cfg,$thisstaff;

        if(!$tid || !is_numeric($tid) || !$thisstaff || !$cfg || !$cfg->getLockTime())
            return 0;

        if(!($ticket = Ticket::lookup($tid)) || !$ticket->checkStaffPerm($thisstaff))
            return $this->json_encode(array('id'=>0, 'retry'=>false, 'msg'=>__('Lock denied!')));

        //is the ticket already locked?
        if($ticket->isLocked() && ($lock=$ticket->getLock()) && !$lock->isExpired()) {
            /*Note: Ticket->acquireLock does the same logic...but we need it here since we need to know who owns the lock up front*/
            //Ticket is locked by someone else.??
            if($lock->getStaffId()!=$thisstaff->getId())
                return $this->json_encode(array('id'=>0, 'retry'=>false, 'msg'=>__('Unable to acquire lock.')));

            //Ticket already locked by staff...try renewing it.
            $lock->renew(); //New clock baby!
        } elseif(!($lock=$ticket->acquireLock($thisstaff->getId(),$cfg->getLockTime()))) {
            //unable to obtain the lock..for some really weired reason!
            //Client should watch for possible loop on retries. Max attempts?
            return $this->json_encode(array('id'=>0, 'retry'=>true));
        }

        return $this->json_encode(array(
            'id'=>$lock->getId(), 'time'=>$lock->getTime(),
            'code' => $lock->getCode()
        ));
    }

    function renewLock($tid, $id) {
        global $thisstaff;

        if(!$tid || !is_numeric($tid) || !$id || !is_numeric($id) || !$thisstaff)
            return $this->json_encode(array('id'=>0, 'retry'=>true));

        if (!($ticket = Ticket::lookup($tid)))
            return $this->json_encode(array('id'=>0, 'retry'=>true));

        $lock = $ticket->getLock();
        if(!$lock || !$lock->getStaffId() || $lock->isExpired()) //Said lock doesn't exist or is is expired
            return self::acquireLock($tid); //acquire the lock

        if($lock->getStaffId()!=$thisstaff->getId()) //user doesn't own the lock anymore??? sorry...try to next time.
            return $this->json_encode(array('id'=>0, 'retry'=>false)); //Give up...

        //Renew the lock.
        $lock->renew(); //Failure here is not an issue since the lock is not expired yet.. client need to check time!

        return $this->json_encode(array('id'=>$lock->getId(), 'time'=>$lock->getTime()));
    }

    function releaseLock($tid, $id=0) {
        global $thisstaff;

        if (!($ticket = Ticket::lookup($tid))) {
            return 0;
        }

        if ($id) {
            // Fetch the lock from the ticket
            if (!($lock = $ticket->getLock())) {
                return 1;
            }
            // Identify the lock by the ID number
            if ($lock->getId() != $id) {
                return 0;
            }
            // You have to own the lock
            if ($lock->getStaffId() != $thisstaff->getId()) {
                return 0;
            }
            // Can't be expired
            if ($lock->isExpired()) {
                return 1;
            }
            return $lock->release() ? 1 : 0;
        }

        return Lock::removeStaffLocks($thisstaff->getId(), $ticket) ? 1 : 0;
    }

    function previewTicket ($tid) {
        global $thisstaff;

        if(!$thisstaff || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, __('No such ticket'));

        include STAFFINC_DIR . 'templates/ticket-preview.tmpl.php';
    }

    function addRemoteCollaborator($tid, $bk, $id) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'No such ticket');
        elseif (!$bk || !$id)
            Http::response(422, 'Backend and user id required');
        elseif (!($backend = StaffAuthenticationBackend::getBackend($bk)))
            Http::response(404, 'User not found');

        $user_info = $backend->lookup($id);
        $form = UserForm::getUserForm()->getForm($user_info);
        $info = array();
        if (!$user_info)
            $info['error'] = __('Unable to find user in directory');

        return self::_addcollaborator($ticket, null, $form, $info);
    }

    //Collaborators utils
    function addCollaborator($tid, $uid=0) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, __('No such ticket'));


        $user = $uid? User::lookup($uid) : null;

        //If not a post then assume new collaborator form
        if(!$_POST)
            return self::_addcollaborator($ticket, $user);

        $user = $form = null;
        if (isset($_POST['id']) && $_POST['id']) { //Existing user/
            $user =  User::lookup($_POST['id']);
        } else { //We're creating a new user!
            $form = UserForm::getUserForm()->getForm($_POST);
            $user = User::fromForm($form);
        }

        $errors = $info = array();
        if ($user) {
            if ($user->getId() == $ticket->getOwnerId())
                $errors['err'] = sprintf(__('Ticket owner, %s, is a collaborator by default!'),
                        Format::htmlchars($user->getName()));
            elseif (($c=$ticket->addCollaborator($user,
                            array('isactive'=>1), $errors))) {
                $note = Format::htmlchars(sprintf(__('%s <%s> added as a collaborator'),
                            Format::htmlchars($c->getName()), $c->getEmail()));
                $ticket->logNote(__('New Collaborator Added'), $note,
                    $thisstaff, false);
                $info = array('msg' => sprintf(__('%s added as a collaborator'),
                            Format::htmlchars($c->getName())));
                return self::_collaborators($ticket, $info);
            }
        }

        if($errors && $errors['err']) {
            $info +=array('error' => $errors['err']);
        } else {
            $info +=array('error' =>__('Unable to add collaborator. Internal error'));
        }

        return self::_addcollaborator($ticket, $user, $form, $info);
    }

    function updateCollaborator($cid) {
        global $thisstaff;

        if(!($c=Collaborator::lookup($cid))
                || !($user=$c->getUser())
                || !($ticket=$c->getTicket())
                || !$ticket->checkStaffPerm($thisstaff)
                )
            Http::response(404, 'Unknown collaborator');

        $errors = array();
        if(!$user->updateInfo($_POST, $errors))
            return self::_collaborator($c ,$user->getForms($_POST), $errors);

        $info = array('msg' => sprintf('%s updated successfully',
                    Format::htmlchars($c->getName())));

        return self::_collaborators($ticket, $info);
    }

    function viewCollaborator($cid) {
        global $thisstaff;

        if(!($collaborator=Collaborator::lookup($cid))
                || !($ticket=$collaborator->getTicket())
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'Unknown collaborator');

        return self::_collaborator($collaborator);
    }

    function showCollaborators($tid) {
        global $thisstaff;

        if(!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'No such ticket');

        if($ticket->getCollaborators())
            return self::_collaborators($ticket);

        return self::_addcollaborator($ticket);
    }

    function previewCollaborators($tid) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'No such ticket');

        ob_start();
        include STAFFINC_DIR . 'templates/collaborators-preview.tmpl.php';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function _addcollaborator($ticket, $user=null, $form=null, $info=array()) {

        $info += array(
                    'title' => sprintf(__('Ticket #%s: Add a collaborator'), $ticket->getNumber()),
                    'action' => sprintf('#tickets/%d/add-collaborator', $ticket->getId()),
                    'onselect' => sprintf('ajax.php/tickets/%d/add-collaborator/', $ticket->getId()),
                    );
        return self::_userlookup($user, $form, $info);
    }


    function updateCollaborators($tid) {
        global $thisstaff;

        if(!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'No such ticket');

        $errors = $info = array();
        if ($ticket->updateCollaborators($_POST, $errors))
            Http::response(201, sprintf('Recipients (%d of %d)',
                        $ticket->getNumActiveCollaborators(),
                        $ticket->getNumCollaborators()));

        if($errors && $errors['err'])
            $info +=array('error' => $errors['err']);

        return self::_collaborators($ticket, $info);
    }



    function _collaborator($collaborator, $form=null, $info=array()) {

        $info += array('action' => '#collaborators/'.$collaborator->getId());

        $user = $collaborator->getUser();

        ob_start();
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function _collaborators($ticket, $info=array()) {

        ob_start();
        include(STAFFINC_DIR . 'templates/collaborators.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function viewUser($tid) {
        global $thisstaff;

        if(!$thisstaff
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'No such ticket');


        if(!($user = User::lookup($ticket->getOwnerId())))
            Http::response(404, 'Unknown user');


        $info = array(
            'title' => sprintf(__('Ticket #%s: %s'), $ticket->getNumber(),
                Format::htmlchars($user->getName()))
            );

        ob_start();
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

    function updateUser($tid) {

        global $thisstaff;

        if(!$thisstaff
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff)
                || !($user = User::lookup($ticket->getOwnerId())))
            Http::response(404, 'No such ticket/user');

        $errors = array();
        if($user->updateInfo($_POST, $errors, true))
             Http::response(201, $user->to_json());

        $forms = $user->getForms();

        $info = array(
            'title' => sprintf(__('Ticket #%s: %s'), $ticket->getNumber(),
                Format::htmlchars($user->getName()))
            );

        ob_start();
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;
    }

    function changeUserForm($tid) {
        global $thisstaff;

        if(!$thisstaff
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'No such ticket');


        $user = User::lookup($ticket->getOwnerId());

        $info = array(
                'title' => sprintf(__('Change user for ticket #%s'), $ticket->getNumber())
                );

        return self::_userlookup($user, null, $info);
    }

    function _userlookup($user, $form, $info) {
        global $thisstaff;

        ob_start();
        include(STAFFINC_DIR . 'templates/user-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

    function manageForms($ticket_id) {
        $forms = DynamicFormEntry::forTicket($ticket_id);
        $info = array('action' => '#tickets/'.Format::htmlchars($ticket_id).'/forms/manage');
        include(STAFFINC_DIR . 'templates/form-manage.tmpl.php');
    }

    function updateForms($ticket_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($ticket = Ticket::lookup($ticket_id)))
            Http::response(404, "No such ticket");
        elseif (!$ticket->checkStaffPerm($thisstaff))
            Http::response(403, "Access Denied");
        elseif (!isset($_POST['forms']))
            Http::response(422, "Send updated forms list");

        // Add new forms
        $forms = DynamicFormEntry::forTicket($ticket_id);
        foreach ($_POST['forms'] as $sort => $id) {
            $found = false;
            foreach ($forms as $e) {
                if ($e->get('form_id') == $id) {
                    $e->set('sort', $sort);
                    $e->save();
                    $found = true;
                    break;
                }
            }
            // New form added
            if (!$found && ($new = DynamicForm::lookup($id))) {
                $f = $new->instanciate();
                $f->set('sort', $sort);
                $f->setTicketId($ticket_id);
                $f->save();
            }
        }

        // Deleted forms
        foreach ($forms as $idx => $e) {
            if (!in_array($e->get('form_id'), $_POST['forms']))
                $e->delete();
        }

        Http::response(201, 'Successfully managed');
    }

    function cannedResponse($tid, $cid, $format='text') {
        global $thisstaff, $cfg;

        if (!($ticket = Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'Unknown ticket ID');


        if ($cid && !is_numeric($cid)) {
            if (!($response=$ticket->getThread()->getVar($cid)))
                Http::response(422, 'Unknown ticket variable');

            // Ticket thread variables are assumed to be quotes
            $response = "<br/><blockquote>{$response->asVar()}</blockquote><br/>";

            //  Return text if html thread is not enabled
            if (!$cfg->isHtmlThreadEnabled())
                $response = Format::html2text($response, 90);
            else
                $response = Format::viewableImages($response);

            // XXX: assuming json format for now.
            return Format::json_encode(array('response' => $response));
        }

        if (!$cfg->isHtmlThreadEnabled())
            $format.='.plain';

        $varReplacer = function (&$var) use($ticket) {
            return $ticket->replaceVars($var);
        };

        include_once(INCLUDE_DIR.'class.canned.php');
        if (!$cid || !($canned=Canned::lookup($cid)) || !$canned->isEnabled())
            Http::response(404, 'No such premade reply');

        return $canned->getFormattedResponse($format, $varReplacer);
    }

    function changeTicketStatus($tid, $status, $id=0) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Access denied');
        elseif (!$tid
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'Unknown ticket #');

        $role = $thisstaff->getRole($ticket->getDeptId());

        $info = array();
        $state = null;
        switch($status) {
            case 'open':
            case 'reopen':
                $state = 'open';
                break;
            case 'close':
                if (!$role->hasPerm(TicketModel::PERM_CLOSE))
                    Http::response(403, 'Access denied');
                $state = 'closed';
                break;
            case 'delete':
                if (!$role->hasPerm(TicketModel::PERM_DELETE))
                    Http::response(403, 'Access denied');
                $state = 'deleted';
                break;
            default:
                $state = $ticket->getStatus()->getState();
                $info['warn'] = sprintf('%s %s',
                        __('Unknown or invalid'), __('status'));
        }

        $info['status_id'] = $id ?: $ticket->getStatusId();

        return self::_changeTicketStatus($ticket, $state, $info);
    }

    function setTicketStatus($tid) {
        global $thisstaff, $ost;

        if (!$thisstaff)
            Http::response(403, 'Access denied');
        elseif (!$tid
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'Unknown ticket #');

        $errors = $info = array();
        if (!$_POST['status_id']
                || !($status= TicketStatus::lookup($_POST['status_id'])))
            $errors['status_id'] = sprintf('%s %s',
                    __('Unknown or invalid'), __('status'));
        elseif ($status->getId() == $ticket->getStatusId())
            $errors['err'] = sprintf(__('Ticket already set to %s status'),
                    __($status->getName()));
        elseif (($role = $thisstaff->getRole($ticket->getDeptId()))) {
            // Make sure the agent has permission to set the status
            switch(mb_strtolower($status->getState())) {
                case 'open':
                    if (!$role->hasPerm(TicketModel::PERM_CLOSE)
                            && !$role->hasPerm(TicketModel::PERM_CREATE))
                        $errors['err'] = sprintf(__('You do not have permission %s.'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!$role->hasPerm(TicketModel::PERM_CLOSE))
                        $errors['err'] = sprintf(__('You do not have permission %s.'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!$role->hasPerm(TicketModel::PERM_DELETE))
                        $errors['err'] = sprintf(__('You do not have permission %s.'),
                                __('to archive/delete tickets'));
                    break;
                default:
                    $errors['err'] = sprintf('%s %s',
                            __('Unknown or invalid'), __('status'));
            }
        } else {
            $errors['err'] = __('Access denied');
        }

        $state = strtolower($status->getState());

        if (!$errors && $ticket->setStatus($status, $_REQUEST['comments'], $errors)) {

            if ($state == 'deleted') {
                $msg = sprintf('%s %s',
                        sprintf(__('Ticket #%s'), $ticket->getNumber()),
                        __('deleted sucessfully')
                        );
            } elseif ($state != 'open') {
                 $msg = sprintf(__('%s status changed to %s'),
                         sprintf(__('Ticket #%s'), $ticket->getNumber()),
                         $status->getName());
            } else {
                $msg = sprintf(
                        __('%s status changed to %s'),
                        __('Ticket'),
                        $status->getName());
            }

            $_SESSION['::sysmsgs']['msg'] = $msg;

            Http::response(201, 'Successfully processed');
        } elseif (!$errors['err']) {
            $errors['err'] =  __('Error updating ticket status');
        }

        $state = $state ?: $ticket->getStatus()->getState();
        $info['status_id'] = $status
            ? $status->getId() : $ticket->getStatusId();

        return self::_changeTicketStatus($ticket, $state, $info, $errors);
    }

    function changeSelectedTicketsStatus($status, $id=0) {
        global $thisstaff, $cfg;

        if (!$thisstaff)
            Http::response(403, 'Access denied');

        $state = null;
        $info = array();
        switch($status) {
            case 'open':
            case 'reopen':
                $state = 'open';
                break;
            case 'close':
                if (!$thisstaff->hasPerm(TicketModel::PERM_CLOSE))
                    Http::response(403, 'Access denied');
                $state = 'closed';
                break;
            case 'delete':
                if (!$thisstaff->hasPerm(TicketModel::PERM_DELETE))
                    Http::response(403, 'Access denied');

                $state = 'deleted';
                break;
            default:
                $info['warn'] = sprintf('%s %s',
                        __('Unknown or invalid'), __('status'));
        }

        $info['status_id'] = $id;

        return self::_changeSelectedTicketsStatus($state, $info);
    }

    function setSelectedTicketsStatus($state) {
        global $thisstaff, $ost;

        $errors = $info = array();
        if (!$thisstaff || !$thisstaff->canManageTickets())
            $errors['err'] = sprintf('%s %s',
                    sprintf(__('You do not have permission %s.'),
                        __('to mass manage tickets')),
                    __('Contact admin for such access'));
        elseif (!$_REQUEST['tids'] || !count($_REQUEST['tids']))
            $errors['err']=sprintf(__('You must select at least %s.'),
                    __('one ticket'));
        elseif (!($status= TicketStatus::lookup($_REQUEST['status_id'])))
            $errors['status_id'] = sprintf('%s %s',
                    __('Unknown or invalid'), __('status'));
        elseif (!$errors) {
            // Make sure the agent has permission to set the status
            switch(mb_strtolower($status->getState())) {
                case 'open':
                    if (!$thisstaff->hasPerm(TicketModel::PERM_CLOSE)
                            && !$thisstaff->hasPerm(TicketModel::PERM_CREATE))
                        $errors['err'] = sprintf(__('You do not have permission %s.'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!$thisstaff->hasPerm(TicketModel::PERM_CLOSE))
                        $errors['err'] = sprintf(__('You do not have permission %s.'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!$thisstaff->hasPerm(TicketModel::PERM_DELETE))
                        $errors['err'] = sprintf(__('You do not have permission %s.'),
                                __('to archive/delete tickets'));
                    break;
                default:
                    $errors['err'] = sprintf('%s %s',
                            __('Unknown or invalid'), __('status'));
            }
        }

        $count = count($_REQUEST['tids']);
        if (!$errors) {
            $i = 0;
            $comments = $_REQUEST['comments'];
            foreach ($_REQUEST['tids'] as $tid) {

                if (($ticket=Ticket::lookup($tid))
                        && $ticket->getStatusId() != $status->getId()
                        && $ticket->checkStaffPerm($thisstaff)
                        && $ticket->setStatus($status, $comments, $errors))
                    $i++;
            }

            if (!$i) {
                $errors['err'] = $errors['err']
                    ?: sprintf(__('Unable to change status for %s'),
                        _N('the selected ticket', 'any of the selected tickets', $count));
            }
            else {
                // Assume success
                if ($i==$count) {

                    if (!strcasecmp($status->getState(), 'deleted')) {
                        $msg = sprintf(__( 'Successfully deleted %s.'),
                                _N('selected ticket', 'selected tickets',
                                    $count));
                    } else {
                       $msg = sprintf(
                            __(
                                /* 1$ will be 'selected ticket(s)', 2$ is the new status */
                                'Successfully changed status of %1$s to %2$s'),
                            _N('selected ticket', 'selected tickets',
                                $count),
                            $status->getName());
                    }

                    $_SESSION['::sysmsgs']['msg'] = $msg;
                } else {

                    if (!strcasecmp($status->getState(), 'deleted')) {
                        $warn = sprintf(__('Successfully deleted %s.'),
                                sprintf(__('%1$d of %2$d selected tickets'),
                                    $i, $count)
                                );
                    } else {

                        $warn = sprintf(
                                __('%1$d of %2$d %3$s status changed to %4$s'),$i, $count,
                                _N('selected ticket', 'selected tickets',
                                    $count),
                                $status->getName());
                    }

                    $_SESSION['::sysmsgs']['warn'] = $warn;
                }

                Http::response(201, 'Successfully processed');
            }
        }

        return self::_changeSelectedTicketsStatus($state, $info, $errors);
    }

    function triggerThreadAction($ticket_id, $thread_id, $action) {
        $thread = ThreadEntry::lookup($thread_id);
        if (!$thread)
            Http::response(404, 'No such ticket thread entry');
        if ($thread->getThread()->getObjectId() != $ticket_id)
            Http::response(404, 'No such ticket thread entry');

        $valid = false;
        foreach ($thread->getActions() as $group=>$list) {
            foreach ($list as $name=>$A) {
                if ($A->getId() == $action) {
                    $valid = true; break;
                }
            }
        }
        if (!$valid)
            Http::response(400, 'Not a valid action for this thread');

        $thread->triggerAction($action);
    }

    private function _changeSelectedTicketsStatus($state, $info=array(), $errors=array()) {

        $count = $_REQUEST['count'] ?:
            ($_REQUEST['tids'] ?  count($_REQUEST['tids']) : 0);

        $info['title'] = sprintf(__('%1$s Tickets &mdash; %2$d selected'),
                TicketStateField::getVerb($state),
                 $count);

        if (!strcasecmp($state, 'deleted')) {

            $info['warn'] = sprintf(__(
                        'Are you sure you want to DELETE %s?'),
                    _N('selected ticket', 'selected tickets', $count)
                    );

            $info['extra'] = sprintf('<strong>%s</strong>', __(
                        'Deleted tickets CANNOT be recovered, including any associated attachments.')
                    );

            $info['placeholder'] = sprintf(__(
                        'Optional reason for deleting %s'),
                    _N('selected ticket', 'selected tickets', $count));
        }

        $info['status_id'] = $info['status_id'] ?: $_REQUEST['status_id'];
        $info['comments'] = Format::htmlchars($_REQUEST['comments']);

        return self::_changeStatus($state, $info, $errors);
    }

    private function _changeTicketStatus($ticket, $state, $info=array(), $errors=array()) {

        $verb = TicketStateField::getVerb($state);

        $info['action'] = sprintf('#tickets/%d/status', $ticket->getId());
        $info['title'] = sprintf(__(
                    /* 1$ will be a verb, like 'open', 2$ will be the ticket number */
                    '%1$s Ticket #%2$s'),
                $verb ?: $state,
                $ticket->getNumber()
                );

        // Deleting?
        if (!strcasecmp($state, 'deleted')) {

            $info['placeholder'] = sprintf(__(
                        'Optional reason for deleting %s'),
                    __('this ticket'));
            $info[ 'warn'] = sprintf(__(
                        'Are you sure you want to DELETE %s?'),
                        __('this ticket'));
            //TODO: remove message below once we ship data retention plug
            $info[ 'extra'] = sprintf('<strong>%s</strong>',
                        __('Deleted tickets CANNOT be recovered, including any associated attachments.')
                        );
        }

        $info['status_id'] = $info['status_id'] ?: $ticket->getStatusId();
        $info['comments'] = Format::htmlchars($_REQUEST['comments']);

        return self::_changeStatus($state, $info, $errors);
    }

    private function _changeStatus($state, $info=array(), $errors=array()) {

        if ($info && isset($info['errors']))
            $errors = array_merge($errors, $info['errors']);

        if (!$info['error'] && isset($errors['err']))
            $info['error'] = $errors['err'];

        include(STAFFINC_DIR . 'templates/ticket-status.tmpl.php');
    }

    function tasks($tid) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'Unknown ticket');

         include STAFFINC_DIR . 'ticket-tasks.inc.php';
    }

    function addTask($tid) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid)))
            Http::response(404, 'Unknown ticket');

        if (!$ticket->checkStaffPerm($thisstaff, Task::PERM_CREATE))
            Http::response(403, 'Permission denied');

        $info=$errors=array();

        if ($_POST) {
            Draft::deleteForNamespace(
                    sprintf('ticket.%d.task', $ticket->getId()),
                    $thisstaff->getId());
            // Default form
            $form = TaskForm::getInstance();
            $form->setSource($_POST);
            // Internal form
            $iform = TaskForm::getInternalForm($_POST);
            $isvalid = true;
            if (!$iform->isValid())
                $isvalid = false;
            if (!$form->isValid())
                $isvalid = false;

            if ($isvalid) {
                $vars = $_POST;
                $vars['object_id'] = $ticket->getId();
                $vars['object_type'] = ObjectModel::OBJECT_TYPE_TICKET;
                $vars['default_formdata'] = $form->getClean();
                $vars['internal_formdata'] = $iform->getClean();
                $desc = $form->getField('description');
                if ($desc
                        && $desc->isAttachmentsEnabled()
                        && ($attachments=$desc->getWidget()->getAttachments()))
                    $vars['cannedattachments'] = $attachments->getClean();
                $vars['staffId'] = $thisstaff->getId();
                $vars['poster'] = $thisstaff;
                $vars['ip_address'] = $_SERVER['REMOTE_ADDR'];
                if (($task=Task::create($vars, $errors)))
                    Http::response(201, $task->getId());
            }

            $info['error'] = __('Error adding task - try again!');
        }

        $info['action'] = sprintf('#tickets/%d/add-task', $ticket->getId());
        $info['title'] = sprintf(
                __( 'Ticket #%1$s: %2$s'),
                $ticket->getNumber(),
                _('Add New Task')
                );

         include STAFFINC_DIR . 'templates/task.tmpl.php';
    }

    function task($tid, $id) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'Unknown ticket');

        // Lookup task and check access
        if (!($task=Task::lookup($id))
                || !$task->checkStaffPerm($thisstaff))
            Http::response(404, 'Unknown task');

        $info=$errors=array();
        $note_form = new SimpleForm(array(
            'attachments' => new FileUploadField(array('id'=>'attach',
            'name'=>'attach:note',
            'configuration' => array('extensions'=>'')))
            ));

        if ($_POST) {
            switch ($_POST['a']) {
            case 'postnote':
                $vars = $_POST;
                $attachments = $note_form->getField('attachments')->getClean();
                $vars['cannedattachments'] = array_merge(
                    $vars['cannedattachments'] ?: array(), $attachments);
                if(($note=$task->postNote($vars, $errors, $thisstaff))) {
                    $msg=__('Note posted successfully');
                    // Clear attachment list
                    $note_form->setSource(array());
                    $note_form->getField('attachments')->reset();
                    Draft::deleteForNamespace('task.note.'.$task->getId(),
                            $thisstaff->getId());
                } else {
                    if(!$errors['err'])
                        $errors['err'] = __('Unable to post the note - missing or invalid data.');
                }
                break;
            default:
                $errors['err'] = __('Unknown action');
            }
        }

        include STAFFINC_DIR . 'templates/task-view.tmpl.php';
    }
}
?>
