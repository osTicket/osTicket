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

    function getAdvancedSearchDialog() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');

        $search = SavedSearch::create();
        $form = $search->getFormFromSession('advsearch') ?: $search->getForm();
        $matches = self::_getSupportedTicketMatches();

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
            $matches = self::_getSupportedTicketMatches();
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
            Http::response(422, 'Validation errors exist on form');
        }

        $search->config = JsonDataEncoder::encode($form->getState());
        if (isset($_POST['name']))
            $search->title = $_POST['name'];
        elseif ($search->__new__)
            Http::response(400, 'A name is required');
        if (!$search->save()) {
            Http::response(500, 'Internal error. Unable to update search');
        }
        Http::response(201, $this->encode(array(
            'id' => $search->id,
            'title' => $search->title,
        )));
    }

    function _getSupportedTicketMatches() {
        // User information
        $matches = array(
            __('Ticket Built-In') => SavedSearch::getExtendedTicketFields(),
            __('Custom Forms') => array()
        );
        foreach (array('ticket'=>'TicketForm', 'user'=>'UserForm', 'organization'=>'OrganizationForm') as $k=>$F) {
            $form = $F::objects()->one();
            $fields = &$matches[$form->getLocal('title')];
            foreach ($form->getFields() as $f) {
                if (!$f->hasData() || $f->isPresentationOnly())
                    continue;
                $fields[":$k!".$f->get('id')] = __(ucfirst($k)).' / '.$f->getLocal('label');
                /* TODO: Support matches on list item properties
                if (($fi = $f->getImpl()) && $fi->hasSubFields()) {
                    foreach ($fi->getSubFields() as $p) {
                        $fields[":$k.".$f->get('id').'.'.$p->get('id')]
                            = __(ucfirst($k)).' / '.$f->getLocal('label').' / '.$p->getLocal('label');
                    }
                }
                */
            }
        }
        $fields = &$matches[__('Custom Forms')];
        foreach (DynamicForm::objects()->filter(array('type'=>'G')) as $form) {
            foreach ($form->getFields() as $f) {
                if (!$f->hasData() || $f->isPresentationOnly())
                    continue;
                $key = sprintf(':field!%d', $f->get('id'), $f->get('id'));
                $fields[$key] = $form->getLocal('title').' / '.$f->getLocal('label');
            }
        }
        return $matches;
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
        $matches = self::_getSupportedTicketMatches();

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
}
