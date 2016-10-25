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

class Dept extends VerySimpleModel
implements TemplateVariable {

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
                'constraint' => array('sla_id' => 'SLA.sla_id'),
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

    var $template;
    var $autorespEmail;

    const ALERTS_DISABLED = 2;
    const ALERTS_DEPT_AND_EXTENDED = 1;
    const ALERTS_DEPT_ONLY = 0;

    const FLAG_ASSIGN_MEMBERS_ONLY = 0x0001;
    const FLAG_DISABLE_AUTO_CLAIM  = 0x0002;

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
                $extended[] = new AnnotatedModel($member->staff, array(
                    'alerts'  => $member->isAlertsEnabled(),
                    'role_id' => $member->role_id,
                ));
            }

            $this->_extended_members = $extended;
        }

        return $this->_extended_members;
    }

    // Get members  eligible members only
    function getAssignees() {
        $members = clone $this->getAvailableMembers();
        // If restricted then filter to primary members ONLY!
        if ($this->assignMembersOnly())
            $members->filter(array('dept_id' => $this->getId()));

        return $members;
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

    function getSLAId() {
        return $this->sla_id;
    }

    function getSLA() {
        return $this->sla;
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

    function getManagerId() {
        return $this->manager_id;
    }

    function getManager() {
        return $this->manager;
    }

    function isManager($staff) {
        if (is_object($staff))
            $staff = $staff->getId();

        return ($this->getManagerId() && $this->getManagerId()==$staff);
    }

    function isMember($staff) {
        if (is_object($staff))
            $staff = $staff->getId();

        return $this->getMembers()->findFirst(array(
            'staff_id' => $staff
        ));
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

    function disableAutoClaim() {
        return $this->flags & self::FLAG_DISABLE_AUTO_CLAIM;
    }

    function isGroupMembershipEnabled() {
        return $this->group_membership;
    }

    function getHashtable() {
        $ht = $this->ht;
        if (static::$meta['joins'])
            foreach (static::$meta['joins'] as $k => $v)
                unset($ht[$k]);

        $ht['assign_members_only'] = $this->assignMembersOnly();
        $ht['disable_auto_claim'] =  $this->disableAutoClaim();
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
            // DO SOME HOUSE CLEANING
            //Move tickets to default Dept. TODO: Move one ticket at a time and send alerts + log notes.
            Ticket::objects()
                ->filter(array('dept_id' => $id))
                ->update(array('dept_id' => $cfg->getDefaultDeptId()));

            //Move Dept members: This should never happen..since delete should be issued only to empty Depts...but check it anyways
            Staff::objects()
                ->filter(array('dept_id' => $id))
                ->update(array('dept_id' => $cfg->getDefaultDeptId()));

            // Clear any settings using dept to default back to system default
            Topic::objects()
                ->filter(array('dept_id' => $id))
                ->delete();
            Email::objects()
                ->filter(array('dept_id' => $id))
                ->delete();

            foreach(FilterAction::objects()
                ->filter(array('type' => FA_RouteDepartment::$type)) as $fa
            ) {
                $config = $fa->getConfiguration();
                if ($config && $config['dept_id'] == $id) {
                    $config['dept_id'] = 0;
                    // FIXME: Move this code into FilterAction class
                    $fa->set('configuration', JsonDataEncoder::encode($config));
                    $fa->save();
                }
            }

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

    private function setFlag($flag, $val) {

        if ($val)
            $this->flags |= $flag;
        else
            $this->flags &= ~$flag;
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

    function getNameById($id) {
        $names = static::getDepartments();
        return $names[$id];
    }

    function getDefaultDeptName() {
        global $cfg;

        return ($cfg
            && ($did = $cfg->getDefaultDeptId())
            && ($names = self::getDepartments()))
            ? $names[$did]
            : null;
    }

    static function getDepartments( $criteria=null, $localize=true) {
        static $depts = null;

        if (!isset($depts) || $criteria) {
            // XXX: This will upset the static $depts array
            $depts = array();
            $query = self::objects();
            if (isset($criteria['publiconly']))
                $query->filter(array(
                            'ispublic' => ($criteria['publiconly'] ? 1 : 0)));

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
                ->values('id', 'pid', 'name', 'parent');

            foreach ($query as $row)
                $depts[$row['id']] = $row;

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
            asort($names);

            // TODO: Use locale-aware sorting mechanism

            if ($criteria)
                return $names;

            $depts = $names;
        }

        return $depts;
    }

    static function getPublicDepartments() {
        static $depts =null;

        if (!$depts)
            $depts = self::getDepartments(array('publiconly'=>true));

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
            $errors['err']=__('Missing or invalid Dept ID (internal error).');

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

        $this->pid = $vars['pid'] ?: null;
        $this->ispublic = isset($vars['ispublic'])?$vars['ispublic']:0;
        $this->email_id = isset($vars['email_id'])?$vars['email_id']:0;
        $this->tpl_id = isset($vars['tpl_id'])?$vars['tpl_id']:0;
        $this->sla_id = isset($vars['sla_id'])?$vars['sla_id']:0;
        $this->autoresp_email_id = isset($vars['autoresp_email_id'])?$vars['autoresp_email_id']:0;
        $this->manager_id = $vars['manager_id'] ?: 0;
        $this->name = Format::striptags($vars['name']);
        $this->signature = Format::sanitize($vars['signature']);
        $this->group_membership = $vars['group_membership'];
        $this->ticket_auto_response = isset($vars['ticket_auto_response'])?$vars['ticket_auto_response']:1;
        $this->message_auto_response = isset($vars['message_auto_response'])?$vars['message_auto_response']:1;
        $this->flags = 0;
        $this->setFlag(self::FLAG_ASSIGN_MEMBERS_ONLY, isset($vars['assign_members_only']));
        $this->setFlag(self::FLAG_DISABLE_AUTO_CLAIM, isset($vars['disable_auto_claim']));

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
      while (list(, list($staff_id, $role_id, $alerts)) = each($access)) {
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
          }
          else {
              $da->role_id = $role_id;
          }
          $da->setAlerts($alerts);

      }

      if ($errors)
          return false;

      if ($dropped) {
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
}

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
                    + Dept::getDepartments()
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

    function getClean() {
        $clean = parent::getClean();

        $clean['ispublic'] = !$clean['private'];
        unset($clean['private']);

        return $clean;
    }

    function render($staff=true, $title=false, $options=array()) {
        return parent::render($staff, $title, $options + array('template' => 'dynamic-form-simple.tmpl.php'));
    }
}
