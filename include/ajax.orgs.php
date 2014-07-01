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

include_once(INCLUDE_DIR.'class.ticket.php');

class OrgsAjaxAPI extends AjaxController {

    function search($type = null) {

        if(!isset($_REQUEST['q'])) {
            Http::response(400, 'Query argument is required');
        }

        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $orgs=array();

        $escaped = db_input(strtolower($_REQUEST['q']), false);
        $sql='SELECT DISTINCT org.id, org.name '
            .' FROM '.ORGANIZATION_TABLE.' org '
            .' LEFT JOIN '.FORM_ENTRY_TABLE.' entry ON (entry.object_type=\'O\' AND entry.object_id = org.id)
               LEFT JOIN '.FORM_ANSWER_TABLE.' value ON (value.entry_id=entry.id) '
            .' WHERE org.name LIKE \'%'.$escaped.'%\' OR value.value LIKE \'%'.$escaped.'%\''
            .' ORDER BY org.created '
            .' LIMIT '.$limit;

        if(($res=db_query($sql)) && db_num_rows($res)){
            while(list($id, $name)=db_fetch_row($res)) {
                $orgs[] = array('name' => Format::htmlchars($name), 'info' => $name,
                    'id' => $id, '/bin/true' => $_REQUEST['q']);
            }
        }

        return $this->json_encode(array_values($orgs));

    }

    function editOrg($id) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif(!($org = Organization::lookup($id)))
            Http::response(404, 'Unknown organization');

        $info = array(
            'title' => sprintf('Update %s', $org->getName())
        );

        $forms = $org->getForms();
        $action = "#orgs/{$org->id}/profile";

        include(STAFFINC_DIR . 'templates/org-profile.tmpl.php');
    }

    function updateOrg($id, $profile=false) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif(!($org = Organization::lookup($id)))
            Http::response(404, 'Unknown organization');

        $errors = array();
        if($org->update($_POST, $errors))
             Http::response(201, $org->to_json());

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
        elseif (!($org = Organization::lookup($id)))
            Http::response(404, 'Unknown organization');

        $info = array();
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            if ($org->delete())
                 Http::response(204, 'Organization deleted successfully');
            else
                $info['error'] = 'Unable to delete organization - try again!';
        }

        include(STAFFINC_DIR . 'templates/org-delete.tmpl.php');
    }

    function addUser($id, $userId=0, $remote=false) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!($org = Organization::lookup($id)))
            Http::response(404, 'Unknown organization');

        $info = array();
        $info['title'] = 'Add User';
        $info['action'] = '#orgs/'.$org->getId().'/add-user';
        $info['onselect'] = 'ajax.php/orgs/'.$org->getId().'/add-user/';

        $info['lookup'] = false;
        if (AuthenticationBackend::getSearchDirectories())
            $info['lookup'] = 'remote';

        if ($_POST) {
            if ($_POST['id']) { //Existing useer
                if (!($user = User::lookup($_POST['id'])))
                    $info['error'] = 'Unknown user selected';
                elseif ($user->getOrgId() == $org->getId())
                    $info['error'] = sprintf('%s already belongs to the organization',
                            Format::htmlchars($user->getName()));
            } else { //Creating new  user
                $form = UserForm::getUserForm()->getForm($_POST);
                if (!($user = User::fromForm($form)))
                    $info['error'] = 'Error adding user - try again!';
            }

            if (!$info['error'] && $user && $user->setOrganization($org))
                Http::response(201, $user->to_json());
            elseif (!$info['error'])
                $info['error'] = 'Unable to add user to the organization - try again';

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
                $info['warn'] = 'User already belongs to this organization!';
            else
                $info['warn'] = "Are you sure you want to change the user's organization?";
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
        elseif (!($org = Organization::lookup($org_id)))
            Http::response(404, 'No such organization');

        $info = array(
            'title' => 'Import Users',
            'action' => "#orgs/$org_id/import-users",
            'upload_url' => "orgs.php?a=import-users",
        );

        if ($_POST) {
            $status = User::importFromPost($_POST['pasted']);
            if (is_string($status))
                $info['error'] = $status;
            else
                Http::response(201, "{\"count\": $status}");
        }
        $info += Format::input($_POST);

        include STAFFINC_DIR . 'templates/user-import.tmpl.php';
    }

    function addOrg() {

        $info = array();

        if ($_POST) {
            $form = OrganizationForm::getDefaultForm()->getForm($_POST);
            if (($org = Organization::fromForm($form)))
                Http::response(201, $org->to_json());

            $info = array('error' =>'Error adding organization - try again!');
        }

        $info['title'] = 'Add New Organization';
        $info['search'] = false;

        return self::_lookupform($form, $info);
    }

    function lookup() {
        return self::_lookupform();
    }

    function selectOrg($id) {

        if ($id) $org = Organization::lookup($id);

        $info = array('title' => 'Select Organization');

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

    static function _lookupform($form=null, $info=array()) {

        if (!$info or !$info['title'])
            $info += array('title' => 'Organization Lookup');

        ob_start();
        include(STAFFINC_DIR . 'templates/org-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function manageForms($org_id) {
        $forms = DynamicFormEntry::forOrganization($org_id);
        $info = array('action' => '#orgs/'.Format::htmlchars($org_id).'/forms/manage');
        include(STAFFINC_DIR . 'templates/form-manage.tmpl.php');
    }

    function updateForms($org_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($org = Organization::lookup($org_id)))
            Http::response(404, "No such ticket");
        elseif (!isset($_POST['forms']))
            Http::response(422, "Send updated forms list");

        // Add new forms
        $forms = DynamicFormEntry::forOrganization($org_id);
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
}
?>
