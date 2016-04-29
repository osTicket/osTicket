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

        $search = new SavedSearch(array(
            'root' => 'T',
            'parent_id' => @$_GET['parent_id'] ?: 0,
        ));
        if ($search->parent_id) {
            $search->flags |= SavedSearch::FLAG_INHERIT_COLUMNS;
        }
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

        $search = new SavedSearch(array('root'=>'T'));
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
        $search = new SavedSearch(array('root' => 'T'));
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
        if (!$search->update($_POST, $errors)
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

        if ($_POST) {
            $data_form = $sort->getDataConfigForm($_POST);
            if ($data_form->isValid()) {
                $sort->update($data_form->getClean() + $_POST);
                if ($sort->save())
                    Http::response(201, 'Successfully updated');
            }
        }

        include STAFFINC_DIR . 'templates/queue-sorting-edit.tmpl.php';
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
        $column = new QueueColumn(array('id' => $_GET['colid']));
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

    function collectQueueCounts($ids=null) {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }

        $queues = CustomQueue::objects()
            ->filter(Q::any(array(
                'flags__hasbit' => CustomQueue::FLAG_PUBLIC,
                'staff_id' => $thisstaff->getId(),
            )));

        if ($ids && is_array($ids))
            $queues->filter(array('id__in' => $ids));

        $query = Ticket::objects();

        // Visibility contraints ------------------
        // TODO: Consider SavedSearch::ignoreVisibilityConstraints()

        // -- Open and assigned to me
        $assigned = Q::any(array(
            'staff_id' => $thisstaff->getId(),
        ));
        // -- Open and assigned to a team of mine
        if ($teams = array_filter($thisstaff->getTeams()))
            $assigned->add(array('team_id__in' => $teams));

        $visibility = Q::any(new Q(array('status__state'=>'open', $assigned)));

        // -- Routed to a department of mine
        if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
            $visibility->add(array('dept_id__in' => $depts));

        $query->filter($visibility);

        foreach ($queues as $queue) {
            $Q = $queue->getBasicQuery();
            if (count($Q->extra) || $Q->isWindowed()) {
                // XXX: This doesn't work
                $query->annotate(array(
                    'q'.$queue->id => $Q->values_flat()
                        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')))
                ));
            }
            else {
                $expr = SqlCase::N()->when(new SqlExpr(new Q($Q->constraints)), 1);
                $query->aggregate(array(
                    'q'.$queue->id => SqlAggregate::COUNT($expr)
                ));
            }
        }

        Http::response(200, false, 'application/json');
        return $this->encode($query->values()->one());
    }
}
