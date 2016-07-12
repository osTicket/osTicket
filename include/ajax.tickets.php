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

        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $tickets=array();
        // Bail out of query is empty
        if (!$_REQUEST['q'])
            return $this->json_encode($tickets);

        $visibility = Q::any(array(
            'staff_id' => $thisstaff->getId(),
            'team_id__in' => $thisstaff->teams->values_flat('team_id'),
        ));

        if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts())) {
            $visibility->add(array('dept_id__in' => $depts));
        }

        $hits = TicketModel::objects()
            ->filter($visibility)
            ->values('user__default_email__address')
            ->annotate(array(
                'number' => new SqlCode('null'),
                'tickets' => SqlAggregate::COUNT('ticket_id', true)))
            ->limit($limit);

        $q = $_REQUEST['q'];

        if (strlen($q) < 3)
            return $this->encode(array());

        global $ost;
        $hits = $ost->searcher->find($q, $hits)
            ->order_by(new SqlCode('__relevance__'), QuerySet::DESC);

        if (preg_match('/\d{2,}[^*]/', $q, $T = array())) {
            $hits = TicketModel::objects()
                ->values('user__default_email__address', 'number')
                ->annotate(array(
                    'tickets' => new SqlCode('1'),
                    '__relevance__' => new SqlCode(1)
                ))
                ->filter($visibility)
                ->filter(array('number__startswith' => $q))
                ->limit($limit)
                ->union($hits);
        }
        elseif (!count($hits) && preg_match('`\w$`u', $q)) {
            // Do wild-card fulltext search
            $_REQUEST['q'] = $q.'*';
            return $this->lookup();
        }

        foreach ($hits as $T) {
            $email = $T['user__default_email__address'];
            $count = $T['tickets'];
            if ($T['number']) {
                $tickets[] = array('id'=>$T['number'], 'value'=>$T['number'],
                    'info'=>"{$T['number']} — {$email}",
                    'matches'=>$_REQUEST['q']);
            }
            else {
                $tickets[] = array('email'=>$email, 'value'=>$email,
                    'info'=>"$email ($count)", 'matches'=>$_REQUEST['q']);
            }
        }

        return $this->json_encode($tickets);
    }

    function acquireLock($tid) {
        global $cfg, $thisstaff;

        if(!$cfg || !$cfg->getLockTime() || $cfg->getTicketLockMode() == Lock::MODE_DISABLED)
            Http::response(418, $this->encode(array('id'=>0, 'retry'=>false)));

        if(!$tid || !is_numeric($tid) || !$thisstaff)
            return 0;

        if (!($ticket = Ticket::lookup($tid)) || !$ticket->checkStaffPerm($thisstaff))
            return $this->encode(array('id'=>0, 'retry'=>false, 'msg'=>__('Lock denied!')));

        //is the ticket already locked?
        if ($ticket->isLocked() && ($lock=$ticket->getLock()) && !$lock->isExpired()) {
            /*Note: Ticket->acquireLock does the same logic...but we need it here since we need to know who owns the lock up front*/
            //Ticket is locked by someone else.??
            if ($lock->getStaffId() != $thisstaff->getId())
                return $this->json_encode(array('id'=>0, 'retry'=>false,
                    'msg' => sprintf(__('Currently locked by %s'),
                        $lock->getStaff()->getAvatarAndName())
                    ));

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

    function renewLock($id, $ticketId) {
        global $thisstaff;

        if (!$id || !is_numeric($id) || !$thisstaff)
            Http::response(403, $this->encode(array('id'=>0, 'retry'=>false)));
        if (!($lock = Lock::lookup($id)))
            Http::response(404, $this->encode(array('id'=>0, 'retry'=>'acquire')));
        if (!($ticket = Ticket::lookup($ticketId)) || $ticket->lock_id != $lock->lock_id)
            // Ticket / Lock mismatch
            Http::response(400, $this->encode(array('id'=>0, 'retry'=>false)));

        if (!$lock->getStaffId() || $lock->isExpired())
            // Said lock doesn't exist or is is expired — fetch a new lock
            return self::acquireLock($ticket->getId());

        if ($lock->getStaffId() != $thisstaff->getId())
            // user doesn't own the lock anymore??? sorry...try to next time.
            Http::response(403, $this->encode(array('id'=>0, 'retry'=>false,
                'msg' => sprintf(__('Currently locked by %s'),
                    $lock->getStaff->getAvatarAndName())
            ))); //Give up...

        // Ensure staff still has access
        if (!$ticket->checkStaffPerm($thisstaff))
            Http::response(403, $this->encode(array('id'=>0, 'retry'=>false,
                'msg' => sprintf(__('You no longer have access to #%s.'),
                $ticket->getNumber())
            )));

        // Renew the lock.
        // Failure here is not an issue since the lock is not expired yet.. client need to check time!
        $lock->renew();

        return $this->encode(array('id'=>$lock->getId(), 'time'=>$lock->getTime(),
            'code' => $lock->getCode()));
    }

    function releaseLock($id) {
        global $thisstaff;

        if (!$id || !is_numeric($id) || !$thisstaff)
            Http::response(403, $this->encode(array('id'=>0, 'retry'=>true)));
        if (!($lock = Lock::lookup($id)))
            Http::response(404, $this->encode(array('id'=>0, 'retry'=>true)));

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

    function previewTicket ($tid) {
        global $thisstaff;

        if(!$thisstaff || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, __('No such ticket'));

        include STAFFINC_DIR . 'templates/ticket-preview.tmpl.php';
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
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($ticket = Ticket::lookup($ticket_id)))
            Http::response(404, "No such ticket");
        elseif (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_EDIT))
            Http::response(403, "Access Denied");

        $forms = DynamicFormEntry::forTicket($ticket->getId());
        $info = array('action' => '#tickets/'.$ticket->getId().'/forms/manage');
        include(STAFFINC_DIR . 'templates/form-manage.tmpl.php');
    }

    function updateForms($ticket_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($ticket = Ticket::lookup($ticket_id)))
            Http::response(404, "No such ticket");
        elseif (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_EDIT))
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
            if (!$cfg->isRichTextEnabled())
                $response = Format::html2text($response, 90);
            else
                $response = Format::viewableImages($response);

            // XXX: assuming json format for now.
            return Format::json_encode(array('response' => $response));
        }

        if (!$cfg->isRichTextEnabled())
            $format.='.plain';

        $varReplacer = function (&$var) use($ticket) {
            return $ticket->replaceVars($var);
        };

        include_once(INCLUDE_DIR.'class.canned.php');
        if (!$cid || !($canned=Canned::lookup($cid)) || !$canned->isEnabled())
            Http::response(404, 'No such premade reply');

        return $canned->getFormattedResponse($format, $varReplacer);
    }

    function transfer($tid) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid)))
            Http::response(404, __('No such ticket'));

        if (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_TRANSFER))
            Http::response(403, __('Permission denied'));

        $errors = array();

        $info = array(
                ':title' => sprintf(__('Ticket #%s: %s'),
                    $ticket->getNumber(),
                    __('Transfer')),
                ':action' => sprintf('#tickets/%d/transfer',
                    $ticket->getId())
                );

        $form = $ticket->getTransferForm($_POST);
        if ($_POST && $form->isValid()) {
            if ($ticket->transfer($form, $errors)) {
                $_SESSION['::sysmsgs']['msg'] = sprintf(
                        __('%s successfully'),
                        sprintf(
                            __('%s transferred to %s department'),
                            __('Ticket'),
                            $ticket->getDept()
                            )
                        );
                Http::response(201, $ticket->getId());
            }

            $form->addErrors($errors);
            $info['error'] = $errors['err'] ?: __('Unable to transfer ticket');
        }

        $info['dept_id'] = $info['dept_id'] ?: $ticket->getDeptId();

        include STAFFINC_DIR . 'templates/transfer.tmpl.php';
    }


    function assign($tid, $target=null) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid)))
            Http::response(404, __('No such ticket'));

        if (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_ASSIGN)
                || !($form = $ticket->getAssignmentForm($_POST,
                        array('target' => $target))))
            Http::response(403, __('Permission denied'));

        $errors = array();
        $info = array(
                ':title' => sprintf(__('Ticket #%s: %s'),
                    $ticket->getNumber(),
                    sprintf('%s %s',
                        $ticket->isAssigned() ?
                            __('Reassign') :  __('Assign'),
                        !strcasecmp($target, 'agents') ?
                            __('to an Agent') : __('to a Team')
                    )),
                ':action' => sprintf('#tickets/%d/assign%s',
                    $ticket->getId(),
                    ($target  ? "/$target": '')),
                );

        if ($ticket->isAssigned()) {
            if ($ticket->getStaffId() == $thisstaff->getId())
                $assigned = __('you');
            else
                $assigned = $ticket->getAssigned();

            $info['notice'] = sprintf(__('%s is currently assigned to <b>%s</b>'),
                    __('This ticket'),
                    Format::htmlchars($assigned)
                    );
        }

        if ($_POST && $form->isValid()) {
            if ($ticket->assign($form, $errors)) {
                $_SESSION['::sysmsgs']['msg'] = sprintf(
                        __('%s successfully'),
                        sprintf(
                            __('%s assigned to %s'),
                            __('Ticket'),
                            $form->getAssignee())
                        );
                Http::response(201, $ticket->getId());
            }

            $form->addErrors($errors);
            $info['error'] = $errors['err'] ?: __('Unable to assign ticket');
        }

        include STAFFINC_DIR . 'templates/assign.tmpl.php';
    }

    function claim($tid) {

        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid)))
            Http::response(404, __('No such ticket'));

        // Check for premissions and such
        if (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_ASSIGN)
                || !$ticket->isOpen() // Claim only open
                || $ticket->getStaff() // cannot claim assigned ticket
                || !($form = $ticket->getClaimForm($_POST)))
            Http::response(403, __('Permission denied'));

        $errors = array();
        $info = array(
                ':title' => sprintf(__('Ticket #%s: %s'),
                    $ticket->getNumber(),
                    __('Claim')),
                ':action' => sprintf('#tickets/%d/claim',
                    $ticket->getId()),

                );

        if ($ticket->isAssigned()) {
            if ($ticket->getStaffId() == $thisstaff->getId())
                $assigned = __('you');
            else
                $assigned = $ticket->getAssigned();

            $info['error'] = sprintf(__('%s is currently assigned to <b>%s</b>'),
                    __('This ticket'),
                    $assigned);
        } else {
            $info['warn'] = sprintf(__('Are you sure you want to CLAIM %s?'),
                    __('this ticket'));
        }

        if ($_POST && $form->isValid()) {
            if ($ticket->claim($form, $errors)) {
                $_SESSION['::sysmsgs']['msg'] = sprintf(
                        __('%s successfully'),
                        sprintf(
                            __('%s assigned to %s'),
                            __('Ticket'),
                            __('you'))
                        );
                Http::response(201, $ticket->getId());
            }

            $form->addErrors($errors);
            $info['error'] = $errors['err'] ?: __('Unable to claim ticket');
        }

        $verb = sprintf('%s, %s', __('Yes'), __('Claim'));

        include STAFFINC_DIR . 'templates/assign.tmpl.php';

    }

    function massProcess($action, $w=null)  {
        global $thisstaff, $cfg;

        $actions = array(
                'transfer' => array(
                    'verbed' => __('transferred'),
                    ),
                'assign' => array(
                    'verbed' => __('assigned'),
                    ),
                'claim' => array(
                    'verbed' => __('assigned'),
                    ),
                'delete' => array(
                    'verbed' => __('deleted'),
                    ),
                'reopen' => array(
                    'verbed' => __('reopen'),
                    ),
                'close' => array(
                    'verbed' => __('closed'),
                    ),
                );

        if (!isset($actions[$action]))
            Http::response(404, __('Unknown action'));


        $info = $errors = $e = array();
        $inc = null;
        $i = $count = 0;
        if ($_POST) {
            if (!$_POST['tids'] || !($count=count($_POST['tids'])))
                $errors['err'] = sprintf(
                        __('You must select at least %s.'),
                        __('one ticket'));
        } else {
            $count  =  $_REQUEST['count'];
        }
        switch ($action) {
        case 'claim':
            $w = 'me';
        case 'assign':
            $inc = 'assign.tmpl.php';
            $info[':action'] = "#tickets/mass/assign/$w";
            $info[':title'] = sprintf('Assign %s',
                    _N('selected ticket', 'selected tickets', $count));

            $form = AssignmentForm::instantiate($_POST);

            $assignCB = function($t, $f, $e) {
                return $t->assign($f, $e);
            };

            $assignees = null;
            switch ($w) {
                case 'agents':
                    $depts = array();
                    $tids = $_POST['tids'] ?: array_filter(explode(',', $_REQUEST['tids']));
                    if ($tids) {
                        $tickets = TicketModel::objects()
                            ->distinct('dept_id')
                            ->filter(array('ticket_id__in' => $tids));

                        $depts = $tickets->values_flat('dept_id');
                    }
                    $members = Staff::objects()
                        ->distinct('staff_id')
                        ->filter(array(
                                    'onvacation' => 0,
                                    'isactive' => 1,
                                    )
                                );

                    if ($depts) {
                        $members->filter(Q::any( array(
                                        'dept_id__in' => $depts,
                                        Q::all(array(
                                            'dept_access__dept__id__in' => $depts,
                                            Q::not(array('dept_access__dept__flags__hasbit'
                                                => Dept::FLAG_ASSIGN_MEMBERS_ONLY))
                                            ))
                                        )));
                    }

                    switch ($cfg->getAgentNameFormat()) {
                    case 'last':
                    case 'lastfirst':
                    case 'legal':
                        $members->order_by('lastname', 'firstname');
                        break;

                    default:
                        $members->order_by('firstname', 'lastname');
                    }

                    $prompt  = __('Select an Agent');
                    $assignees = array();
                    foreach ($members as $member)
                         $assignees['s'.$member->getId()] = $member->getName();

                    if (!$assignees)
                        $info['warn'] =  __('No agents available for assignment');
                    break;
                case 'teams':
                    $assignees = array();
                    $prompt = __('Select a Team');
                    foreach (Team::getActiveTeams() as $id => $name)
                        $assignees['t'.$id] = $name;

                    if (!$assignees)
                        $info['warn'] =  __('No teams available for assignment');
                    break;
                case 'me':
                    $info[':action'] = '#tickets/mass/claim';
                    $info[':title'] = sprintf('Claim %s',
                            _N('selected ticket', 'selected tickets', $count));
                    $info['warn'] = sprintf(
                            __('Are you sure you want to CLAIM %s?'),
                            _N('selected ticket', 'selected tickets', $count));
                    $verb = sprintf('%s, %s', __('Yes'), __('Claim'));
                    $id = sprintf('s%s', $thisstaff->getId());
                    $assignees = array($id => $thisstaff->getName());
                    $vars = $_POST ?: array('assignee' => array($id));
                    $form = ClaimForm::instantiate($vars);
                    $assignCB = function($t, $f, $e) {
                        return $t->claim($f, $e);
                    };
                    break;
            }

            if ($assignees != null)
                $form->setAssignees($assignees);

            if ($prompt && ($f=$form->getField('assignee')))
                $f->configure('prompt', $prompt);

            if ($_POST && $form->isValid()) {
                foreach ($_POST['tids'] as $tid) {
                    if (($t=Ticket::lookup($tid))
                            // Make sure the agent is allowed to
                            // access and assign the task.
                            && $t->checkStaffPerm($thisstaff, Ticket::PERM_ASSIGN)
                            // Do the assignment
                            && $assignCB($t, $form, $e)
                            )
                        $i++;
                }

                if (!$i) {
                    $info['error'] = sprintf(
                            __('Unable to %1$s %2$s'),
                            __('assign'),
                            _N('selected ticket', 'selected tickets', $count));
                }
            }
            break;
        case 'transfer':
            $inc = 'transfer.tmpl.php';
            $info[':action'] = '#tickets/mass/transfer';
            $info[':title'] = sprintf('Transfer %s',
                    _N('selected ticket', 'selected tickets', $count));
            $form = TransferForm::instantiate($_POST);
            if ($_POST && $form->isValid()) {
                foreach ($_POST['tids'] as $tid) {
                    if (($t=Ticket::lookup($tid))
                            // Make sure the agent is allowed to
                            // access and transfer the task.
                            && $t->checkStaffPerm($thisstaff, Ticket::PERM_TRANSFER)
                            // Do the transfer
                            && $t->transfer($form, $e)
                            )
                        $i++;
                }

                if (!$i) {
                    $info['error'] = sprintf(
                            __('Unable to %1$s %2$s'),
                            __('transfer'),
                            _N('selected ticket', 'selected tickets', $count));
                }
            }
            break;
        case 'delete':
            $inc = 'delete.tmpl.php';
            $info[':action'] = '#tickets/mass/delete';
            $info[':title'] = sprintf('Delete %s',
                    _N('selected ticket', 'selected tickets', $count));

            $info[':placeholder'] = sprintf(__(
                        'Optional reason for deleting %s'),
                    _N('selected ticket', 'selected tickets', $count));
            $info['warn'] = sprintf(__(
                        'Are you sure you want to DELETE %s?'),
                    _N('selected ticket', 'selected tickets', $count));
            $info[':extra'] = sprintf('<strong>%s</strong>',
                        __('Deleted tickets CANNOT be recovered, including any associated attachments.')
                        );

            // Generic permission check.
            if (!$thisstaff->hasPerm(Ticket::PERM_DELETE, false))
                $errors['err'] = sprintf(
                        __('You do not have permission %s'),
                        __('to delete tickets'));

            if ($_POST && !$errors) {
                foreach ($_POST['tids'] as $tid) {
                    if (($t=Ticket::lookup($tid))
                            && $t->checkStaffPerm($thisstaff, Ticket::PERM_DELETE)
                            && $t->delete($_POST['comments'], $e)
                            )
                        $i++;
                }

                if (!$i) {
                    $info['error'] = sprintf(
                            __('Unable to %1$s %2$s'),
                            __('delete'),
                            _N('selected ticket', 'selected tickets', $count));
                }
            }
            break;
        default:
            Http::response(404, __('Unknown action'));
        }

        if ($_POST && $i) {

            // Assume success
            if ($i==$count) {
                $msg = sprintf(__('Successfully %1$s %2$s.' /* Tokens are <actioned> <x selected ticket(s)> */ ),
                        $actions[$action]['verbed'],
                        sprintf('%1$d %2$s',
                            $count,
                            _N('selected ticket', 'selected tickets', $count))
                        );
                $_SESSION['::sysmsgs']['msg'] = $msg;
            } else {
                $warn = sprintf(
                        __('%1$d of %2$d %3$s %4$s'
                        /* Tokens are <x> of <y> <selected ticket(s)> <actioned> */),
                        $i, $count,
                        _N('selected ticket', 'selected tickets',
                            $count),
                        $actions[$action]['verbed']);
                $_SESSION['::sysmsgs']['warn'] = $warn;
            }
            Http::response(201, 'processed');
        } elseif($_POST && !isset($info['error'])) {
            $info['error'] = $errors['err'] ?: sprintf(
                    __('Unable to %1$s %2$s'),
                    __('process'),
                    _N('selected ticket', 'selected tickets', $count));
        }

        if ($_POST)
            $info = array_merge($info, Format::htmlchars($_POST));

        include STAFFINC_DIR . "templates/$inc";
        //  Copy checked tickets to the form.
        echo "
        <script type=\"text/javascript\">
        $(function() {
            $('form#tickets input[name=\"tids[]\"]:checkbox:checked')
            .each(function() {
                $('<input>')
                .prop('type', 'hidden')
                .attr('name', 'tids[]')
                .val($(this).val())
                .appendTo('form.mass-action');
            });
        });
        </script>";

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

                // Check if ticket is closeable
                if (is_string($closeable=$ticket->isCloseable()))
                    $info['warn'] =  $closeable;

                break;
            case 'delete':
                if (!$role->hasPerm(TicketModel::PERM_DELETE))
                    Http::response(403, 'Access denied');
                $state = 'deleted';
                break;
            default:
                $state = $ticket->getStatus()->getState();
                $info['warn'] = sprintf(__('%s: Unknown or invalid'),
                        __('status'));
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
                        $errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!$role->hasPerm(TicketModel::PERM_CLOSE))
                        $errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!$role->hasPerm(TicketModel::PERM_DELETE))
                        $errors['err'] = sprintf(__('You do not have permission %s'),
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
                if (!$thisstaff->hasPerm(TicketModel::PERM_CLOSE, false))
                    Http::response(403, 'Access denied');
                $state = 'closed';
                break;
            case 'delete':
                if (!$thisstaff->hasPerm(TicketModel::PERM_DELETE, false))
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
                    sprintf(__('You do not have permission %s'),
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
                    if (!$thisstaff->hasPerm(TicketModel::PERM_CLOSE, false)
                            && !$thisstaff->hasPerm(TicketModel::PERM_CREATE, false))
                        $errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!$thisstaff->hasPerm(TicketModel::PERM_CLOSE, false))
                        $errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!$thisstaff->hasPerm(TicketModel::PERM_DELETE, false))
                        $errors['err'] = sprintf(__('You do not have permission %s'),
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
                        _N('selected ticket', 'selected tickets', $count));
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

        $info['title'] = sprintf(__('Change Status &mdash; %1$d %2$s selected'),
                 $count,
                 _N('ticket', 'tickets', $count)
                 );

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

            $info['error'] = sprintf('%s - %s', __('Error adding task'), __('Please try again!'));
        }

        $info['action'] = sprintf('#tickets/%d/add-task', $ticket->getId());
        $info['title'] = sprintf(
                __( 'Ticket #%1$s: %2$s'),
                $ticket->getNumber(),
                __('Add New Task')
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

        $info = $errors = array();
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

        if ($_POST) {
            $vars = $_POST;
            switch ($_POST['a']) {
            case 'postnote':
                $attachments = $note_attachments_form->getField('attachments')->getClean();
                $vars['cannedattachments'] = array_merge(
                    $vars['cannedattachments'] ?: array(), $attachments);
                if (($note=$task->postNote($vars, $errors, $thisstaff))) {
                    $msg=__('Note posted successfully');
                    // Clear attachment list
                    $note_attachments_form->setSource(array());
                    $note_attachments_form->getField('attachments')->reset();
                    Draft::deleteForNamespace('task.note.'.$task->getId(),
                            $thisstaff->getId());
                } else {
                    if (!$errors['err'])
                        $errors['err'] = __('Unable to post the note - missing or invalid data.');
                }
                break;
            case 'postreply':
                $attachments = $reply_attachments_form->getField('attachments')->getClean();
                $vars['cannedattachments'] = array_merge(
                    $vars['cannedattachments'] ?: array(), $attachments);
                if (($response=$task->postReply($vars, $errors))) {
                    $msg=__('Update posted successfully');
                    // Clear attachment list
                    $reply_attachments_form->setSource(array());
                    $reply_attachments_form->getField('attachments')->reset();
                    Draft::deleteForNamespace('task.reply.'.$task->getId(),
                            $thisstaff->getId());
                } else {
                    if (!$errors['err'])
                        $errors['err'] = __('Unable to post the reply - missing or invalid data.');
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
