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

class Team {

    var $id;
    var $ht;

    var $members;

    function Team($id) {

        return $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;
        
        $sql='SELECT team.*,count(member.staff_id) as members '
            .' FROM '.TEAM_TABLE.' team '
            .' LEFT JOIN '.TEAM_MEMBER_TABLE.' member USING(team_id) '
            .' WHERE team.team_id='.db_input($id)
            .' GROUP BY team.team_id ';

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['team_id'];
        $this->members=array();

        return $this->id;
    }

    function reload() {
        return $this->load($this->getId());
    }

    function asVar() {
        return $this->getName();
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->ht['name'];
    }

    function getNumMembers() {
        return $this->ht['members'];
    }

    function getMembers() {

        if(!$this->members && $this->getNumMembers()) {
            $sql='SELECT m.staff_id FROM '.TEAM_MEMBER_TABLE.' m '
                .'LEFT JOIN '.STAFF_TABLE.' s USING(staff_id) '
                .'WHERE m.team_id='.db_input($this->getId()).' AND s.staff_id IS NOT NULL '
                .'ORDER BY s.lastname, s.firstname';
            if(($res=db_query($sql)) && db_num_rows($res)) {
                while(list($id)=db_fetch_row($res))
                    if(($staff= Staff::lookup($id)))
                        $this->members[]= $staff;
            }
        }

        return $this->members;
    }

    function hasMember($staff) {
        return db_count(
             'SELECT COUNT(*) FROM '.TEAM_MEMBER_TABLE
            .' WHERE team_id='.db_input($this->getId())
            .'   AND staff_id='.db_input($staff->getId())) !== 0;
    }

    function getLeadId() {
        return $this->ht['lead_id'];
    }

    function getTeamLead() {
        if(!$this->lead && $this->getLeadId())
            $this->lead=Staff::lookup($this->getLeadId());

        return $this->lead;
    }

    function getLead() {
        return $this->getTeamLead();
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return  $this->getHashtable();
    }

    function isEnabled() {
        return ($this->ht['isenabled']);
    }

    function isActive() {
        return $this->isEnabled();
    }

    function update($vars, &$errors) {

        //reset team lead if they're being deleted
        if($this->getLeadId()==$vars['lead_id'] 
                && $vars['remove'] && in_array($this->getLeadId(), $vars['remove']))
            $vars['lead_id']=0;

        //Save the changes...
        if(!Team::save($this->getId(), $vars, $errors))
            return false;

        //Delete staff marked for removal...
        if($vars['remove']) {
            $sql='DELETE FROM '.TEAM_MEMBER_TABLE
                .' WHERE team_id='.db_input($this->getId())
                .' AND staff_id IN ('
                    .implode(',', db_input($vars['remove']))
                .')';
            db_query($sql);
        }

        //Reload.
        $this->reload();

        return true;
    }

    function delete() {
        global $thisstaff;

        if(!$thisstaff || !($id=$this->getId()))
            return false;

        # Remove the team
        $res = db_query(
            'DELETE FROM '.TEAM_TABLE.' WHERE team_id='.db_input($id)
          .' LIMIT 1');
        if (db_affected_rows($res) != 1)
            return false;

        # Remove members of this team
        db_query('DELETE FROM '.TEAM_MEMBER_TABLE
               .' WHERE team_id='.db_input($id));

        # Reset ticket ownership for tickets owned by this team
        db_query('UPDATE '.TICKET_TABLE.' SET team_id=0 WHERE team_id='
            .db_input($id));

        return true;
    }

    /* ----------- Static function ------------------*/
    function lookup($id) {
        return ($id && is_numeric($id) && ($team= new Team($id)) && $team->getId()==$id)?$team:null;
    }


    function getIdbyName($name) {

        $sql='SELECT team_id FROM '.TEAM_TABLE.' WHERE name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function getTeams( $availableOnly=false ) {
        
        $teams=array();
        $sql='SELECT team_id, name FROM '.TEAM_TABLE;
        if($availableOnly) {
            //Make sure the members are active...TODO: include group check!!
            $sql='SELECT t.team_id, t.name, count(m.staff_id) as members '
                .' FROM '.TEAM_TABLE.' t '
                .' LEFT JOIN '.TEAM_MEMBER_TABLE.' m ON(m.team_id=t.team_id) '
                .' INNER JOIN '.STAFF_TABLE.' s ON(s.staff_id=m.staff_id AND s.isactive=1 AND onvacation=0) '
                .' INNER JOIN '.GROUP_TABLE.' g ON(g.group_id=s.group_id AND g.group_enabled=1) '
                .' WHERE t.isenabled=1 '
                .' GROUP BY t.team_id '
                .' HAVING members>0'
                .' ORDER by t.name ';
        }
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id, $name)=db_fetch_row($res))
                $teams[$id] = $name;
        }

        return $teams;
    }

    function getActiveTeams() {
        return self::getTeams(true);
    }

    function create($vars, &$errors) { 
        return self::save(0, $vars, $errors);
    }

    function save($id, $vars, &$errors) {

        if($id && $vars['id']!=$id)
            $errors['err']='Missing or invalid team';
            
        if(!$vars['name']) {
            $errors['name']='Team name required';
        } elseif(strlen($vars['name'])<3) {
            $errors['name']='Team name must be at least 3 chars.';
        } elseif(($tid=Team::getIdByName($vars['name'])) && $tid!=$id) {
            $errors['name']='Team name already exists';
        }
        
        if($errors) return false;

        $sql='SET updated=NOW(),isenabled='.db_input($vars['isenabled']).
             ',name='.db_input($vars['name']).
             ',noalerts='.db_input(isset($vars['noalerts'])?$vars['noalerts']:0).
             ',notes='.db_input(Format::sanitize($vars['notes']));

        if($id) {
            $sql='UPDATE '.TEAM_TABLE.' '.$sql.',lead_id='.db_input($vars['lead_id']).' WHERE team_id='.db_input($id);
            if(db_query($sql) && db_affected_rows())
                return true;
                    
            $errors['err']='Unable to update the team. Internal error';
        } else {
            $sql='INSERT INTO '.TEAM_TABLE.' '.$sql.',created=NOW()';
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;
                
            $errors['err']='Unable to create the team. Internal error';
        }
        
        return false;
    }
}
?>
