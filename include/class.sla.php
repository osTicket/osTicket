<?php
/*********************************************************************
    class.sla.php

    SLA

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
class SLA {

    var $id;

    var $info;
    var $config;

    function SLA($id) {
        $this->id=0;
        $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT * FROM '.SLA_TABLE.' WHERE id='.db_input($id);
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['id'];
        return true;
    }

    function reload() {
        return $this->load();
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->ht['name'];
    }

    function getGracePeriod() {
        return $this->ht['grace_period'];
    }

    function getNotes() {
        return $this->ht['notes'];
    }

    function getHashtable() {
        return array_merge($this->getConfig()->getInfo(), $this->ht);
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getConfig() {
        if (!isset($this->config))
            $this->config = new SlaConfig($this->getId());
        return $this->config;
    }

    function isActive() {
        return ($this->ht['isactive']);
    }

    function isTransient() {
        return $this->getConfig()->get('transient', false);
    }

    function sendAlerts() {
        return (!$this->ht['disable_overdue_alerts']);
    }

    function alertOnOverdue() {
        return $this->sendAlerts();
    }

    function priorityEscalation() {
        return ($this->ht['enable_priority_escalation']);
    }

    function update($vars,&$errors) {

        if(!SLA::save($this->getId(),$vars,$errors))
            return false;

        $this->reload();
        $this->getConfig()->set('transient', isset($vars['transient']) ? 1 : 0);

        return true;
    }

    function delete() {
        global $cfg;

        if(!$cfg || $cfg->getDefaultSLAId()==$this->getId())
            return false;

        $id=$this->getId();
        $sql='DELETE FROM '.SLA_TABLE.' WHERE id='.db_input($id).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            db_query('UPDATE '.DEPT_TABLE.' SET sla_id=0 WHERE sla_id='.db_input($id));
            db_query('UPDATE '.TOPIC_TABLE.' SET sla_id=0 WHERE sla_id='.db_input($id));
            db_query('UPDATE '.TICKET_TABLE.' SET sla_id='.db_input($cfg->getDefaultSLAId()).' WHERE sla_id='.db_input($id));
        }

        return $num;
    }

    /** static functions **/
    function create($vars,&$errors) {
        if (($id = SLA::save(0,$vars,$errors)) && ($sla = self::lookup($id)))
            $sla->getConfig()->set('transient',
                isset($vars['transient']) ? 1 : 0);
        return $id;
    }

    function getSLAs() {

        $slas=array();
        $sql='SELECT id, name, isactive, grace_period FROM '.SLA_TABLE.' ORDER BY name';
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while($row=db_fetch_array($res))
                $slas[$row['id']] = sprintf('%s (%d hrs - %s)',
                        $row['name'],
                        $row['grace_period'],
                        $row['isactive']?'Active':'Disabled');
        }

        return $slas;
    }


    function getIdByName($name) {

        $sql='SELECT id FROM '.SLA_TABLE.' WHERE name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function lookup($id) {
        return ($id && is_numeric($id) && ($sla= new SLA($id)) && $sla->getId()==$id)?$sla:null;
    }

    function save($id,$vars,&$errors) {


        if(!$vars['grace_period'])
            $errors['grace_period']='Grace period required';
        elseif(!is_numeric($vars['grace_period']))
            $errors['grace_period']='Numeric value required (in hours)';

        if(!$vars['name'])
            $errors['name']='Name required';
        elseif(($sid=SLA::getIdByName($vars['name'])) && $sid!=$id)
            $errors['name']='Name already exists';

        if($errors) return false;

        $sql=' updated=NOW() '.
             ',isactive='.db_input($vars['isactive']).
             ',name='.db_input($vars['name']).
             ',grace_period='.db_input($vars['grace_period']).
             ',disable_overdue_alerts='.db_input(isset($vars['disable_overdue_alerts'])?1:0).
             ',enable_priority_escalation='.db_input(isset($vars['enable_priority_escalation'])?1:0).
             ',notes='.db_input(Format::sanitize($vars['notes']));

        if($id) {
            $sql='UPDATE '.SLA_TABLE.' SET '.$sql.' WHERE id='.db_input($id);
            if(db_query($sql))
                return true;

            $errors['err']='Unable to update SLA. Internal error occurred';
        }else{
            if (isset($vars['id']))
                $sql .= ', id='.db_input($vars['id']);

            $sql='INSERT INTO '.SLA_TABLE.' SET '.$sql.',created=NOW() ';
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;

            $errors['err']='Unable to add SLA. Internal error';
        }

        return false;
    }
}

require_once(INCLUDE_DIR.'class.config.php');
class SlaConfig extends Config {
    var $table = CONFIG_TABLE;

    function SlaConfig($id) {
        parent::Config("sla.$id");
    }
}
?>
