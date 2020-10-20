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
include_once INCLUDE_DIR.'class.businesshours.php';
include_once INCLUDE_DIR.'class.schedule.php';

class SLA extends VerySimpleModel
implements TemplateVariable {

    static $meta = array(
        'table' => SLA_TABLE,
        'pk' => array('id'),
    );

    const FLAG_ACTIVE       = 0x0001;
    const FLAG_ESCALATE     = 0x0002;
    const FLAG_NOALERTS     = 0x0004;
    const FLAG_TRANSIENT    = 0x0008;

    protected $_config;
    protected $_schedule;

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->getLocal('name');
    }

    function getGracePeriod() {
        return $this->grace_period;
    }

    // Add Grace Period to datetime
    function addGracePeriod(Datetime $date, BusinessHoursSchedule $schedule
            = null, &$timeline=array()) {
        global $cfg;

        // Requested schedule takes precedence, then local and lastly the
        // system default as a fall-back
        if (($schedule = $schedule ?: $this->getSchedule() ?:
                    $cfg->getDefaultSchedule())) {
            if (($schedule->addWorkingHours($date,
                            $this->getGracePeriod(), $timeline)))
                return $date;
        }

        // No schedule, no problem - just add the hours and call ot a day.
        $time = round($this->getGracePeriod()*3600);
        $interval = new DateInterval('PT'.$time.'S');
        $date->add($interval);

        return $date;
    }

    function getScheduleId() {
        return $this->schedule_id;
    }

    function getSchedule() {
        if (!isset($this->_schedule) && $this->getScheduleId())
            $this->_schedule = BusinessHoursSchedule::lookup(
                    $this->getScheduleId());

        return $this->_schedule;
    }

    function getInfo() {
        $base = $this->ht;
        $base['isactive'] = $this->flags & self::FLAG_ACTIVE;
        $base['disable_overdue_alerts'] = $this->flags & self::FLAG_NOALERTS;
        $base['enable_priority_escalation'] = $this->flags & self::FLAG_ESCALATE;
        $base['transient'] = $this->flags & self::FLAG_TRANSIENT;
        return $base;
    }

    function getCreateDate() {
        return $this->created;
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function isActive() {
        return $this->flags & self::FLAG_ACTIVE;
    }

    function isTransient() {
        return $this->flags & self::FLAG_TRANSIENT;
    }

    function sendAlerts() {
        return 0 === ($this->flags & self::FLAG_NOALERTS);
    }

    function hasFlag($flag) {
        return ($this->get('flags', 0) & $flag) != 0;
    }

    function flagChanged($flag, $var) {
        if (($this->hasFlag($flag) && $var != $flag) ||
            (!$this->hasFlag($flag) && $var == $flag))
                return true;
    }

    function alertOnOverdue() {
        return $this->sendAlerts();
    }

    function priorityEscalation() {
        return $this->flags && self::FLAG_ESCALATE;
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

    // TemplateVariable interface
    function asVar() {
        return $this->getName();
    }

    static function getVarScope() {
        return array(
            'name' => __('Service Level Agreement'),
            'graceperiod' => __("Grace Period (hrs)"),
        );
    }

    function update($vars, &$errors) {
        $vars = Format::htmlchars($vars);
        if (!$vars['grace_period'])
            $errors['grace_period'] = __('Grace period required');
        elseif (!is_numeric($vars['grace_period']))
            $errors['grace_period'] = __('Numeric value required (in hours)');
        elseif ($vars['grace_period'] > 8760)
            $errors['grace_period'] = sprintf(
                    __('%s cannot be more than 8760 hours'),
                    __('Grace period')
                    );

        if (!$vars['name'])
            $errors['name'] = __('Name is required');
        elseif (($sid=SLA::getIdByName($vars['name'])) && $sid!=$vars['id'])
            $errors['name'] = __('Name already exists');

        if ($errors)
            return false;

        $vars['disable_overdue_alerts'] = isset($vars['disable_overdue_alerts']) ? self::FLAG_NOALERTS : 0;
        $vars['transient'] = isset($vars['transient']) ? self::FLAG_TRANSIENT : 0;
        //flags
        $auditDisableOverdue = $this->flagChanged(self::FLAG_NOALERTS, $vars['disable_overdue_alerts']);
        $auditTransient = $this->flagChanged(self::FLAG_TRANSIENT, $vars['transient']);
        $auditStatus = $this->flagChanged(self::FLAG_ACTIVE, $vars['isactive']);

        foreach ($vars as $key => $value) {
            if (isset($this->$key) && ($this->$key != $value) ||
               ($auditDisableOverdue && $key == 'disable_overdue_alerts' ||
                $auditTransient && $key == 'transient' || $auditStatus && $key == 'isactive')) {
                $type = array('type' => 'edited', 'key' => $key);
                Signal::send('object.edited', $this, $type);
            }
        }

        $this->name = $vars['name'];
        $this->schedule_id = $vars['schedule_id'];
        $this->grace_period = $vars['grace_period'];
        $this->notes = Format::sanitize($vars['notes']);
        $this->flags =
              ($vars['isactive'] ? self::FLAG_ACTIVE : 0)
            | ($vars['disable_overdue_alerts'])
            | ($vars['enable_priority_escalation'])
            | ($vars['transient']);

        if ($this->save())
            return true;

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

        $type = array('type' => 'deleted');
        Signal::send('object.deleted', $this, $type);

        return $num;
    }

    /** static functions **/
    static function getSLAs($criteria=array()) {

       $slas = self::objects()
           ->order_by('name')
           ->values_flat('id', 'name', 'flags', 'grace_period');

        $entries = array();
        foreach ($slas as $row) {
            if ($criteria['nameOnly'])
                $entries[$row[0]] = __(self::getLocalById($row[0], 'name', $row[1]));
            else {
                $row[2] = $row[2] & self::FLAG_ACTIVE;
                $entries[$row[0]] = sprintf(__('%s (%d hours - %s)'
                            /* Tokens are <name> (<#> hours - <Active|Disabled>) */),
                            self::getLocalById($row[0], 'name', $row[1]),
                            $row[3],
                            $row[2] ? __('Active') : __('Disabled'));
            }
        }

        return $entries;
    }

    static function getSLAName($id) {
        $slas = static::getSLAs();
        return @$slas[$id];
    }

    static function getIdByName($name) {
        $row = static::objects()
            ->filter(array('name'=>$name))
            ->values_flat('id')
            ->first();

        return $row ? $row[0] : 0;
    }

    function __toString() {
        return $this->getName();
    }

    static function create($vars=false, &$errors=array()) {
        $vars = Format::htmlchars($vars);
        $sla = new static($vars);
        $sla->created = SqlFunction::NOW();
        return $sla;
    }

    static function __create($vars, &$errors=array()) {
        $sla = self::create($vars);
        $sla->save();
        return $sla;
    }
}
?>
