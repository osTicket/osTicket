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

class SLA extends VerySimpleModel {

    static $meta = array(
        'table' => SLA_TABLE,
        'pk' => array('id'),
    );

    //TODO: Use flags

    var $_config;

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->getLocal('name');
    }

    function getGracePeriod() {
        return $this->grace_period;
    }

    function getHashtable() {
        $this->getHashtable();
    }

    function getInfo() {
        return array_merge($this->getConfig()->getInfo(), $this->ht);
    }

    function getConfig() {
        if (!isset($this->_config))
            $this->_config = new SlaConfig($this->getId());

        return $this->_config;
    }

    function getCreateDate() {
        return $this->created;
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function isActive() {
        return ($this->isactive);
    }

    function isTransient() {
        return $this->getConfig()->get('transient', false);
    }

    function sendAlerts() {
        return $this->disable_overdue_alerts;
    }

    function alertOnOverdue() {
        return $this->sendAlerts();
    }

    function priorityEscalation() {
        return ($this->enable_priority_escalation);
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('sla.%s.%s', $subtag, $this->getId()));
    }

    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->ht[$subtag];
    }

    static function getLocalById($id, $subtag, $default) {
        $tag = _H(sprintf('sla.%s.%s', $subtag, $id));
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $default;
    }

    function update($vars, &$errors) {

        if (!$vars['grace_period'])
            $errors['grace_period'] = __('Grace period required');
        elseif (!is_numeric($vars['grace_period']))
            $errors['grace_period'] = __('Numeric value required (in hours)');

        if (!$vars['name'])
            $errors['name'] = __('Name is required');
        elseif (($sid=SLA::getIdByName($vars['name'])) && $sid!=$vars['id'])
            $errors['name'] = __('Name already exists');

        if ($errors)
            return false;

        $this->isactive = $vars['isactive'];
        $this->name = $vars['name'];
        $this->grace_period = $vars['grace_period'];
        $this->disable_overdue_alerts = isset($vars['disable_overdue_alerts']) ? 1 : 0;
        $this->enable_priority_escalation = isset($vars['enable_priority_escalation'])? 1: 0;
        $this->notes = Format::sanitize($vars['notes']);

        if ($this->save()) {
            $this->getConfig()->set('transient', isset($vars['transient']) ? 1 : 0);
            return true;
        }

        if (isset($this->id)) {
            $errors['err']=sprintf(__('Unable to update %s.'), __('this SLA plan'))
               .' '.__('Internal error occurred');
        } else {
            $errors['err']=sprintf(__('Unable to add %s.'), __('this SLA plan'))
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
        global $cfg;

        if(!$cfg || $cfg->getDefaultSLAId()==$this->getId())
            return false;

        //TODO: Use ORM to delete & update
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
    static function getSLAs($criteria=array()) {

       $slas = self::objects()
           ->order_by('name')
           ->values_flat('id', 'name', 'isactive', 'grace_period');

        $entries = array();
        foreach ($slas as $row) {
            $entries[$row[0]] = sprintf(__('%s (%d hours - %s)'
                        /* Tokens are <name> (<#> hours - <Active|Disabled>) */),
                        self::getLocalById($row[0], 'name', $row[1]),
                        $row[3],
                        $row[2] ? __('Active') : __('Disabled'));
        }

        return $entries;
    }

    static function getIdByName($name) {
        $row = static::objects()
            ->filter(array('name'=>$name))
            ->values_flat('id')
            ->first();

        return $row ? $row[0] : 0;
    }

    static function create($vars=false, &$errors=array()) {
        $sla = parent::create($vars);
        $sla->created = SqlFunction::NOW();
        return $sla;
    }

    static function __create($vars, &$errors=array()) {
        $sla = self::create($vars);
        $sla->save();
        return $sla;
    }
}

require_once(INCLUDE_DIR.'class.config.php');
class SlaConfig extends Config {
    var $table = CONFIG_TABLE;

    function __construct($id) {
        parent::__construct("sla.$id");
    }
}
?>
