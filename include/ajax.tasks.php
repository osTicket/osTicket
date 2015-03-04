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

        // No perm. check -- preview allowed for staff
        // XXX: perhaps force preview via parent object?
        if(!$thisstaff || !($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        include STAFFINC_DIR . 'templates/task-preview.tmpl.php';
    }

    function edit($tid) {
        global $thisstaff;

        if(!($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        if (!$task->checkStaffPerm($thisstaff, Task::PERM_EDIT))
            Http::response(403, __('Permission Denied'));

        $info = $errors = array();
        $forms = DynamicFormEntry::forObject($task->getId(),
                ObjectModel::OBJECT_TYPE_TASK);

        if ($_POST) {
            $info = Format::htmlchars($_POST);
            $info['error'] = $errors['err'] ?: __('Coming soon!');
        }

        include STAFFINC_DIR . 'templates/task-edit.tmpl.php';
    }

    function massProcess($action)  {
        global $thisstaff;

        $actions = array(
                'transfer' => array(
                    'verbed' => __('transferred'),
                    ),
                'assign' => array(
                    'verbed' => __('assigned'),
                    ),
                'delete' => array(
                    'verbed' => __('deleted'),
                    ),
                );

        if (!isset($actions[$action]))
            Http::response(404, __('Unknown action'));


        $errors = $e = array();
        $inc = null;
        $i = $count = 0;
        if ($_POST) {
            if (!$_POST['tids'] || !($count=count($_POST['tids'])))
                $errors['err'] = sprintf(
                        __('You must select at least %s.'),
                        __('one task'));
        } else {
            $count  =  $_REQUEST['count'];
        }

        switch ($action) {
        case 'assign':
            $inc = 'task-assign.tmpl.php';
            if ($_POST && !$errors) {
                if (!isset($_POST['staff_id']) || !is_numeric($_POST['staff_id']))
                    $errors['staff_id'] = __('Assignee selection required');
                else {
                    foreach ($_POST['tids'] as $tid) {
                        if (($t=Task::lookup($tid))
                                && $t->getDeptId() != $_POST['dept_id']
                                // Make sure the agent is allowed to
                                // access and assign the task.
                                && $t->checkStaffPerm($thisstaff, Task::PERM_ASSIGN)
                                // Do the transfer
                                && $t->assign($_POST, $e)
                                )
                            $i++;
                    }

                    if (!$i) {
                        $info['error'] = sprintf(
                                __('Unable to %1$s %2$s'),
                                __('assign'),
                                _N('selected task', 'selected tasks', $count));
                    }
                }
            }
            break;
        case 'transfer':
            $inc = 'task-transfer.tmpl.php';
            if ($_POST && !$errors) {
                if (!isset($_POST['dept_id']) || !is_numeric($_POST['dept_id']))
                    $errors['dept_id'] = __('Department selection required');
                else {
                    foreach ($_POST['tids'] as $tid) {
                        if (($t=Task::lookup($tid))
                                && $t->getDeptId() != $_POST['dept_id']
                                // Make sure the agent is allowed to
                                // access and transfer the task.
                                && $t->checkStaffPerm($thisstaff, Task::PERM_TRANSFER)
                                // Do the transfer
                                && $t->transfer($_POST, $e)
                                )
                            $i++;
                    }

                    if (!$i) {
                        $info['error'] = sprintf(
                                __('Unable to %1$s %2$s'),
                                __('transfer'),
                                _N('selected task', 'selected tasks', $count));
                    }
                }
            }
            break;
        case 'delete':
            $inc = 'task-delete.tmpl.php';
            $info[':placeholder'] = sprintf(__(
                        'Optional reason for deleting %s'),
                    _N('selected task', 'selected tasks', $count));
            $info['warn'] = sprintf(__(
                        'Are you sure you want to DELETE %s?'),
                    _N('selected task', 'selected tasks', $count));
            $info[':extra'] = sprintf('<strong>%s</strong>',
                        __('Deleted tasks CANNOT be recovered, including any associated attachments.')
                        );

            if ($_POST && !$errors) {
                foreach ($_POST['tids'] as $tid) {
                    if (($t=Task::lookup($tid))
                            && $t->getDeptId() != $_POST['dept_id']
                            && $t->checkStaffPerm($thisstaff, Task::PERM_DELETE)
                            && $t->delete($_POST, $e)
                            )
                        $i++;
                }

                if (!$i) {
                    $info['error'] = sprintf(
                            __('Unable to %1$s %2$s'),
                            __('delete'),
                            _N('selected task', 'selected tasks', $count));
                }
            }
            break;
        default:
            Http::response(404, __('Unknown action'));
        }


        if ($_POST && $i) {

            // Assume success
            if ($i==$count) {
                $msg = sprintf(__('Successfully %s %s.'),
                        $actions[$action]['verbed'],
                        sprintf(__('%1$d %2$s'),
                            $count,
                            _N('selected task', 'selected tasks', $count))
                        );
                $_SESSION['::sysmsgs']['msg'] = $msg;
            } else {
                $warn = sprintf(
                        __('%1$d of %2$d %3$s %4$s'), $i, $count,
                        _N('selected task', 'selected tasks',
                            $count),
                        $actions[$action]['verbed']);
                $_SESSION['::sysmsgs']['warn'] = $warn;
            }
            Http::response(201, 'processed');
        } elseif($_POST && !isset($info['error'])) {
            $info['error'] = $errors['err'] ?: sprintf(
                    __('Unable to %1$s  %2$s'),
                    $actions[$action]['verbed'],
                    _N('selected task', 'selected tasks', $count));
        }

        if ($_POST)
            $info = array_merge($info, Format::htmlchars($_POST));


        include STAFFINC_DIR . "templates/$inc";
        //  Copy checked tasks to the form.
        echo "
        <script type=\"text/javascript\">
        $(function() {
            $('form#tasks input[name=\"tids[]\"]:checkbox:checked')
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

    function transfer($tid) {
        global $thisstaff;

        if(!($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        if (!$task->checkStaffPerm($thisstaff, Task::PERM_TRANSFER))
            Http::response(403, __('Permission Denied'));

        $errors = array();

        $info = array(
                ':title' => sprintf(__('Task #%s: %s'),
                    $task->getNumber(),
                    __('Tranfer')),
                ':action' => sprintf('#tasks/%d/transfer',
                    $task->getId())
                );

        if ($_POST) {
            if ($task->transfer($_POST, $errors)) {
                $_SESSION['::sysmsgs']['msg'] = sprintf(
                        __('%s successfully'),
                        sprintf(
                            __('%s transferred to %s department'),
                            __('Task'),
                            $task->getDept()
                            )
                        );
                Http::response(201, $task->getId());
            }

            $info = array_merge($info, Format::htmlchars($_POST));
            $info['error'] = $errors['err'] ?: __('Unable to transfer task');
        }

        $info['dept_id'] = $info['dept_id'] ?: $task->getDeptId();

        include STAFFINC_DIR . 'templates/task-transfer.tmpl.php';
    }

    function assign($tid) {
        global $thisstaff;

        if (!($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        if (!$task->checkStaffPerm($thisstaff, Task::PERM_ASSIGN))
            Http::response(403, __('Permission Denied'));

        $errors = array();
        $info = array(
                ':title' => sprintf(__('Task #%s: %s'),
                    $task->getNumber(),
                    $task->isAssigned() ? __('Reassign') :  __('Assign')),
                ':action' => sprintf('#tasks/%d/assign',
                    $task->getId()),
                );
        if ($_POST) {
            if ($task->assign($_POST,  $errors)) {
                $_SESSION['::sysmsgs']['msg'] = sprintf(
                        __('%s successfully'),
                        sprintf(
                            __('%s assigned to %s'),
                            __('Task'),
                            $task->getStaff()
                            )
                        );

                Http::response(201, $task->getId());
            }

            $info = array_merge($info, Format::htmlchars($_POST));
            $info['error'] = $errors['err'] ?: __('Unable to assign task');
        }

        $info['staff_id'] = $info['staff_id'] ?: $task->getStaffId();

        include STAFFINC_DIR . 'templates/task-assign.tmpl.php';
    }

   function delete($tid) {
        global $thisstaff;

        if(!($task=Task::lookup($tid)))
            Http::response(404, __('No such task'));

        if (!$task->checkStaffPerm($thisstaff, Task::PERM_DELETE))
            Http::response(403, __('Permission Denied'));

        $errors = array();
        $info = array(
                ':title' => sprintf(__('Task #%s: %s'),
                    $task->getNumber(),
                    __('Delete')),
                ':action' => sprintf('#tasks/%d/delete',
                    $task->getId()),
                );

        if ($_POST) {
            if ($task->delete($_POST,  $errors)) {
                $_SESSION['::sysmsgs']['msg'] = sprintf(
                            __('%s #%s deleted successfully'),
                            __('Task'),
                            $task->getNumber(),
                            $task->getDept());
                Http::response(201, 0);
            }
            $info = array_merge($info, Format::htmlchars($_POST));
            $info['error'] = $errors['err'] ?: __('Unable to delete task');
        }
        $info[':placeholder'] = sprintf(__(
                    'Optional reason for deleting %s'),
                __('this task'));
        $info['warn'] = sprintf(__(
                    'Are you sure you want to DELETE %s?'),
                    __('this task'));
        $info[':extra'] = sprintf('<strong>%s</strong>',
                    __('Deleted tasks CANNOT be recovered, including any associated attachments.')
                    );

        include STAFFINC_DIR . 'templates/task-delete.tmpl.php';
    }


    function task($tid) {
        global $thisstaff;

        if (!($task=Task::lookup($tid))
                || !$task->checkStaffPerm($thisstaff))
            Http::response(404, __('No such task'));

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
