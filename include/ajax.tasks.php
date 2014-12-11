<?php
/*********************************************************************
    ajax.tasks.php

    AJAX interface for tasks

    Peter Rotich <peter@osticket.com>
    Copyright (c)  20014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('403');

include_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.ajax.php');
require_once(INCLUDE_DIR.'class.task.php');

class TasksAjaxAPI extends AjaxController {

    function preview($tid) {
        global $thisstaff;

        // TODO: check staff's access.
        if(!$thisstaff || !($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        include STAFFINC_DIR . 'templates/task-preview.tmpl.php';
    }

    function edit($tid) {
        global $thisstaff;

        // TODO: check staff's access.
        if(!$thisstaff || !($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        $info = $errors = array();
        $forms = DynamicFormEntry::forObject($task->getId(),
                ObjectModel::OBJECT_TYPE_TASK);

        if ($_POST) {
            $info = Format::htmlchars($_POST);
            $info['error'] = $errors['err'] ?: __('Coming soon!');
        }

        include STAFFINC_DIR . 'templates/task-edit.tmpl.php';
    }

    function transfer($tid) {
        global $thisstaff;

        // TODO: check staff's access.
        if(!$thisstaff || !($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        $info = $errors = array();
        if ($_POST) {
            if ($task->transfer($_POST,  $errors)) {
                Http::response(201, $task->getId());

            }

            $info = Format::htmlchars($_POST);
            $info['error'] = $errors['err'] ?: __('Unable to transfer task');
        }

        include STAFFINC_DIR . 'templates/task-transfer.tmpl.php';
    }

    function assign($tid) {
        global $thisstaff;

        // TODO: check staff's access.
        if(!$thisstaff || !($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        $info = $errors = array();
        if ($_POST) {
            if ($task->assign($_POST,  $errors)) {
                Http::response(201, $task->getId());

            }

            $info = Format::htmlchars($_POST);
            $info['error'] = $errors['err'] ?: __('Unable to assign task');
        }

        include STAFFINC_DIR . 'templates/task-assign.tmpl.php';
    }

   function delete($tid) {
        global $thisstaff;

        // TODO: check staff's access.
        if(!$thisstaff || !($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        $info = $errors = array();
        if ($_POST) {
            if ($task->delete($_POST,  $errors)) {
                Http::response(201, 0);

            }

            $info = Format::htmlchars($_POST);
            $info['error'] = $errors['err'] ?: __('Unable to delete task');
        }
        $info['placeholder'] = sprintf(__(
                    'Optional reason for deleting %s'),
                __('this task'));
        $info['warn'] = sprintf(__(
                    'Are you sure you want to DELETE %s?'),
                    __('this task'));
        $info['extra'] = sprintf('<strong>%s</strong>',
                    __('Deleted tasks CANNOT be recovered, including any associated attachments.')
                    );

        include STAFFINC_DIR . 'templates/task-delete.tmpl.php';
    }


    function task($tid) {
        global $thisstaff;

        // TODO: check staff's access.
        if (!$thisstaff || !($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        $info=$errors=array();
        $task_note_form = new Form(array(
            'attachments' => new FileUploadField(array('id'=>'attach',
            'name'=>'attach:note',
            'configuration' => array('extensions'=>'')))
            ));

        if ($_POST) {

            switch ($_POST['a']) {
            case 'postnote':
                $vars = $_POST;
                $attachments = $task_note_form->getField('attachments')->getClean();
                $vars['cannedattachments'] = array_merge(
                    $vars['cannedattachments'] ?: array(), $attachments);
                if(($note=$task->postNote($vars, $errors, $thisstaff))) {
                    $msg=__('Note posted successfully');
                    // Clear attachment list
                    $task_note_form->setSource(array());
                    $task_note_form->getField('attachments')->reset();
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
