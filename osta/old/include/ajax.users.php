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
require_once INCLUDE_DIR.'ajax.tickets.php';

class UsersAjaxAPI extends AjaxController {

    /* Assumes search by basic info for now */
    function search($type = null, $fulltext=false) {

        if(!isset($_REQUEST['q'])) {
            Http::response(400, __('Query argument is required'));
        }

        $matches = array();
        if (!$_REQUEST['q'])
            return $this->json_encode($matches);

        $q = Format::sanitize($_REQUEST['q']);
        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $users=array();
        $emails=array();
        $matches = array();

        if (strlen(Format::searchable($q)) < 3)
            return $this->encode(array());

        if (!$type || !strcasecmp($type, 'remote')) {
            foreach (AuthenticationBackend::searchUsers($q) as $u) {
                if (!trim($u['email']))
                    // Email is required currently
                    continue;
                $name = new UsersName(array('first' => $u['first'], 'last' => $u['last']));
                $matches[] = array('email' => $u['email'], 'name'=>(string) $name,
                    'info' => "{$u['email']} - $name (remote)",
                    'id' => "auth:".$u['id'], "/bin/true" => $q);
                $emails[] = $u['email'];
            }
        }

        if (!$type || !strcasecmp($type, 'local')) {

            $users = User::objects()
                ->values_flat('id', 'name', 'default_email__address')
                ->limit($limit);

            if ($fulltext) {
                global $ost;
                $users = $ost->searcher->find($q, $users);
                $users->order_by(new SqlCode('__relevance__'), QuerySet::DESC)
                    ->distinct('id');

                if (!count($emails) && !count($users) && preg_match('`\w$`u', $q)) {
                    // Do wildcard full-text search
                    $_REQUEST['q'] = $q."*";
                    return $this->search($type, $fulltext);
                }
            } else {
                $base = clone $users;
                $users->filter(array('name__contains' => $q));
                $users->union($base->copy()->filter(array(
                                'org__name__contains' => $q)), false);
                $users->union($base->copy()->filter(array(
                                'emails__address__contains' => $q)),  false);
                $users->union($base->copy()->filter(array(
                                'account__username__contains' => $q)), false);
                if (UserForm::getInstance()->getField('phone')) {
                      $users->union($base->copy()->filter(array(
                                'cdata__phone__contains' => $q)), false);
                }
            }

            // Omit already-imported remote users
            if ($emails = array_filter($emails)) {
                $users->union(User::objects()
                    ->values_flat('id', 'name', 'default_email__address')
                    ->filter(array(
                        'emails__address__in' => $emails
                )));
            }

            foreach ($users as $U) {
                list($id, $name, $email) = $U;
                foreach ($matches as $i=>$u) {
                    if ($u['email'] == $email) {
                        unset($matches[$i]);
                        break;
                    }
                }
                $name = Format::htmlchars(new UsersName($name));
                $matches[] = array('email'=>$email, 'name'=>$name, 'info'=>"$email - $name",
                    "id" => $id, "/bin/true" => $q);
            }
            usort($matches, function($a, $b) { return strcmp($a['name'], $b['name']); });
        }

        return $this->json_encode(array_values($matches));

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
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            Http::response(403, 'Permission Denied');
        elseif(!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $info = array(
            'title' => sprintf(__('Update %s'), Format::htmlchars($user->getName()))
        );
        $forms = $user->getForms();

        include(STAFFINC_DIR . 'templates/user.tmpl.php');
    }

    function updateUser($id) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            Http::response(403, 'Permission Denied');
        elseif(!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $errors = array();
        $form = UserForm::getUserForm()->getForm($_POST);
        if (!is_string($form->getField('name')->getValue()))
            Http::response(404, 'Invalid Data');

        if ($user->updateInfo($_POST, $errors, true) && !$errors)
             Http::response(201, $user->to_json(),  'application/json');

        $forms = $user->getForms();
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
    }

    static function register($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_MANAGE))
            Http::response(403, 'Permission Denied');
        elseif (!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $errors = $info = array();
        if ($_POST) {
            // Register user on post
            if ($user->getAccount())
                $info['error'] = __('User already registered');
            elseif ($user->register($_POST, $errors))
                Http::response(201, 'Account created successfully');

            // Unable to create user.
            $info = Format::htmlchars($_POST);
            if ($errors['err'])
                $info['error'] = $errors['err'];
            else
                $info['error'] = sprintf('%s - %s', __('Unable to register user'), __('Please try again!'));
        }

        include(STAFFINC_DIR . 'templates/user-register.tmpl.php');
    }

    function manage($id, $target=null) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_MANAGE))
            Http::response(403, 'Permission Denied');
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
                $info['error'] = __('Unable to update account.')
                    .' '.__('Correct any errors below and try again.');
        }

        $info['_target'] = $target;

        include(STAFFINC_DIR . 'templates/user-account.tmpl.php');
    }

    function delete($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_DELETE))
            Http::response(403, 'Permission Denied');
        elseif (!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $info = array();
        if ($_POST) {
            if ($user->tickets->count()) {
                if ($_POST['deletetickets']) {
                    if (!$user->deleteAllTickets())
                        $info['error'] = __('You do not have permission to delete a user with tickets!');
                } else {
                    $info['error'] = __('You cannot delete a user with tickets!');
                }
            }

            if (!$info['error'] && $user->delete())
                 Http::response(204, 'User deleted successfully');
            elseif (!$info['error'])
                $info['error'] = sprintf('%s - %s', __('Unable to delete user'), __('Please try again!'));
        }

        include(STAFFINC_DIR . 'templates/user-delete.tmpl.php');
    }

    function getUser($id=false) {

        if(($user=User::lookup(($id) ? $id : $_REQUEST['id'])))
           Http::response(201, $user->to_json(), 'application/json');

        $info = array('error' => sprintf(__('%s: Unknown or invalid ID.'), _N('end user', 'end users', 1)));

        return self::_lookupform(null, $info);
    }

    function lookup() {
        return self::addUser();
    }

    static function addUser() {
        global $thisstaff;

        $info = array();

        if (!AuthenticationBackend::getSearchDirectories())
            $info['lookup'] = 'local';

        if ($_POST) {
            if (!$thisstaff->hasPerm(User::PERM_CREATE))
                Http::response(403, 'Permission Denied');

            $info['title'] = __('Add New User');
            $form = UserForm::getUserForm()->getForm($_POST);
            if (!is_string($form->getField('name')->getValue()))
                Http::response(404, 'Invalid Data');
            if (($user = User::fromForm($form)))
                Http::response(201, $user->to_json(), 'application/json');

            $info['error'] = sprintf('%s - %s', __('Error adding user'), __('Please try again!'));
        }

        return self::_lookupform($form, $info);
    }

    function addRemoteUser($bk, $id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_CREATE))
            Http::response(403, 'Permission Denied');
        elseif (!$bk || !$id)
            Http::response(422, 'Backend and user id required');
        elseif (!($backend = AuthenticationBackend::getSearchDirectoryBackend($bk))
                || !($user_info = $backend->lookup($id)))
            Http::response(404, 'User not found');

        $form = UserForm::getUserForm()->getForm($user_info);
        $info = array('title' => __(
            /* `remote` users are those in a remore directory such as LDAP */
            'Import Remote User'));
        if (!$user_info)
            $info['error'] = __('Unable to find user in directory');

        include(STAFFINC_DIR . 'templates/user-lookup.tmpl.php');
    }

    function importUsers() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_CREATE))
            Http::response(403, 'Permission Denied');

        $info = array(
            'title' => __('Import Users'),
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
        global $thisstaff;

        if ($id)
            $user = User::lookup($id);

        $info = array('title' => __('Select User'));

        ob_start();
        include(STAFFINC_DIR . 'templates/user-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

    static function _lookupform($form=null, $info=array()) {
        global $thisstaff;

        if (!$info or !$info['title']) {
            if ($thisstaff->hasPerm(User::PERM_CREATE))
                $info += array('title' => __('Lookup or create a user'));
            else
                $info += array('title' => __('Lookup a user'));
        }

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
        $info['title'] = sprintf(__('Organization for %s'),
            Format::htmlchars($user->getName()));
        $info['action'] = '#users/'.$user->getId().'/org';
        $info['onselect'] = 'ajax.php/users/'.$user->getId().'/org';

        if ($_POST) {
            if ($_POST['orgid']) { //Existing org.
                if (!($org = Organization::lookup($_POST['orgid'])))
                    $info['error'] = __('Unknown organization selected');
            } else { //Creating new org.
                $form = OrganizationForm::getDefaultForm()->getForm($_POST);
                if (!($org = Organization::fromForm($form)))
                    $info['error'] = __('Unable to create organization.')
                        .' '.__('Correct any errors below and try again.');
            }

            if ($org && $user->setOrganization($org))
                Http::response(201, $org->to_json(), 'application/json');
            elseif (! $info['error'])
                $info['error'] = __('Unable to add user to organization.')
                    .' '.__('Correct any errors below and try again.');

        } elseif ($orgId)
            $org = Organization::lookup($orgId);
        elseif ($org = $user->getOrganization()) {
            $info['title'] = sprintf(__('%s &mdash; Organization'), Format::htmlchars($user->getName()));
            $info['action'] = $info['onselect'] = '';
            $tmpl = 'org.tmpl.php';
        }

        if ($org && $user->getOrgId() && $org->getId() != $user->getOrgId())
            $info['warning'] = __("Are you sure you want to change the user's organization?");

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
        $forms = DynamicFormEntry::forObject($user_id, 'U');
        $info = array('action' => '#users/'.Format::htmlchars($user_id).'/forms/manage');
        include(STAFFINC_DIR . 'templates/form-manage.tmpl.php');
    }

    function updateForms($user_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            Http::response(403, 'Permission Denied');
        elseif (!($user = User::lookup($user_id)))
            Http::response(404, "No such user");
        elseif (!isset($_POST['forms']))
            Http::response(422, "Send updated forms list");

        // Add new forms
        $forms = DynamicFormEntry::forObject($user_id, 'U');
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

    function exportTickets($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');
        elseif (!$id)
            Http::response(403, __('User ID Required'));

        $user = User::lookup($id);
        if (!$user)
            Http::response(403, __('User Not Found'));

        $queue = $user->getTicketsQueue();

        if ($_POST) {
            $api = new TicketsAjaxAPI();
            return $api->queueExport($queue);
        }

        $info = array('action' => "#users/$id/tickets/export");

        include STAFFINC_DIR . 'templates/queue-export.tmpl.php';
    }
}
?>
