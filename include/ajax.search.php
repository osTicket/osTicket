<?php
/*********************************************************************
    ajax.search.php

    AJAX interface for searches, queue management, etc.

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('403');

include_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.ajax.php');

class SearchAjaxAPI extends AjaxController {

    function getAdvancedSearchDialog($key=false, $context='advsearch') {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');

        $search = new AdhocSearch(array(
            'root' => 'T',
            'staff_id' => $thisstaff->getId(),
            'parent_id' => @$_GET['parent_id'] ?: 0,
        ));
        if ($search->parent_id) {
            $search->flags |= SavedSearch::FLAG_INHERIT_COLUMNS;
        }

        if (isset($_SESSION[$context]) && $key && $_SESSION[$context][$key])
            $search->config = $_SESSION[$context][$key];

        $this->_tryAgain($search);
    }

    function editSearch($id) {
        global $thisstaff;

        $search = SavedSearch::lookup($id);
        if (!$thisstaff)
            Http::response(403, 'Agent login is required');
        elseif (!$search || !$search->checkAccess($thisstaff))
            Http::response(404, 'No such saved search');

        $this->_tryAgain($search);
    }

    function addField($name) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');

        $search = new SavedSearch(array(
                    'root'=>'T'
                    ));
        $searchable = $search->getSupportedMatches();
        if (!($F = $searchable[$name]))
            Http::response(404, 'No such field: ', print_r($name, true));

        $fields = SavedSearch::getSearchField($F, $name);
        $form = new AdvancedSearchForm($fields);
        // Check the box to search the field by default
        if ($F = $form->getField("{$name}+search"))
            $F->value = true;

        ob_start();
        include STAFFINC_DIR . 'templates/advanced-search-field.tmpl.php';
        $html = ob_get_clean();

        return $this->encode(array(
            'success' => true,
            'html' => $html,
        ));
    }

    function doSearch() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');

        $search = new AdhocSearch(array(
                    'root' => 'T',
                    'staff_id' => $thisstaff->getId()));

        $form = $search->getForm($_POST);
        if (false === $this->_setupSearch($search, $form)) {
            return;
        }

        Http::response(200, $this->encode(array(
            'redirect' => 'tickets.php?queue=adhoc',
        )));
    }

    function _hasErrors(SavedSearch $search, $form) {
        if (!$form->isValid()) {
            $this->_tryAgain($search, $form);
            return true;
        }
    }

    function _setupSearch(SavedSearch $search, $form, $key='advsearch') {
        if ($this->_hasErrors($search, $form))
            return false;

        if ($key) {
            $keep = array();
            // Add in new search to the list of recent searches
            $criteria = $search->isolateCriteria($form->getClean());
            $token = $this->_hashCriteria($criteria);
            $keep[$token] = $criteria;
            // Keep the last 5 recent searches looking from the beginning of
            // the recent search list
            if (isset($_SESSION[$key])) {
                reset($_SESSION[$key]);
                for ($i = 0; $i < 5; $i++) {
                    $k = key($_SESSION[$key]);
                    $v = current($_SESSION[$key]);
                    if (!$k)
                        break;
                    $keep[$k] = $v;
                }
            }
            $_SESSION[$key] = $keep;
        }
    }

    function _hashCriteria($criteria, $size=10) {
        $parts = array();
        foreach ($criteria as $C) {
            list($name, $method, $value) = $C;
            if (is_array($value))
                $value = implode('+', $value);
            $parts[] = "{$name} {$method} {$value}";
        }
        $hash = sha1(implode(' ', $parts), true);
        return substr(
            str_replace(array('+','/','='), '', base64_encode($hash)),
            -$size);
    }

    function _tryAgain($search, $form=null, $errors=array(), $info=array()) {
        if (!$form)
            $form = $search->getForm();
        include STAFFINC_DIR . 'templates/advanced-search.tmpl.php';
    }

    function createSearch() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');


        $search = SavedSearch::create(array(
                    'title' => __('Add Queue'),
                    'root' => 'T',
                    'staff_id' => $thisstaff->getId(),
                    'parent_id' =>  $_GET['pid'],
                    ));
        $this->_tryAgain($search);
    }

    function saveSearch($id=0) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');

        if ($id) { //  update
            if (!($search = SavedSearch::lookup($id))
                    || !$search->checkAccess($thisstaff))
                Http::response(404, 'No such saved search');
        } else { // new search
            $search = SavedSearch::create(array(
                        'root' => 'T',
                        'staff_id' => $thisstaff->getId()
                        ));
        }

        if (false === $this->_saveSearch($search))
            return;

        $info = array(
                'msg' => sprintf('%s %s %s',
                    __('Search'),
                    $id ? __('updated') : __('created'),
                    __('successfully')),
                );
        $this->_tryAgain($search, null, null, $info);
    }

    function _saveSearch(SavedSearch $search) {
        $_POST['queue-name'] = Format::htmlchars($_POST['queue-name']);

        // Validate the form.
        $form = $search->getForm($_POST);
        if ($this->_hasErrors($search, $form))
            return false;

        $errors = array();
        if (!$search->update($_POST, $errors)
                || !$search->save(true)) {

            $form->addError(sprintf(
                        __('Unable to update %s. Correct error(s) below and try again.'),
                        __('queue')));
            $this->_tryAgain($search, $form, $errors);
            return false;
        }

        if (false === $this->_setupSearch($search, $form)) {
            return false;
        }

        return true;
    }

    function editColumn($column_id) {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        elseif (!($column = QueueColumn::lookup($column_id))) {
            Http::response(404, 'No such queue');
        }

        if ($_POST) {
            $data_form = $column->getDataConfigForm($_POST);
            if ($data_form->isValid()) {
                $column->update($_POST, 'Ticket');
                if ($column->save())
                    Http::response(201, 'Successfully updated');
            }
        }

        $root = 'Ticket';
        include STAFFINC_DIR . 'templates/queue-column-edit.tmpl.php';
    }

    function editSort($sort_id) {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        elseif (!($sort = QueueSort::lookup($sort_id))) {
            Http::response(404, 'No such queue sort');
        }

        $data_form = $sort->getDataConfigForm($_POST ?: false);
        if ($_POST) {
            if ($data_form->isValid()) {
                $sort->update($data_form->getClean() + $_POST);
                if ($sort->save())
                    Http::response(201, 'Successfully updated');
            }
        }

        include STAFFINC_DIR . 'templates/queue-sorting-edit.tmpl.php';
    }

    function getQueue($id) {
        global $thisstaff;

        $queue = SavedSearch::lookup($id);
        if (!$thisstaff)
            Http::response(403, 'Agent login is required');
        elseif (!$queue || !$queue->checkAccess($thisstaff))
            Http::response(404, 'No such queue');

        Http::response(200, $this->encode(array(
            'name' => $queue->getName(),
            'criteria' => nl2br(Format::htmlchars($queue->describeCriteria())),
        )));
    }

    function deleteQueue($id) {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        if ($id && (!($queue = CustomQueue::lookup($id)))) {
            Http::response(404, 'No such queue');
        }
        if (!$queue || !$queue->checkAccess($thisstaff)) {
            Http::response(404, 'No such queue');
        }
        if ($_POST) {
            if (!$queue->delete()) {
                Http::response(500, 'Unable to delete queue');
            }
            Http::response(201, 'Have a nice day');
           $_SESSION['::sysmsgs']['msg'] = sprintf(__( 'Successfully deleted%s.'),
                       $queue->getName());
        }

       $info = array(
               ':action' => sprintf('#queue/%s/delete', $queue->getId()),
               ':title' => sprintf('%s %s', __('Please Confirm'), __('Queue Deletion')),
               'warn' => __('Deleted Queues cannot be recovered'),
               ':message' => sprintf('Are you sure you want to delete %s queue?', $queue->getName()),
               ':confirm' => 'Yes, Delete!'
               );

        include STAFFINC_DIR . 'templates/confirm.tmpl.php';
    }

    function previewQueue($id=false) {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        if ($id && (!($queue = CustomQueue::lookup($id)))) {
            Http::response(404, 'No such queue');
        }

        if (!$queue) {
            $queue = CustomQueue::create();
        }

        $queue->update($_POST);

        $form = $queue->getForm($_POST);
        $tickets = $queue->getQuery($form);
        $count = 10; // count($queue->getBasicQuery($form));

        include STAFFINC_DIR . 'templates/queue-preview.tmpl.php';
    }

    function addCondition() {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        elseif (!isset($_GET['field']) || !isset($_GET['id'])
            || !isset($_GET['object_id'])
        ) {
            Http::response(400, '`field`, `id`, and `object_id` parameters required');
        }
        elseif (!is_numeric($_GET['object_id'])) {
            Http::response(400, '`object_id` should be an integer');
        }
        $fields = SavedSearch::getSearchableFields('Ticket');
        if (!isset($fields[$_GET['field']])) {
            Http::response(400, sprintf('%s: No such searchable field'),
                Format::htmlchars($_GET['field']));
        }

        list($label, $field) = $fields[$_GET['field']];
        // Ensure `name` is preserved
        $field_name = $_GET['field'];
        $id = $_GET['id'];
        $object_id = $_GET['object_id'];
        $condition = new QueueColumnCondition(array());
        include STAFFINC_DIR . 'templates/queue-column-condition.tmpl.php';
    }

    function addConditionProperty() {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        elseif (!isset($_GET['prop']) || !isset($_GET['condition'])) {
            Http::response(400, '`prop` and `condition` parameters required');
        }

        $prop = $_GET['prop'];
        $id = $_GET['condition'];
        include STAFFINC_DIR . 'templates/queue-column-condition-prop.tmpl.php';
    }

    function collectQueueCounts($ids=null) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');

        $criteria = array();
        if ($ids && is_array($ids))
            $criteria = array('id__in' => $ids);
        $counts = SavedQueue::counts($thisstaff, true, $criteria);
        Http::response(200, false, 'application/json');
        return $this->encode(Format::number($counts));
    }
}
