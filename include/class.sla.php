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

    function getDueDate($createdDate) {
        $weekday = intval($createdDate->format('w'));
        $supportDays = $this->getSupportDays();

        $counter = 0;
        $graceSeconds = $this->getGracePeriod() * 3600;
        $startTime = $createdDate;

        // Abort if we try to set an SLA which is too large.
        while ($counter < 150)
        {
            $supportWeekday = ($weekday + $counter) % 7;
            $supportDay = $supportDays[$supportWeekday];
            $remainingSeconds = $supportDay->getGracePeriodSeconds($graceSeconds, $startTime);

            if ($remainingSeconds <= 0) {
                $date = $createdDate;

                // If the grace period expires on a future date (i.e. not today), calculate the
                // time based on the support start time for that day. Otherwise, the SLA expires
                // today so time should be calculated relative to the ticket creation date.
                if ($counter > 0)
                {
                    $date->setTime(0, 0);
                    $dueDayStart = $supportDay->getStartTime();
                    $date->add(new DateInterval('P' . $counter . 'D')); // Add the number of days
                    $date->add(new DateInterval('PT' . $dueDayStart->format('H') . 'H' . $dueDayStart->format('i') . 'M')); // Add support start time on that day
                }
                
                $date->add(new DateInterval('PT' . $graceSeconds . 'S')); // Add remaining grace period seconds

                return $date;
            }

            unset($startTime);
            $graceSeconds = $remainingSeconds;
            $counter++;
        }

        $defaultDate = $createdDate;
        $defaultDate->add(new DateInterval('P150D'));
        return $defaultDate;
    }

    function getSupportDays() {
        return array(
            0 => new SupportDay(intval($this->sun_mode), $this->sun_start_time, $this->sun_end_time), // Sunday
            1 => new SupportDay(intval($this->mon_mode), $this->mon_start_time, $this->mon_end_time), // Monday
            2 => new SupportDay(intval($this->tue_mode), $this->tue_start_time, $this->tue_end_time), // Tuesday
            3 => new SupportDay(intval($this->wed_mode), $this->wed_start_time, $this->wed_end_time), // Wednesday
            4 => new SupportDay(intval($this->thu_mode), $this->thu_start_time, $this->thu_end_time), // Thursday
            5 => new SupportDay(intval($this->fri_mode), $this->fri_start_time, $this->fri_end_time), // Friday
            6 => new SupportDay(intval($this->sat_mode), $this->sat_start_time, $this->sat_end_time)  // Saturday
        );
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

        if (!$vars['grace_period'])
            $errors['grace_period'] = __('Grace period required');
        elseif (!is_numeric($vars['grace_period']))
            $errors['grace_period'] = __('Numeric value required (in hours)');
        
        elseif (intval($vars['grace_period'])>800)
            $errors['grace_period'] = __('Grace period must be les than 100 hours');

        if (!$vars['name'])
            $errors['name'] = __('Name is required');
        elseif (($sid=SLA::getIdByName($vars['name'])) && $sid!=$vars['id'])
            $errors['name'] = __('Name already exists');
        
        if ($vars['sun_start_time'] != null){
        $sunCheck = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['sun_start_time']); 
        if ($sunCheck == false) { $errors['sun_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        elseif ($vars['sun_mode'] == 2 ) { 
            if ($vars['sun_start_time'] >= $vars['sun_end_time'] )
                    $errors['sun_start_time'] = __('Start time is greater than end time');}
        }
       
        if ($vars['sun_end_time'] != null){
        $sunCheck1 = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['sun_end_time']); 
        if ($sunCheck1 == false) { $errors['sun_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        }

        if ($vars['mon_start_time'] != null){
        $monCheck = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['mon_start_time']); 
        if ($monCheck == false) { $errors['mon_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        elseif ($vars['mon_mode'] == 2 ) { 
            if ($vars['mon_start_time'] >= $vars['mon_end_time'] )
                    $errors['mon_start_time'] = __('Start time is greater than end time');}
        }
        
        if ($vars['mon_end_time'] != null){
        $monCheck1 = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['mon_end_time']); 
        if ($monCheck1 == false) { $errors['mon_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        }
        
        if ($vars['tue_start_time'] != null){
            $tueCheck = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['tue_start_time']); 
        if ($tueCheck == false) { $errors['tue_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
            elseif ($vars['tue_mode'] == 2 ) { 
            if ($vars['tue_start_time'] >= $vars['tue_end_time'] )
                    $errors['tue_start_time'] = __('Start time is greater than end time');}    
        }
        
        if ($vars['tue_end_time'] != null){
            $tueCheck1 = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['tue_end_time']); 
        if ($tueCheck1 == false) { $errors['tue_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        }
        
        if ($vars['wed_start_time'] != null){
            $wedCheck = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['wed_start_time']); 
        if ($wedCheck == false) { $errors['wed_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        elseif ($vars['wed_mode'] == 2 ) { 
            if ($vars['wed_start_time'] >= $vars['wed_end_time'] )
                    $errors['wed_start_time'] = __('Start time is greater than end time');}  
        }
        
        if ($vars['wed_end_time'] != null){
            $wedCheck1 = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['wed_end_time']); 
        if ($wedCheck1 == false) { $errors['wed_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        } 
        
        if ($vars['thu_start_time'] != null){
           $thuCheck = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['thu_start_time']); 
        if ($thuCheck == false) { $errors['thu_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        elseif ($vars['thu_mode'] == 2 ) { 
            if ($vars['thu_start_time'] >= $vars['thu_end_time'] )
                    $errors['thu_start_time'] = __('Start time is greater than end time');} 
        } 
        
        if ($vars['thu_end_time'] != null){
            $thuCheck1 = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['thu_end_time']); 
        if ($thuCheck1 == false) { $errors['thu_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}  
        } 
        
        if ($vars['fri_start_time'] != null){
            $friCheck = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['fri_start_time']); 
        if ($friCheck == false) { $errors['fri_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        elseif ($vars['fri_mode'] == 2 ) { 
            if ($vars['fri_start_time'] >= $vars['fri_end_time'] )
                    $errors['fri_start_time'] = __('Start time is greater than end time');}
        } 
        
        if ($vars['fri_end_time'] != null){
            $friCheck1 = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['fri_end_time']); 
        if ($friCheck1 == false) { $errors['fri_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        }   
     
        if ($vars['sat_start_time'] != null){
             $satCheck = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['sat_start_time']); 
        if ($satCheck == false) { $errors['sat_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        elseif ($vars['sat_mode'] == 2 ) { 
            if ($vars['sat_start_time'] >= $vars['sat_end_time'] )
                    $errors['sat_start_time'] = __('Start time is greater than end time');}
        }

        if ($vars['sat_end_time'] != null){
            $satCheck1 = DateTime::createFromFormat('d.m.Y H:i', "10.10.2010 " .$vars['sat_end_time']); 
        if ($satCheck1 == false) { $errors['sat_start_time'] = __('<br>Please enter valid time format e.g. 11:00');}
        }

        if ($errors)
            return false;

        $this->name = $vars['name'];
        $this->grace_period = $vars['grace_period'];

        $this->sun_mode = $vars['sun_mode'];
        
        $this->sun_start_time = $vars['sun_start_time'];
        $this->sun_end_time = $vars['sun_end_time'];

        $this->mon_mode = $vars['mon_mode'];
        $this->mon_start_time = $vars['mon_start_time'];
        $this->mon_end_time = $vars['mon_end_time'];

        $this->tue_mode = $vars['tue_mode'];
        $this->tue_start_time = $vars['tue_start_time'];
        $this->tue_end_time = $vars['tue_end_time'];

        $this->wed_mode = $vars['wed_mode'];
        $this->wed_start_time = $vars['wed_start_time'];
        $this->wed_end_time = $vars['wed_end_time'];

        $this->thu_mode = $vars['thu_mode'];
        $this->thu_start_time = $vars['thu_start_time'];
        $this->thu_end_time = $vars['thu_end_time'];

        $this->fri_mode = $vars['fri_mode'];
        $this->fri_start_time = $vars['fri_start_time'];
        $this->fri_end_time = $vars['fri_end_time'];

        $this->sat_mode = $vars['sat_mode'];
        $this->sat_start_time = $vars['sat_start_time'];
        $this->sat_end_time = $vars['sat_end_time'];

        $this->notes = Format::sanitize($vars['notes']);
        $this->flags =
              ($vars['isactive'] ? self::FLAG_ACTIVE : 0)
            | (isset($vars['disable_overdue_alerts']) ? self::FLAG_NOALERTS : 0)
            | (isset($vars['enable_priority_escalation']) ? self::FLAG_ESCALATE : 0)
            | (isset($vars['transient']) ? self::FLAG_TRANSIENT : 0);

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

        return $num;
    }

    /** static functions **/
    static function getSLAs($criteria=array()) {

       $slas = self::objects()
           ->order_by('name')
           ->values_flat('id', 'name', 'flags', 'grace_period');

        $entries = array();
        foreach ($slas as $row) {
            $row[2] = $row[2] & self::FLAG_ACTIVE;
            $entries[$row[0]] = sprintf(__('%s (%d hours - %s)'
                        /* Tokens are <name> (<#> hours - <Active|Disabled>) */),
                        self::getLocalById($row[0], 'name', $row[1]),
                        $row[3],
                        $row[2] ? __('Active') : __('Disabled'));
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

    static function create($vars=false, &$errors=array()) {
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
