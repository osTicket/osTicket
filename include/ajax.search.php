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

        $search = SavedSearch::create(array(
            'root' => 'T',
        ));
        if (isset($_SESSION[$context])) {
            // Use the most recent search
            if (!$key) {
                reset($_SESSION[$context]);
                $key = key($_SESSION[$context]);
            }
            $search->config = $_SESSION[$context][$key];
        }
        $this->_tryAgain($search, $search->getForm());
    }

    function editSearch($id) {
        global $thisstaff;

        $search = SavedSearch::lookup($id);
        if (!$thisstaff)
            Http::response(403, 'Agent login is required');
        elseif (!$search || !$search->checkAccess($thisstaff))
            Http::response(404, 'No such saved search');

        $this->_tryAgain($search, $search->getForm());
    }

    function addField($name) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');

        $search = SavedSearch::create(array('root'=>'T'));
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
        $search = SavedSearch::create(array('root' => 'T'));
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
                while (count($keep) < 5) {
                    list($k, $v) = each($_SESSION[$key]);
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

    function _tryAgain($search, $form, $errors=array()) {
        $matches = $search->getSupportedMatches();
        include STAFFINC_DIR . 'templates/advanced-search.tmpl.php';
    }

    function saveSearch($id) {
        global $thisstaff;

        $search = SavedSearch::lookup($id);
        if (!$thisstaff)
            Http::response(403, 'Agent login is required');
        elseif (!$search || !$search->checkAccess($thisstaff))
            Http::response(404, 'No such saved search');

        if (false === $this->_saveSearch($search))
            return;

        Http::response(200, $this->encode(array(
            'redirect' => 'tickets.php?queue='.Format::htmlchars($search->id),
        )));
    }

    function _saveSearch(SavedSearch $search) {
        $form = $search->getForm($_POST);
        $errors = array();
        if (!$search->update($_POST, $form, $errors)
            || !$search->save()
        ) {
            return $this->_tryAgain($search, $form, $errors);
        }

        if (false === $this->_setupSearch($search, $form)) {
            return false;
        }

        return true;
    }

    function createSearch() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');

        $search = SavedSearch::create(array('root' => 'T'));
        $search->staff_id = $thisstaff->getId();
        if (false === $this->_saveSearch($search))
            return;

        Http::response(200, $this->encode(array(
            'redirect' => 'tickets.php?queue='.Format::htmlchars($search->id),
        )));
    }

    function deleteSearch($id) {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        elseif (!($search = SavedSearch::lookup($id))) {
            Http::response(404, 'No such saved search');
        }
        elseif (!$search->delete()) {
            Http::response(500, 'Unable to delete search');
        }

        Http::response(200, $this->encode(array(
            'id' => $search->id,
            'success' => true,
        )));
    }


    function editColumn($queue_id, $column) {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        elseif (!($queue = CustomQueue::lookup($queue_id))) {
            Http::response(404, 'No such queue');
        }

        $data_form = new QueueDataConfigForm($_POST);
        include STAFFINC_DIR . 'templates/queue-column.tmpl.php';
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
        elseif (!isset($_GET['field']) || !isset($_GET['id']) || !isset($_GET['colid'])) {
            Http::response(400, '`field`, `id`, and `colid` parameters required');
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
        $column = QueueColumn::create(array('id' => $_GET['colid']));
        $condition = new QueueColumnCondition();
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

    function addColumn() {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        elseif (!isset($_GET['field'])) {
            Http::response(400, '`field` parameter is required');
        }

        $field = $_GET['field'];
        // XXX: This method should receive a queue ID or queue root so that
        //      $field can be properly checked
        $fields = SavedSearch::getSearchableFields('Ticket');
        if (!isset($fields[$field])) {
            Http::response(400, 'Not a supported field for this queue');
        }

        // Get the tabbed column configuration
        list($label, $F) = $fields[$field];
        $column = QueueColumn::create(array(
            "id"        => (int) $_GET['id'],
            "heading"   => _S($F->getLabel()),
            "primary"   => $field,
            "width"     => 100,
        ));
        ob_start();
        include STAFFINC_DIR .  'templates/queue-column.tmpl.php';
        $config = ob_get_clean();

        // Send back the goodies
        Http::response(200, $this->encode(array(
            'config' => $config,
            'id' => $column->id,
            'heading' => _S($F->getLabel()),
            'width' => $column->getWidth(),
        )), 'application/json');
    }
}
