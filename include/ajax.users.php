<?php
/*********************************************************************
    ajax.users.php

    AJAX interface for  users (based on submitted tickets)
    XXX: osTicket doesn't support user accounts at the moment.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('403');

include_once(INCLUDE_DIR.'class.ticket.php');
require_once INCLUDE_DIR.'class.note.php';

class UsersAjaxAPI extends AjaxController {

    /* Assumes search by emal for now */
    function search($type = null) {

        if(!isset($_REQUEST['q'])) {
            Http::response(400, 'Query argument is required');
        }

        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $users=array();
        $emails=array();

        if (!$type || !strcasecmp($type, 'remote')) {
            foreach (AuthenticationBackend::searchUsers($_REQUEST['q']) as $u) {
                $name = "{$u['first']} {$u['last']}";
                $users[] = array('email' => $u['email'], 'name'=>$name,
                    'info' => "{$u['email']} - $name (remote)",
                    'id' => "auth:".$u['id'], "/bin/true" => $_REQUEST['q']);
                $emails[] = $u['email'];
            }
        }

        if (!$type || !strcasecmp($type, 'local')) {
            $remote_emails = ($emails = array_filter($emails))
                ? ' OR email.address IN ('.implode(',',db_input($emails)).') '
                : '';

            $escaped = db_input(strtolower($_REQUEST['q']), false);
            $sql='SELECT DISTINCT user.id, email.address, name '
                .' FROM '.USER_TABLE.' user '
                .' JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id '
                .' LEFT JOIN '.FORM_ENTRY_TABLE.' entry ON (entry.object_type=\'U\' AND entry.object_id = user.id)
                   LEFT JOIN '.FORM_ANSWER_TABLE.' value ON (value.entry_id=entry.id) '
                .' WHERE email.address LIKE \'%'.$escaped.'%\'
                   OR user.name LIKE \'%'.$escaped.'%\'
                   OR value.value LIKE \'%'.$escaped.'%\''.$remote_emails
                .' ORDER BY user.created '
                .' LIMIT '.$limit;

            if(($res=db_query($sql)) && db_num_rows($res)){
                while(list($id,$email,$name)=db_fetch_row($res)) {
                    foreach ($users as $i=>$u) {
                        if ($u['email'] == $email) {
                            unset($users[$i]);
                            break;
                        }
                    }
                    $name = Format::htmlchars($name);
                    $users[] = array('email'=>$email, 'name'=>$name, 'info'=>"$email - $name",
                        "id" => $id, "/bin/true" => $_REQUEST['q']);
                }
            }
        }

        return $this->json_encode(array_values($users));

    }

    function preview($id) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif(!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $info = array(
                'title' => '',
                'useredit' => sprintf('#users/%d/edit', $user->getId()),
                );
        ob_start();
        echo sprintf('<div style="width:650px; padding: 2px 2px 0 5px;"
                id="u%d">', $user->getId());
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
        echo '</div>';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;

    }


    function editUser($id) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif(!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $info = array(
            'title' => sprintf('Update %s', Format::htmlchars($user->getName()))
        );
        $forms = $user->getForms();

        include(STAFFINC_DIR . 'templates/user.tmpl.php');
    }

    function updateUser($id) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif(!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $errors = array();
        if($user->updateInfo($_POST, $errors))
             Http::response(201, $user->to_json());

        $forms = $user->getForms();
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
    }

    function register($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $errors = $info = array();
        if ($_POST) {
            // Register user on post
            if ($user->getAccount())
                $info['error'] = 'User already registered';
            elseif ($user->register($_POST, $errors))
                Http::response(201, 'Account created successfully');

            // Unable to create user.
            $info = Format::htmlchars($_POST);
            if ($errors['err'])
                $info['error'] = $errors['err'];
            else
                $info['error'] = 'Unable to register user - try again!';
        }

        include(STAFFINC_DIR . 'templates/user-register.tmpl.php');
    }

    function manage($id, $target=null) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        if (!($account = $user->getAccount()))
            return self::register($id);

        $errors = array();
        $info = $account->getInfo();

        if ($_POST) {
            if ($account->update($_POST, $errors))
                Http::response(201, 'Account updated successfully');

            // Unable to update account
            $info = Format::htmlchars($_POST);

            if ($errors['err'])
                $info['error'] = $errors['err'];
            else
                $info['error'] = 'Unable to update account - try again!';
        }

        $info['_target'] = $target;

        include(STAFFINC_DIR . 'templates/user-account.tmpl.php');
    }

    function delete($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $info = array();
        if ($_POST) {
            if ($user->tickets->count()) {
                if (!$thisstaff->canDeleteTickets()) {
                    $info['error'] = 'You do not have permission to delete a user with tickets!';
                } elseif ($_POST['deletetickets']) {
                    foreach($user->tickets as $ticket)
                        $ticket->delete();
                } else {
                    $info['error'] = 'You cannot delete a user with tickets!';
                }
            }

            if (!$info['error'] && $user->delete())
                 Http::response(204, 'User deleted successfully');
            elseif (!$info['error'])
                $info['error'] = 'Unable to delete user - try again!';
        }

        include(STAFFINC_DIR . 'templates/user-delete.tmpl.php');
    }

    function getUser($id=false) {

        if(($user=User::lookup(($id) ? $id : $_REQUEST['id'])))
           Http::response(201, $user->to_json());

        $info = array('error' =>'Unknown or invalid user');

        return self::_lookupform(null, $info);
    }

    function lookup() {
        return self::addUser();
    }

    function addUser() {

        $info = array();

        if (!AuthenticationBackend::getSearchDirectories())
            $info['lookup'] = 'local';

        if ($_POST) {
            $info['title'] = 'Add New User';
            $form = UserForm::getUserForm()->getForm($_POST);
            if (($user = User::fromForm($form)))
                Http::response(201, $user->to_json());

            $info['error'] = 'Error adding user - try again!';
        }

        return self::_lookupform($form, $info);
    }

    function addRemoteUser($bk, $id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$bk || !$id)
            Http::response(422, 'Backend and user id required');
        elseif (!($backend = AuthenticationBackend::getSearchDirectoryBackend($bk))
                || !($user_info = $backend->lookup($id)))
            Http::response(404, 'User not found');

        $form = UserForm::getUserForm()->getForm($user_info);
        $info = array('title' => 'Import Remote User');
        if (!$user_info)
            $info['error'] = 'Unable to find user in directory';

        include(STAFFINC_DIR . 'templates/user-lookup.tmpl.php');
    }

    function importUsers() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');

        $info = array(
            'title' => 'Import Users',
            'action' => '#users/import',
            'upload_url' => "users.php?do=import-users",
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

    function selectUser($id) {

        if ($id)
            $user = User::lookup($id);

        $info = array('title' => 'Select User');

        ob_start();
        include(STAFFINC_DIR . 'templates/user-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

    static function _lookupform($form=null, $info=array()) {

        if (!$info or !$info['title'])
            $info += array('title' => 'Lookup or create a user');

        ob_start();
        include(STAFFINC_DIR . 'templates/user-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;
    }

    function searchStaff() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required for searching');
        elseif (!$thisstaff->isAdmin())
            Http::response(403,
                'Administrative privilege is required for searching');
        elseif (!isset($_REQUEST['q']))
            Http::response(400, 'Query argument is required');

        $users = array();
        foreach (AuthenticationBackend::getSearchDirectories() as $ab) {
            foreach ($ab->search($_REQUEST['q']) as $u)
                $users[] = $u;
        }

        return $this->json_encode($users);
    }

    function updateOrg($id, $orgId = 0) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $info = array();
        $info['title'] = 'Organization for '.Format::htmlchars($user->getName());
        $info['action'] = '#users/'.$user->getId().'/org';
        $info['onselect'] = 'ajax.php/users/'.$user->getId().'/org';

        if ($_POST) {
            if ($_POST['orgid']) { //Existing org.
                if (!($org = Organization::lookup($_POST['orgid'])))
                    $info['error'] = 'Unknown organization selected';
            } else { //Creating new org.
                $form = OrganizationForm::getDefaultForm()->getForm($_POST);
                if (!($org = Organization::fromForm($form)))
                    $info['error'] = 'Unable to create organization - try again!';
            }

            if ($org && $user->setOrganization($org))
                Http::response(201, $org->to_json());
            elseif (! $info['error'])
                $info['error'] = 'Unable to add organization - try again!';

        } elseif ($orgId)
            $org = Organization::lookup($orgId);
        elseif ($org = $user->getOrganization()) {
            $info['title'] = sprintf('%s &mdash; %s', Format::htmlchars($user->getName()), 'Organization');
            $info['action'] = $info['onselect'] = '';
            $tmpl = 'org.tmpl.php';
        }

        if ($org && $user->getOrgId() && $org->getId() != $user->getOrgId())
            $info['warning'] = 'Are you sure you want to change user\'s organization?';

        $tmpl = $tmpl ?: 'org-lookup.tmpl.php';

        ob_start();
        include(STAFFINC_DIR . "templates/$tmpl");
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function createNote($id) {
        if (!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        require_once INCLUDE_DIR . 'ajax.note.php';
        $ajax = new NoteAjaxAPI();
        return $ajax->createNote('U'.$id);
    }

    function manageForms($user_id) {
        $forms = DynamicFormEntry::forUser($user_id);
        $info = array('action' => '#users/'.Format::htmlchars($user_id).'/forms/manage');
        include(STAFFINC_DIR . 'templates/form-manage.tmpl.php');
    }

    function updateForms($user_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($user = User::lookup($user_id)))
            Http::response(404, "No such user");
        elseif (!isset($_POST['forms']))
            Http::response(422, "Send updated forms list");

        // Add new forms
        $forms = DynamicFormEntry::forUser($user_id);
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
                $user->addForm($new, $sort);
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
