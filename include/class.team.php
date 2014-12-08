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

class Team extends VerySimpleModel {

    static $meta = array(
        'table' => TEAM_TABLE,
        'pk' => array('team_id'),
        'joins' => array(
            'staffmembers' => array(
                'reverse' => 'StaffTeamMember.team'
            ),
            'lead' => array(
                'constraint' => array('lead_id' => 'Staff.staff_id'),
            ),
        ),
    );

    var $members;

    function asVar() {
        return $this->__toString();
    }

    function __toString() {
        return (string) $this->getName();
    }

    function getId() {
        return $this->team_id;
    }

    function getName() {
        return $this->name;
    }

    function getNumMembers() {
        return $this->members->count();
    }

    function getMembers() {
        if (!isset($this->members)) {
            $this->members = Staff::objects()
                ->filter(array('teams__team_id'=>$this->getId()))
                ->order_by('lastname', 'firstname');
        }
        return $this->members;
    }

    function hasMember($staff) {
        return $this->getMembers()
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
        unset($base['staffmembers']);
        return $base;
    }

    function getInfo() {
        return  $this->getHashtable();
    }

    function isEnabled() {
        return $this->isenabled;
    }

    function isActive() {
        return $this->isEnabled();
    }

    function alertsEnabled() {
        return !$this->noalerts;
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

    function updateMembership($vars) {

        // Delete staff marked for removal...
        if ($vars['remove']) {
            $this->staffmembers
                ->filter(array(
                    'staff_id__in' => $vars['remove']))
                ->delete();
        }
        return true;
    }

    function delete() {
        global $thisstaff;

        if (!$thisstaff || !($id=$this->getId()))
            return false;

        # Remove the team
        if (!parent::delete())
            return false;

        # Remove members of this team
        $this->staffmembers->delete();

        # Reset ticket ownership for tickets owned by this team
        db_query('UPDATE '.TICKET_TABLE.' SET team_id=0 WHERE team_id='
            .db_input($id));

        return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    /* ----------- Static function ------------------*/

    static function getIdbyName($name) {
        $row = static::objects()
            ->filter(array('name'=>$name))
            ->values_flat('team_id')
            ->first();

        return $row ? $row[0] : null;
    }

    static function getTeams( $availableOnly=false ) {
        static $names;

        if (isset($names))
            return $names;

        $names = array();
        $teams = static::objects()
            ->values_flat('team_id', 'name', 'isenabled');

        if ($availableOnly) {
            //Make sure the members are active...TODO: include group check!!
            $teams->annotate(array('members'=>Aggregate::COUNT('staffmembers')))
                ->filter(array(
                    'isenabled'=>1,
                    'staffmembers__staff__isactive'=>1,
                    'staffmembers__staff__onvacation'=>0,
                    'staffmembers__staff__group__group_enabled'=>1,
                ))
                ->filter(array('members__gt'=>0))
                ->order_by('name');
        }

        foreach ($teams as $row) {
            list($id, $name, $isenabled) = $row;
            $names[$id] = self::getLocalById($id, 'name', $name);
            if (!$isenabled)
                $names[$id] .= ' ' . __('(disabled)');
        }

        return $names;
    }

    static function getActiveTeams() {
        return self::getTeams(true);
    }

    static function create($vars=array()) {
        $team = parent::create($vars);
        $team->created = SqlFunction::NOW();
        return $team;
    }

    function update($vars, &$errors) {
        if (isset($this->team_id) && $this->getId() != $vars['id'])
            $errors['err']=__('Missing or invalid team');

        if(!$vars['name']) {
            $errors['name']=__('Team name is required');
        } elseif(strlen($vars['name'])<3) {
            $errors['name']=__('Team name must be at least 3 chars.');
        } elseif(($tid=static::getIdByName($vars['name']))
                && (!isset($this->team_id) || $tid!=$this->getId())) {
            $errors['name']=__('Team name already exists');
        }

        if ($errors)
            return false;

        $this->isenabled = $vars['isenabled'];
        $this->name = $vars['name'];
        $this->noalerts = isset($vars['noalerts'])?$vars['noalerts']:0;
        $this->notes = Format::sanitize($vars['notes']);
        if (isset($vars['lead_id']))
            $this->lead_id = $vars['lead_id'];

        // reset team lead if they're being removed from the team
        if ($this->getLeadId() == $vars['lead_id']
                && $vars['remove'] && in_array($this->getLeadId(), $vars['remove']))
            $this->lead_id = 0;

        if ($this->save())
            return $this->updateMembership($vars);

        if ($this->__new__) {
            $errors['err']=sprintf(__('Unable to create %s.'), __('this team'))
               .' '.__('Internal error occurred');
        }
        else {
            $errors['err']=sprintf(__('Unable to update %s.'), __('this team'))
               .' '.__('Internal error occurred');
        }
        return false;
    }
}
?>
