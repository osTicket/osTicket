<?php
/*********************************************************************
    class.dept.php

    Department class

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR . 'class.search.php';
require_once INCLUDE_DIR.'class.role.php';

class Dept extends VerySimpleModel
implements TemplateVariable, Searchable {

    static $meta = array(
        'table' => DEPT_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'parent' => array(
                'constraint' => array('pid' => 'Dept.id'),
                'null' => true,
            ),
            'email' => array(
                'constraint' => array('email_id' => 'Email.email_id'),
                'null' => true,
             ),
            'sla' => array(
                'constraint' => array('sla_id' => 'SLA.id'),
                'null' => true,
            ),
            'manager' => array(
                'null' => true,
                'constraint' => array('manager_id' => 'Staff.staff_id'),
            ),
            'members' => array(
                'null' => true,
                'list' => true,
                'reverse' => 'Staff.dept',
            ),
            'extended' => array(
                'null' => true,
                'list' => true,
                'reverse' => 'StaffDeptAccess.dept'
            ),
        ),
    );

    var $_members;
    var $_primary_members;
    var $_extended_members;

    var $_groupids;
    var $config;

    var $schedule;

    var $template;
    var $autorespEmail;

    const ALERTS_DISABLED = 2;
    const DISPLAY_DISABLED = 2;
    const ALERTS_DEPT_AND_EXTENDED = 1;
    const ALERTS_DEPT_ONLY = 0;
    const ALERTS_ADMIN_ONLY = 3;

    const FLAG_ASSIGN_MEMBERS_ONLY = 0x0001;
    const FLAG_DISABLE_AUTO_CLAIM  = 0x0002;
    const FLAG_ACTIVE = 0x0004;
    const FLAG_ARCHIVED = 0x0008;
    const FLAG_ASSIGN_PRIMARY_ONLY = 0x0010;
    const FLAG_DISABLE_REOPEN_AUTO_ASSIGN = 0x0020;

    const PERM_DEPT = 'visibility.departments';

    static protected $perms = array(
        self::PERM_DEPT => array(
            'title' => /* @trans */ 'Department',
            'desc'  => /* @trans */ 'Ability to see all Departments',
            'primary' => true,
        ),
    );

    function asVar() {
        return $this->getName();
    }

    static function getVarScope() {
        return array(
            'name' => 'Department name',
            'manager' => array(
                'class' => 'Staff', 'desc' => 'Department manager',
                'exclude' => 'dept',
            ),
            'members' => array(
                'class' => 'UserList', 'desc' => 'Department members',
            ),
            'parent' => array(
                'class' => 'Dept', 'desc' => 'Parent department',
            ),
            'sla' => array(
                'class' => 'SLA', 'desc' => 'Service Level Agreement',
            ),
            'signature' => 'Department signature',
        );
    }

    function getVar($tag) {
        switch ($tag) {
        case 'members':
            return new UserList($this->getMembers()->all());
        }
    }

    static function getSearchableFields() {
        return array(
            'name' => new TextboxField(array(
                'label' => __('Name'),
            )),
            'manager' => new DepartmentManagerSelectionField(array(
                'label' => __('Manager'),
            )),
        );
    }

    static function supportsCustomData() {
        return false;
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getLocalName($locale=false) {
        $tag = $this->getTranslateTag();
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->name;
    }
    static function getLocalById($id, $subtag, $default) {
        $tag = _H(sprintf('dept.%s.%s', $subtag, $id));
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $default;
    }
    static function getLocalNameById($id, $default) {
        return static::getLocalById($id, 'name', $default);
    }

    function getTranslateTag($subtag='name') {
        return _H(sprintf('dept.%s.%s', $subtag, $this->getId()));
    }

    function getFullName() {
        return self::getNameById($this->getId());
    }

    function getStatus() {
        if($this->flags & self::FLAG_ACTIVE)
          return __('Active');
        elseif($this->flags & self::FLAG_ARCHIVED)
          return __('Archived');
        else
          return __('Disabled');
    }

    function allowsReopen() {
      return !($this->flags & self::FLAG_ARCHIVED);
    }

    function isActive() {
        return !!($this->flags & self::FLAG_ACTIVE);
    }

    function getEmailId() {
        return $this->email_id;
    }

    /**
     * getAlertEmail
     *
     * Fetches either the department email (for replies) if configured.
     * Otherwise, the system alert email address is used.
     */
    function getAlertEmail() {
        global $cfg;

        if ($this->email)
            return $this->email;

        return $cfg ? $cfg->getDefaultEmail() : null;
    }

    function getEmail() {
        global $cfg;

        if ($this->email)
            return $this->email;

        return $cfg? $cfg->getDefaultEmail() : null;
    }

    function getNumMembers() {
        return count($this->getMembers());
    }

    function getMembers() {
        if (!isset($this->_members)) {
            $members = Staff::objects()
                ->distinct('staff_id')
                ->constrain(array(
                    // Ensure that joining through dept_access is only relevant
                    // for this department, so that the `alerts` annotation
                    // can work properly
                    'dept_access' => new Q(array('dept_access__dept_id' => $this->getId()))
                ))
                ->filter(Q::any(array(
                    'dept_id' => $this->getId(),
                    'staff_id' => $this->manager_id,
                    'dept_access__dept_id' => $this->getId(),
                )));

            $this->_members = Staff::nsort($members);
        }
        return $this->_members;
    }

    function getAvailableMembers() {
        $members = clone $this->getMembers();
        return $members->filter(array(
            'isactive' => 1,
            'onvacation' => 0,
        ));
    }

    function getPrimaryMembers() {
        if (!isset($this->_primary_members)) {
            $members = clone $this->getMembers();
            $members->filter(array('dept_id' =>$this->getId()));
            $this->_primary_members = $members->all();
        }

        return $this->_primary_members;
    }

    function getExtendedMembers() {
        if (!isset($this->_exended_members)) {
            // We need a query set so we can sort the names
            $members = StaffDeptAccess::objects();
            $members->filter(array('dept_id' => $this->getId()));
            $members = Staff::nsort($members, 'staff__');
            $extended = array();
            foreach($members as $member) {
                if (!$member->staff)
                    continue;
                // Annoted the staff model with alerts and role
                $extended[] = AnnotatedModel::wrap($member->staff, array(
                    'alerts'  => $member->isAlertsEnabled(),
                    'role_id' => $member->role_id,
                ));
            }

            $this->_extended_members = $extended;
        }

        return $this->_extended_members;
    }

    // Get eligible members only
    function getAssignees($criteria=array()) {
        if (!$this->assignPrimaryOnly() && !$this->assignMembersOnly()) {
            // this is for if all agents is set - assignment is not restricted
            // based on department membership.
            $members =  Staff::objects()->filter(array(
                'onvacation' => 0,
                'isactive' => 1,
            ));
        } else {
            //this gets just the members of the dept including extended access
            $members = clone $this->getAvailableMembers();

            //Restrict to the primary members only
            if ($this->assignPrimaryOnly())
                $members->filter(array('dept_id' => $this->getId()));
        }

        // Restrict agents based on visibility of the assigner
        if (($staff=$criteria['staff']))
            $members = $staff->applyDeptVisibility($members);

        // Sort based on set name format
        return Staff::nsort($members);
    }

    function getMembersForAlerts() {
        if ($this->isGroupMembershipEnabled() == self::ALERTS_DISABLED) {
            // Disabled for this department
            $rv = array();
        }
        else {
            $rv = clone $this->getAvailableMembers();
            $rv->filter(Q::any(array(
                // Ensure "Alerts" is enabled — must be a primary member or
                // have alerts enabled on your membership and have alerts
                // configured to extended to extended access members
                'dept_id' => $this->getId(),
                // NOTE: Manager is excluded here if not a member
                Q::all(array(
                    'dept_access__dept__group_membership' => self::ALERTS_DEPT_AND_EXTENDED,
                    'dept_access__flags__hasbit' => StaffDeptAccess::FLAG_ALERTS,
                )),
            )));
        }
        return $rv;
    }

    function getNumMembersForAlerts() {
        return count($this->getMembersForAlerts());
    }

    function getSLAId() {
        return $this->sla_id;
    }

    function getSLA() {
        return $this->sla;
    }

    function getScheduleId() {
        return $this->schedule_id;
    }

    function getSchedule() {
        if (!isset($this->schedule) && $this->getScheduleId())
            $this->schedule = BusinessHoursSchedule::lookup(
                        $this->getScheduleId());

        return $this->schedule;
    }

    function getTemplateId() {
         return $this->tpl_id;
    }

    function getTemplate() {
        global $cfg;

        if (!$this->template) {
            if (!($this->template = EmailTemplateGroup::lookup($this->getTemplateId())))
                $this->template = $cfg->getDefaultTemplate();
        }

        return $this->template;
    }

    function getAutoRespEmail() {

        if (!$this->autorespEmail) {
            if (!$this->autoresp_email_id
                    || !($this->autorespEmail = Email::lookup($this->autoresp_email_id)))
                $this->autorespEmail = $this->getEmail();
        }

        return $this->autorespEmail;
    }

    function getEmailAddress() {
        if(($email=$this->getEmail()))
            return $email->getAddress();
    }

    function getSignature() {
        return $this->signature;
    }

    function canAppendSignature() {
        return ($this->getSignature() && $this->isPublic());
    }

    // Check if an agent or team is eligible for assignment
    function canAssign($assignee) {


        if ($assignee instanceof Staff) {
            // Primary members only
            if ($this->assignPrimaryOnly() && !$this->isPrimaryMember($assignee))
                return false;

            // Extended members only
            if ($this->assignMembersOnly() && !$this->isMember($assignee))
                return false;
        } elseif (!$assignee instanceof Team) {
            // Assignee can only be an Agent or a Team
            return false;
        }

        // Make sure agent / team  is availabe for assignment
        if (!$assignee->isAvailable())
             return false;

        return true;
    }

    function getManagerId() {
        return $this->manager_id;
    }

    function getManager() {
        return $this->manager;
    }

    function isManager(Staff $staff) {
        $staff = $staff->getId();

        return ($this->getManagerId() && $this->getManagerId()==$staff);
    }

    function isMember(Staff $staff) {
        $staff = $staff->getId();

        return $this->getMembers()->findFirst(array(
            'staff_id' => $staff
        ));
    }

    function isPrimaryMember(Staff $staff) {
        return ($staff->getDeptId() == $this->getId());
    }

    function isPublic() {
         return $this->ispublic;
    }

    function autoRespONNewTicket() {
        return $this->ticket_auto_response;
    }

    function autoRespONNewMessage() {
        return $this->message_auto_response;
    }

    function noreplyAutoResp() {
         return $this->noreply_autoresp;
    }

    function assignMembersOnly() {
        return $this->flags & self::FLAG_ASSIGN_MEMBERS_ONLY;
    }

    function assignPrimaryOnly() {
        return $this->flags & self::FLAG_ASSIGN_PRIMARY_ONLY;
    }

    function getAssignmentFlag() {
        if($this->flags & self::FLAG_ASSIGN_MEMBERS_ONLY)
          return 'members';
        elseif($this->flags & self::FLAG_ASSIGN_PRIMARY_ONLY)
          return 'primary';
        else
          return 'all';
    }

    function disableAutoClaim() {
        return $this->flags & self::FLAG_DISABLE_AUTO_CLAIM;
    }

    function disableReopenAutoAssign() {
        return $this->flags & self::FLAG_DISABLE_REOPEN_AUTO_ASSIGN;
    }

    function isGroupMembershipEnabled() {
        return $this->group_membership;
    }

    function getHashtable() {
        $ht = $this->ht;
        if (static::$meta['joins'])
            foreach (static::$meta['joins'] as $k => $v)
                unset($ht[$k]);

        $ht['disable_auto_claim'] =  $this->disableAutoClaim();
        $ht['status'] = $this->getStatus();
        $ht['assignment_flag'] = $this->getAssignmentFlag();
        $ht['disable_reopen_auto_assign'] =  $this->disableReopenAutoAssign();
        return $ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function delete() {
        global $cfg;

        if (!$cfg
            // Default department cannot be deleted
            || $this->getId()==$cfg->getDefaultDeptId()
            // Department  with users cannot be deleted
            || $this->members->count()
        ) {
            return 0;
        }

        $id = $this->getId();
        if (parent::delete()) {
            $type = array('type' => 'deleted');
            Signal::send('object.deleted', $this, $type);

            // DO SOME HOUSE CLEANING
            //Move tickets to default Dept. TODO: Move one ticket at a time and send alerts + log notes.
            Ticket::objects()
                ->filter(array('dept_id' => $id))
                ->update(array('dept_id' => $cfg->getDefaultDeptId()));

            // Move tasks
            Task::objects()
                ->filter(array('dept_id' => $id))
                ->update(array('dept_id' => $cfg->getDefaultDeptId()));

            //Move Dept members: This should never happen..since delete should be issued only to empty Depts...but check it anyways
            Staff::objects()
                ->filter(array('dept_id' => $id))
                ->update(array('dept_id' => $cfg->getDefaultDeptId()));

            // Clear any settings using dept to default back to system default
            Topic::objects()
                ->filter(array('dept_id' => $id))
                ->update(array('dept_id' => 0));

            Email::objects()
                ->filter(array('dept_id' => $id))
                ->update(array('dept_id' => 0));

            // Delete extended access entries
            StaffDeptAccess::objects()
                ->filter(array('dept_id' => $id))
                ->delete();
        }
        return true;
    }

    function __toString() {
        return $this->getName();
    }

    function getParent() {
        return static::lookup($this->ht['pid']);
    }

    /**
     * getFullPath
     *
     * Utility function to retrieve a '/' separated list of department IDs
     * in the ancestry of this department. This is used to populate the
     * `path` field in the database and is used for access control rather
     * than the ID field since nesting of departments is necessary and
     * department access can be cascaded.
     *
     * Returns:
     * Slash-separated string of ID ancestry of this department. The string
     * always starts and ends with a slash, and will always contain the ID
     * of this department last.
     */
    function getFullPath() {
        $path = '';
        if ($p = $this->getParent())
            $path .= $p->getFullPath();
        else
            $path .= '/';
        $path .= $this->getId() . '/';
        return $path;
    }

    /**
     * setFlag
     *
     * Utility method to set/unset flag bits
     *
     */

    public function setFlag($flag, $val) {

        if ($val)
            $this->flags |= $flag;
        else
            $this->flags &= ~$flag;
    }

    function hasFlag($flag) {
        return ($this->get('flags', 0) & $flag) != 0;
    }

    function flagChanged($flag, $var) {
        if (($this->hasFlag($flag) && !$var) ||
            (!$this->hasFlag($flag) && $var))
                return true;
    }

    static function export($dept, $criteria=null, $filename='') {
        include_once(INCLUDE_DIR.'class.error.php');
        $members = $dept->getMembers();

        //Sort based on name formating
        $members = Staff::nsort($members);
        Export::departmentMembers($dept, $members, $filename);
    }

    /*----Static functions-------*/
    static function getIdByName($name, $pid=null) {
        $row = static::objects()
            ->filter(array(
                        'name' => $name,
                        'pid'  => $pid ?: null))
            ->values_flat('id')
            ->first();

        return $row ? $row[0] : 0;
    }

    static function getEmailIdById($id) {
        $row = static::objects()
            ->filter(array('id' => $id))
            ->values_flat('email_id')
            ->first();

        return $row ? $row[0] : 0;
    }

    static function getNameById($id) {
        $names = Dept::getDepartments();
        return $names[$id];
    }

    static function getDefaultDeptName() {
        global $cfg;

        return ($cfg
            && ($did = $cfg->getDefaultDeptId())
            && ($names = self::getDepartments()))
            ? $names[$did]
            : null;
    }

    static function getDepartments($criteria=null, $localize=true, $disabled=true) {
        $depts = null;

        if (!isset($depts) || $criteria) {
            // XXX: This will upset the static $depts array
            $depts = array();
            $query = self::objects();
            if (isset($criteria['publiconly']) && $criteria['publiconly'])
                $query->filter(array(
                             'ispublic' => ($criteria['publiconly'] ? 1 : 0)));

            if (isset($criteria['activeonly']) && $criteria['activeonly'])
                $query->filter(array(
                            'flags__hasbit' => Dept::FLAG_ACTIVE));

            if ($manager=$criteria['manager'])
                $query->filter(array(
                            'manager_id' => is_object($manager)?$manager->getId():$manager));

            if (isset($criteria['nonempty'])) {
                $query->annotate(array(
                    'user_count' => SqlAggregate::COUNT('members')
                ))->filter(array(
                    'user_count__gt' => 0
                ));
            }

            $query->order_by('name')
                 ->values('id', 'pid', 'flags', 'name', 'parent');

            foreach ($query as $row) {
              $display = ($row['flags'] & self::FLAG_ACTIVE);

              $depts[$row['id']] = array('id' => $row['id'], 'pid'=>$row['pid'], 'name'=>$row['name'],
                  'parent'=>$row['parent'], 'disabled' => !$display);
            }

            $localize_this = function($id, $default) use ($localize) {
                if (!$localize)
                    return $default;

                $tag = _H("dept.name.{$id}");
                $T = CustomDataTranslation::translate($tag);
                return $T != $tag ? $T : $default;
            };

            // Resolve parent names
            $names = array();
            foreach ($depts as $id=>$info) {
                $name = $info['name'];
                $loop = array($id=>true);
                $parent = false;
                while ($info['pid'] && ($info = $depts[$info['pid']])) {
                    $name = sprintf('%s / %s', $info['name'], $name);
                    if (isset($loop[$info['pid']]))
                        break;
                    $loop[$info['pid']] = true;
                    $parent = $info;
                }
                // Fetch local names
                $names[$id] = $localize_this($id, $name);
            }

            // Apply requested filters
            $requested_names = array();
            foreach ($names as $id=>$n) {
                $info = $depts[$id];
                if (!$disabled && $info['disabled'])
                    continue;
                if ($disabled === self::DISPLAY_DISABLED && $info['disabled'])
                    $n .= " - ".__("(disabled)");

                $requested_names[$id] = $n;
            }
            asort($requested_names);

            // TODO: Use locale-aware sorting mechanism
            if ($criteria)
                return $requested_names;

            $depts = $requested_names;
        }

        return $requested_names;
    }

    static function getPublicDepartments() {
        $depts =null;

        if (!$depts)
            $depts = self::getDepartments(array('publiconly'=>true));

        return $depts;
    }

    static function getActiveDepartments() {
        $depts =null;

        if (!$depts)
            $depts = self::getDepartments(array('activeonly'=>true));

        return $depts;
    }

    static function create($vars=false, &$errors=array()) {
        $dept = new static($vars);
        $dept->created = SqlFunction::NOW();
        return $dept;
    }

    static function __create($vars, &$errors) {
        $dept = self::create($vars);
        if (!$dept->update($vars, $errors))
          return false;

       $dept->save();

       return $dept;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();

        return parent::save($refetch || $this->dirty);
    }

    function update($vars, &$errors) {
        global $cfg;

        $id = $this->id;
        if ($id && $id != $vars['id'])
            $errors['err']=__('Missing or invalid Dept ID.')
                .' '.__('Internal error occurred');

        if (!$vars['name']) {
            $errors['name']=__('Name required');
        } elseif (($did = static::getIdByName($vars['name'], $vars['pid']))
                && $did != $id) {
            $errors['name']=__('Department already exists');
        }

        if (!$vars['ispublic'] && $cfg && ($vars['id']==$cfg->getDefaultDeptId()))
            $errors['ispublic']=__('System default department cannot be private');

        if ($vars['pid'] && !($p = static::lookup($vars['pid'])))
            $errors['pid'] = __('Department selection is required');

        $dept = Dept::lookup($vars['pid']);
        if ($dept) {
          if (!$dept->isActive())
            $errors['dept_id'] = sprintf(__('%s selected must be active'), __('Parent Department'));
          elseif (strpos($dept->getFullPath(), '/'.$this->getId().'/') !== false)
            $errors['pid'] = sprintf(__('%s cannot contain the current %s'), __('Parent Department'), __('Department'));
        }

        if ($vars['sla_id'] && !SLA::lookup($vars['sla_id']))
            $errors['sla_id'] = __('Invalid SLA');

        if ($vars['manager_id'] && !Staff::lookup($vars['manager_id']))
            $errors['manager_id'] = __('Unknown Staff');

        if ($vars['email_id'] && !Email::lookup($vars['email_id']))
            $errors['email_id'] = __('Unknown System Email');

        if ($vars['tpl_id'] && !EmailTemplateGroup::lookup($vars['tpl_id']))
            $errors['tpl_id'] = __('Unknown Template Set');

        if ($vars['autoresp_email_id'] && !Email::lookup($vars['autoresp_email_id']))
            $errors['autoresp_email_id'] = __('Unkown System Email');

        // Format access update as [array(dept_id, role_id, alerts?)]
        $access = array();
        if (isset($vars['members'])) {
            foreach (@$vars['members'] as $staff_id) {
                $access[] = array($staff_id, $vars['member_role'][$staff_id],
                    @$vars['member_alerts'][$staff_id]);
            }
        }
        $this->updateAccess($access, $errors);

        if ($errors)
            return false;

        $vars['disable_auto_claim'] = isset($vars['disable_auto_claim']) ? 1 : 0;
        if ($this->getId()) {
            //flags
            $disableAutoClaim = $this->flagChanged(self::FLAG_DISABLE_AUTO_CLAIM, $vars['disable_auto_claim']);
            $disableAutoAssign = $this->flagChanged(self::FLAG_DISABLE_REOPEN_AUTO_ASSIGN, $vars['disable_reopen_auto_assign']);
            $ticketAssignment = ($this->getAssignmentFlag() != $vars['assignment_flag']);
            foreach ($vars as $key => $value) {
                if ($key == 'status' && $this->getStatus() && strtolower($this->getStatus()) != $value) {
                    $type = array('type' => 'edited', 'status' => ucfirst($value));
                    Signal::send('object.edited', $this, $type);
                } elseif ((isset($this->$key) && ($this->$key != $value) && $key != 'members') ||
                         ($disableAutoClaim && $key == 'disable_auto_claim') ||
                          $ticketAssignment && $key == 'assignment_flag' ||
                          $disableAutoAssign && $key == 'disable_reopen_auto_assign') {
                    $type = array('type' => 'edited', 'key' => $key);
                    Signal::send('object.edited', $this, $type);
                }
            }
        }
        if ($vars['disable_auto_claim'] !== 1)
            unset($vars['disable_auto_claim']);

        $this->pid = $vars['pid'] ?: null;
        $this->ispublic = isset($vars['ispublic']) ? (int) $vars['ispublic'] : 0;
        $this->email_id = isset($vars['email_id']) ? (int) $vars['email_id'] : 0;
        $this->tpl_id = isset($vars['tpl_id']) ? (int) $vars['tpl_id'] : 0;
        $this->sla_id = isset($vars['sla_id']) ? (int) $vars['sla_id'] : 0;
        $this->schedule_id = isset($vars['schedule_id']) ? (int) $vars['schedule_id'] : 0;
        $this->autoresp_email_id = isset($vars['autoresp_email_id']) ? (int) $vars['autoresp_email_id'] : 0;
        $this->manager_id = $vars['manager_id'] ?: 0;
        $this->name = Format::striptags($vars['name']);
        $this->signature = Format::sanitize($vars['signature']);
        $this->group_membership = $vars['group_membership'];
        $this->ticket_auto_response = isset($vars['ticket_auto_response'])?$vars['ticket_auto_response']:1;
        $this->message_auto_response = isset($vars['message_auto_response'])?$vars['message_auto_response']:1;
        $this->flags = $vars['flags'] ?: 0;

        $this->setFlag(self::FLAG_ASSIGN_MEMBERS_ONLY, isset($vars['assign_members_only']));
        $this->setFlag(self::FLAG_DISABLE_AUTO_CLAIM, isset($vars['disable_auto_claim']));
        $this->setFlag(self::FLAG_DISABLE_REOPEN_AUTO_ASSIGN, isset($vars['disable_reopen_auto_assign']));

        $filter_actions = FilterAction::objects()->filter(array('type' => 'dept', 'configuration' => '{"dept_id":'. $this->getId().'}'));
        if ($filter_actions && $vars['status'] == 'active')
          FilterAction::setFilterFlags($filter_actions, 'Filter::FLAG_INACTIVE_DEPT', false);
        else
          FilterAction::setFilterFlags($filter_actions, 'Filter::FLAG_INACTIVE_DEPT', true);

        if ($cfg && ($this->getId() == $cfg->getDefaultDeptId()))
            $vars['status'] = 'active';

        switch ($vars['status']) {
          case 'active':
            $this->setFlag(self::FLAG_ACTIVE, true);
            $this->setFlag(self::FLAG_ARCHIVED, false);
            break;

          case 'disabled':
            $this->setFlag(self::FLAG_ACTIVE, false);
            $this->setFlag(self::FLAG_ARCHIVED, false);
            break;

          case 'archived':
            $this->setFlag(self::FLAG_ACTIVE, false);
            $this->setFlag(self::FLAG_ARCHIVED, true);
        }

        switch ($vars['assignment_flag']) {
          case 'all':
            $this->setFlag(self::FLAG_ASSIGN_MEMBERS_ONLY, false);
            $this->setFlag(self::FLAG_ASSIGN_PRIMARY_ONLY, false);
            break;
          case 'members':
            $this->setFlag(self::FLAG_ASSIGN_MEMBERS_ONLY, true);
            $this->setFlag(self::FLAG_ASSIGN_PRIMARY_ONLY, false);
            break;
          case 'primary':
            $this->setFlag(self::FLAG_ASSIGN_MEMBERS_ONLY, false);
            $this->setFlag(self::FLAG_ASSIGN_PRIMARY_ONLY, true);
            break;
        }

        $this->path = $this->getFullPath();

        $wasnew = $this->__new__;
        if ($this->save() && $this->extended->saveAll()) {
            if ($wasnew) {
                // The ID wasn't available until after the commit
                $this->path = $this->getFullPath();
                $this->save();
            }
            return true;
        }

        if (isset($this->id))
            $errors['err']=sprintf(__('Unable to update %s.'), __('this department'))
               .' '.__('Internal error occurred');
        else
            $errors['err']=sprintf(__('Unable to create %s.'), __('this department'))
               .' '.__('Internal error occurred');

        return false;
    }

    function updateAccess($access, &$errors) {
      reset($access);
      $dropped = array();
      foreach ($this->extended as $DA)
          $dropped[$DA->staff_id] = 1;
      foreach ($access as $acc) {
          list ($staff_id, $role_id, $alerts) = $acc;
          unset($dropped[$staff_id]);
          if (!$role_id || !Role::lookup($role_id))
              $errors['members'][$staff_id] = __('Select a valid role');
          if (!$staff_id || !($staff=Staff::lookup($staff_id)))
              $errors['members'][$staff_id] = __('No such agent');

          if ($staff->dept_id == $this->id) {

              // If primary member then simply update the role.
              if (($m = $this->members->findFirst(array(
                                  'staff_id' => $staff_id))))
                  $m->role_id = $role_id;
              continue;
          }

          $da = $this->extended->findFirst(array('staff_id' => $staff_id));
          if (!isset($da)) {
              $da = new StaffDeptAccess(array(
                  'staff_id' => $staff_id, 'role_id' => $role_id
              ));
              $this->extended->add($da);
              $type = array('type' => 'edited', 'key' => 'Staff Added');
              Signal::send('object.edited', $this, $type);
          }
          else {
              $da->role_id = $role_id;
          }
          $da->setAlerts($alerts);

      }

      if ($errors)
          return false;

      if ($dropped) {
          $type = array('type' => 'edited', 'key' => 'Staff Removed');
          Signal::send('object.edited', $this, $type);
          $this->extended->saveAll();
          $this->extended
              ->filter(array('staff_id__in' => array_keys($dropped)))
              ->delete();
          $this->extended->reset();
      }

      // Save any role change.
      $this->members->saveAll();

      return true;
    }

    static function getPermissions() {
        return self::$perms;
    }
}
RolePermission::register(/* @trans */ 'Miscellaneous', Dept::getPermissions());

class DepartmentQuickAddForm
extends Form {
    function getFields() {
        if ($this->fields)
            return $this->fields;

        return $this->fields = array(
            'pid' => new ChoiceField(array(
                'label' => '',
                'default' => 0,
                'choices' =>
                    array(0 => '— '.__('Top-Level Department').' —')
                    + Dept::getPublicDepartments()
            )),
            'name' => new TextboxField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __('Name'),
                    'classes' => 'span12',
                    'autofocus' => true,
                    'length' => 128,
                ),
            )),
            'email_id' => new ChoiceField(array(
                'label' => __('Email Mailbox'),
                'default' => 0,
                'choices' =>
                    array(0 => '— '.__('System Default').' —')
                    + Email::getAddresses(),
                'configuration' => array(
                    'classes' => 'span12',
                ),
            )),
            'private' => new BooleanField(array(
                'configuration' => array(
                    'classes' => 'form footer',
                    'desc' => __('This department is for internal use'),
                ),
            )),
        );
    }

    function getClean($validate = true) {
        $clean = parent::getClean();

        $clean['ispublic'] = !$clean['private'];
        unset($clean['private']);

        return $clean;
    }

    function render($staff=true, $title=false, $options=array()) {
        return parent::render($staff, $title, $options + array('template' => 'dynamic-form-simple.tmpl.php'));
    }
}
