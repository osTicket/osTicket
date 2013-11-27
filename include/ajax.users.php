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

        $escaped = db_input(strtolower($_REQUEST['q']), false);
        $sql='SELECT DISTINCT user.id, email.address, name '
            .' FROM '.USER_TABLE.' user '
            .' JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id '
            .' LEFT JOIN '.FORM_ENTRY_TABLE.' entry ON (entry.object_type=\'U\' AND entry.object_id = user.id)
               LEFT JOIN '.FORM_ANSWER_TABLE.' value ON (value.entry_id=entry.id) '
            .' WHERE email.address LIKE \'%'.$escaped.'%\'
               OR user.name LIKE \'%'.$escaped.'%\'
               OR value.value LIKE \'%'.$escaped.'%\'
               ORDER BY user.created '
            .' LIMIT '.$limit;

        if(($res=db_query($sql)) && db_num_rows($res)){
            while(list($id,$email,$name)=db_fetch_row($res)) {
                $users[] = array('email'=>$email, 'name'=>$name, 'info'=>"$email - $name",
                    "id" => $id, "/bin/true" => $_REQUEST['q']);
            }
        }

        return $this->json_encode($users);

    }

    function getUser() {

        if(($user=User::lookup($_REQUEST['id'])))
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

}
?>
