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
require_once(INCLUDE_DIR.'class.queue.php');

class SearchAjaxAPI extends AjaxController {

    function getAdvancedSearchDialog() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');

        $search = SavedSearch::create();
        $form = $search->getFormFromSession('advsearch') ?: $search->getForm();
        $matches = SavedSearch::getSupportedTicketMatches();

        include STAFFINC_DIR . 'templates/advanced-search.tmpl.php';
    }

    function addField($name) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');

        @list($type, $id) = explode('!', $name, 2);

        switch (strtolower($type)) {
        case ':ticket':
        case ':user':
        case ':organization':
        case ':field':
            // Support nested field ids for list properties and such
            if (strpos($id, '.') !== false)
                list(,$id) = explode('!', $id, 2);
            if (!($field = DynamicFormField::lookup($id)))
                Http::response(404, 'No such field: ', print_r($id, true));

            $impl = $field->getImpl();
            $impl->set('label', sprintf('%s / %s',
                $field->form->getLocal('title'), $field->getLocal('label')
            ));
            break;

        default:
            $extended = SavedSearch::getExtendedTicketFields();

            if (isset($extended[$name])) {
                $impl = $extended[$name];
                break;
            }
            Http::response(400, 'No such field type');
        }

        $fields = SavedSearch::getSearchField($impl, $name);
        $form = new SimpleForm($fields);
        // Check the box to search the field by default
        if ($F = $form->getField("{$name}+search"))
            $F->value = true;

        ob_start();
        include STAFFINC_DIR . 'templates/advanced-search-field.tmpl.php';
        $html = ob_get_clean();

        return $this->encode(array(
            'success' => true,
            'html' => $html,
            // Send the current formfield UID to be resent with the next
            // addField request and set above
            'ff_uid' => FormField::$uid,
        ));
    }

    function doSearch() {
        global $thisstaff;

        $search = SavedSearch::create();

        $form = $search->getForm($_POST);
        if (!$form->isValid()) {
            $matches = SavedSearch::getSupportedTicketMatches();
            include STAFFINC_DIR . 'templates/advanced-search.tmpl.php';
            return;
        }
        $_SESSION['advsearch'] = $form->getState();

        Http::response(200, $this->encode(array(
            'redirect' => 'tickets.php?advanced',
        )));
    }

    function saveSearch($id) {
        global $thisstaff;

        $search = SavedSearch::lookup($id);
        if (!$search || !$search->checkAccess($thisstaff))
            Http::response(404, 'No such saved search');
        elseif (!$thisstaff)
            Http::response(403, 'Agent login is required');

        return self::_saveSearch($search);
    }

    function _saveSearch($search) {
        $data = array();
        foreach ($_POST['form'] as $id=>$info) {
            $name = $info['name'];
            if (substr($name, -2) == '[]')
                $data[substr($name, 0, -2)][] = $info['value'];
            else
                $data[$name] = $info['value'];
        }
        $form = $search->getForm($data);
        $form->setSource($data);
        if (!$data || !$form->isValid()) {
            Http::response(422, 'Validation errors exist on criteria');
        }

        $search->config = JsonDataEncoder::encode($form->getState());
        if (isset($_POST['name']))
            $search->title = Format::htmlchars($_POST['name']);
        elseif ($search->__new__)
            Http::response(400, 'A name is required');
        if (!$search->save()) {
            Http::response(500, 'Unable to update search. Internal error occurred');
        }
        Http::response(201, $this->encode(array(
            'id' => $search->id,
            'title' => $search->title,
        )));
    }

    function createSearch() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');

        $search = SavedSearch::create();
        $search->staff_id = $thisstaff->getId();
        return self::_saveSearch($search);
    }

    function loadSearch($id) {
        global $thisstaff;

        if (!$thisstaff) {
            Http::response(403, 'Agent login is required');
        }
        elseif (!($search = SavedSearch::lookup($id))) {
            Http::response(404, 'No such saved search');
        }

        if ($state = JsonDataParser::parse($search->config)) {
            $form = $search->loadFromState($state);
            $form->loadState($state);
        }
        $matches = SavedSearch::getSupportedTicketMatches();

        include STAFFINC_DIR . 'templates/advanced-search.tmpl.php';
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
