<?php
/*********************************************************************
    ajax.thread.php

    AJAX interface for thread

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2015 osTicket
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

class ThreadAjaxAPI extends AjaxController {

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


    function addRemoteCollaborator($tid, $bk, $id) {
        global $thisstaff;

        if (!($thread=Thread::lookup($tid))
                || !($object=$thread->getObject())
                || !$object->checkStaffPerm($thisstaff))
            Http::response(404, 'No such thread');
        elseif (!$bk || !$id)
            Http::response(422, 'Backend and user id required');
        elseif (!($backend = StaffAuthenticationBackend::getBackend($bk)))
            Http::response(404, 'User not found');

        $user_info = $backend->lookup($id);
        $form = UserForm::getUserForm()->getForm($user_info);
        $info = array();
        if (!$user_info)
            $info['error'] = __('Unable to find user in directory');

        return self::_addcollaborator($thread, null, $form, $info);
    }

    //Collaborators utils
    function addCollaborator($tid, $uid=0) {
        global $thisstaff;

        if (!($thread=Thread::lookup($tid))
                || !($object=$thread->getObject())
                || !$object->checkStaffPerm($thisstaff))
            Http::response(404, __('No such thread'));


        $user = $uid? User::lookup($uid) : null;

        //If not a post then assume new collaborator form
        if(!$_POST)
            return self::_addcollaborator($thread, $user);

        $user = $form = null;
        if (isset($_POST['id']) && $_POST['id']) { //Existing user/
            $user =  User::lookup($_POST['id']);
        } else { //We're creating a new user!
            $form = UserForm::getUserForm()->getForm($_POST);
            $user = User::fromForm($form);
        }

        $errors = $info = array();
        if ($user) {
            // FIXME: Refuse to add ticket owner??
            if (($c=$thread->addCollaborator($user,
                            array('isactive'=>1), $errors))) {
                $info = array('msg' => sprintf(__('%s added as a collaborator'),
                            Format::htmlchars($c->getName())));
                return self::_collaborators($thread, $info);
            }
        }

        if($errors && $errors['err']) {
            $info +=array('error' => $errors['err']);
        } else {
            $info +=array('error' =>__('Unable to add collaborator.').' '.__('Internal error occurred'));
        }

        return self::_addcollaborator($thread, $user, $form, $info);
    }

    function updateCollaborator($tid, $cid) {
        global $thisstaff;

        if (!($thread=Thread::lookup($tid))
                || !($object=$thread->getObject())
                || !$object->checkStaffPerm($thisstaff))
            Http::response(405, 'No such thread');


        if (!($c=Collaborator::lookup(array(
                            'id' => $cid,
                            'thread_id' => $thread->getId())))
                || !($user=$c->getUser()))
            Http::response(406, 'Unknown collaborator');

        $errors = array();
        if(!$user->updateInfo($_POST, $errors))
            return self::_collaborator($c ,$user->getForms($_POST), $errors);

        $info = array('msg' => sprintf('%s updated successfully',
                    Format::htmlchars($c->getName())));

        return self::_collaborators($thread, $info);
    }

    function viewCollaborator($tid, $cid) {
        global $thisstaff;

        if (!($thread=Thread::lookup($tid))
                || !($object=$thread->getObject())
                || !$object->checkStaffPerm($thisstaff))
            Http::response(404, 'No such thread');


        if (!($collaborator=Collaborator::lookup(array(
                            'id' => $cid,
                            'thread_id' => $thread->getId()))))
            Http::response(404, 'Unknown collaborator');

        return self::_collaborator($collaborator);
    }

    function showCollaborators($tid) {
        global $thisstaff;

        if(!($thread=Thread::lookup($tid))
                || !($object=$thread->getObject())
                || !$object->checkStaffPerm($thisstaff))
            Http::response(404, 'No such thread');

        if ($thread->getCollaborators())
            return self::_collaborators($thread);

        return self::_addcollaborator($thread);
    }

    function previewCollaborators($tid) {
        global $thisstaff;

        if (!($thread=Thread::lookup($tid))
                || !($object=$thread->getObject())
                || !$object->checkStaffPerm($thisstaff))
            Http::response(404, 'No such thread');

        ob_start();
        include STAFFINC_DIR . 'templates/collaborators-preview.tmpl.php';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function _addcollaborator($thread, $user=null, $form=null, $info=array()) {
        global $thisstaff;

        $info += array(
                    'title' => __('Add a collaborator'),
                    'action' => sprintf('#thread/%d/add-collaborator',
                        $thread->getId()),
                    'onselect' => sprintf('ajax.php/thread/%d/add-collaborator/',
                        $thread->getId()),
                    );

        ob_start();
        include STAFFINC_DIR . 'templates/user-lookup.tmpl.php';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function updateCollaborators($tid) {
        global $thisstaff;

        if (!($thread=Thread::lookup($tid))
                || !($object=$thread->getObject())
                || !$object->checkStaffPerm($thisstaff))
            Http::response(404, 'No such thread');

        $errors = $info = array();
        if ($thread->updateCollaborators($_POST, $errors))
            Http::response(201, $this->json_encode(array(
                            'id' => $thread->getId(),
                            'text' => sprintf('Recipients (%d of %d)',
                                $thread->getNumActiveCollaborators(),
                                $thread->getNumCollaborators())
                            )
                        ));

        if($errors && $errors['err'])
            $info +=array('error' => $errors['err']);

        return self::_collaborators($thread, $info);
    }



    function _collaborator($collaborator, $form=null, $info=array()) {
        global $thisstaff;

        $info += array('action' => sprintf('#thread/%d/collaborators/%d',
                    $collaborator->thread_id, $collaborator->getId()));

        $user = $collaborator->getUser();

        ob_start();
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function _collaborators($thread, $info=array()) {

        ob_start();
        include(STAFFINC_DIR . 'templates/collaborators.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
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
}
?>
