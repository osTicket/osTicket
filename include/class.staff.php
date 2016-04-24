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
include_once(INCLUDE_DIR.'class.passwd.php');
include_once(INCLUDE_DIR.'class.user.php');
include_once(INCLUDE_DIR.'class.auth.php');

class Staff extends VerySimpleModel
implements AuthenticatedUser, EmailContact, TemplateVariable {

    static $meta = array(
        'table' => STAFF_TABLE,
        'pk' => array('staff_id'),
        'joins' => array(
            'dept' => array(
                'constraint' => array('dept_id' => 'Dept.id'),
            ),
            'role' => array(
                'constraint' => array('role_id' => 'Role.id'),
            ),
            'dept_access' => array(
                'reverse' => 'StaffDeptAccess.staff',
            ),
            'teams' => array(
                'reverse' => 'TeamMember.staff',
            ),
        ),
    );

    var $authkey;
    var $departments;
    var $stats = array();
    var $_extra;
    var $passwd_change;
    var $_roles = null;
    var $_teams = null;
    var $_config = null;
    var $_perm;

    function __onload() {

        // WE have to patch info here to support upgrading from old versions.
        $time = null;
        if (isset($this->passwdreset) && $this->passwdreset)
            $time=strtotime($this->passwdreset);
        elseif (isset($this->added) && $this->added)
            $time=strtotime($this->added);

        if ($time)
            $this->passwd_change = time()-$time; //XXX: check timezone issues.
    }

    function get($field, $default=false) {

        // Autoload config if not loaded already
        if (!isset($this->_config))
            $this->getConfig();

        if (isset($this->_config[$field]))
            return $this->_config[$field];

        return parent::get($field, $default);
    }

    function getConfig() {

        if (!isset($this->_config) && $this->getId()) {
            $_config = new Config('staff.'.$this->getId(),
                    // Defaults
                    array(
                        'default_from_name' => '',
                        'datetime_format'   => '',
                        'thread_view_order' => '',
                        ));
            $this->_config = $_config->getInfo();
        }

        return $this->_config;
    }

    function __toString() {
        return (string) $this->getName();
    }

    function asVar() {
        return $this->__toString();
    }

    static function getVarScope() {
      return array(
        'dept' => array('class' => 'Dept', 'desc' => __('Department')),
        'email' => __('Email Address'),
        'name' => array(
          'class' => 'PersonsName', 'desc' => __('Agent name'),
        ),
        'mobile' => __('Mobile Number'),
        'phone' => __('Phone Number'),
        'signature' => __('Signature'),
        'timezone' => "Agent's configured timezone",
        'username' => 'Access username',
      );
    }

    function getVar($tag) {
        switch ($tag) {
        case 'mobile':
            return Format::phone($this->ht['mobile']);
        case 'phone':
            return Format::phone($this->ht['phone']);
        }
    }

    function getHashtable() {
        $base = $this->ht;
        unset($base['teams']);
        unset($base['dept_access']);

        if ($this->getConfig())
            $base += $this->getConfig();

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
        list($bk, ) = explode(':', $this->getAuthKey());

        // If administering a user other than yourself, fallback to the
        // agent's declared backend, if any
        if (!$bk && $this->backend)
            $bk = $this->backend;

        return StaffAuthenticationBackend::getBackend($bk);
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
        return $this->save();
    }

    /* check if passwd reset is due. */
    function isPasswdResetDue() {
        global $cfg;
        return ($cfg && $cfg->getPasswdResetPeriod()
                    && $this->passwd_change>($cfg->getPasswdResetPeriod()*30*24*60*60));
    }

    function setPassword($new, $current=false) {
        // Allow the backend to update the password. This is the preferred
        // method as it allows for integration with password policies and
        // also allows for remotely updating the password where possible and
        // supported.
        if (!($bk = $this->getAuthBackend())
            || !$bk instanceof AuthBackend
        ) {
            // Fallback to osTicket authentication token udpates
            $bk = new osTicketAuthentication();
        }

        // And now for the magic
        if (!$bk->supportsPasswordChange()) {
            throw new PasswordUpdateFailed(
                __('Authentication backend does not support password updates'));
        }
        // Backend should throw PasswordUpdateFailed directly
        $rv = $bk->setPassword($this, $new, $current);

        // Successfully updated authentication tokens
        $this->change_passwd = 0;
        $this->cancelResetTokens();
        $this->passwdreset = SqlFunction::NOW();

        return $rv;
    }

    function canAccess($something) {
        if ($something instanceof RestrictedAccess)
            return $something->checkStaffPerm($this);

        return true;
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
    function getUserId() {
        return $this->getId();
    }

    function getEmail() {
        return $this->email;
    }

    function getAvatar($size=null) {
        global $cfg;
        $source = $cfg->getStaffAvatarSource();
        $avatar = $source->getAvatar($this);
        if (isset($size))
            $avatar->setSize($size);
        return $avatar;
    }

    function getUserName() {
        return $this->username;
    }

    function getPasswd() {
        return $this->passwd;
    }

    function getName() {
        return new AgentsName(array('first' => $this->ht['firstname'], 'last' => $this->ht['lastname']));
    }

    function getAvatarAndName() {
        return $this->getAvatar().Format::htmlchars((string) $this->getName());
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

    function getReplyFromNameType() {
        return $this->default_from_name;
    }

    function getDefaultPaperSize() {
        return $this->default_paper_size;
    }

    function forcePasswdChange() {
        return $this->change_passwd;
    }

    function getDepartments() {
        // TODO: Cache this in the agent's session as it is unlikely to
        //       change while logged in

        if (!isset($this->departments)) {

            // Departments the staff is "allowed" to access...
            // based on the group they belong to + user's primary dept + user's managed depts.
            $sql='SELECT DISTINCT d.id FROM '.STAFF_TABLE.' s '
                .' LEFT JOIN '.STAFF_DEPT_TABLE.' g ON (s.staff_id=g.staff_id) '
                .' INNER JOIN '.DEPT_TABLE.' d ON (LOCATE(CONCAT("/", s.dept_id, "/"), d.path) OR d.manager_id=s.staff_id OR LOCATE(CONCAT("/", g.dept_id, "/"), d.path)) '
                .' WHERE s.staff_id='.db_input($this->getId());
            $depts = array();
            if (($res=db_query($sql)) && db_num_rows($res)) {
                while(list($id)=db_fetch_row($res))
                    $depts[] = $id;
            }

            /* ORM method — about 2.0ms slower
            $q = Q::any(array(
                'path__contains' => '/'.$this->dept_id.'/',
                'manager_id' => $this->getId(),
            ));
            // Add in extended access
            foreach ($this->dept_access->depts->values_flat('dept_id') as $row) {
                // Skip primary dept
                if ($row[0] == $this->dept_id)
                    continue;
                $q->add(new Q(array('path__contains'=>'/'.$row[0].'/')));
            }

            $dept_ids = Dept::objects()
                ->filter($q)
                ->distinct('id')
                ->values_flat('id');

            foreach ($dept_ids as $row)
                $depts[] = $row[0];
            */

            $this->departments = $depts;
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

    function getDeptId() {
        return $this->dept_id;
    }

    function getDept() {
        return $this->dept;
    }

    function setDepartmentId($dept_id, $eavesdrop=false) {
        // Grant access to the current department
        $old = $this->dept_id;
        if ($eavesdrop) {
            $da = StaffDeptAccess::create(array(
                'dept_id' => $old,
                'role_id' => $this->role_id,
            ));
            $da->setAlerts(true);
            $this->dept_access->add($da);
        }

        // Drop extended access to new department
        $this->dept_id = $dept_id;
        if ($da = $this->dept_access->findFirst(array(
            'dept_id' => $dept_id))
        ) {
            $this->dept_access->remove($da);
        }
    }

    function usePrimaryRoleOnAssignment() {
        return $this->getExtraAttr('def_assn_role', true);
    }

    function getLanguage() {
        return (isset($this->lang)) ? $this->lang : false;
    }

    function getTimezone() {
        if (isset($this->timezone))
            return $this->timezone;
    }

    function getLocale() {
        //XXX: isset is required here to avoid possible crash when upgrading
        // installation where locale column doesn't exist yet.
        return isset($this->locale) ? $this->locale : 0;
    }

    function getRole($dept=null) {
        $deptId = is_object($dept) ? $dept->getId() : $dept;
        if ($deptId && $deptId != $this->dept_id) {
            if (isset($this->_roles[$deptId]))
                return $this->_roles[$deptId];

            if ($access = $this->dept_access->findFirst(array('dept_id' => $deptId)))
                return $this->_roles[$deptId] = $access->role;

            if (!$this->usePrimaryRoleOnAssignment())
                // View only access
                return new Role(array());

            // Fall through to primary role
        }
        // For the primary department, use the primary role
        return $this->role;
    }

    function hasPerm($perm, $global=true) {
        if ($global)
            return $this->getPermission()->has($perm);
        if ($this->getRole()->hasPerm($perm))
            return true;
        foreach ($this->dept_access as $da)
            if ($da->role->hasPerm($perm))
                return true;
        return false;
    }

    function canManageTickets() {
        return $this->hasPerm(TicketModel::PERM_DELETE, false)
                || $this->hasPerm(TicketModel::PERM_TRANSFER, false)
                || $this->hasPerm(TicketModel::PERM_ASSIGN, false)
                || $this->hasPerm(TicketModel::PERM_CLOSE, false);
    }

    function isManager() {
        return (($dept=$this->getDept()) && $dept->getManagerId()==$this->getId());
    }

    function isStaff() {
        return TRUE;
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
        return ($this->isactive() && !$this->onVacation());
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

    function getTasksStats() {

        if (!$this->stats['tasks'])
            $this->stats['tasks'] = Task::getStaffStats($this);

        return  $this->stats['tasks'];
    }

    function getNumAssignedTasks() {
        return ($stats=$this->getTasksStats()) ? $stats['assigned'] : 0;
    }

    function getNumClosedTasks() {
        return ($stats=$this->getTasksStats()) ? $stats['closed'] : 0;
    }

    function getExtraAttr($attr=false, $default=null) {
        if (!isset($this->_extra) && isset($this->extra))
            $this->_extra = JsonDataParser::decode($this->extra);

        return $attr
            ? (isset($this->_extra[$attr]) ? $this->_extra[$attr] : $default)
            : $this->_extra;
    }

    function setExtraAttr($attr, $value, $commit=true) {
        $this->getExtraAttr();
        $this->_extra[$attr] = $value;
        $this->extra = JsonDataEncoder::encode($this->_extra);

        if ($commit) {
            $this->save();
        }
    }

    function getPermission() {
        if (!isset($this->_perm)) {
            $this->_perm = new RolePermission($this->permissions);
        }
        return $this->_perm;
    }

    function getPermissionInfo() {
        return $this->getPermission()->getInfo();
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

        if(!$vars['email'] || !Validator::is_valid_email($vars['email']))
            $errors['email']=__('Valid email is required');
        elseif(Email::getIdByEmail($vars['email']))
            $errors['email']=__('Already in-use as system email');
        elseif (($uid=static::getIdByEmail($vars['email']))
                && (!isset($this->staff_id) || $uid!=$this->getId()))
            $errors['email']=__('Email already in use by another agent');

        if($vars['phone'] && !Validator::is_phone($vars['phone']))
            $errors['phone']=__('Valid phone number is required');

        if($vars['mobile'] && !Validator::is_phone($vars['mobile']))
            $errors['mobile']=__('Valid phone number is required');

        if($vars['default_signature_type']=='mine' && !$vars['signature'])
            $errors['default_signature_type'] = __("You don't have a signature");

        // Update the user's password if requested
        if ($vars['passwd1']) {
            try {
                $this->setPassword($vars['passwd1'], $vars['cpasswd']);
            }
            catch (BadPassword $ex) {
                $errors['passwd1'] = $ex->getMessage();
            }
            catch (PasswordUpdateFailed $ex) {
                // TODO: Add a warning banner or crash the update
            }
        }

        $this->firstname = $vars['firstname'];
        $this->lastname = $vars['lastname'];
        $this->email = $vars['email'];
        $this->phone = Format::phone($vars['phone']);
        $this->phone_ext = $vars['phone_ext'];
        $this->mobile = Format::phone($vars['mobile']);
        $this->signature = Format::sanitize($vars['signature']);
        $this->timezone = $vars['timezone'];
        $this->locale = $vars['locale'];
        if (!$cfg->showAssignedTickets())
            // Allow local unsetting if unset globally
            $this->show_assigned_tickets = isset($vars['show_assigned_tickets']) ? 1 : 0;
        $this->max_page_size = $vars['max_page_size'];
        $this->auto_refresh_rate = $vars['auto_refresh_rate'];
        $this->default_signature_type = $vars['default_signature_type'];
        $this->default_paper_size = $vars['default_paper_size'];
        $this->lang = $vars['lang'];
        $this->onvacation = isset($vars['onvacation']) ? 1 : 0;

        if (isset($vars['avatar_code']))
          $this->setExtraAttr('avatar', $vars['avatar_code']);

        if ($errors)
            return false;

        $_SESSION['::lang'] = null;
        TextDomain::configureForUser($this);

        // Update the config information
        $_config = new Config('staff.'.$this->getId());
        $_config->updateAll(array(
                    'datetime_format' => $vars['datetime_format'],
                    'default_from_name' => $vars['default_from_name'],
                    'thread_view_order' => $vars['thread_view_order'],
                    )
                );
        $this->_config = $_config->getInfo();

        return $this->save();
    }

    function updateTeams($membership, &$errors) {
        $dropped = array();
        foreach ($this->teams as $TM)
            $dropped[$TM->team_id] = 1;

        reset($membership);
        while(list(, list($team_id, $alerts)) = each($membership)) {
            $member = $this->teams->findFirst(array('team_id' => $team_id));
            if (!$member) {
                $this->teams->add($member = new TeamMember(array(
                    'team_id' => $team_id,
                )));
            }
            $member->setAlerts($alerts);
            if (!$errors)
                $member->save();
            unset($dropped[$member->team_id]);
        }
        if (!$errors && $dropped) {
            $member = $this->teams
                ->filter(array('team_id__in' => array_keys($dropped)))
                ->delete();
            $this->teams->reset();
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
        Ticket::objects()
            ->filter(array('staff_id' => $this->getId()))
            ->update(array('staff_id' => 0));

        //Update the poster and clear staff_id on ticket thread table.
        ThreadEntry::objects()
            ->filter(array('staff_id' => $this->getId()))
            ->update(array(
                'staff_id' => 0,
                'poster' => $this->getName()->getOriginal(),
            ));

        // Cleanup Team membership table.
        TeamMember::objects()
            ->filter(array('staff_id'=>$this->getId()))
            ->delete();

        // Cleanup staff dept access
        StaffDeptAccess::objects()
            ->filter(array('staff_id'=>$this->getId()))
            ->delete();

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

    static function getStaffMembers($criteria=array()) {
        global $cfg;

        $members = static::objects();

        if (isset($criteria['available'])) {
            $members = $members->filter(array(
                'onvacation' => 0,
                'isactive' => 1,
            ));
        }

        $members = self::nsort($members);

        $users=array();
        foreach ($members as $M) {
            $users[$M->getId()] = $M->getName();
        }

        return $users;
    }

    static function getAvailableStaffMembers() {
        return self::getStaffMembers(array('available'=>true));
    }

    static function nsort(QuerySet $qs, $path='', $format=null) {
        global $cfg;

        $format = $format ?: $cfg->getAgentNameFormat();
        switch ($format) {
        case 'last':
        case 'lastfirst':
        case 'legal':
            $qs->order_by("{$path}lastname", "{$path}firstname");
            break;
        default:
            $qs->order_by("${path}firstname", "${path}lastname");
        }

        return $qs;
    }

    static function getIdByUsername($username) {
        $row = static::objects()->filter(array('username' => $username))
            ->values_flat('staff_id')->first();
        return $row ? $row[0] : 0;
    }

    static function getIdByEmail($email) {
        $row = static::objects()->filter(array('email' => $email))
            ->values_flat('staff_id')->first();
        return $row ? $row[0] : 0;
    }


    static function create($vars=false) {
        $staff = new static($vars);
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

    function sendResetEmail($template='pwreset-staff', $log=true) {
        global $ost, $cfg;

        $content = Page::lookupByType($template);
        $token = Misc::randCode(48); // 290-bits

        if (!$content)
            return new BaseError(/* @trans */ 'Unable to retrieve password reset email template');

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

        $info = array('email' => $email, 'vars' => &$vars, 'log'=>$log);
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

    static function importCsv($stream, $defaults=array(), $callback=false) {
        require_once INCLUDE_DIR . 'class.import.php';

        $importer = new CsvImporter($stream);
        $imported = 0;
        $fields = array(
            'firstname' => new TextboxField(array(
                'label' => __('First Name'),
            )),
            'lastname' => new TextboxField(array(
                'label' => __('Last Name'),
            )),
            'email' => new TextboxField(array(
                'label' => __('Email Address'),
                'configuration' => array(
                    'validator' => 'email',
                ),
            )),
            'username' => new TextboxField(array(
                'label' => __('Username'),
                'validators' => function($self, $value) {
                    if (!Validator::is_username($value))
                        $self->addError('Not a valid username');
                },
            )),
        );
        $form = new SimpleForm($fields);

        try {
            db_autocommit(false);
            $errors = array();
            $records = $importer->importCsv($form->getFields(), $defaults);
            foreach ($records as $data) {
                if (!isset($data['email']) || !isset($data['username']))
                    throw new ImportError('Both `username` and `email` fields are required');

                if ($agent = self::lookup(array('username' => $data['username']))) {
                    // TODO: Update the user
                }
                elseif ($agent = self::create($data, $errors)) {
                    if ($callback)
                        $callback($agent, $data);
                    $agent->save();
                }
                else {
                    throw new ImportError(sprintf(__('Unable to import (%s): %s'),
                        $data['username'],
                        print_r($errors, true)
                    ));
                }
                $imported++;
            }
            db_autocommit(true);
        }
        catch (Exception $ex) {
            db_rollback();
            return $ex->getMessage();
        }
        return $imported;
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

        if(!$vars['email'] || !Validator::is_valid_email($vars['email']))
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

        if(!$vars['dept_id'])
            $errors['dept_id']=__('Department is required');
        if(!$vars['role_id'])
            $errors['role_id']=__('Role for primary department is required');

        // Ensure we will still have an administrator with access
        if ($vars['isadmin'] !== '1' || $vars['islocked'] === '1') {
            $sql = 'select count(*), max(staff_id) from '.STAFF_TABLE
                .' WHERE isadmin=1 and isactive=1';
            if (($res = db_query($sql))
                    && (list($count, $sid) = db_fetch_row($res))) {
                if ($count == 1 && $sid == $uid) {
                    $errors['isadmin'] = __(
                        'Cowardly refusing to remove or lock out the only active administrator'
                    );
                }
            }
        }

        // Update some things for ::updateAccess to inspect
        $this->setDepartmentId($vars['dept_id']);

        // Format access update as [array(dept_id, role_id, alerts?)]
        $access = array();
        if (isset($vars['dept_access'])) {
            foreach (@$vars['dept_access'] as $dept_id) {
                $access[] = array($dept_id, $vars['dept_access_role'][$dept_id],
                    @$vars['dept_access_alerts'][$dept_id]);
            }
        }
        $this->updateAccess($access, $errors);
        $this->setExtraAttr('def_assn_role',
            isset($vars['assign_use_pri_role']), false);

        // Format team membership as [array(team_id, alerts?)]
        $teams = array();
        if (isset($vars['teams'])) {
            foreach (@$vars['teams'] as $team_id) {
                $teams[] = array($team_id, @$vars['team_alerts'][$team_id]);
            }
        }
        $this->updateTeams($teams, $errors);

        // Update the local permissions
        $this->updatePerms($vars['perms'], $errors);

        $this->isadmin = $vars['isadmin'];
        $this->isactive = isset($vars['islocked']) ? 0 : 1;
        $this->isvisible = isset($vars['isvisible'])?1:0;
        $this->onvacation = isset($vars['onvacation'])?1:0;
        $this->assigned_only = isset($vars['assigned_only'])?1:0;
        $this->role_id = $vars['role_id'];
        $this->username = $vars['username'];
        $this->firstname = $vars['firstname'];
        $this->lastname = $vars['lastname'];
        $this->email = $vars['email'];
        $this->backend = $vars['backend'];
        $this->phone = Format::phone($vars['phone']);
        $this->phone_ext = $vars['phone_ext'];
        $this->mobile = Format::phone($vars['mobile']);
        $this->notes = Format::sanitize($vars['notes']);

        if ($errors)
            return false;

        if ($this->save()) {
            if ($vars['welcome_email'])
                $this->sendResetEmail('registration-staff', false);
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

    /**
     * Parameters:
     * $access - (<array($dept_id, $role_id, $alerts)>) a list of the complete,
     *      extended access for this agent. Any the agent currently has, which
     *      is not listed will be removed.
     * $errors - (<array>) list of error messages from the process, which will
     *      be indexed by the dept_id number.
     */
    function updateAccess($access, &$errors) {
        reset($access);
        $dropped = array();
        foreach ($this->dept_access as $DA)
            $dropped[$DA->dept_id] = 1;
        while (list(, list($dept_id, $role_id, $alerts)) = each($access)) {
            unset($dropped[$dept_id]);
            if (!$role_id || !Role::lookup($role_id))
                $errors['dept_access'][$dept_id] = __('Select a valid role');
            if (!$dept_id || !Dept::lookup($dept_id))
                $errors['dept_access'][$dept_id] = __('Select a valid departent');
            if ($dept_id == $this->getDeptId())
                $errors['dept_access'][$dept_id] = __('Agent already has access to this department');
            $da = $this->dept_access->findFirst(array('dept_id' => $dept_id));
            if (!isset($da)) {
                $da = new StaffDeptAccess(array(
                    'dept_id' => $dept_id, 'role_id' => $role_id
                ));
                $this->dept_access->add($da);
            }
            else {
                $da->role_id = $role_id;
            }
            $da->setAlerts($alerts);
            if (!$errors)
                $da->save();
        }
        if (!$errors && $dropped) {
            $this->dept_access
                ->filter(array('dept_id__in' => array_keys($dropped)))
                ->delete();
            $this->dept_access->reset();
        }
        return !$errors;
    }

    function updatePerms($vars, &$errors=array()) {
        if (!$vars) {
            $this->permissions = '';
            return;
        }
        $permissions = $this->getPermission();
        foreach (RolePermission::allPermissions() as $g => $perms) {
            foreach ($perms as $k => $v) {
                $permissions->set($k, in_array($k, $vars) ? 1 : 0);
            }
        }
        $this->permissions = $permissions->toJson();
        return true;
    }

}

interface RestrictedAccess {
    function checkStaffPerm($staff);
}

class StaffDeptAccess extends VerySimpleModel {
    static $meta = array(
        'table' => STAFF_DEPT_TABLE,
        'pk' => array('staff_id', 'dept_id'),
        'select_related' => array('dept', 'role'),
        'joins' => array(
            'dept' => array(
                'constraint' => array('dept_id' => 'Dept.id'),
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
            ),
            'role' => array(
                'constraint' => array('role_id' => 'Role.id'),
            ),
        ),
    );

    const FLAG_ALERTS =     0x0001;

    function isAlertsEnabled() {
        return $this->flags & self::FLAG_ALERTS != 0;
    }

    function setFlag($flag, $value) {
        if ($value)
            $this->flags |= $flag;
        else
            $this->flags &= ~$flag;
    }

    function setAlerts($value) {
        $this->setFlag(self::FLAG_ALERTS, $value);
    }
}

/**
 * This form is used to administratively change the password. The
 * ChangePasswordForm is used for an agent to change their own password.
 */
class PasswordResetForm
extends AbstractForm {
    function buildFields() {
        return array(
            'welcome_email' => new BooleanField(array(
                'default' => true,
                'configuration' => array(
                    'desc' => __('Send the agent a password reset email'),
                ),
            )),
            'passwd1' => new PasswordField(array(
                'placeholder' => __('New Password'),
                'required' => true,
                'configuration' => array(
                    'classes' => 'span12',
                ),
                'visibility' => new VisibilityConstraint(
                    new Q(array('welcome_email' => false)),
                    VisibilityConstraint::HIDDEN
                ),
            )),
            'passwd2' => new PasswordField(array(
                'placeholder' => __('Confirm Password'),
                'required' => true,
                'configuration' => array(
                    'classes' => 'span12',
                ),
                'visibility' => new VisibilityConstraint(
                    new Q(array('welcome_email' => false)),
                    VisibilityConstraint::HIDDEN
                ),
            )),
            'change_passwd' => new BooleanField(array(
                'default' => true,
                'configuration' => array(
                    'desc' => __('Require password change at next login'),
                    'classes' => 'form footer',
                ),
                'visibility' => new VisibilityConstraint(
                    new Q(array('welcome_email' => false)),
                    VisibilityConstraint::HIDDEN
                ),
            )),
        );
    }

    function validate($clean) {
        if ($clean['passwd1'] != $clean['passwd2'])
            $this->getField('passwd1')->addError(__('Passwords do not match'));
    }
}

class PasswordChangeForm
extends AbstractForm {
    function buildFields() {
        $fields = array(
            'current' => new PasswordField(array(
                'placeholder' => __('Current Password'),
                'required' => true,
                'configuration' => array(
                    'autofocus' => true,
                ),
            )),
            'passwd1' => new PasswordField(array(
                'label' => __('Enter a new password'),
                'placeholder' => __('New Password'),
                'required' => true,
            )),
            'passwd2' => new PasswordField(array(
                'placeholder' => __('Confirm Password'),
                'required' => true,
            )),
        );

        // When using the password reset system, the current password is not
        // required for agents.
        if (isset($_SESSION['_staff']['reset-token'])) {
            unset($fields['current']);
            $fields['passwd1']->set('configuration', array('autofocus' => true));
        }
        else {
            $fields['passwd1']->set('layout',
                new GridFluidCell(12, array('style' => 'padding-top: 20px'))
            );
        }
        return $fields;
    }

    function getInstructions() {
        return __('Confirm your current password and enter a new password to continue');
    }

    function validate($clean) {
        if ($clean['passwd1'] != $clean['passwd2'])
            $this->getField('passwd1')->addError(__('Passwords do not match'));
    }
}

class ResetAgentPermissionsForm
extends AbstractForm {
    function buildFields() {
        $permissions = array();
        foreach (RolePermission::allPermissions() as $g => $perms) {
            foreach ($perms as $k => $v) {
                if (!$v['primary'])
                    continue;
                $permissions[$g][$k] = "{$v['title']} — {$v['desc']}";
            }
        }
        return array(
            'clone' => new ChoiceField(array(
                'default' => 0,
                'choices' =>
                    array(0 => '— '.__('Clone an existing agent').' —')
                    + Staff::getStaffMembers(),
                'configuration' => array(
                    'classes' => 'span12',
                ),
            )),
            'perms' => new ChoiceField(array(
                'choices' => $permissions,
                'widget' => 'TabbedBoxChoicesWidget',
                'configuration' => array(
                    'multiple' => true,
                ),
            )),
        );
    }

    function getClean() {
        $clean = parent::getClean();
        // Index permissions as ['ticket.edit' => 1]
        $clean['perms'] = array_keys($clean['perms']);
        return $clean;
    }

    function render($staff=true, $title=false, $options=array()) {
        return parent::render($staff, $title, $options + array('template' => 'dynamic-form-simple.tmpl.php'));
    }
}

class ChangeDepartmentForm
extends AbstractForm {
    function buildFields() {
        return array(
            'dept_id' => new ChoiceField(array(
                'default' => 0,
                'required' => true,
                'label' => __('Primary Department'),
                'choices' =>
                    array(0 => '— '.__('Primary Department').' —')
                    + Dept::getDepartments(),
                'configuration' => array(
                    'classes' => 'span12',
                ),
            )),
            'role_id' => new ChoiceField(array(
                'default' => 0,
                'required' => true,
                'label' => __('Primary Role'),
                'choices' =>
                    array(0 => '— '.__('Corresponding Role').' —')
                    + Role::getRoles(),
                'configuration' => array(
                    'classes' => 'span12',
                ),
            )),
            'eavesdrop' => new BooleanField(array(
                'configuration' => array(
                    'desc' => __('Maintain access to current primary department'),
                    'classes' => 'form footer',
                ),
            )),
            // alerts?
        );
    }

    function getInstructions() {
        return __('Change the primary department and primary role of the selected agents');
    }

    function getClean() {
        $clean = parent::getClean();
        $clean['eavesdrop'] = $clean['eavesdrop'] ? 1 : 0;
        return $clean;
    }

    function render($staff=true, $title=false, $options=array()) {
        return parent::render($staff, $title, $options + array('template' => 'dynamic-form-simple.tmpl.php'));
    }
}

class StaffQuickAddForm
extends AbstractForm {
    static $layout = 'GridFormLayout';

    function buildFields() {
        global $cfg;

        return array(
            'firstname' => new TextboxField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __("First Name"),
                    'autofocus' => true,
                ),
                'layout' => new GridFluidCell(6),
            )),
            'lastname' => new TextboxField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __("Last Name"),
                ),
                'layout' => new GridFluidCell(6),
            )),
            'email' => new TextboxField(array(
                'required' => true,
                'configuration' => array(
                    'validator' => 'email',
                    'placeholder' => __('Email Address — e.g. me@mycompany.com'),
                    'length' => 128,
                  ),
            )),
            'dept_id' => new ChoiceField(array(
                'label' => __('Department'),
                'required' => true,
                'choices' => Dept::getDepartments(),
                'default' => $cfg->getDefaultDeptId(),
                'layout' => new GridFluidCell(6),
            )),
            'role_id' => new ChoiceField(array(
                'label' => __('Primary Role'),
                'required' => true,
                'choices' =>
                    array(0 => __('Select Role'))
                    + Role::getRoles(),
                'layout' => new GridFluidCell(6),
            )),
            'isadmin' => new BooleanField(array(
                'label' => __('Account Type'),
                'configuration' => array(
                    'desc' => __('Agent has access to the admin panel'),
                ),
                'layout' => new GridFluidCell(6),
            )),
            'welcome_email' => new BooleanField(array(
                'configuration' => array(
                    'desc' => __('Send a welcome email with login information'),
                ),
                'default' => true,
                'layout' => new GridFluidCell(12, array('style' => 'padding-top: 50px')),
            )),
            'passwd1' => new PasswordField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __("Temporary Password"),
                ),
                'visibility' => new VisibilityConstraint(
                    new Q(array('welcome_email' => false))
                ),
                'layout' => new GridFluidCell(6),
            )),
            'passwd2' => new PasswordField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __("Confirm Password"),
                ),
                'visibility' => new VisibilityConstraint(
                    new Q(array('welcome_email' => false))
                ),
                'layout' => new GridFluidCell(6),
            )),
            // TODO: Add role_id drop-down
        );
    }

    function getClean() {
        $clean = parent::getClean();
        list($clean['username'],) = preg_split('/[^\w.-]/u', $clean['email'], 2);
        if (mb_strlen($clean['username']) < 3 || Staff::lookup($clean['username']))
            $clean['username'] = mb_strtolower($clean['firstname']);


        // Inherit default dept's role as primary role
        $clean['assign_use_pri_role'] = true;

        // Default permissions
        $clean['perms'] = array(
            User::PERM_CREATE,
            User::PERM_EDIT,
            User::PERM_DELETE,
            User::PERM_MANAGE,
            User::PERM_DIRECTORY,
            Organization::PERM_CREATE,
            Organization::PERM_EDIT,
            Organization::PERM_DELETE,
            FAQ::PERM_MANAGE,
        );
        return $clean;
    }
}
