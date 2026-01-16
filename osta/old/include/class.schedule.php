<?php
/*********************************************************************
    class.schedule.php

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2019 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

/**
 * Schedule
 *
 */
class Schedule extends VerySimpleModel {

    static $meta = array(
        'table' => SCHEDULE_TABLE,
        'ordering' => array('name'),
        'pk' => array('id'),
        'joins' => array(
            'entries' => array(
                'reverse' => 'ScheduleEntry.schedule',
            ),
        ),
    );

    // Supported Schedule types based on flags.
    protected static $types = array(
            'bizhrs' =>
            /* @trans */ 'Business Hours',
            'hdays' =>
            /* @trans */ 'Holiday Hours',
            );

    // Supported Flags
    // FLAG_BIZHRS: Schedule type of Business Hours otherwise Holiday Hours
    // is assumed.
    const FLAG_BIZHRS  = 0x0001;

    protected $_entries;
    protected $_holidays;
    protected $_form;
    protected $_config;

    public function setFlag($flag, $val) {

        if ($val)
            $this->flags |= $flag;
        else
            $this->flags &= ~$flag;
    }

    function get($field, $default=false) {

        try {
            return parent::get($field, $default);
        } catch (Exception $e) {}

        if (!isset($this->_config))
            $this->getConfig();

        if (isset($this->_config[$field]))
            return $this->_config[$field];
    }

    function getConfig() {

        if (!isset($this->_config) && $this->getId()) {
            $_config = new Config('schedule.'.$this->getId());
            $this->_config = $_config->getInfo();
        }

        return $this->_config;
    }


    function getId() {
        return $this->get('id');
    }

    function getName() {
        return $this->getLocal('name');
    }

    function getType() {
        return ($this->flags & self::FLAG_BIZHRS) ? 'bizhrs' : 'hdays';
    }

    function getTypeDesc() {
        $types = self::getTypes();
        return $types[$this->getType()];
    }

    function getTimezone() {
        global $cfg;

        return $this->get('timezone') ?: $cfg->getTimezone();
    }

    function getDatetimeZone() {
        return new DatetimeZone($this->getTimezone());
    }

    function getCreated() {
         return $this->get('created');
    }

    function getUpdated() {
         return $this->get('updated');
    }

    function getInfo() {
        $info = $this->ht;
        $info['type'] = $this->getType();
        return $info;
    }

    function getNumEntries() {
        return $this->getEntries()->count();
    }

    function getEntries() {

        if (!$this->_entries) {
            $this->_entries = ScheduleEntry::objects()
                ->filter(array('schedule_id' => $this->getId()));
        }
        return $this->_entries;
    }

    function getEntry($id) {
        return $this->entries->findFirst(array('id' => $id));
    }

    function addEntry(ScheduleEntryForm $form, &$errors) {

        if (!($vars=$form->process()))
            return false;

        if (!$this->isEntryUnique($vars, $errors))
            $errors['error'] = $errors['error'] ?: __('Entry must be unique');

        if ($errors)
            return false;

        $vars['schedule_id'] = $this->getId();

        if (!($entry = ScheduleEntry::create($vars)))
            return false;

        $this->_entries = false;
        return $entry;
    }

    function isEntryNameUnique($name, $id=0) {
        $entry = $this->entries->findFirst(array('name' => $name));
        return !($entry && $entry->getId() != $id);
    }

    function isEntryUnique($vars, &$errors) {
        // Make sure name is inique
        if (!$vars['name']
                || !$this->isEntryNameUnique($vars['name'], $vars['id'] ?: 0))
            $errors['name'] = __('Name must be unique');

        switch ($vars['repeats']) {
            case 'weekly':
                if ($vars['day'] < 6 ) { // Weekday
                    if ($this->entries->findFirst(['repeats' => 'weekdays']))
                        $errors['error'] = __('Weekdays entry already exists');
                } elseif ($vars['day'] > 5) { //Weekend
                    if ($this->entries->findFirst(['repeats' => 'weekends']))
                        $errors['error'] = __('Weekends entry already exists');
                }
                break;
            case 'weekdays':
                if ($this->entries->findFirst(['repeats' => 'weekly',
                            'day__lt' => 6]))
                    $errors['error'] = __('Week day entry already exists');
                break;
            case 'weekends':
                if ($this->entries->findFirst(['repeats' => 'weekly',
                            'day__gt' => 5]))
                    $errors['error'] = __('Weekend day entry already exists');
                break;
            case 'daily':
                if (!$vars['id'] && $this->entries->count())
                    $errors['error'] = __('Other entries already exists');
                break;
        }

        // Daily entry cannot coexist with other entries - mf is selfish af.
        if (!$errors['error'] && strcasecmp($vars['repeats'], 'daily'))
            if ($this->entries->findFirst(['repeats' => 'daily']))
                $errors['error'] = __('Daily entry already exists');


        if (!$errors['error']) {
            $keys = array_intersect_key($vars, array_flip(
                        ['repeats', 'day', 'week', 'month']));
            $keys['schedule_id'] = $this->getId();
            //  Once entries can repeat on different dates
            if ($keys['repeats'] == 'never')
                $keys['starts_on'] = $vars['starts_on'];

            $entries= ScheduleEntry::objects()
                ->filter($keys);
            if ($vars['id'])
                $entries->exclude(['id' => $vars['id']]);

            if ($entries->count())
                $errors['error'] = __('Entry matching the selection already exists');
        }

        return  !count($errors);
    }

    function isHolidays() {
        return !$this->isBusinessHours();
    }

    function isBusinessHours() {
        return ($this->flags & self::FLAG_BIZHRS);
    }

    function getForm($source=null) {

        if (!isset($this->_form))
            $this->_form = self::basicForm($source ?: $this->getInfo());

        return $this->_form;
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('schedule.%s.%s', $subtag, $this->id));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->get($subtag);
    }

    function getHolidays() {
        if (!$this->isBusinessHours())
            return false;

        if (!isset($this->_holidays)) {
            $config = $this->getConfiguration();
            $this->_holidays = $config['holidays'] ?: array();
        }

        return $this->_holidays;
    }

    function getNumHolidaysSchedules() {
        return count($this->getHolidays() ?: array());
    }

    function getConfiguration() {
        $config = $this->getConfig();
        return JsonDataParser::decode($config['configuration']);
    }

    function saveConfiguration($vars) {
        $config = new Config('schedule.'.$this->getId());
        return $config->updateAll(array(
                    'configuration' => JsonDataEncoder::encode([
                        'holidays' => $vars['holidays'] ?: array()
                    ]),
                    )
                );
    }

    function update($vars, &$errors) {

        $form = $this->getForm($vars);
        if (!$form->isValid())
            return false;

        $data = $form->getClean();
        foreach (['name', 'timezone', 'description'] as $f)
            $this->set($f, $data[$f]);
        // Set Schedule type flag only on create.
        if (!$this->getId() && isset($data['type']))
            $this->setFlag(self::FLAG_BIZHRS, ($data['type'] == 'bizhrs'));

        if (!$this->save(true))
            return false;

        // Update the config information
        $this->saveConfiguration(array(
                    'holidays' =>  $vars['holidays']));
        // Update sorting
        foreach ($this->getEntries() as $e) {
            $id = $e->getId();
            if (isset($_POST["sort-{$id}"])) {
                $e->sort = $_POST["sort-$id"];
                $e->save(true);
            }
        }

        // Reset the form so we don't cache old data
        unset($this->_form);

        return true;
    }

    function save($refetch=false) {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        if (isset($this->dirty['description']))
            $this->description = Format::sanitize($this->description);

        return parent::save($refetch);
    }

    function cloneEntries($schedule) {
        if (!$schedule) return;
        foreach ($schedule->getEntries() as $entry) {
            $vars = $entry->ht;
            unset($vars['id']);
            $vars['schedule_id'] = $this->getId();
            ScheduleEntry::create($vars);
        }
    }

    function delete() {

        // TODO: Deny delete for in-use schedules

        if (!parent::delete())
            return false;

        $this->entries->delete();

        return true;
    }

    function getEntryForm($source=null) {
        return new ScheduleEntryForm($source, array(
                    'timezone' => $this->getTimezone(),
                    'holidays' => !$this->isBusinessHours(),
                    ));
    }

    function getDiagnosticForm($source=null) {

        if (!$source)
            $source = ['date' => time(), 'hours' => 24];

        return new ScheduleDiagnosticForm($source, array(
                    'timezone' => $this->getTimezone(),
                    ));
    }

    static function create($ht=false, &$errors=array()) {
        $inst = new static($ht);
        $inst->set('created', new SqlFunction('NOW'));
        if ($inst->save())
            return $inst;
    }

    static function __create($vars) {
        $s = static::create($vars);
        $s->set('created', new SqlFunction('NOW'));
        $s->set('updated', new SqlFunction('NOW'));
        $s->save(true);
        foreach ($vars['entries'] ?: array() as $info) {
            $entry = new ScheduleEntry($info);
            $entry->schedule_id = $s->getId();
            $entry->save();
        }

        if (isset($vars['configuration']))
            $s->saveConfiguration($vars['configuration']);

        return $s;
    }

    static function getIdByName($name, $pid=0) {
        $schedule = self::objects()->filter(array(
            'name' => $name,
        ))->values_flat('id')->first();
        return $schedule ? $schedule[0] : 0;
    }

    static function lookup($id) {

        if (!($schedule = parent::lookup($id)))
            return null;

        return $schedule;
    }

    static function getSchedules($criteria=array()) {
        $schedules = self::objects();
        if ($criteria)
            $schedules->filter($criteria);

        $schedules->order_by('name');

        return $schedules;
    }

    static function getTypes() {
        static $translated = false;
        if (!$translated) {
            foreach (self::$types as $k => $v)
                self::$types[$k] = __($v);
        }

        return self::$types;
    }


    static function basicForm($source=null) {

        return new SimpleForm(array(
            'name' => new TextboxField(array(
                'required' => true,
                'label' => __('Name'),
                'configuration' => array(
                    'size' => 60,
                    'length' => 0,
                    'autofocus' => true,
                ),
            )),
            'type' => new ChoiceField(array(
                'required' => true,
                'editable' => false,
                'label' => __('Type'),
                'validator-error' => __('Selection required'),
                'choices' => self::getTypes(),
                'configuration' => array(
                    'mode' => 'view',
                    ),
             )),
            'timezone' => new TimezoneField(array(
                'required'=>false,
                'label'=>__('Timezone'),
                'hint' => __('Leave selection empty for Floating Timezone'),
                'configuration' => array(
                    'autodetect'=>false,
                    'prompt' => __("Schedule's timezone")
                ),
            )),
            'description' => new TextareaField(array(
                'label'=> 'Description',
                'required'=>false,
                'default'=>'',
                'configuration' => array(
                    'html' => true,
                    'size' => 'small',
                    'placeholder' => __('Brief description of the schedule'),
                    ),
            )),
        ), $source);
    }
}

/*
 * BusinessHoursSchedule - extension of schedule
 *
 */
class BusinessHoursSchedule extends Schedule {

    // Holidays schedules applicable to this schedule
    protected $holidays;

    public function addWorkingHours(Datetime $date, $hours,
            &$timeline=array()) {
        // Delegate adding  working hours to Business Hours utility class
        $bhrs = new BusinessHours($this);
        if (!$bhrs->addWorkingHours($date, $hours))
            return false;

        $timeline = $bhrs->getTimeline();

        return $date;
    }

    public function getHolidaysSchedules() {

        if (!isset($this->holidays)) {
            $this->holidays = array();
            foreach ($this->getHolidays() ?:array() as $id) {
                if (($s=HolidaysSchedule::lookup($id)))
                    $this->holidays[] = $s;
            }
        }
        return $this->holidays;
    }

    static function getSchedules($criteria=array()) {
        return parent::getSchedules($criteria + array(
                    'flags__hasbit' => Schedule::FLAG_BIZHRS));
    }

    static function lookup($id) {
        return parent::lookup(array(
                    'id' => $id,
                    'flags__hasbit' => Schedule::FLAG_BIZHRS));
    }
}
/*
 * HolidaysSchedule
 *
 */
class HolidaysSchedule extends Schedule {

    static function getSchedules($criteria=array()) {
        return parent::getSchedules($criteria)
            ->exclude(array('flags__hasbit' => Schedule::FLAG_BIZHRS));
    }

    static function lookup($id) {
        return parent::lookup($id);
    }
}

/**
 * ScheduleEntry: An entry in a schedule.
 *
 */
class ScheduleEntry extends VerySimpleModel {

    static $meta = array(
        'table' => SCHEDULE_ENTRY_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'schedule' => array(
                'null' => true,
                'constraint' => array('schedule_id' => 'Schedule.id'),
            ),
        ),
    );

    var $_form;

    protected static $frequencies = array(
            'never' =>
            /* @trans */ 'Once',
            'daily' =>
            /* @trans */ 'Daily',
            'weekly' =>
            /* @trans */ 'Weekly',
            'monthly' =>
            /* @trans */ 'Monthly',
            'yearly' =>
            /* @trans */ 'Yearly',
     );

    protected static $days = array(

            1 =>
            /* @trans */ 'Monday',
            2 =>
            /* @trans */ 'Tuesday',
            3 =>
            /* @trans */ 'Wednesday',
            4 =>
            /* @trans */ 'Thursday',
            5 =>
            /* @trans */ 'Friday',
            6 =>
            /* @trans */ 'Saturday',
            7 =>
            /* @trans */ 'Sunday',
     );

    protected static $weeks = array(
            1 =>
            /* @trans */ 'First',
            2 =>
            /* @trans */ 'Second',
            3 =>
            /* @trans */ 'Third',
            4 =>
            /* @trans */ 'Fourth',
            5 =>
            /* @trans */ 'Fifth',
            -1 =>
            /* @trans */ 'Last',
     );

    protected static $months = array( 1 =>
            /* @trans */ 'January',
            /* @trans */ 'February',
            /* @trans */ 'March',
            /* @trans */ 'April',
            /* @trans */ 'May',
            /* @trans */ 'June',
            /* @trans */ 'July',
            /* @trans */ 'August',
            /* @trans */ 'September',
            /* @trans */ 'October',
            /* @trans */ 'November',
            /* @trans */ 'December'
    );

    protected $_timezone;
    protected $_starts;
    protected $_ends;
    protected $_stops;

    function getId() {
        return $this->get('id');
    }

    function getSchedule() {
        return $this->schedule;
    }

    function getScheduleId() {
        return $this->get('schedule_id');
    }

    function getCreated() {
         return $this->get('created');
    }

    function getUpdated() {
         return $this->get('updated');
    }

    function getTimezone() {
        if (!isset($this->_timezone))
            $this->_timezone = $this->getSchedule()->getTimezone();

        return $this->_timezone;
    }

    function getDatetimeZone() {
        return new DatetimeZone($this->getTimezone());
    }

    function diffTime(Datetime $date) {
        // Set the time based on datetime given
        $start = clone $this->getStartsDatetime();
        list($h, $m, $s) = explode(':', $date->format('H:i:s'));
        $start->setTime($h, $m, $s);
        return $this->getEndsDatetime()->getTimestamp() - $start->getTimestamp();
    }

    function diff() {
        return ($this->getEndsDatetime()->getTimestamp() -
                $this->getStartsDatetime()->getTimestamp());
    }

    function getHours() {
        return  ($this->diff()+1)/3600;
    }

    function getMinutes() {
        return $this->diff()/60;
    }

    function isFullDay() {
        return ($this->getHours() >= 24);
    }

    function isWithinHours(Datetime $dt) {
        return !($this->isAfterHours($dt) || $this->isBeforeHours($dt));
    }

    function isBeforeHours(Datetime $dt) {
        return strtotime($dt->format('H:i:s')) <
            strtotime($this->getStartsTime());
    }

    function isAfterHours(Datetime $dt) {
        return strtotime($dt->format('H:i:s')) >
            strtotime($this->getEndsTime());
    }

    function isOneTime() {
        return !strcasecmp($this->getRepeats(), 'never');
    }

    function getStartsDatetime() {
        if (!isset($this->_starts))
            $this->_starts = new Datetime(sprintf('%s %s',
                        $this->ht['starts_on'], $this->ht['starts_at']),
                        $this->getDatetimeZone());

        return $this->_starts;
    }

    function getStartsTime() {
        return $this->getStartsDatetime()->format('H:i:s');
    }

    function getEndsDatetime() {
        if (!isset($this->_ends))
            $this->_ends = new Datetime(sprintf('%s %s',
                        $this->ht['ends_on'], $this->ht['ends_at']),
                    $this->getDatetimeZone());

        return $this->_ends;
    }

    function getEndsTime() {
        return $this->getEndsDatetime()->format('H:i:s');
    }

    function getStopsDatetime() {
        if (!isset($this->_stops)) {
            if ($this->ht['stops_on'])
                $this->_stops = new Datetime($this->ht['stops_on'],
                    $this->getDatetimeZone());
            elseif ($this->isOneTime())
                $this->_stops = $this->getEndsDatetime();
        }

        return $this->_stops;
    }

    function getIntervalSpec(Datetime $dt) {
        $info = $this->getInfo();
        switch ($info['repeats']) {
            case 'never':
                $starts = $this->getStartsDatetime();
                return $starts->format('Y-m-d');
                break;
            case 'daily':
                return sprintf('%s %s',
                        'today', $dt->format('Y-m-d'));
                break;
            case 'weekdays':
                if ($dt->format('N') > 5)
                    return 'weekday';
                else
                    return sprintf('%s %s',
                            'today', $dt->format('Y-m-d'));
                break;
            case 'weekends':
                if ($dt->format('N') > 5)
                     return sprintf('%s %s',
                             'today', $dt->format('Y-m-d'));
                else
                    return sprintf('Next Saturday %s',
                            $dt->format('Y-m-d'));
                break;
            case 'weekly':
                return sprintf('%s %s',
                        self::$days[$info['day']],
                        $dt->format('Y-m-d'));
                break;
            case 'monthly':
                if (!$info['week'] && $info['day']> 0)
                    return sprintf('%s-%s',
                            $dt->format('Y-m'),
                            $info['day']);
                else
                    return sprintf('%s %s of %s %d',
                             self::$weeks[$info['week']],
                             self::$days[$info['day']],
                             $dt->format('F'),
                             $dt->format('Y'));
                break;
            case 'yearly':
                if ($info['week'] > 0) {
                    return sprintf('%s %s %s %s',
                            self::$months[$info['month']],
                            $dt->format('Y'),
                            self::$weeks[$info['week']],
                            self::$days[$info['day']]);
                } elseif ($info['week'] == -1) {
                    return sprintf('last %s of %s %s',
                            self::$days[$info['day']],
                            self::$months[$info['month']],
                            $dt->format('Y'));

                } else {
                    return sprintf('%s %d %d',
                            self::$months[$info['month']],
                            $info['day'],
                            $dt->format('Y'));
                }
                break;
        }
    }

    function getCurrent($from=null) {
        if (!isset($this->_current) || $from) {
            // Figure out starting  point (from)
            $from = is_object($from) ? clone $from : Format::parseDateTime($from ?: 'now');
            $start =  $this->getStartsDatetime();
            if ($start->getTimestamp() > $from->getTimestamp())
                $from = clone $start;
            // Check to make sure we're still in scope
            $stop = $this->getStopsDatetime();
            if ($stop && $stop->getTimestamp() < $from->getTimestamp())
                return null;

            // Figure out start time for the entry.
            $from->modify($this->getIntervalSpec($from));
            $this->_current = clone $from;
        }
        return $this->_current;
    }

    function next() {
        if (!($current=$this->getCurrent()))
            return null;

        // Advance the interval based on frequencry
        switch ($this->ht['repeats']) {
            case 'daily':
                $current->modify('+1 day');
                break;
            case 'weekly':
                $current->modify('+1 week');
                break;
            case 'weekdays':
                $current->modify('+1 weekday');
                break;
            case 'weekends':
                // Heavy lifting done in getIntervalSpec
                $current->modify('+1 day');
                break;
            case 'monthly':
                $current->modify('+1 month');
                break;
            case 'yearly':
                $current->modify('+1 year');
                break;
            case 'never':
                return null;
        }
        // Set interval spec for specific day/week/month
        $current->modify($this->getIntervalSpec($current));
        // Make sure we're still in scope
        $stops = $this->getStopsDatetime();
        if ($stops && $stops->getTimestamp() < $current->getTimestamp())
            return null;

        // Advance current position
        $this->_current = $current;

        return $current;
    }

    function getOccurrences($start=null, $end=null, $num=5) {
        $occurrences = array();
        if (($current = $this->getCurrent($start))) {
            $start = $start ?: $current;
            while (count($occurrences) < $num) {
                $date = $current->format('Y-m-d');
                if ($end && strtotime($date) > strtotime($end))
                    break;
                if (strtotime($date) >= strtotime($start->format('Y-m-d')))
                    $occurrences[$date] = $this;

                if (!($current=$this->next()))
                    break;
            }
        }

        return $occurrences;
    }

    function getName() {
        return $this->getLocal('name');
    }

    function getRepeats() {
        return $this->get('repeats');
    }

    function getDesc() {
        $info = $this->getInfo();
        $repeats = $this->getRepeats();
        $days = self::getDays();
        $weeks = self::getWeeks();
        $months = self::getMonths();
        $frequencies = self::getFrequencies();
        $starts = $this->getStartsDatetime();
        $ends = $this->getEndsDatetime();

        $when = '';
        $desc = $frequencies[$info['repeats']];
        switch ($info['repeats']) {
            case 'weekly':
                $when = $days[$info['day']];
                break;
            case 'weekdays':
                $desc = sprintf('%s (%s)',
                        __('Weekdays'), __('Mon-Fri'));
                break;
            case 'weekends':
                $desc = sprintf('%s (%s)',
                         __('Weekends'), __('Sat-Sun'));
                break;
            case 'monthly':
                if (!$info['week'])
                    $when = sprintf(__('%s of the Month'),
                            Format::ordinalsuffix($info['day']));
                else
                    $when = sprintf('the %s %s',
                            $weeks[$info['week']], $days[$info['day']]);
                break;
            case 'yearly':
                if (!$info['week']) {
                    $when =sprintf('%s %s',
                            $months[$info['month']],
                            Format::ordinalsuffix($info['day']));
                } else {
                    $when = sprintf('the %s %s in %s',
                            $weeks[$info['week']],
                            $days[$info['day']],
                            $months[$info['month']]);
                }
                break;
            case 'never':
                $when = $starts->format('F jS, Y');
                break;
        }

        if ($when)
            $desc .=' '.sprintf(__('on %s'), $when);

        if (!$this->isFullDay()) {
            $desc .= sprintf(' <small>(%s - %s)</small>',
                    $starts->format('h:i a'),
                    $ends->format('h:i a'));
        }

        return $desc;
    }

    function getSortOrder() {
        return $this->get('sort');
    }

    function getInfo() {
        return $this->ht;
    }

    function getForm($source=null) {

        if (!$this->_form) {
            if (!$source) {
                $source = $this->getInfo();
                $starts = $this->getStartsDatetime();
                $source['starts_on'] = $starts->getTimestamp();
                $source['starts_at'] = $starts->format('h:i a');
                $ends = $this->getEndsDatetime();
                // TODO: Add support for 'ends_on' - We don't support date
                // range at the moment - assuming ends on the same day.
                $source['ends_at'] = $ends->format('h:i a');
                if (($stops=$this->getStopsDatetime()))
                    $source['stops_on'] = $stops->getTimestamp();

                // See if time spans all day.
                if ($this->isFullDay())
                    $source['allday'] = true;

                // Map UI fields
                switch ($source['repeats']) {
                    case 'weekly':
                        $source['weekly_day'] = $source['day'];
                        break;
                    case 'weekdays':
                    case 'weekends':
                        // Keep the UI dumb af
                        $source['weekly_day'] = $source['repeats'];
                        $source['repeats'] = 'weekly';
                        break;
                    case 'monthly':
                        $source['monthly'] = $source['week'] ?: 'day';
                        $source['monthly_week'] = $source['week'];
                        $source['monthly_day'] = $source['day'];
                        break;
                    case 'yearly':
                        $source['yearly'] = $source['week'] ?: 'date';
                        $source['yearly_month'] = $source['month'];
                        $source['yearly_week'] = $source['week'];
                        $source['yearly_day'] = $source['day'];
                        break;
                }
            }
            $this->_form =   new ScheduleEntryForm($source);
        }

        return $this->_form;
    }

    function getFields() {
        return $this->getForm()->getFields();
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('schedulentry.%s.%s', $subtag, $this->id));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->get($subtag);
    }

    function toString() {
        return $this->getName();
    }

    function __toString() {
        return $this->toString();
    }

    function update(ScheduleEntryForm $form, &$errors=array()) {

        if (!($vars=$form->process()))
            return false;

        $vars['id'] = $this->getId();
        if (!$this->getSchedule()->isEntryUnique($vars, $errors))
            return false;

        foreach ($vars as $k => $v)
            $this->set($k, $v);

        return $this->save();
    }

    function delete() {
        return parent::delete();
    }

    function save($refetch=false) {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));

        return parent::save($refetch);
    }

    static function getFrequencies() {
        static $translated = false;
        if (!$translated) {
            foreach (static::$frequencies as $k=>$v)
                static::$frequencies[$k] = __($v);
        }

        return static::$frequencies;
    }

    static function getDays() {
        static $translated = false;
        if (!$translated) {
            foreach (static::$days as $k=>$v)
                static::$days[$k] = __($v);
        }

        return static::$days;
    }

    static function getWeeks() {
        static $translated = false;
        if (!$translated) {
            foreach (static::$weeks as $k=>$v)
                static::$weeks[$k] = __($v);
        }

        return static::$weeks;
    }

    static function getMonths() {
        static $translated = false;
        if (!$translated) {
            foreach (static::$months as $k=>$v)
                static::$months[$k] = __($v);
        }

        return static::$months;
    }
    static function create($ht=false) {
        $inst = new static($ht);
        $inst->set('created', new SqlFunction('NOW'));
        if ($inst->save())
            return $inst;

    }
}

class ScheduleEntryForm
extends AbstractForm {
    static $layout = 'GridFormLayout';
    function buildFields() {
        $timezone = $this->options['timezone'];
        $allday = ($this->options['holidays']);
        $fields = array(
                'name' => new TextboxField(array(
                    'required' => true,
                    'layout' => new GridFluidCell(8),
                    'label' => __('Name'),
                    'configuration' => array(
                      //  'translatable' => $item ? $item->getTranslateTag('value') : false,
                        'size' => 60,
                        'length' => 0,
                        'autofocus' => true,
                        ),
                )),
                'starts_on'  =>  new DatetimeField(array(
                    'label' => __('Starts on'),
                    'required' => true,
                    'layout' => new GridFluidCell(6),
                    'configuration' => array(
                        'time' => false,
                        'gmt' => false,
                        'future' => true,
                        'showtimezone' => false,
                        ),
                )),
                'allday' => new BooleanField(array(
                    'required' => false,
                    'default' => $allday,
                    'label' => __('All Day'),
                    'layout' => new GridFluidCell(6),
                    'configuration'=>array(
                        'desc'=>__('Full 24-hour day'))
                )),
                'starts_at'  =>  new TimeField(array(
                    'required' => true,
                    'default' => $allday ? '12:00 am' : '8:00 am',
                    'label' =>  __('Starts at'),
                    'layout' => new GridFluidCell(6),
                    'configuration' => array(
                        'timezone' => $timezone,
                        'showtimezone' => false,
                        ),
                   'visibility' => new VisibilityConstraint(
                        new Q(array('allday__eq'=>false)),
                        VisibilityConstraint::HIDDEN),
                )),
                'ends_at'  =>  new TimeField(array(
                    'required' => true,
                    'default' => $allday ? '11:59 pm' : '5:00 pm',
                    'label' => __('Ends at'),
                    'layout' => new GridFluidCell(6),
                    'configuration' => array(
                        'timezone' => $timezone,
                        'showtimezone' => false,
                        ),
                   'visibility' => new VisibilityConstraint(
                        new Q(array('allday__eq'=>false)),
                        VisibilityConstraint::HIDDEN),
                )),
                'repeats' => new ChoiceField(array(
                    'required' => true,
                    'layout' => new GridFluidCell(6),
                    'label' => __('Repeats'),
                    'validator-error' => __('Selection required'),
                    'choices' => ScheduleEntry::getFrequencies(),
                 )),
                'stops_on'  =>  new DatetimeField(array(
                    'label' => __('Until'),
                    'required' => false,
                    'layout' => new GridFluidCell(4),
                    'configuration' => array(
                        'time' => false,
                        'gmt' => false,
                        'future' => true,
                        'placeholder' => __('Forever'),
                        'showtimezone' => false,
                        ),
                   'visibility' => new VisibilityConstraint(
                        new Q(array('repeats__eq' => 'daily|weekly|monthly|yearly')),
                        VisibilityConstraint::VISIBLE ),
                )),
                'weekly_day' => new ChoiceField(array(
                    'required' => true,
                    'default' => "",
                    'layout' => new GridFluidCell(6),
                    'label' => __('Day of the Week'),
                    'choices' => ScheduleEntry::getDays() + array(
                        'weekdays' => sprintf('%s (%s)',
                            __('Weekdays'), __('Mon-Fri')),
                        'weekends' => sprintf('%s (%s)',
                            __('Weekends'), __('Sat-Sun'))),
                    'validator-error' => __('Selection required'),
                    'configuration'=>array('prompt'=>__('Select Day of the Week')),
                    'visibility' => new VisibilityConstraint(
                        new Q(array('repeats__eq'=>'weekly')),
                        VisibilityConstraint::HIDDEN ),
                 )),
                'monthly' => new ChoiceField(array(
                    'required' => true,
                    'default' => 'day',
                    'layout' => new GridFluidCell(6, array(
                            'break' => true)),
                    'label' => __('On the'),
                    'validator-error' => __('Selection required'),
                    'choices' => array(
                        'day'  => __('Day of the Month Entered'),
                        '1' => __('First'),
                        '2' => __('Second'),
                        '3' => __('Third'),
                        '4' => __('Fourth'),
                        '5' => __('Fifth'),
                        '-1' => __('Last'),
                        ),
                    'visibility' => new VisibilityConstraint(
                        new Q(array('repeats__eq'=>'monthly')),
                        VisibilityConstraint::HIDDEN ),
                 )),
                'monthly_day' => new ChoiceField(array(
                    'required' => true,
                    'default' => "",
                    'layout' => new GridFluidCell(6),
                    'label' => __('Day'),
                    'choices' => ScheduleEntry::getDays(),
                    'validator-error' => __('Selection required'),
                    'visibility' => new VisibilityConstraint(
                        new Q(array('monthly__neq'=>'day')),
                        VisibilityConstraint::HIDDEN ),
                 )),
                'yearly' => new ChoiceField(array(
                    'required' => true,
                    'layout' => new GridFluidCell(4),
                    'label' => __('On the'),
                    'validator-error' => __('Selection required'),
                    'choices' => array(
                        'date'  => __('Date Entered'),
                        '1' => __('First'),
                        '2' => __('Second'),
                        '3' => __('Third'),
                        '4' => __('Fourth'),
                        '5' => __('Fifth'),
                        '-1' => __('Last'),
                        ),
                    'visibility' => new VisibilityConstraint(
                        new Q(array('repeats__eq'=>'yearly')),
                        VisibilityConstraint::HIDDEN ),
                 )),
                'yearly_day' => new ChoiceField(array(
                    'required' => true,
                    'default' => '',
                    'layout' => new GridFluidCell(4),
                    'label' => __('Day'),
                    'choices' => ScheduleEntry::getDays(),
                    'validator-error' => __('Selection required'),
                    'configuration'=>array('prompt'=>__('Day')),
                    'visibility' => new VisibilityConstraint(
                        new Q(array('yearly__neq'=>'date')),
                        VisibilityConstraint::HIDDEN ),
                 )),
                'yearly_month' => new ChoiceField(array(
                    'required' => true,
                    'default' => 0,
                    'layout' => new GridFluidCell(4),
                    'label' => __('In'),
                    'choices' => ScheduleEntry::getMonths(),
                    'validator-error' => __('Selection required'),
                    'configuration'=>array('prompt'=>__('Month')),
                    'visibility' => new VisibilityConstraint(
                        new Q(array('yearly__neq'=>'date')),
                        VisibilityConstraint::HIDDEN ),
                 )),
            );

        return $fields;
    }

    function process($validate=true) {

        if (!$this->isValid())
            return false;

        // Parse && validate data
        $errors = array();
        $data = $this->getClean($validate);
        $vars = array('name' => $data['name'], 'repeats' => $data['repeats']);
        if (($startsOn=Format::parseDateTime($data['starts_on'])))
            $vars['starts_on'] = $startsOn->format('Y-m-d');
        else
            $errors['starts_on'] = __('Valid Start Date required');

        if ($data['stops_on'] && $startsOn &&
                ($stopsOn = Format::parseDateTime($data['stops_on']))) {
            if ($stopsOn->getTimestamp() <= $startsOn->getTimestamp())
                $errors['ends_on'] = __('Must be in the future');
        }

        if ($data['allday']) {
            $data['starts_at'] = '00:00:00';
            $data['ends_at']  = '23:59:59';
        } else {
            $data['starts_at'] = date('H:i:s', strtotime($data['starts_at']));
            $data['ends_at'] = date('H:i:s', strtotime($data['ends_at']));
            $startsAt = strtotime($vars['starts_on'].' '.$data['starts_at']);
            $endsAt = strtotime($vars['starts_on'] .' '.$data['ends_at'])+59;
            if ($startsAt >= $endsAt)
                $errors['ends_at'] = __('Invalid time span');
        }

        if ($errors) {
            // Replay any errors back on the form fields
            $this->addErrors($errors);
            return false;
        }

        $dt = DateTimeImmutable::createFromMutable($startsOn);
        // Start time
        list($h,$m,$s) = explode(':', $data['starts_at']);
        $starts = $dt->setTime($h, $m, 00);
        $vars['starts_on'] = $starts->format('Y-m-d');
        $vars['starts_at'] = $starts->format('H:i:s');
        // end time
        list($h,$m,$s) = explode(':', $data['ends_at']);
        $ends = $dt->setTime($h, $m, $m == '00' ? 00 : 59);
        $vars['ends_on'] = $ends->format('Y-m-d');
        $vars['ends_at'] = $ends->format('H:i:s');

        // Stop date
        if ($stopsOn)
            $vars['stops_on'] = $stopsOn->format('Y-m-d H:i:s');

        switch ($data['repeats']) {
            case 'weekly':
                switch ($data['weekly_day']) {
                // Under the hood we're overloading repeats on weekdays &
                // weekends to keep the UI stupid and simple.
                case 'weekdays':
                case 'weekends':
                    $vars['repeats'] = $data['weekly_day'];
                    break;
                default:
                    $vars['day'] = $data['weekly_day'];
                    break;
                }
                break;
            case 'monthly':
                $vars['day'] = $data['monthly_day'] ?: null;
                if ($data['monthly'] == 'day')
                    $vars['day'] = $startsOn->format('d');
                else
                    $vars['week'] = $data['monthly'];
                break;
            case 'yearly':
                if ($data['yearly'] == 'date') {
                    $vars['week'] =  null;
                    $vars['day'] = $startsOn->format('d');
                    $vars['month'] = $startsOn->format('m');
                } else {
                    $vars['week'] = $data['yearly'];
                    $vars['day'] = $data['yearly_day'];
                    $vars['month'] = $data['yearly_month'];
                }
                break;
        }

        return $vars;
    }

    function emitJavascript($options=array()) {


        if (!($starts=$this->getField('starts_on')))
            return;

        $keys = array();
        foreach (array('weekly_day' => 'day',
                    'monthly_day' => 'day',
                    'yearly_day' => 'day',
                    'yearly_week' => 'week',
                    'yearly_month' => 'month') as $k => $v) {
            if (($f=$this->getField($k)))
                $keys[$f->getWidget()->id] = $v;
        }
        $id = $starts->getWidget()->id;
        ?>
        <script type="text/javascript">
          $(function() {
            $('#<?php echo $id; ?>').on('change', function() {
                var keys = <?php echo JsonDataEncoder::encode($keys); ?>;
                var d = $(this).datepicker('getDate');
                if (d) {
                    var a = new Array();
                    a['month'] = d.getMonth()+1;
                    a['week'] = Math.ceil(d.getDate() / 7);
                    a['day'] = d.getDay() || 7;
                    console.log(a);
                    $.each(keys, function(key, value) {
                        $sel =  $('#'+key);
                        $val = $('#'+key+'  option:selected').val();
                        console.log(key, value, $val, a[value]);
                        $sel.removeClass('error');
                        if ($val.length == 0)
                            $sel.val(a[value]);
                        else if ($val != a[value])
                            $sel.addClass('error')
                             .bind('change fucus', function() {
                                $(this).removeClass('error');
                               });
                    });
                }
            });
          });
        </script>
        <?php
        parent::emitJavascript($options);
    }
}

class ScheduleDiagnosticForm
extends AbstractForm {
    static $layout = 'GridFormLayout';
    function buildFields() {
        $fields = array(
                'date'  =>  new DatetimeField(array(
                    'label' => __('Date Time'),
                    'required' => true,
                    'layout' => new GridFluidCell(6),
                    'configuration' => array(
                        'time' => true,
                        'gmt' => false,
                        'future' => false,
                        'max' => time(),
                        'showtimezone' => true,
                        ),
                )),
                'hours'  =>  new TextboxField(array(
                    'label' => __('Hours'),
                    'required' => true,
                    'layout' => new GridFluidCell(3),
                    'validator' => 'number',
                    'configuration' => ['size'=>10, 'length'=>10],
                )),
            );
        return $fields;
    }

    function emitJavascript($options=array()) {
        $date = $this->getField('date')->getWidget()->id;
        $hours = $this->getField('hours')->getWidget()->id;
        ?>
        <script type="text/javascript">
          $(function() {
            $('#<?php echo $date; ?>, #<?php echo $hours; ?>').on('change',
                    function() {
                $('#diagnostic-results').hide();
            });
          });
        </script>
        <?php
        parent::emitJavascript($options);
    }

}
?>
