<?php
/*********************************************************************
    class.staff.php

    Everything about staff.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once(INCLUDE_DIR.'class.ticket.php');
include_once(INCLUDE_DIR.'class.dept.php');
include_once(INCLUDE_DIR.'class.error.php');
include_once(INCLUDE_DIR.'class.team.php');
include_once(INCLUDE_DIR.'class.group.php');
include_once(INCLUDE_DIR.'class.passwd.php');
include_once(INCLUDE_DIR.'class.user.php');
include_once(INCLUDE_DIR.'class.auth.php');

class Staff extends AuthenticatedUser {

    var $ht;
    var $id;

    var $dept;
    var $departments;
    var $group;
    var $teams;
    var $timezone;
    var $stats;

    function Staff($var) {
        $this->id =0;
        return ($this->load($var));
    }

    function load($var='') {

        if(!$var && !($var=$this->getId()))
            return false;

        $sql='SELECT staff.created as added, grp.*, staff.* '
            .' FROM '.STAFF_TABLE.' staff '
            .' LEFT JOIN '.GROUP_TABLE.' grp ON(grp.group_id=staff.group_id)
               WHERE ';

        if (is_numeric($var))
            $sql .= 'staff_id='.db_input($var);
        elseif (Validator::is_email($var))
            $sql .= 'email='.db_input($var);
        elseif (is_string($var))
            $sql .= 'username='.db_input($var);
        else
            return null;

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return NULL;


        $this->ht=db_fetch_array($res);
        $this->id  = $this->ht['staff_id'];
        $this->teams = $this->ht['teams'] = array();
        $this->group = $this->dept = null;
        $this->departments = $this->stats = array();
        $this->config = new Config('staff.'.$this->id);

        //WE have to patch info here to support upgrading from old versions.
        if(($time=strtotime($this->ht['passwdreset']?$this->ht['passwdreset']:$this->ht['added'])))
            $this->ht['passwd_change'] = time()-$time; //XXX: check timezone issues.

        if($this->ht['timezone_id'])
            $this->ht['tz_offset'] = Timezone::getOffsetById($this->ht['timezone_id']);
        elseif($this->ht['timezone_offset'])
            $this->ht['tz_offset'] = $this->ht['timezone_offset'];

        return ($this->id);
    }

    function reload() {
        return $this->load();
    }

    function __toString() {
        return (string) $this->getName();
    }

    function asVar() {
        return $this->__toString();
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->config->getInfo() + $this->getHashtable();
    }

    // AuthenticatedUser implementation...
    // TODO: Move to an abstract class that extends Staff
    function getRole() {
        return 'staff';
    }

    function getAuthBackend() {
        list($authkey, ) = explode(':', $this->getAuthKey());
        return StaffAuthenticationBackend::getBackend($authkey);
    }

    /*compares user password*/
    function check_passwd($password, $autoupdate=true) {

        /*bcrypt based password match*/
        if(Passwd::cmp($password, $this->getPasswd()))
            return true;

        //Fall back to MD5
        if(!$password || strcmp($this->getPasswd(), MD5($password)))
            return false;

        //Password is a MD5 hash: rehash it (if enabled) otherwise force passwd change.
        $sql='UPDATE '.STAFF_TABLE.' SET passwd='.db_input(Passwd::hash($password))
            .' WHERE staff_id='.db_input($this->getId());

        if(!$autoupdate || !db_query($sql))
            $this->forcePasswdRest();

        return true;
    }

    function cmp_passwd($password) {
        return $this->check_passwd($password, false);
    }

    function hasPassword() {
        return (bool) $this->ht['passwd'];
    }

    function forcePasswdRest() {
        return db_query('UPDATE '.STAFF_TABLE.' SET change_passwd=1 WHERE staff_id='.db_input($this->getId()));
    }

    /* check if passwd reset is due. */
    function isPasswdResetDue() {
        global $cfg;
        return ($cfg && $cfg->getPasswdResetPeriod()
                    && $this->ht['passwd_change']>($cfg->getPasswdResetPeriod()*30*24*60*60));
    }

    function isPasswdChangeDue() {
        return $this->isPasswdResetDue();
    }

    function getTZoffset() {
        return $this->ht['tz_offset'];
    }

    function observeDaylight() {
        return $this->ht['daylight_saving']?true:false;
    }

    function getRefreshRate() {
        return $this->ht['auto_refresh_rate'];
    }

    function getPageLimit() {
        return $this->ht['max_page_size'];
    }

    function getId() {
        return $this->id;
    }

    function getEmail() {
        return $this->ht['email'];
    }

    function getUserName() {
        return $this->ht['username'];
    }

    function getPasswd() {
        return $this->ht['passwd'];
    }

    function getName() {
        return new PersonsName($this->ht['firstname'].' '.$this->ht['lastname']);
    }

    function getFirstName() {
        return $this->ht['firstname'];
    }

    function getLastName() {
        return $this->ht['lastname'];
    }

    function getSignature() {
        return $this->ht['signature'];
    }

    function getDefaultSignatureType() {
        return $this->ht['default_signature_type'];
    }

    function getDefaultPaperSize() {
        return $this->ht['default_paper_size'];
    }

    function forcePasswdChange() {
        return ($this->ht['change_passwd']);
    }

    function getDepartments() {

        if($this->departments)
            return $this->departments;

        //Departments the staff is "allowed" to access...
        // based on the group they belong to + user's primary dept + user's managed depts.
        $sql='SELECT DISTINCT d.dept_id FROM '.STAFF_TABLE.' s '
            .' LEFT JOIN '.GROUP_DEPT_TABLE.' g ON(s.group_id=g.group_id) '
            .' INNER JOIN '.DEPT_TABLE.' d ON(d.dept_id=s.dept_id OR d.manager_id=s.staff_id OR d.dept_id=g.dept_id) '
            .' WHERE s.staff_id='.db_input($this->getId());

        $depts = array();
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id)=db_fetch_row($res))
                $depts[] = $id;
        } else { //Neptune help us! (fallback)
            $depts = array_merge($this->getGroup()->getDepartments(), array($this->getDeptId()));
        }

        $this->departments = array_filter(array_unique($depts));


        return $this->departments;
    }

    function getDepts() {
        return $this->getDepartments();
    }

    function getManagedDepartments() {

        return ($depts=Dept::getDepartments(
                    array('manager' => $this->getId())
                    ))?array_keys($depts):array();
    }

    function getGroupId() {
        return $this->ht['group_id'];
    }

    function getGroup() {

        if(!$this->group && $this->getGroupId())
            $this->group = Group::lookup($this->getGroupId());

        return $this->group;
    }

    function getDeptId() {
        return $this->ht['dept_id'];
    }

    function getDept() {

        if(!$this->dept && $this->getDeptId())
            $this->dept= Dept::lookup($this->getDeptId());

        return $this->dept;
    }

    function getLanguage() {
        static $cached = false;
        if (!$cached) $cached = &$_SESSION['staff:lang'];

        if (!$cached) {
            $cached = $this->config->get('lang');
            if (!$cached)
                $cached = Internationalization::getDefaultLanguage();
        }
        return $cached;
    }

    function isManager() {
        return (($dept=$this->getDept()) && $dept->getManagerId()==$this->getId());
    }

    function isStaff() {
        return TRUE;
    }

    function isGroupActive() {
        return ($this->ht['group_enabled']);
    }

    function isactive() {
        return ($this->ht['isactive']);
    }

    function isVisible() {
         return ($this->ht['isvisible']);
    }

    function onVacation() {
        return ($this->ht['onvacation']);
    }

    function isAvailable() {
        return ($this->isactive() && $this->isGroupActive() && !$this->onVacation());
    }

    function showAssignedOnly() {
        return ($this->ht['assigned_only']);
    }

    function isAccessLimited() {
        return $this->showAssignedOnly();
    }

    function isAdmin() {
        return ($this->ht['isadmin']);
    }

    function isTeamMember($teamId) {
        return ($teamId && in_array($teamId, $this->getTeams()));
    }

    function canAccessDept($deptId) {
        return ($deptId && in_array($deptId, $this->getDepts()) && !$this->isAccessLimited());
    }

    function canCreateTickets() {
        return ($this->ht['can_create_tickets']);
    }

    function canEditTickets() {
        return ($this->ht['can_edit_tickets']);
    }

    function canDeleteTickets() {
        return ($this->ht['can_delete_tickets']);
    }

    function canCloseTickets() {
        return ($this->ht['can_close_tickets']);
    }

    function canPostReply() {
        return ($this->ht['can_post_ticket_reply']);
    }

    function canViewStaffStats() {
        return ($this->ht['can_view_staff_stats']);
    }

    function canAssignTickets() {
        return ($this->ht['can_assign_tickets']);
    }

    function canTransferTickets() {
        return ($this->ht['can_transfer_tickets']);
    }

    function canBanEmails() {
        return ($this->ht['can_ban_emails']);
    }

    function canManageTickets() {
        return ($this->isAdmin()
                 || $this->canDeleteTickets()
                    || $this->canCloseTickets());
    }

    function canManagePremade() {
        return ($this->ht['can_manage_premade']);
    }

    function canManageCannedResponses() {
        return $this->canManagePremade();
    }

    function canManageFAQ() {
        return ($this->ht['can_manage_faq']);
    }

    function canManageFAQs() {
        return $this->canManageFAQ();
    }

    function showAssignedTickets() {
        return ($this->ht['show_assigned_tickets']);
    }

    function getTeams() {

        if(!$this->teams) {
            $sql='SELECT team_id FROM '.TEAM_MEMBER_TABLE
                .' WHERE staff_id='.db_input($this->getId());
            if(($res=db_query($sql)) && db_num_rows($res))
                while(list($id)=db_fetch_row($res))
                    $this->teams[] = $id;
        }

        return $this->teams;
    }
    /* stats */

    function resetStats() {
        $this->stats = array();
    }

    /* returns staff's quick stats - used on nav menu...etc && warnings */
    function getTicketsStats() {

        if(!$this->stats['tickets'])
            $this->stats['tickets'] = Ticket::getStaffStats($this);

        return  $this->stats['tickets'];
    }

    function getNumAssignedTickets() {
        return ($stats=$this->getTicketsStats())?$stats['assigned']:0;
    }

    function getNumClosedTickets() {
        return ($stats=$this->getTicketsStats())?$stats['closed']:0;
    }

    //Staff profile update...unfortunately we have to separate it from admin update to avoid potential issues
    function updateProfile($vars, &$errors) {
        global $cfg;

        $vars['firstname']=Format::striptags($vars['firstname']);
        $vars['lastname']=Format::striptags($vars['lastname']);

        if($this->getId()!=$vars['id'])
            $errors['err']='Internal Error';

        if(!$vars['firstname'])
            $errors['firstname']='First name required';

        if(!$vars['lastname'])
            $errors['lastname']='Last name required';

        if(!$vars['email'] || !Validator::is_email($vars['email']))
            $errors['email']='Valid email required';
        elseif(Email::getIdByEmail($vars['email']))
            $errors['email']='Already in-use as system email';
        elseif(($uid=Staff::getIdByEmail($vars['email'])) && $uid!=$this->getId())
            $errors['email']='Email already in-use by another staff member';

        if($vars['phone'] && !Validator::is_phone($vars['phone']))
            $errors['phone']='Valid number required';

        if($vars['mobile'] && !Validator::is_phone($vars['mobile']))
            $errors['mobile']='Valid number required';

        if($vars['passwd1'] || $vars['passwd2'] || $vars['cpasswd']) {

            if(!$vars['passwd1'])
                $errors['passwd1']='New password required';
            elseif($vars['passwd1'] && strlen($vars['passwd1'])<6)
                $errors['passwd1']='Must be at least 6 characters';
            elseif($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2']))
                $errors['passwd2']='Password(s) do not match';

            if (($rtoken = $_SESSION['_staff']['reset-token'])) {
                $_config = new Config('pwreset');
                if ($_config->get($rtoken) != $this->getId())
                    $errors['err'] =
                        'Invalid reset token. Logout and try again';
                elseif (!($ts = $_config->lastModified($rtoken))
                        && ($cfg->getPwResetWindow() < (time() - strtotime($ts))))
                    $errors['err'] =
                        'Invalid reset token. Logout and try again';
            }
            elseif(!$vars['cpasswd'])
                $errors['cpasswd']='Current password required';
            elseif(!$this->cmp_passwd($vars['cpasswd']))
                $errors['cpasswd']='Invalid current password!';
            elseif(!strcasecmp($vars['passwd1'], $vars['cpasswd']))
                $errors['passwd1']='New password MUST be different from the current password!';
        }

        if(!$vars['timezone_id'])
            $errors['timezone_id']='Time zone required';

        if($vars['default_signature_type']=='mine' && !$vars['signature'])
            $errors['default_signature_type'] = "You don't have a signature";

        if($errors) return false;

        $this->config->set('lang', $vars['lang']);
        $_SESSION['staff:lang'] = null;

        $sql='UPDATE '.STAFF_TABLE.' SET updated=NOW() '
            .' ,firstname='.db_input($vars['firstname'])
            .' ,lastname='.db_input($vars['lastname'])
            .' ,email='.db_input($vars['email'])
            .' ,phone="'.db_input(Format::phone($vars['phone']),false).'"'
            .' ,phone_ext='.db_input($vars['phone_ext'])
            .' ,mobile="'.db_input(Format::phone($vars['mobile']),false).'"'
            .' ,signature='.db_input(Format::sanitize($vars['signature']))
            .' ,timezone_id='.db_input($vars['timezone_id'])
            .' ,daylight_saving='.db_input(isset($vars['daylight_saving'])?1:0)
            .' ,show_assigned_tickets='.db_input(isset($vars['show_assigned_tickets'])?1:0)
            .' ,max_page_size='.db_input($vars['max_page_size'])
            .' ,auto_refresh_rate='.db_input($vars['auto_refresh_rate'])
            .' ,default_signature_type='.db_input($vars['default_signature_type'])
            .' ,default_paper_size='.db_input($vars['default_paper_size']);


        if($vars['passwd1']) {
            $sql.=' ,change_passwd=0, passwdreset=NOW(), passwd='.db_input(Passwd::hash($vars['passwd1']));
            $info = array('password' => $vars['passwd1']);
            Signal::send('auth.pwchange', $this, $info);
            $this->cancelResetTokens();
        }

        $sql.=' WHERE staff_id='.db_input($this->getId());

        //echo $sql;

        return (db_query($sql));
    }


    function updateTeams($teams) {

        if($teams) {
            foreach($teams as $k=>$id) {
                $sql='INSERT IGNORE INTO '.TEAM_MEMBER_TABLE.' SET updated=NOW() '
                    .' ,staff_id='.db_input($this->getId()).', team_id='.db_input($id);
                db_query($sql);
            }
        }

        $sql='DELETE FROM '.TEAM_MEMBER_TABLE.' WHERE staff_id='.db_input($this->getId());
        if($teams)
            $sql.=' AND team_id NOT IN('.implode(',', db_input($teams)).')';

        db_query($sql);

        return true;
    }

    function update($vars, &$errors) {

        if(!$this->save($this->getId(), $vars, $errors))
            return false;

        $this->updateTeams($vars['teams']);
        $this->reload();

        Signal::send('model.modified', $this);

        return true;
    }

    function delete() {
        global $thisstaff;

        if (!$thisstaff || $this->getId() == $thisstaff->getId())
            return 0;

        $sql='DELETE FROM '.STAFF_TABLE
            .' WHERE staff_id = '.db_input($this->getId()).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            // DO SOME HOUSE CLEANING
            //Move remove any ticket assignments...TODO: send alert to Dept. manager?
            db_query('UPDATE '.TICKET_TABLE.' SET staff_id=0 WHERE staff_id='.db_input($this->getId()));

            //Update the poster and clear staff_id on ticket thread table.
            db_query('UPDATE '.TICKET_THREAD_TABLE
                    .' SET staff_id=0, poster= '.db_input($this->getName()->getOriginal())
                    .' WHERE staff_id='.db_input($this->getId()));

            //Cleanup Team membership table.
            db_query('DELETE FROM '.TEAM_MEMBER_TABLE.' WHERE staff_id='.db_input($this->getId()));

            // Destrory config settings
            $this->config->destroy();
        }

        Signal::send('model.deleted', $this);

        return $num;
    }

    /**** Static functions ********/
    function getStaffMembers($availableonly=false) {

        $sql='SELECT s.staff_id, CONCAT_WS(" ", s.firstname, s.lastname) as name '
            .' FROM '.STAFF_TABLE.' s ';

        if($availableonly) {
            $sql.=' INNER JOIN '.GROUP_TABLE.' g ON(g.group_id=s.group_id AND g.group_enabled=1) '
                 .' WHERE s.isactive=1 AND s.onvacation=0';
        }

        $sql.='  ORDER BY s.lastname, s.firstname';
        $users=array();
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id, $name) = db_fetch_row($res))
                $users[$id] = $name;
        }

        return $users;
    }

    function getAvailableStaffMembers() {
        return self::getStaffMembers(true);
    }

    function getIdByUsername($username) {

        $sql='SELECT staff_id FROM '.STAFF_TABLE.' WHERE username='.db_input($username);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id) = db_fetch_row($res);

        return $id;
    }
    function getIdByEmail($email) {

        $sql='SELECT staff_id FROM '.STAFF_TABLE.' WHERE email='.db_input($email);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id) = db_fetch_row($res);

        return $id;
    }

    function lookup($id) {
        return ($id && ($staff= new Staff($id)) && $staff->getId()) ? $staff : null;
    }

    function login($username, $passwd, &$errors, $strike=true) {
        global $ost, $cfg;


        if($_SESSION['_staff']['laststrike']) {
            if((time()-$_SESSION['_staff']['laststrike'])<$cfg->getStaffLoginTimeout()) {
                $errors['err']='Max. failed login attempts reached';
                $_SESSION['_staff']['laststrike'] = time(); //reset timer.
            } else { //Timeout is over.
                //Reset the counter for next round of attempts after the timeout.
                $_SESSION['_staff']['laststrike']=null;
                $_SESSION['_staff']['strikes']=0;
            }
        }

        if(!$username || !$passwd || is_numeric($username))
            $errors['err'] = 'Username and password required';

        if($errors) return false;

        if(($user=new StaffSession(trim($username))) && $user->getId() && $user->check_passwd($passwd)) {
            self::_do_login($user, $username);

            Signal::send('auth.login.succeeded', $user);
            $user->cancelResetTokens();

            return $user;
        }

        $info = array('username'=>$username, 'password'=>$passwd);
        Signal::send('auth.login.failed', null, $info);

        //If we get to this point we know the login failed.
        $_SESSION['_staff']['strikes']+=1;
        if(!$errors && $_SESSION['_staff']['strikes']>$cfg->getStaffMaxLogins()) {
            $errors['err']='Forgot your login info? Contact Admin.';
            $_SESSION['_staff']['laststrike']=time();
            $alert='Excessive login attempts by a staff member?'."\n".
                   'Username: '.$username."\n".'IP: '.$_SERVER['REMOTE_ADDR']."\n".'TIME: '.date('M j, Y, g:i a T')."\n\n".
                   'Attempts #'.$_SESSION['_staff']['strikes']."\n".'Timeout: '.($cfg->getStaffLoginTimeout()/60)." minutes \n\n";
            $ost->logWarning('Excessive login attempts ('.$username.')', $alert, ($cfg->alertONLoginError()));

        } elseif($_SESSION['_staff']['strikes']%2==0) { //Log every other failed login attempt as a warning.
            $alert='Username: '.$username."\n".'IP: '.$_SERVER['REMOTE_ADDR'].
                   "\n".'TIME: '.date('M j, Y, g:i a T')."\n\n".'Attempts #'.$_SESSION['_staff']['strikes'];
            $ost->logWarning('Failed staff login attempt ('.$username.')', $alert, false);
        }

        return false;
    }

    function _do_login($user, $username) {
        global $ost;

        //update last login && password reset stuff.
        $sql='UPDATE '.STAFF_TABLE.' SET lastlogin=NOW() ';
        if($user->isPasswdResetDue() && !$user->isAdmin())
            $sql.=',change_passwd=1';
        $sql.=' WHERE staff_id='.db_input($user->getId());
        db_query($sql);
        //Now set session crap and lets roll baby!
        $_SESSION['_staff'] = array(); //clear.
        $_SESSION['_staff']['userID'] = $user->getId();
        $user->refreshSession(true); //set the hash.
        $_SESSION['TZ_OFFSET'] = $user->getTZoffset();
        $_SESSION['TZ_DST'] = $user->observeDaylight();

        //Log debug info.
        $ost->logDebug('Staff login',
                sprintf("%s logged in [%s]", $user->getUserName(), $_SERVER['REMOTE_ADDR'])); //Debug.

        //Regenerate session id.
        $sid=session_id(); //Current id
        session_regenerate_id(TRUE);
        //Destroy old session ID - needed for PHP version < 5.1.0 TODO: remove when we move to php 5.3 as min. requirement.
        if(($session=$ost->getSession()) && is_object($session) && $sid!=session_id())
            $session->destroy($sid);

        return $user;
    }

    function create($vars, &$errors) {
        if(($id=self::save(0, $vars, $errors)) && ($staff=Staff::lookup($id))) {
            if ($vars['teams'])
                $staff->updateTeams($vars['teams']);
            if ($vars['welcome_email'])
                $staff->sendResetEmail('registration-staff');
            Signal::send('model.created', $staff);
        }

        return $id;
    }

    function cancelResetTokens() {
        // TODO: Drop password-reset tokens from the config table for
        //       this user id
        $sql = 'DELETE FROM '.CONFIG_TABLE.' WHERE `namespace`="pwreset"
            AND `value`='.db_input($this->getId());
        db_query($sql, false);
        unset($_SESSION['_staff']['reset-token']);
    }

    function sendResetEmail($template='pwreset-staff') {
        global $ost, $cfg;

        $content = Page::lookup(Page::getIdByType($template));
        $token = Misc::randCode(48); // 290-bits

        if (!$content)
            return new Error('Unable to retrieve password reset email template');

        $vars = array(
            'url' => $ost->getConfig()->getBaseUrl(),
            'token' => $token,
            'staff' => $this,
            'recipient' => $this,
            'reset_link' => sprintf(
                "%s/scp/pwreset.php?token=%s",
                $ost->getConfig()->getBaseUrl(),
                $token),
        );
        $vars['link'] = &$vars['reset_link'];

        if (!($email = $cfg->getAlertEmail()))
            $email = $cfg->getDefaultEmail();

        $info = array('email' => $email, 'vars' => &$vars, 'log'=>true);
        Signal::send('auth.pwreset.email', $this, $info);

        if ($info['log'])
            $ost->logWarning('Staff Password Reset', sprintf(
               'Password reset was attempted for staff member: %s<br><br>
                Requested-User-Id: %s<br>
                Source-Ip: %s<br>
                Email-Sent-To: %s<br>
                Email-Sent-Via: %s',
                $this->getName(),
                $_POST['userid'],
                $_SERVER['REMOTE_ADDR'],
                $this->getEmail(),
                $email->getEmail()
            ), false);

        $msg = $ost->replaceTemplateVariables(array(
            'subj' => $content->getName(),
            'body' => $content->getBody(),
        ), $vars);

        $_config = new Config('pwreset');
        $_config->set($vars['token'], $this->getId());

        $email->send($this->getEmail(), Format::striptags($msg['subj']),
            $msg['body']);
    }

    function save($id, $vars, &$errors) {

        $vars['username']=Format::striptags($vars['username']);
        $vars['firstname']=Format::striptags($vars['firstname']);
        $vars['lastname']=Format::striptags($vars['lastname']);

        if($id && $id!=$vars['id'])
            $errors['err']='Internal Error';

        if(!$vars['firstname'])
            $errors['firstname']='First name required';
        if(!$vars['lastname'])
            $errors['lastname']='Last name required';

        $error = '';
        if(!$vars['username'] || !Validator::is_username($vars['username'], $error))
            $errors['username']=($error) ? $error : 'Username required';
        elseif(($uid=Staff::getIdByUsername($vars['username'])) && $uid!=$id)
            $errors['username']='Username already in use';

        if(!$vars['email'] || !Validator::is_email($vars['email']))
            $errors['email']='Valid email required';
        elseif(Email::getIdByEmail($vars['email']))
            $errors['email']='Already in-use system email';
        elseif(($uid=Staff::getIdByEmail($vars['email'])) && $uid!=$id)
            $errors['email']='Email already in use by another staff member';

        if($vars['phone'] && !Validator::is_phone($vars['phone']))
            $errors['phone']='Valid number required';

        if($vars['mobile'] && !Validator::is_phone($vars['mobile']))
            $errors['mobile']='Valid number required';

        if($vars['passwd1'] || $vars['passwd2'] || !$id) {
            if($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2'])) {
                $errors['passwd2']='Password(s) do not match';
            }
            elseif ($vars['backend'] != 'local' || $vars['welcome_email']) {
                // Password can be omitted
            }
            elseif(!$vars['passwd1'] && !$id) {
                $errors['passwd1']='Temp. password required';
                $errors['temppasswd']='Required';
            } elseif($vars['passwd1'] && strlen($vars['passwd1'])<6) {
                $errors['passwd1']='Must be at least 6 characters';
            }
        }

        if(!$vars['dept_id'])
            $errors['dept_id']='Department required';

        if(!$vars['group_id'])
            $errors['group_id']='Group required';

        if(!$vars['timezone_id'])
            $errors['timezone_id']='Time zone required';

        if($errors) return false;


        $sql='SET updated=NOW() '
            .' ,isadmin='.db_input($vars['isadmin'])
            .' ,isactive='.db_input($vars['isactive'])
            .' ,isvisible='.db_input(isset($vars['isvisible'])?1:0)
            .' ,onvacation='.db_input(isset($vars['onvacation'])?1:0)
            .' ,assigned_only='.db_input(isset($vars['assigned_only'])?1:0)
            .' ,dept_id='.db_input($vars['dept_id'])
            .' ,group_id='.db_input($vars['group_id'])
            .' ,timezone_id='.db_input($vars['timezone_id'])
            .' ,daylight_saving='.db_input(isset($vars['daylight_saving'])?1:0)
            .' ,username='.db_input($vars['username'])
            .' ,firstname='.db_input($vars['firstname'])
            .' ,lastname='.db_input($vars['lastname'])
            .' ,email='.db_input($vars['email'])
            .' ,backend='.db_input($vars['backend'])
            .' ,phone="'.db_input(Format::phone($vars['phone']),false).'"'
            .' ,phone_ext='.db_input($vars['phone_ext'])
            .' ,mobile="'.db_input(Format::phone($vars['mobile']),false).'"'
            .' ,signature='.db_input(Format::sanitize($vars['signature']))
            .' ,notes='.db_input(Format::sanitize($vars['notes']));

        if($vars['passwd1']) {
            $sql.=' ,passwd='.db_input(Passwd::hash($vars['passwd1']));

            if(isset($vars['change_passwd']))
                $sql.=' ,change_passwd=1';
        }
        elseif (!isset($vars['change_passwd']))
            $sql .= ' ,change_passwd=0';

        if($id) {
            $sql='UPDATE '.STAFF_TABLE.' '.$sql.' WHERE staff_id='.db_input($id);
            if(db_query($sql) && db_affected_rows())
                return true;

            $errors['err']='Unable to update the user. Internal error occurred';
        } else {
            $sql='INSERT INTO '.STAFF_TABLE.' '.$sql.', created=NOW()';
            if(db_query($sql) && ($uid=db_insert_id()))
                return $uid;

            $errors['err']='Unable to create user. Internal error';
        }

        return false;
    }
}
?>
