<?php
/*********************************************************************
    class.team.php

    Teams

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Team extends VerySimpleModel
implements TemplateVariable {

    static $meta = array(
        'table' => TEAM_TABLE,
        'pk' => array('team_id'),
        'joins' => array(
            'lead' => array(
                'null' => true,
                'constraint' => array('lead_id' => 'Staff.staff_id'),
            ),
            'members' => array(
                'list' => true,
                'reverse' => 'TeamMember.team',
            ),
        ),
    );

    const FLAG_ENABLED  = 0x0001;
    const FLAG_NOALERTS = 0x0002;

    var $_members;

    function asVar() {
        return $this->__toString();
    }

    function __toString() {
        return (string) $this->getName();
    }

    static function getVarScope() {
        return array(
            'name' => __('Team Name'),
            'lead' => array(
                'class' => 'Staff', 'desc' => __('Team Lead'),
            ),
            'members' => array(
                'class' => 'UserList', 'desc' => __('Team Members'),
            ),
        );
    }

    function getVar($tag) {
        switch ($tag) {
        case 'members':
            return new UserList($this->getMembers()->all());
        }
    }

    function getId() {
        return $this->team_id;
    }

    function getName() {
        return $this->name;
    }
    function getLocalName() {
        return $this->getLocal('name');
    }

    function getNumMembers() {
        return $this->members->count();
    }

    function getMembers() {
        if (!isset($this->_members)) {
            $this->_members = array();
            foreach ($this->members as $m)
                $this->_members[] = $m->staff;
        }
        return $this->_members;
    }

    function hasMember($staff) {
        return $this->members
            ->filter(array('staff_id'=>$staff->getId()))
            ->count() !== 0;
    }

    function getLeadId() {
        return $this->lead_id;
    }

    function getTeamLead() {
        return $this->lead;
    }

    function getLead() {
        return $this->getTeamLead();
    }

    function getHashtable() {
        $base = $this->ht;
        $base['isenabled'] = $this->isEnabled();
        $base['noalerts'] = !$this->alertsEnabled();
        unset($base['members']);
        return $base;
    }

    function getInfo() {
        return  $this->getHashtable();
    }

    function isEnabled() {
        return $this->flags & self::FLAG_ENABLED;
    }

    function isActive() {
        return $this->isEnabled();
    }

    function alertsEnabled() {
        return ($this->flags & self::FLAG_NOALERTS) == 0;
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('team.%s.%s', $subtag, $this->getId()));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->ht[$subtag];
    }
    static function getLocalById($id, $subtag, $default) {
        $tag = _H(sprintf('team.%s.%s', $subtag, $id));
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $default;
    }

    function update($vars, &$errors=array()) {

        if (!$vars['name']) {
            $errors['name']=__('Team name is required');
        } elseif(($tid=self::getIdByName($vars['name'])) && $tid!=$vars['id']) {
            $errors['name']=__('Team name already exists');
        }

        // Reset team lead if they're getting removed
        if (isset($this->lead_id)
                && $this->lead_id == $vars['lead_id']
                && $vars['remove']
                && in_array($this->lead_id, $vars['remove']))
            $vars['lead_id'] =0 ;

        $this->flags =
              ($vars['isenabled'] ? self::FLAG_ENABLED : 0)
            | (isset($vars['noalerts']) ? self::FLAG_NOALERTS : 0);
        $this->lead_id = $vars['lead_id'] ?: 0;
        $this->name = Format::striptags($vars['name']);
        $this->notes = Format::sanitize($vars['notes']);

        // Format access update as [array(staff_id, alerts?)]
        $access = array();
        if (isset($vars['members'])) {
            foreach (@$vars['members'] as $staff_id) {
                $access[] = array($staff_id, @$vars['member_alerts'][$staff_id]);
            }
        }
        $this->updateMembers($access, $errors);

        if ($errors)
            return false;

        if ($this->save())
            return $this->members->saveAll();

        if (isset($this->team_id)) {
            $errors['err']=sprintf(__('Unable to update %s.'), __('this team'))
               .' '.__('Internal error occurred');
        } else {
            $errors['err']=sprintf(__('Unable to create %s.'), __('this team'))
               .' '.__('Internal error occurred');
        }

        return false;
    }

    function updateMembers($access, &$errors) {
      reset($access);
      $dropped = array();
      foreach ($this->members as $member)
          $dropped[$member->staff_id] = 1;
      while (list(, list($staff_id, $alerts)) = each($access)) {
          unset($dropped[$staff_id]);
          if (!$staff_id || !Staff::lookup($staff_id))
              $errors['members'][$staff_id] = __('No such agent');
          $member = $this->members->findFirst(array('staff_id' => $staff_id));
          if (!isset($member)) {
              $member = new TeamMember(array('staff_id' => $staff_id));
              $this->members->add($member);
          }
          $member->setAlerts($alerts);
      }
      if (!$errors && $dropped) {
          $this->members
              ->filter(array('staff_id__in' => array_keys($dropped)))
              ->delete();
          $this->members->reset();
      }
      return !$errors;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();

        return parent::save($refetch || $this->dirty);
    }

    function delete() {
        global $thisstaff;

        if (!$thisstaff || !($id=$this->getId()))
            return false;

        # Remove the team
        if (!parent::delete())
            return false;

        # Remove members of this team
        $this->members->delete();

        # Reset ticket ownership for tickets owned by this team
        Ticket::objects()
            ->filter(array('team_id' => $id))
            ->update(array('team_id' => 0));

        return true;
    }

    /* ----------- Static function ------------------*/
    static function getIdByName($name) {

        $row = self::objects()
            ->filter(array('name'=>trim($name)))
            ->values_flat('team_id')
            ->first();

        return $row ? $row[0] : 0;
    }

    static function getTeams($criteria=array()) {
        static $teams = null;
        if (!$teams || $criteria) {
            $teams = array();
            $query = static::objects()
                ->values_flat('team_id', 'name', 'flags')
                ->order_by('name');

            if (isset($criteria['active']) && $criteria['active']) {
                $query->annotate(array('members_count'=>SqlAggregate::COUNT('members')))
                ->filter(array(
                    'flags__hasbit'=>self::FLAG_ENABLED,
                    'members__staff__isactive'=>1,
                    'members__staff__onvacation'=>0,
                ))
                ->filter(array('members_count__gt'=>0));
            }

            $items = array();
            foreach ($query as $row) {
                //TODO: Fix enabled - flags is a bit field.
                list($id, $name, $flags) = $row;
                $enabled = $flags & self::FLAG_ENABLED;
                $items[$id] = sprintf('%s%s',
                    self::getLocalById($id, 'name', $name),
                    ($enabled || isset($criteria['active']))
                        ? '' : ' ' . __('(disabled)'));
            }

            //TODO: sort if $criteria['localize'];
            if ($criteria)
                return $items;

            $teams = $items;
        }

        return $teams;
    }

    static function getActiveTeams() {
        static $teams = null;

        if (!isset($teams))
            $teams = self::getTeams(array('active'=>true));

        return $teams;
    }

    static function create($vars=false) {
        $team = new static($vars);
        $team->created = SqlFunction::NOW();
        return $team;
    }

    static function __create($vars, &$errors) {
        return self::create($vars)->save();
    }

}

class TeamMember extends VerySimpleModel {
    static $meta = array(
        'table' => TEAM_MEMBER_TABLE,
        'pk' => array('team_id', 'staff_id'),
        'select_related' => array('staff'),
        'joins' => array(
            'team' => array(
                'constraint' => array('team_id' => 'Team.team_id'),
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
            ),
        ),
    );

    const FLAG_ALERTS = 0x0001;

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

class TeamQuickAddForm
extends AbstractForm {
    function buildFields() {
        return array(
            'name' => new TextboxField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __('Name'),
                    'classes' => 'span12',
                    'autofocus' => true,
                    'length' => 128,
                ),
            )),
            'lead_id' => new ChoiceField(array(
                'label' => __('Optionally select a leader for the team'),
                'default' => 0,
                'choices' =>
                    array(0 => '— '.__('None').' —')
                    + Staff::getStaffMembers(),
                'configuration' => array(
                    'classes' => 'span12',
                ),
            )),
        );
    }

    function render($staff=true, $title=false, $options=array()) {
        return parent::render($staff, $title, $options + array('template' => 'dynamic-form-simple.tmpl.php'));
    }
}
