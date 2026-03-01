<?php
/*********************************************************************
    ajax.orgs.php

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('403');

require_once INCLUDE_DIR . 'class.organization.php';
include_once(INCLUDE_DIR.'class.ticket.php');
require_once INCLUDE_DIR.'ajax.tickets.php';

class OrgsAjaxAPI extends AjaxController {

    function search($type = null) {

        if(!isset($_REQUEST['q'])) {
            Http::response(400, 'Query argument is required');
        }

        if (!$_REQUEST['q'])
            return $this->json_encode(array());

        $q = $_REQUEST['q'];
        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;

        if (strlen(Format::searchable($q)) < 3)
            return $this->encode(array());

        $orgs = Organization::objects()
            ->values_flat('id', 'name')
            ->limit($limit);

        global $ost;
        $orgs = $ost->searcher->find($q, $orgs);
        $orgs->order_by(new SqlCode('__relevance__'), QuerySet::DESC)
            ->distinct('id');

        if (!count($orgs) && preg_match('`\w$`u', $q)) {
            // Do wildcard full-text search
            $_REQUEST['q'] = $q."*";
            return $this->search($type);
        }

        $matched = array();
        foreach ($orgs as $O) {
            list($id, $name) = $O;
            $matched[] = array('name' => Format::htmlchars($name), 'info' => $name,
                'id' => $id, '/bin/true' => $_REQUEST['q']);
        }

        return $this->json_encode(array_values($matched));

    }

    function editOrg($id) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(Organization::PERM_EDIT))
            Http::response(403, 'Permission Denied');
        elseif(!($org = Organization::lookup($id)))
            Http::response(404, 'Unknown organization');

        $info = array(
            'title' => sprintf(__('Update %s'), $org->getName())
        );

        $forms = $org->getForms();
        $action = "#orgs/{$org->id}/profile";

        include(STAFFINC_DIR . 'templates/org-profile.tmpl.php');
    }

    function updateOrg($id, $profile=false) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(Organization::PERM_EDIT))
            Http::response(403, 'Permission Denied');
        elseif(!($org = Organization::lookup($id)))
            Http::response(404, 'Unknown organization');

        $errors = array();
        if ($profile) {
            if ($org->updateProfile($_POST, $errors))
                Http::response(201, $org->to_json(), 'application/json');
        } elseif ($org->update($_POST, $errors))
             Http::response(201, $org->to_json(), 'application/json');

        $forms = $org->getForms();

        if ($profile) {
            $action = "#orgs/{$org->id}/profile";
            include(STAFFINC_DIR . 'templates/org-profile.tmpl.php');
        }
        else {
            $action = "#orgs/{$org->id}";
            include(STAFFINC_DIR . 'templates/org.tmpl.php');
        }
    }


    function delete($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(Organization::PERM_DELETE))
            Http::response(403, 'Permission Denied');
        elseif (!($org = Organization::lookup($id)))
            Http::response(404, 'Unknown organization');

        $info = array();
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            if ($org->delete())
                 Http::response(204, 'Organization deleted successfully');
            else
                $info['error'] = sprintf('%s - %s', __('Unable to delete organization'), __('Please try again!'));
        }

        include(STAFFINC_DIR . 'templates/org-delete.tmpl.php');
    }

    function addUser($id, $userId=0, $remote=false) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            Http::response(403, 'Permission Denied');
        elseif (!($org = Organization::lookup($id)))
            Http::response(404, 'Unknown organization');

        $info = array();
        $info['title'] = __('Add User');
        $info['action'] = '#orgs/'.$org->getId().'/add-user';
        $info['onselect'] = 'ajax.php/orgs/'.$org->getId().'/add-user/';

        if (!AuthenticationBackend::getSearchDirectories())
            $info['lookup'] = 'local';

        if ($_POST) {
            if ($_POST['id']) { //Existing useer
                if (!($user = User::lookup($_POST['id'])))
                    $info['error'] = __('Unknown user selected');
                elseif ($user->getOrgId() == $org->getId())
                    $info['error'] = sprintf('%s already belongs to the organization',
                            Format::htmlchars($user->getName()));
            } else { //Creating new  user
                $form = UserForm::getUserForm()->getForm($_POST);
                $can_create = $thisstaff->hasPerm(User::PERM_CREATE);
                if (!($user = User::fromForm($form, $can_create)))
                    $info['error'] = sprintf('%s - %s', __('Error adding user'), __('Please try again!'));
            }

            if (!$info['error'] && $user && $user->setOrganization($org))
                Http::response(201, $user->to_json(), 'application/json');
            elseif (!$info['error'])
                $info['error'] = sprintf('%s - %s', __('Unable to add user to the organization'), __('Please try again!'));

        } elseif ($remote && $userId) {
            list($bk, $userId) = explode(':', $userId, 2);
            if (!($backend = AuthenticationBackend::getSearchDirectoryBackend($bk))
                    || !($user_info = $backend->lookup($userId)))
                Http::response(404, 'User not found');

            $form = UserForm::getUserForm()->getForm($user_info);
        } elseif ($userId) //Selected local user
            $user = User::lookup($userId);

        if ($user && $user->getOrgId()) {
            if ($user->getOrgId() == $org->getId())
                $info['warn'] = __('User already belongs to this organization!');
            else
                $info['warn'] = __("Are you sure you want to change the user's organization?");
        }

        ob_start();
        include(STAFFINC_DIR . 'templates/user-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;
    }

    function importUsers($org_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(Organization::PERM_CREATE))
            Http::response(403, 'Permission Denied');
        elseif (!($org = Organization::lookup($org_id)))
            Http::response(404, 'No such organization');

        $info = array(
            'title' => __('Import Users'),
            'action' => "#orgs/$org_id/import-users",
            'upload_url' => "orgs.php?a=import-users",
        );

        if ($_POST) {
            $status = User::importFromPost($_POST['pasted'],
                array('org_id'=>$org_id));
            if (is_string($status))
                $info['error'] = $status;
            else
                Http::response(201, "{\"count\": $status}");
        }
        $info += Format::input($_POST);

        include STAFFINC_DIR . 'templates/user-import.tmpl.php';
    }

    function addOrg() {
        global $thisstaff;

        if (!$thisstaff->hasPerm(Organization::PERM_CREATE))
            Http::response(403, 'Permission Denied');

        $info = array();

        if ($_POST) {
            $form = OrganizationForm::getDefaultForm()->getForm($_POST);
            if (($org = Organization::fromForm($form)))
                Http::response(201, $org->to_json(), 'application/json');

            $info = array('error' =>sprintf('%s - %s', __('Error adding organization'), __('Please try again!')));
        }

        $info['title'] = __('Add New Organization');
        $info['search'] = false;

        return $this->_lookupform($form, $info);
    }

    function lookup() {
        return $this->_lookupform();
    }

    function selectOrg($id) {

        if ($id) $org = Organization::lookup($id);

        $info = array('title' => __('Select Organization'));

        ob_start();
        include(STAFFINC_DIR . 'templates/org-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

    function createNote($id) {
        if (!($org = Organization::lookup($id)))
            Http::response(404, 'Unknown organization');

        require_once INCLUDE_DIR . 'ajax.note.php';
        $ajax = new NoteAjaxAPI();
        return $ajax->createNote('O'.$id);
    }

    function _lookupform($form=null, $info=array()) {

        if (!$info or !$info['title'])
            $info += array('title' => __('Organization Lookup'));

        if ($_POST && ($org = Organization::lookup($_POST['orgid']))) {
            Http::response(201, $org->to_json(), 'application/json');
        }

        ob_start();
        include(STAFFINC_DIR . 'templates/org-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function manageForms($org_id) {
        $forms = DynamicFormEntry::forObject($org_id, 'O');
        $info = array('action' => '#orgs/'.Format::htmlchars($org_id).'/forms/manage');
        include(STAFFINC_DIR . 'templates/form-manage.tmpl.php');
    }

    function updateForms($org_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!$thisstaff->hasPerm(Organization::PERM_EDIT))
            Http::response(403, 'Permission Denied');
        elseif (!($org = Organization::lookup($org_id)))
            Http::response(404, "No such ticket");
        elseif (!isset($_POST['forms']))
            Http::response(422, "Send updated forms list");

        // Add new forms
        $forms = DynamicFormEntry::forObject($org_id, 'O');
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
                $org->addForm($new, $sort);
            }
        }

        // Deleted forms
        foreach ($forms as $idx => $e) {
            if (!in_array($e->get('form_id'), $_POST['forms']))
                $e->delete();
        }

        Http::response(201, 'Successfully managed');
    }

    function exportTickets($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');
        elseif (!$id)
            Http::response(403, __('Organization ID Required'));

        $org = Organization::lookup($id);
        if (!$org)
            Http::response(403, __('Organization Not Found'));

        $queue = $org->getTicketsQueue();

        if ($_POST) {
            $api = new TicketsAjaxAPI();
            return $api->queueExport($queue);
        }

        $info = array('action' => "#orgs/$id/tickets/export");

        include STAFFINC_DIR . 'templates/queue-export.tmpl.php';
    }
}
?>
