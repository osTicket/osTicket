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
        $form = $search->getForm();
        if (isset($_SESSION['advsearch']))
            $form->loadState($_SESSION['advsearch']);
        $matches = Filter::getSupportedMatches();

        include STAFFINC_DIR . 'templates/advanced-search.tmpl.php';
    }

    function addField($name) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');

    }

    function doSearch() {
        global $thisstaff;

        $search = SavedSearch::create();

        // Add "other" fields (via $_POST['other'][])

        $form = $search->getForm($_POST);
        if (!$form->isValid()) {
            $matches = Filter::getSupportedMatches();
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
        if (!$data || !$form->isValid()) {
            Http::response(422, 'Validation errors exist on form');
        }

        $search->config = JsonDataEncoder::encode($form->getState());
        if (isset($_POST['name']))
            $search->title = $_POST['name'];
        if (!$search->save()) {
            Http::response(500, 'Internal error. Unable to update search');
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

        $form = $search->getForm();
        if ($state = JsonDataParser::parse($search->config))
            $form->loadState($state);

        $matches = Filter::getSupportedMatches();
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
