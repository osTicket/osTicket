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

class UsersAjaxAPI extends AjaxController {

    /* Assumes search by emal for now */
    function search() {

        if(!isset($_REQUEST['q'])) {
            Http::response(400, 'Query argument is required');
        }

        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $users=array();
        $emails=array();
        foreach (StaffAuthenticationBackend::searchUsers($_REQUEST['q']) as $u) {
            $name = "{$u['first']} {$u['last']}";
            $users[] = array('email' => $u['email'], 'name'=>$name,
                'info' => "{$u['email']} - $name (remote)",
                'id' => "auth:".$u['id'], "/bin/true" => $_REQUEST['q']);
            $emails[] = $u['email'];
        }
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

        return $this->json_encode(array_values($users));

    }

    function editUser($id) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif(!($user = User::lookup($id)))
            Http::response(404, 'Unknown user');

        $info = array(
            'title' => sprintf('Update %s', $user->getName())
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

    function getUser($id=false) {

        if(($user=User::lookup(($id) ? $id : $_REQUEST['id'])))
           Http::response(201, $user->to_json());

        $info = array('error' =>'Unknown or invalid user');

        return self::_lookupform(null, $info);
    }

    function addUser() {

        $form = UserForm::getUserForm()->getForm($_POST);
        if (($user = User::fromForm($form)))
            Http::response(201, $user->to_json());

        $info = array('error' =>'Error adding user - try again!');

        return self::_lookupform($form, $info);
    }

    function addRemoteUser($bk, $id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$bk || !$id)
            Http::response(422, 'Backend and user id required');
        elseif (!($backend = StaffAuthenticationBackend::getBackend($bk)))
            Http::response(404, 'User not found');

        $user_info = $backend->lookup($id);
        $form = UserForm::getUserForm()->getForm($user_info);
        $info = array('title' => 'Import Remote User');
        if (!$user_info)
            $info['error'] = 'Unable to find user in directory';

        include(STAFFINC_DIR . 'templates/user-lookup.tmpl.php');
    }

    function getLookupForm() {
        return self::_lookupform();
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
            $info += array('title' => 'User Lookup');

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
        foreach (StaffAuthenticationBackend::allRegistered() as $ab) {
            if (!$ab instanceof AuthDirectorySearch)
                continue;

            foreach ($ab->search($_REQUEST['q']) as $u)
                $users[] = $u;
        }
        return $this->json_encode($users);
    }
}
?>
