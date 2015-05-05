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
                'null' => true,
                'list' => true,
                'reverse' => 'TeamMember.team',
            ),
        ),
    );

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

        if (!isset($this->_members)) {
            $this->_members = array();
            foreach ($this->members as $m)
                $this->_members[] = $m->staff;
        }

        return new UserList($this->_members);
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

    function update($vars, &$errors=array()) {

        if (!$vars['name']) {
            $errors['name']=__('Team name is required');
        } elseif(($tid=self::getIdByName($vars['name'])) && $tid!=$vars['id']) {
            $errors['name']=__('Team name already exists');
        }

        if ($errors)
            return false;

        // Reset team lead if they're getting removed
        if (isset($this->lead_id)
                && $this->lead_id == $vars['lead_id']
                && $vars['remove']
                && in_array($this->lead_id, $vars['remove']))
            $vars['lead_id'] =0 ;

        $this->isenabled = $vars['isenabled'];
        $this->noalerts = isset($vars['noalerts']) ? $vars['noalerts'] : 0;
        $this->lead_id = $vars['lead_id'] ?: 0;
        $this->name = $vars['name'];
        $this->notes = Format::sanitize($vars['notes']);

        if ($this->save()) {
            // Remove checked members
            if ($vars['remove'] && is_array($vars['remove'])) {
                TeamMember::objects()
                    ->filter(array(
                        'staff_id__in' => $vars['remove']))
                    ->delete();
            }

            return true;
        }

        if (isset($this->team_id)) {
            $errors['err']=sprintf(__('Unable to update %s.'), __('this team'))
               .' '.__('Internal error occurred');
        } else {
            $errors['err']=sprintf(__('Unable to create %s.'), __('this team'))
               .' '.__('Internal error occurred');
        }

        return false;
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
        $this->staffmembers->delete();

        # Reset ticket ownership for tickets owned by this team
        db_query('UPDATE '.TICKET_TABLE.' SET team_id=0 WHERE team_id='
            .db_input($id));

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
                ->values_flat('team_id', 'name', 'isenabled')
                ->order_by('name');

            if (isset($criteria['active']) && $criteria['active']) {
                $query->annotate(array('members_count'=>SqlAggregate::COUNT('members')))
                ->filter(array(
                    'isenabled'=>1,
                    'members__staff__isactive'=>1,
                    'members__staff__onvacation'=>0,
                    'members__staff__group__flags__hasbit'=>Group::FLAG_ENABLED,
                ))
                ->filter(array('members_count__gt'=>0));
            }

            $items = array();
            foreach ($query as $row) {
                //TODO: Fix enabled - flags is a bit field.
                list($id, $name, $enabled) = $row;
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
        $team = parent::create($vars);
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
        'joins' => array(
            'team' => array(
                'constraint' => array('team_id' => 'Team.team_id'),
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
            ),
        ),
    );
}
?>
