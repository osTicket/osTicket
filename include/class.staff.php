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
include_once(INCLUDE_DIR.'class.role.php');
include_once(INCLUDE_DIR.'class.group.php');
include_once(INCLUDE_DIR.'class.passwd.php');
include_once(INCLUDE_DIR.'class.user.php');
include_once(INCLUDE_DIR.'class.auth.php');

class Staff extends VerySimpleModel
implements AuthenticatedUser {

    static $meta = array(
        'table' => STAFF_TABLE,
        'pk' => array('staff_id'),
        'select_related' => array('group'),
        'joins' => array(
            'dept' => array(
                'constraint' => array('dept_id' => 'Dept.id'),
            ),
            'group' => array(
                'constraint' => array('group_id' => 'Group.id'),
            ),
            'teams' => array(
                'null' => true,
                'list' => true,
                'reverse' => 'TeamMember.staff',
            ),
        ),
    );

    var $authkey;
    var $departments;
    var $timezone;
    var $stats = array();
    var $_extra;
    var $passwd_change;
    var $_roles = null;
    var $_teams = null;
    var $_perms = null;

    function __onload() {
        // WE have to patch info here to support upgrading from old versions.
        if ($time=strtotime($this->passwdreset ?: (isset($this->added) ? $this->added : '')))
            $this->passwd_change = time()-$time; //XXX: check timezone issues.
    }

    function __toString() {
        return (string) $this->getName();
    }

    function asVar() {
        return $this->__toString();
    }

    function getHashtable() {
        $base = $this->ht;
        $base['group'] = $base['group_id'];
        unset($base['teams']);
        return $base;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    // AuthenticatedUser implementation...
    // TODO: Move to an abstract class that extends Staff
    function getUserType() {
        return 'staff';
    }

    function getAuthBackend() {
        list($authkey, ) = explode(':', $this->getAuthKey());
        return StaffAuthenticationBackend::getBackend($authkey);
    }

    function setAuthKey($key) {
        $this->authkey = $key;
    }

    function getAuthKey() {
        return $this->authkey;
    }

    // logOut the user
    function logOut() {

        if ($bk = $this->getAuthBackend())
            return $bk->signOut($this);

        return false;
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
        $this->passwd = Passwd::hash($password);

        if(!$autoupdate || !$this->save())
            $this->forcePasswdRest();

        return true;
    }

    function cmp_passwd($password) {
        return $this->check_passwd($password, false);
    }

    function hasPassword() {
        return (bool) $this->passwd;
    }

    function forcePasswdRest() {
        $this->change_passwd = 1;
        return $this->update();
    }

    /* check if passwd reset is due. */
    function isPasswdResetDue() {
        global $cfg;
        return ($cfg && $cfg->getPasswdResetPeriod()
                    && $this->passwd_change>($cfg->getPasswdResetPeriod()*30*24*60*60));
    }

    function isPasswdChangeDue() {
        return $this->isPasswdResetDue();
    }

    function getRefreshRate() {
        return $this->auto_refresh_rate;
    }

    function getPageLimit() {
        return $this->max_page_size;
    }

    function getId() {
        return $this->staff_id;
    }

    function getEmail() {
        return $this->email;
    }

    function getUserName() {
        return $this->username;
    }

    function getPasswd() {
        return $this->passwd;
    }

    function getName() {
        return new PersonsName($this->firstname.' '.$this->lastname);
    }

    function getFirstName() {
        return $this->firstname;
    }

    function getLastName() {
        return $this->lastname;
    }

    function getSignature() {
        return $this->signature;
    }

    function getDefaultSignatureType() {
        return $this->default_signature_type;
    }

    function getDefaultPaperSize() {
        return $this->default_paper_size;
    }

    function forcePasswdChange() {
        return $this->change_passwd;
    }

    function getDepartments() {

        if (!isset($this->departments)) {

            // Departments the staff is "allowed" to access...
            // based on the group they belong to + user's primary dept + user's managed depts.
            $dept_ids = array();
            $depts = Dept::objects()
                ->filter(Q::any(array(
                    'id' => $this->dept_id,
                    'groups__group_id' => $this->group_id,
                    'manager_id' => $this->getId(),
                )))
                ->values_flat('id');

            foreach ($depts as $row)
                $dept_ids[] = $row[0];

            if (!$dept_ids) { //Neptune help us! (fallback)
                $dept_ids = array_merge($this->getGroup()->getDepartments(), array($this->getDeptId()));
            }

            $this->departments = array_filter(array_unique($dept_ids));
        }

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
        return $this->group_id;
    }

    function getGroup() {
        return $this->group;
    }

    function getDeptId() {
        return $this->dept_id;
    }

    function getDept() {
        return $this->dept;
    }

    function getLanguage() {
        return $this->lang;
    }

    function getTimezone() {
        return $this->timezone;
    }

    function getLocale() {
        return $this->locale;
    }

    function getRole($dept=null) {

        if ($dept) {
            $deptId = is_object($dept) ? $dept->getId() : $dept;
            if (isset($this->_roles[$deptId]))
                return $this->_roles[$deptId];

            if (($role=$this->group->getRole($deptId)))
                return $this->_roles[$deptId] = $role;
        }

        return $this->group->getRole();
    }

    function hasPermission($perm) {
        if (!isset($this->_perms)) {
            foreach ($this->getDepartments() as $deptId) {
                if (($role = $this->getRole($deptId))) {
                    foreach ($role->getPermission()->getInfo() as $perm=>$v) {
                        $this->_perms[$perm] |= $v;
                    }
                }
            }
        }
        return @$this->_perms[$perm];
    }

    function canCreateTickets() {
        return $this->hasPermission('ticket.create');
    }

    function canAssignTickets() {
        return $this->hasPermission('ticket.create');
    }

    function canCloseTickets() {
        return $this->hasPermission('ticket.close');
    }

    function canDeleteTickets() {
        return $this->hasPermission('ticket.delete');
    }

    function canManageTickets() {
        return ($this->isAdmin()
                || $this->canDeleteTickets()
                || $this->canCloseTickets());
    }

    function isManager() {
        return (($dept=$this->getDept()) && $dept->getManagerId()==$this->getId());
    }

    function isStaff() {
        return TRUE;
    }

    function isGroupActive() {
        return $this->group->isEnabled();
    }

    function isactive() {
        return $this->isactive;
    }

    function isVisible() {
         return $this->isvisible;
    }

    function onVacation() {
        return $this->onvacation;
    }

    function isAvailable() {
        return ($this->isactive() && $this->isGroupActive() && !$this->onVacation());
    }

    function showAssignedOnly() {
        return $this->assigned_only;
    }

    function isAccessLimited() {
        return $this->showAssignedOnly();
    }

    function isAdmin() {
        return $this->isadmin;
    }

    function isTeamMember($teamId) {
        return ($teamId && in_array($teamId, $this->getTeams()));
    }

    function canAccessDept($deptId) {
        return ($deptId && in_array($deptId, $this->getDepts()) && !$this->isAccessLimited());
    }

    function showAssignedTickets() {
        return $this->show_assigned_tickets;
    }

    function getTeams() {

        if (!isset($this->_teams)) {
            $this->_teams = array();
            foreach ($this->teams as $team)
                 $this->_teams[] = $team->team_id;
        }

        return $this->_teams;
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

    function getExtraAttr($attr=false, $default=null) {
        if (!isset($this->_extra))
            $this->_extra = JsonDataParser::decode($this->extra);

        return $attr ? (@$this->_extra[$attr] ?: $default) : $this->_extra;
    }

    function setExtraAttr($attr, $value, $commit=true) {
        $this->getExtraAttr();
        $this->_extra[$attr] = $value;

        if ($commit) {
            $this->extra = JsonDataEncoder::encode($this->_extra);
            $this->save();
        }
    }

    function onLogin($bk) {
        // Update last apparent language preference
        $this->setExtraAttr('browser_lang',
            Internationalization::getCurrentLanguage(),
            false);

        $this->lastlogin = SqlFunction::NOW();
        $this->save();
    }

    //Staff profile update...unfortunately we have to separate it from admin update to avoid potential issues
    function updateProfile($vars, &$errors) {
        global $cfg;

        $vars['firstname']=Format::striptags($vars['firstname']);
        $vars['lastname']=Format::striptags($vars['lastname']);

        if (isset($this->staff_id) && $this->getId() != $vars['id'])
            $errors['err']=__('Internal error occurred');

        if(!$vars['firstname'])
            $errors['firstname']=__('First name is required');

        if(!$vars['lastname'])
            $errors['lastname']=__('Last name is required');

        if(!$vars['email'] || !Validator::is_email($vars['email']))
            $errors['email']=__('Valid email is required');
        elseif(Email::getIdByEmail($vars['email']))
            $errors['email']=__('Already in-use as system email');
        elseif (($uid=static::getIdByEmail($vars['email']))
                && (!isset($this->staff_id) || $uid!=$this->getId()))
            $errors['email']=__('Email already in-use by another agent');

        if($vars['phone'] && !Validator::is_phone($vars['phone']))
            $errors['phone']=__('Valid phone number is required');

        if($vars['mobile'] && !Validator::is_phone($vars['mobile']))
            $errors['mobile']=__('Valid phone number is required');

        if($vars['passwd1'] || $vars['passwd2'] || $vars['cpasswd']) {

            if(!$vars['passwd1'])
                $errors['passwd1']=__('New password is required');
            elseif($vars['passwd1'] && strlen($vars['passwd1'])<6)
                $errors['passwd1']=__('Password must be at least 6 characters');
            elseif($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2']))
                $errors['passwd2']=__('Passwords do not match');

            if (($rtoken = $_SESSION['_staff']['reset-token'])) {
                $_config = new Config('pwreset');
                if ($_config->get($rtoken) != $this->getId())
                    $errors['err'] =
                        __('Invalid reset token. Logout and try again');
                elseif (!($ts = $_config->lastModified($rtoken))
                        && ($cfg->getPwResetWindow() < (time() - strtotime($ts))))
                    $errors['err'] =
                        __('Invalid reset token. Logout and try again');
            }
            elseif(!$vars['cpasswd'])
                $errors['cpasswd']=__('Current password is required');
            elseif(!$this->cmp_passwd($vars['cpasswd']))
                $errors['cpasswd']=__('Invalid current password!');
            elseif(!strcasecmp($vars['passwd1'], $vars['cpasswd']))
                $errors['passwd1']=__('New password MUST be different from the current password!');
        }

        if($vars['default_signature_type']=='mine' && !$vars['signature'])
            $errors['default_signature_type'] = __("You don't have a signature");

        if($errors) return false;

        $_SESSION['staff:lang'] = null;
        TextDomain::configureForUser($this);

        $this->firstname = $vars['firstname'];
        $this->lastname = $vars['lastname'];
        $this->email = $vars['email'];
        $this->phone = Format::phone($vars['phone']);
        $this->phone_ext = $vars['phone_ext'];
        $this->mobile = Format::phone($vars['mobile']);
        $this->signature = Format::sanitize($vars['signature']);
        $this->timezone = $vars['timezone'];
        $this->locale = $vars['locale'];
        $this->show_assigned_tickets = isset($vars['show_assigned_tickets'])?1:0;
        $this->max_page_size = $vars['max_page_size'];
        $this->auto_refresh_rate = $vars['auto_refresh_rate'];
        $this->default_signature_type = $vars['default_signature_type'];
        $this->default_paper_size = $vars['default_paper_size'];
        $this->lang = $vars['lang'];

        if ($vars['passwd1']) {
            $this->change_passwd = 0;
            $this->passwdreset = SqlFunction::NOW();
            $this->passwd = Passwd::hash($vars['passwd1']);
            $info = array('password' => $vars['passwd1']);
            Signal::send('auth.pwchange', $this, $info);
            $this->cancelResetTokens();
        }

        return $this->save();
    }

    function updateTeams($team_ids) {

        if (is_array($team_ids)) {
            $members = TeamMember::objects()
                ->filter(array('staff_id' => $this->getId()));
            foreach ($members as $member) {
                if ($idx = array_search($member->team_id, $team_ids)) {
                    unset($team_ids[$idx]);
                } else {
                    $member->delete();
                }
            }

            foreach ($team_ids as $id) {
                TeamMember::create(array(
                    'staff_id'=>$this->getId(),
                    'team_id'=>$id
                ))->save();
            }
        } else {
            TeamMember::objects()
                ->filter(array('staff_id'=>$this->getId()))
                ->delete();
        }

        return true;
    }

    function delete() {
        global $thisstaff;

        if (!$thisstaff || $this->getId() == $thisstaff->getId())
            return false;

        if (!parent::delete())
            return false;

        // DO SOME HOUSE CLEANING
        //Move remove any ticket assignments...TODO: send alert to Dept. manager?
        db_query('UPDATE '.TICKET_TABLE.' SET staff_id=0 WHERE staff_id='.db_input($this->getId()));

        //Update the poster and clear staff_id on ticket thread table.
        db_query('UPDATE '.TICKET_THREAD_TABLE
                .' SET staff_id=0, poster= '.db_input($this->getName()->getOriginal())
                .' WHERE staff_id='.db_input($this->getId()));

        // Cleanup Team membership table.
        $this->updateTeams(array());

        return true;
    }

    /**** Static functions ********/
    static function lookup($var) {
        if (is_array($var))
            return parent::lookup($var);
        elseif (is_numeric($var))
            return parent::lookup(array('staff_id'=>$var));
        elseif (Validator::is_email($var))
            return parent::lookup(array('email'=>$var));
        elseif (is_string($var))
            return parent::lookup(array('username'=>$var));
        else
            return null;
    }

    static function getStaffMembers($availableonly=false) {

        $members = static::objects()->order_by('lastname', 'firstname');

        if ($availableonly) {
            $members = $members->filter(array(
                'group__flags__hasbit' => Group::FLAG_ENABLED,
                'onvacation' => 0,
                'isactive' => 1,
            ));
        }

        $users=array();
        foreach ($members as $M) {
            $users[$M->getId()] = $M->getName();
        }

        return $users;
    }

    static function getAvailableStaffMembers() {
        return self::getStaffMembers(true);
    }

    static function getIdByUsername($username) {
        $row = static::objects()->filter(array('username' => $username))
            ->values_flat('staff_id')->first();
        return $row ? $row[0] : 0;
    }

    function getIdByEmail($email) {
        $row = static::objects()->filter(array('email' => $email))
            ->values_flat('staff_id')->first();
        return $row ? $row[0] : 0;
    }


    static function create($vars=false) {
        $staff = parent::create($vars);
        $staff->created = SqlFunction::NOW();
        return $staff;
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

        $content = Page::lookupByType($template);
        $token = Misc::randCode(48); // 290-bits

        if (!$content)
            return new Error(/* @trans */ 'Unable to retrieve password reset email template');

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
            $ost->logWarning(_S('Agent Password Reset'), sprintf(
             _S('Password reset was attempted for agent: %1$s<br><br>
                Requested-User-Id: %2$s<br>
                Source-Ip: %3$s<br>
                Email-Sent-To: %4$s<br>
                Email-Sent-Via: %5$s'),
                $this->getName(),
                $_POST['userid'],
                $_SERVER['REMOTE_ADDR'],
                $this->getEmail(),
                $email->getEmail()
            ), false);

        $lang = $this->lang ?: $this->getExtraAttr('browser_lang');
        $msg = $ost->replaceTemplateVariables(array(
            'subj' => $content->getLocalName($lang),
            'body' => $content->getLocalBody($lang),
        ), $vars);

        $_config = new Config('pwreset');
        $_config->set($vars['token'], $this->getId());

        $email->send($this->getEmail(), Format::striptags($msg['subj']),
            $msg['body']);
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    function update($vars, &$errors) {

        $vars['username']=Format::striptags($vars['username']);
        $vars['firstname']=Format::striptags($vars['firstname']);
        $vars['lastname']=Format::striptags($vars['lastname']);

        if (isset($this->staff_id) && $this->getId() != $vars['id'])
            $errors['err']=__('Internal Error');

        if(!$vars['firstname'])
            $errors['firstname']=__('First name required');
        if(!$vars['lastname'])
            $errors['lastname']=__('Last name required');

        $error = '';
        if(!$vars['username'] || !Validator::is_username($vars['username'], $error))
            $errors['username']=($error) ? $error : __('Username is required');
        elseif (($uid=static::getIdByUsername($vars['username']))
                && (!isset($this->staff_id) || $uid!=$this->getId()))
            $errors['username']=__('Username already in use');

        if(!$vars['email'] || !Validator::is_email($vars['email']))
            $errors['email']=__('Valid email is required');
        elseif(Email::getIdByEmail($vars['email']))
            $errors['email']=__('Already in use system email');
        elseif (($uid=static::getIdByEmail($vars['email']))
                && (!isset($this->staff_id) || $uid!=$this->getId()))
            $errors['email']=__('Email already in use by another agent');

        if($vars['phone'] && !Validator::is_phone($vars['phone']))
            $errors['phone']=__('Valid phone number is required');

        if($vars['mobile'] && !Validator::is_phone($vars['mobile']))
            $errors['mobile']=__('Valid phone number is required');

        if($vars['passwd1'] || $vars['passwd2'] || !$vars['id']) {
            if($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2'])) {
                $errors['passwd2']=__('Passwords do not match');
            }
            elseif ($vars['backend'] != 'local' || $vars['welcome_email']) {
                // Password can be omitted
            }
            elseif(!$vars['passwd1'] && !$vars['id']) {
                $errors['passwd1']=__('Temporary password is required');
                $errors['temppasswd']=__('Required');
            } elseif($vars['passwd1'] && strlen($vars['passwd1'])<6) {
                $errors['passwd1']=__('Password must be at least 6 characters');
            }
        }

        if(!$vars['dept_id'])
            $errors['dept_id']=__('Department is required');

        if(!$vars['group_id'])
            $errors['group_id']=__('Group is required');

        if ($errors)
            return false;

        $this->isadmin = $vars['isadmin'];
        $this->isactive = $vars['isactive'];
        $this->isvisible = isset($vars['isvisible'])?1:0;
        $this->onvacation = isset($vars['onvacation'])?1:0;
        $this->assigned_only = isset($vars['assigned_only'])?1:0;
        $this->dept_id = $vars['dept_id'];
        $this->group_id = $vars['group_id'];
        $this->timezone = $vars['timezone'];
        $this->username = $vars['username'];
        $this->firstname = $vars['firstname'];
        $this->lastname = $vars['lastname'];
        $this->email = $vars['email'];
        $this->backend = $vars['backend'];
        $this->phone = Format::phone($vars['phone']);
        $this->phone_ext = $vars['phone_ext'];
        $this->mobile = Format::phone($vars['mobile']);
        $this->signature = Format::sanitize($vars['signature']);
        $this->notes = Format::sanitize($vars['notes']);

        if ($vars['passwd1']) {
            $this->passwd = Passwd::hash($vars['passwd1']);
            if (isset($vars['change_passwd']))
                $this->change_passwd = 1;
        }
        elseif (!isset($vars['change_passwd'])) {
            $this->change_passwd = 0;
        }

        if ($this->save() && $this->updateTeams($vars['teams'])) {
            if ($vars['welcome_email'])
                $this->sendResetEmail('registration-staff');
            return true;
        }

        if (isset($this->staff_id)) {
            $errors['err']=sprintf(__('Unable to update %s.'), __('this agent'))
               .' '.__('Internal error occurred');
        } else {
            $errors['err']=sprintf(__('Unable to create %s.'), __('this agent'))
               .' '.__('Internal error occurred');
        }
        return false;
    }
}
?>
