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

        $visibility = $thisstaff->getTicketsVisibility();
        $hits = Ticket::objects()
            ->filter($visibility)
            ->values('user__default_email__address', 'cdata__subject', 'user__name', 'ticket_id', 'thread__id', 'flags')
            ->annotate(array(
                'number' => new SqlCode('null'),
                'tickets' => SqlAggregate::COUNT('ticket_id', true),
                'tasks' => SqlAggregate::COUNT('tasks__id', true),
                'collaborators' => SqlAggregate::COUNT('thread__collaborators__id', true),
                'entries' => SqlAggregate::COUNT('thread__entries__id', true),
            ))
            ->order_by(SqlAggregate::SUM(new SqlCode('Z1.relevance')), QuerySet::DESC)
            ->limit($limit);

        $q = $_REQUEST['q'];

        if (strlen(Format::searchable($q)) < 3)
            return $this->encode(array());

        global $ost;
        $hits = $ost->searcher->find($q, $hits, false);

        if (preg_match('/\d{2,}[^*]/', $q, $T = array())) {
            $hits = Ticket::objects()
                ->values('user__default_email__address', 'number', 'cdata__subject', 'user__name', 'ticket_id', 'thread__id', 'flags')
                ->annotate(array(
                    'tickets' => new SqlCode('1'),
                    'tasks' => SqlAggregate::COUNT('tasks__id', true),
                    'collaborators' => SqlAggregate::COUNT('thread__collaborators__id', true),
                    'entries' => SqlAggregate::COUNT('thread__entries__id', true),
                ))
                ->filter($visibility)
                ->filter(array('number__startswith' => $q))
                ->order_by('number')
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
                $tickets[$T['number']] = array('id'=>$T['number'], 'value'=>$T['number'],
                    'ticket_id'=>$T['ticket_id'],
                    'info'=>"{$T['number']} — {$email}",
                    'subject'=>$T['cdata__subject'],
                    'user'=>$T['user__name'],
                    'tasks'=>$T['tasks'],
                    'thread_id'=>$T['thread__id'],
                    'collaborators'=>$T['collaborators'],
                    'entries'=>$T['entries'],
                    'mergeType'=>Ticket::getMergeTypeByFlag($T['flags']),
                    'children'=>count(Ticket::getChildTickets($T['ticket_id'])) > 0 ? true : false,
                    'matches'=>$_REQUEST['q']);
            }
            else {
                $tickets[$email] = array('email'=>$email, 'value'=>$email,
                    'info'=>"$email ($count)", 'matches'=>$_REQUEST['q']);
            }
        }
        $tickets = array_values($tickets);

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

    function mergeTickets($ticket_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($ticket = Ticket::lookup($ticket_id)))
            Http::response(404, "No such ticket");
        elseif (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_EDIT))
            Http::response(403, "Access Denied");

        //retrieve the parent and child tickets
        $parent = Ticket::objects()
            ->filter(array('ticket_id'=>$ticket_id))
            ->values_flat('ticket_id', 'number', 'ticket_pid', 'sort', 'thread__id', 'user_id', 'cdata__subject', 'user__name', 'flags')
            ->annotate(array('tasks' => SqlAggregate::COUNT('tasks__id', true),
                             'collaborators' => SqlAggregate::COUNT('thread__collaborators__id', true),
                             'entries' => SqlAggregate::COUNT('thread__entries__id', true),));
        $tickets =  Ticket::getChildTickets($ticket_id);
        $tickets = $parent->union($tickets);

        //fix sort of tickets
        $sql = sprintf('SELECT * FROM (%s) a ORDER BY sort', $tickets->getQuery());
        $res = db_query($sql);
        $tickets = db_assoc_array($res);
        $info = array('action' => '#tickets/'.$ticket->getId().'/merge');

        return self::_updateMerge($ticket, $tickets, $info);
    }

    function updateMerge($ticket_id) {
        global $thisstaff;

        $info = array();
        $errors = array();

        if ($_POST['dtids']) {
            foreach($_POST['dtids'] as $key => $value) {
                if (is_numeric($key) && $ticket = Ticket::lookup($value))
                    $ticket->unlink();
            }
            return true;
        } elseif ($_POST['tids']) {
            if ($parent = Ticket::merge($_POST))
                Http::response(201, 'Successfully managed');
            else
                $info['error'] = $errors['err'] ?: __('Unable to merge ticket');
        }

        $parentModel = Ticket::objects()
            ->filter(array('ticket_id'=>$ticket_id))
            ->values_flat('ticket_id', 'number', 'ticket_pid', 'sort', 'thread__id', 'user_id', 'cdata__subject', 'user__name', 'flags')
            ->annotate(array('tasks' => SqlAggregate::COUNT('tasks__id', true),
                             'collaborators' => SqlAggregate::COUNT('thread__collaborators__id', true),
                             'entries' => SqlAggregate::COUNT('thread__entries__id', true),));

        if ($parent->getMergeType() == 'visual') {
            $tickets = Ticket::getChildTickets($ticket_id);
            $tickets = $parentModel->union($tickets);
        } else
            $tickets = $parentModel;

        return self::_updateMerge($parent, $tickets, $info);
    }

    private function _updateMerge($ticket, $tickets, $info) {
        include(STAFFINC_DIR . 'templates/merge-tickets.tmpl.php');
    }

    function previewMerge($tid) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'No such ticket');

        ob_start();
        include STAFFINC_DIR . 'templates/merge-preview.tmpl.php';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
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
            return $ticket->replaceVars($var, array('recipient' => $ticket->getOwner()));
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

    function referrals($tid) {
      return $this->refer($tid);
    }

    function refer($tid, $target=null) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid)))
            Http::response(404, __('No such ticket'));

        if (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_ASSIGN)
                || !($form = $ticket->getReferralForm($_POST,
                        array('target' => $target))))
            Http::response(403, __('Permission denied'));

        $errors = array();
        $info = array(
                ':title' => sprintf(__('Ticket #%s: %s'),
                    $ticket->getNumber(),
                    __('Refer')
                    ),
                ':action' => sprintf('#tickets/%d/refer%s',
                    $ticket->getId(),
                    ($target  ? "/$target": '')),
                );

        if ($_POST) {
            switch ($_POST['do']) {
                case 'refer':
                    if ($form->isValid() && $ticket->refer($form, $errors)) {
                        $clean = $form->getClean();
                        if ($clean['comments'])
                            $ticket->logNote('Referral', $clean['comments'], $thisstaff);
                        $_SESSION['::sysmsgs']['msg'] = sprintf(
                                __('%s successfully'),
                                sprintf(
                                    __('%s referred to %s'),
                                    sprintf(__('Ticket #%s'),
                                         sprintf('<a href="tickets.php?id=%d"><b>%s</b></a>',
                                             $ticket->getId(),
                                             $ticket->getNumber()))
                                    ,
                                    $form->getReferee())
                                );
                        Http::response(201, $ticket->getId());
                    }

                    $form->addErrors($errors);
                    $info['error'] = $errors['err'] ?: __('Unable to refer ticket');
                    break;
                case 'manage':
                    $remove = array();
                    if (is_array($_POST['referrals'])) {
                        $remove = array();
                        foreach ($_POST['referrals'] as $k => $v)
                            if ($v[0] == '-')
                                $remove[] = substr($v, 1);
                        if (count($remove)) {
                            $num = $ticket->thread->referrals
                                ->filter(array('id__in' => $remove))
                                ->delete();
                            if ($num) {
                                $info['msg'] = sprintf(
                                        __('%s successfully'),
                                        sprintf(__('Removed %d referrals'),
                                            $num
                                            )
                                        );
                            }
                            //TODO: log removal
                        }
                    }
                    break;
                default:
                     $errors['err'] = __('Unknown Action');
            }
        }

        $thread = $ticket->getThread();
        include STAFFINC_DIR . 'templates/refer.tmpl.php';
    }

    function editField($tid, $fid) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid)))
            Http::response(404, __('No such ticket'));
        elseif (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_EDIT))
            Http::response(403, __('Permission denied'));
        elseif (!($field=$ticket->getField($fid)))
            Http::response(404, __('No such field'));

        $errors = array();
        $info = array(
                ':title' => sprintf(__('Ticket #%s: %s %s'),
                  $ticket->getNumber(),
                  __('Update'),
                  $field->getLabel()
                  ),
              ':action' => sprintf('#tickets/%d/field/%s/edit',
                  $ticket->getId(), $field->getId())
              );

        $form = $field->getEditForm($_POST);
        if ($_POST && $form->isValid()) {

            if ($ticket->updateField($form, $errors)) {
                $msg = sprintf(
                      __('%s successfully'),
                      sprintf(
                          __('%s updated'),
                          __($field->getLabel())
                          )
                      );

                switch (true) {
                    case $field instanceof DateTime:
                    case $field instanceof DatetimeField:
                        $clean = Format::datetime((string) $field->getClean());
                        break;
                    case $field instanceof FileUploadField:
                        $field->save();
                        $answer =  $field->getAnswer();
                        $clean = $answer->display() ?: '&mdash;' . __('Empty') .  '&mdash;';
                        break;
                    case $field instanceof DepartmentField:
                        $clean = (string) Dept::lookup($field->getClean());
                        break;
                    default:
                        $clean =  $field->getClean();
                        $clean = is_array($clean) ? implode($clean, ',') :
                            (string) $clean;
                        if (strlen($clean) > 200)
                             $clean = Format::truncate($clean, 200);
                }

                $clean = is_array($clean) ? $clean[0] : $clean;
                Http::response(201, $this->json_encode(['value' =>
                            $clean ?: '&mdash;' . __('Empty') .  '&mdash;',
                            'id' => $fid, 'msg' => $msg]));
            }

            $form->addErrors($errors);
            $info['error'] = $errors['err'] ?: __('Unable to update field');
        }

        include STAFFINC_DIR . 'templates/field-edit.tmpl.php';
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
                $msg = sprintf(
                        __('%s successfully'),
                        sprintf(
                            __('%s assigned to %s'),
                            __('Ticket'),
                            $form->getAssignee())
                        );

                $assignee =  $ticket->isAssigned() ? Format::htmlchars(implode('/', $ticket->getAssignees())) :
                                            '<span class="faded">&mdash; '.__('Unassigned').' &mdash;';
                Http::response(201, $this->json_encode(['value' =>
                    $assignee, 'id' => 'assign', 'msg' => $msg]));
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

    function release($tid) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid)))
            Http::response(404, __('No such ticket'));

        if (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_RELEASE) && !$thisstaff->isManager())
            Http::response(403, __('Permission denied'));

        $errors = array();
        if (!$ticket->isAssigned())
            $errors['err'] = __('Ticket is not assigned!');

        $info = array(':title' => sprintf(__('Ticket #%s: %s'),
                    $ticket->getNumber(),
                    __('Release Confirmation')));

        $form = ReleaseForm::instantiate($_POST);
        $hasData = ($_POST['sid'] || $_POST['tid']);

        $staff = $ticket->getStaff();
        $team = $ticket->getTeam();
        if ($_POST) {
            if ($hasData && $ticket->release($_POST, $errors)) {
                $data = array();

                if ($staff && !$ticket->getStaff())
                    $data['staff'] = array($staff->getId(), (string) $staff->getName()->getOriginal());
                if ($team && !$ticket->getTeam())
                    $data['team'] = $team->getId();
                $ticket->logEvent('released', $data);

                $comments = $form->getComments();
                if ($comments) {
                    $title = __('Assignment Released');
                    $_errors = array();

                    $ticket->postNote(
                        array('note' => $comments, 'title' => $title),
                        $_errors, $thisstaff, false);
                }

                $_SESSION['::sysmsgs']['msg'] = __('Ticket assignment released successfully');
                Http::response(201, $ticket->getId());
            }

            if (!$hasData)
                $errors['err'] = __('Please check an assignee to release assignment');

            $form->addErrors($errors);
            $info['error'] = $errors['err'] ?: __('Unable to release ticket assignment');
        }

        if($errors && $errors['err'])
            $info['error'] = $errors['err'] ?: __('Unable to release ticket');

        include STAFFINC_DIR . 'templates/release.tmpl.php';
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
                'refer' => array(
                    'verbed' => __('referred'),
                    ),
                'merge' => array(
                    'verbed' => __('merged'),
                    ),
                'link' => array(
                    'verbed' => __('linked'),
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
        case 'merge':
        case 'link':
            $inc = 'merge-tickets.tmpl.php';
            $ticketIds = $_GET ? explode(',', $_GET['tids']) : explode(',', $_POST['tids']);
            $tickets = array();
            $title = strpos($_SERVER['PATH_INFO'], 'link') !== false ? 'link' : 'merge';
            $eventName = ($title && $title == 'link') ? 'linked' : 'merged';
            $permission = ($title && $title == 'link') ? (Ticket::PERM_LINK) : (Ticket::PERM_MERGE);
            $hasPermission = array();
            $parent = false;

            $tickets = Ticket::objects()
                ->filter(array('ticket_id__in'=>$ticketIds))
                ->values_flat('ticket_id', 'flags', 'dept_id', 'ticket_pid');
            foreach ($tickets as $ticket) {
                list($ticket_id, $flags, $dept_id, $ticket_pid) = $ticket;
                $mergeType = Ticket::getMergeTypeByFlag($flags);
                $isParent = Ticket::isParent($flags);
                $role = $thisstaff->getRole($dept_id);
                $hasPermission[] = $role->hasPerm($permission);

                if (!$ticket)
                    continue;
                elseif (!$parent && $isParent && $mergeType != 'visual') {
                    $parent = $ticket;
                    $parentMergeType = $mergeType;
                }

                if ($mergeType != 'visual' && $title == 'link')
                    $info['error'] = sprintf(
                            __('One or more Tickets selected is part of a merge. Merged Tickets cannot be %s.'),
                            __($eventName)
                            );

                if ($parent && ($isParent && $mergeType != 'visual') && $parent[0] != $ticket_id)
                    $info['error'] = sprintf(
                            __('More than one Parent Ticket selected. %1$s cannot be %2$s.'),
                            _N('The selected Ticket', 'The selected Tickets', $count),
                            __($eventName)
                            );

                if ($ticket_pid && $mergeType != 'visual' && $title == 'merge')
                    $info['error'] = sprintf(
                            __('One or more Tickets selected is a merged child. %1$s cannot be %2$s.'),
                            _N('The selected Ticket', 'The selected Tickets', $count),
                            __($eventName)
                            );
            }
            //move parent ticket to top of list
            if (count($ticketIds) > 1) {
                $ticketIdsSorted = $ticketIds;
                if ($parent && $parentMergeType != 'visual') {
                    foreach ($ticketIdsSorted as $key => $value) {
                        if ($value == $parent[0]) {
                            unset($ticketIdsSorted[$key]);
                            array_unshift($ticketIdsSorted, $value);
                            array_unshift($ticketIdsSorted, new SqlField('ticket_id'));
                        }
                    }
                }

                $expr = call_user_func_array(array('SqlFunction', 'FIELD'), $ticketIdsSorted);
            }
            $tickets = Ticket::objects()
                 ->filter(array('ticket_id__in'=>$ticketIds))
                 ->values_flat('ticket_id', 'number', 'ticket_pid', 'sort', 'thread__id',
                               'user_id', 'cdata__subject', 'user__name', 'flags')
                 ->annotate(array('tasks' => SqlAggregate::COUNT('tasks__id', true),
                                  'collaborators' => SqlAggregate::COUNT('thread__collaborators__id'),
                                  'entries' => SqlAggregate::COUNT('thread__entries__id'),))
                 ->order_by($expr ?: 'sort');
            $ticket = Ticket::lookup($parent[0] ?: $ticket[0]);

            // Generic permission check.
            if (in_array(false, $hasPermission) && !$ticket->getThread()->isReferred()) {
                $info['error'] = sprintf(
                        __('You do not have permission to %1$s %2$s'),
                        __($title),
                        _N('the selected Ticket', 'the selected Tickets', $count));
                $info = array_merge($info, Format::htmlchars($_POST));
            } else
                $info['action'] = sprintf('#tickets/%s/merge', $ticket->getId());
            break;
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
                        $tickets = Ticket::objects()
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
                        $all_agent_depts = Dept::objects()->filter(
                            Q::all( array('id__in' => $depts,
                            Q::not(array('flags__hasbit'
                                => Dept::FLAG_ASSIGN_MEMBERS_ONLY)),
                            Q::not(array('flags__hasbit'
                                => Dept::FLAG_ASSIGN_PRIMARY_ONLY))
                            )))->values_flat('id');
                        if (!count($all_agent_depts)) {
                            $members->filter(Q::any( array(
                                        'dept_id__in' => $depts,
                                        Q::all(array(
                                            'dept_access__dept__id__in' => $depts,
                                            Q::not(array('dept_access__dept__flags__hasbit'
                                                => Dept::FLAG_ASSIGN_MEMBERS_ONLY,
                                                'dept_access__dept__flags__hasbit'
                                                    => Dept::FLAG_ASSIGN_PRIMARY_ONLY))
                                            ))
                                        )));
                        }
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

        $role = $ticket->getRole($thisstaff);

        $info = array();
        $state = null;
        switch($status) {
            case 'open':
            case 'reopen':
                $state = 'open';
                break;
            case 'close':
                if (!$role->hasPerm(Ticket::PERM_CLOSE))
                    Http::response(403, 'Access denied');
                $state = 'closed';

                // Check if ticket is closeable
                if (is_string($closeable=$ticket->isCloseable()))
                    $info['warn'] =  $closeable;

                break;
            case 'delete':
                if (!$role->hasPerm(Ticket::PERM_DELETE))
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
        elseif (($role = $ticket->getRole($thisstaff))) {
            // Make sure the agent has permission to set the status
            switch(mb_strtolower($status->getState())) {
                case 'open':
                    if (!$role->hasPerm(Ticket::PERM_CLOSE)
                            && !$role->hasPerm(Ticket::PERM_CREATE))
                        $errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!$role->hasPerm(Ticket::PERM_CLOSE))
                        $errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!$role->hasPerm(Ticket::PERM_DELETE))
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
            $failures = array();
            // Set children statuses (if applicable)
            if ($_REQUEST['children']) {
                $children = $ticket->getChildren();

                foreach ($children as $cid) {
                    $child = Ticket::lookup($cid[0]);
                    if (!$child->setStatus($status, '', $errors))
                        $failures[$cid[0]] = $child->getNumber();
                }
            }

            if (!$failures) {
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
            } else {
                $tickets = array();
                foreach ($failures as $id=>$num) {
                    $tickets[] = sprintf('<a href="tickets.php?id=%d"><b>#%s</b></a>',
                                    $id,
                                    $num);
                }
                $info['warn'] = sprintf(__('Error updating ticket status for %s'),
                                 ($tickets) ? implode(', ', $tickets) : __('child tickets')
                                 );
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
                if (!$thisstaff->hasPerm(Ticket::PERM_CLOSE, false))
                    Http::response(403, 'Access denied');
                $state = 'closed';
                break;
            case 'delete':
                if (!$thisstaff->hasPerm(Ticket::PERM_DELETE, false))
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
                    if (!$thisstaff->hasPerm(Ticket::PERM_CLOSE, false)
                            && !$thisstaff->hasPerm(Ticket::PERM_CREATE, false))
                        $errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!$thisstaff->hasPerm(Ticket::PERM_CLOSE, false))
                        $errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!$thisstaff->hasPerm(Ticket::PERM_DELETE, false))
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

    function markAs($tid, $action='') {
        global $thisstaff;

        // Standard validation
        if (!($ticket=Ticket::lookup($tid)))
            Http::response(404, __('No such ticket'));

        if (!$ticket->checkStaffPerm($thisstaff, Ticket::PERM_MARKANSWERED) && !$thisstaff->isManager())
            Http::response(403, __('Permission denied'));

        $errors = array();
        $info = array(':title' => __('Please Confirm'));

        // Instantiate form for comment field
        $form = MarkAsForm::instantiate($_POST);

        // Mark as answered or unanswered
        if ($_POST) {
            switch($action) {
                case 'answered':
                    if($ticket->isAnswered())
                        $errors['err'] = __('Ticket is already marked as answered');
                    elseif (!$ticket->markAnswered())
                        $errors['err'] = __('Cannot mark ticket as answered');
                    break;

                case 'unanswered':
                    if(!$ticket->isAnswered())
                        $errors['err'] = __('Ticket is already marked as unanswered');
                    elseif (!$ticket->markUnAnswered())
                        $errors['err'] - __('Cannot mark ticket as unanswered');
                    break;

                default:
                    Http::response(404, __('Unknown action'));
            }

            // Retrun errors to form (if any)
            if($errors) {
                $info['error'] = $errors['err'] ?: sprintf(__('Unable to mark ticket as %s'), $action);
                $form->addErrors($errors);
            } else {
                // Add comment (if provided)
                $comments = $form->getComments();
                if ($comments) {
                    $title = __(sprintf('Ticket Marked %s', ucfirst($action)));
                    $_errors = array();

                    $ticket->postNote(
                        array('note' => $comments, 'title' => $title),
                        $_errors, $thisstaff, false);
                }

                // Add success messages and log activity
                $_SESSION['::sysmsgs']['msg'] = sprintf(__('Ticket marked as %s successfully'), $action);
                $msg = sprintf(__('Ticket flagged as %s by %s'), $action, $thisstaff->getName());
                $ticket->logActivity(sprintf(__('Ticket Marked %s'), ucfirst($action)), $msg);
                Http::response(201, $ticket->getId());
            }
        }

        include STAFFINC_DIR . 'templates/mark-as.tmpl.php';
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

        // Has Children?
        $info['children'] = ($ticket->getChildren()->count());

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

    function relations($tid) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffPerm($thisstaff))
            Http::response(404, 'Unknown ticket');

         include STAFFINC_DIR . 'ticket-relations.inc.php';
    }

    function addTask($tid, $vars=array()) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid)))
            Http::response(404, 'Unknown ticket');

        if (!$ticket->checkStaffPerm($thisstaff, Task::PERM_CREATE))
            Http::response(403, 'Permission denied');

        $info=$errors=array();

        // Internal form
        $iform = TaskForm::getInternalForm($_POST);
        // Due date must be before tickets due date
        if ($ticket && $ticket->getEstDueDate()
                &&  Misc::db2gmtime($ticket->getEstDueDate()) > Misc::gmtime()
                && ($f=$iform->getField('duedate'))) {
            $f->configure('max', Misc::db2gmtime($ticket->getEstDueDate()));
        }
        $vars = array_merge($_SESSION[':form-data'] ?: array(), $vars);

        if ($_POST) {
            Draft::deleteForNamespace(
                    sprintf('ticket.%d.task', $ticket->getId()),
                    $thisstaff->getId());
            // Default form
            $form = TaskForm::getInstance();
            $form->setSource($_POST);

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
                    $vars['files'] = $attachments->getFiles();
                $vars['staffId'] = $thisstaff->getId();
                $vars['poster'] = $thisstaff;
                $vars['ip_address'] = $_SERVER['REMOTE_ADDR'];
                if (($task=Task::create($vars, $errors))) {

                  if ($_SESSION[':form-data']['eid']) {
                    //add internal note to ticket:
                    $taskLink = sprintf('<a href="tasks.php?id=%d"><b>#%s</b></a>',
                        $task->getId(),
                        $task->getNumber());

                    $entryLink = sprintf('<a href="#entry-%d"><b>%s</b></a>',
                        $_SESSION[':form-data']['eid'],
                        Format::datetime($_SESSION[':form-data']['timestamp']));

                    $note = array(
                            'title' => __('Task Created From Thread Entry'),
                            'body' => sprintf(__(
                                // %1$s is the task ID number and %2$s is the thread
                                // entry date
                                'Task %1$s<br/> Thread Entry: %2$s'),
                                $taskLink, $entryLink)
                            );

                  $ticket->logNote($note['title'], $note['body'], $thisstaff);

                    //add internal note to task:
                    $ticketLink = sprintf('<a href="tickets.php?id=%d"><b>#%s</b></a>',
                        $ticket->getId(),
                        $ticket->getNumber());

                    $note = array(
                            'title' => __('Task Created From Thread Entry'),
                            'note' => sprintf(__('This Task was created from Ticket %1$s'), $ticketLink),
                    );

                    $task->postNote($note, $errors, $thisstaff);
                  }
                }

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
                $attachments = $note_attachments_form->getField('attachments')->getFiles();
                $vars['files'] = array_merge(
                    $vars['files'] ?: array(), $attachments);
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
                $attachments = $reply_attachments_form->getField('attachments')->getFiles();
                $vars['files'] = array_merge(
                    $vars['files'] ?: array(), $attachments);
                if (($response=$task->postReply($vars, $errors))) {
                    $msg=__('Update posted successfully');
                    // Clear attachment list
                    $reply_attachments_form->setSource(array());
                    $reply_attachments_form->getField('attachments')->reset();
                    Draft::deleteForNamespace('task.response.'.$task->getId(),
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


    function export($id) {
        global $thisstaff;

        if (is_numeric($id))
            $queue = SavedSearch::lookup($id);
        else
            $queue = AdhocSearch::load($id);

        return $this->queueExport($queue);
    }

    function queueExport(CustomQueue $queue) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');
        elseif (!$queue || !$queue->checkAccess($thisstaff))
            Http::response(404, 'No such saved queue');

        $errors = array();
        if ($_POST && is_array($_POST['fields'])) {
            // Cache export preferences
            $id = $queue->getId();
            $_SESSION['Export:Q'.$id]['fields'] = $_POST['fields'];
            $_SESSION['Export:Q'.$id]['filename'] = $_POST['filename'];
            $_SESSION['Export:Q'.$id]['delimiter'] = $_POST['delimiter'];
            // Save fields selection if requested
            if ($queue->isSaved() && isset($_POST['save-changes']))
               $queue->updateExports(array_flip($_POST['fields']));

            // Filename of the report
            if (isset($_POST['filename'])
                    && ($parts = pathinfo($_POST['filename']))) {
                $filename = $_POST['filename'];
                if (strcasecmp($parts['extension'], 'csv'))
                      $filename ="$filename.csv";
            } else {
                $filename = sprintf('%s Tickets-%s.csv',
                        $queue->getName(),
                        strftime('%Y%m%d'));
            }

            try {
                $interval = 5;
                $options = ['filename' => $filename,
                    'interval' => $interval];
                // Create desired exporter
                $exporter = new CsvExporter($options);
                // Acknowledge the export
                $exporter->ack();
                // Phew... now we're free to do the export
                // Ask the queue to export to the exporter
                $queue->export($exporter);
                $exporter->finalize();
                // Email the export if it exists
                $exporter->email($thisstaff);
                // Delete the file.
                @$exporter->delete();
                exit;
            } catch (Exception $ex) {
                $errors['err'] = __('Unable to prepare the export');
            }
        }

        include STAFFINC_DIR . 'templates/queue-export.tmpl.php';

    }
}
?>
