<?php

require_once INCLUDE_DIR . 'class.orm.php';

class FilterAction extends VerySimpleModel {
    static $meta = array(
        'table' => FILTER_ACTION_TABLE,
        'pk' => array('id'),
        'ordering' => array('sort'),
    );

    static $registry = array();

    var $_impl;
    var $_config;

    function getId() {
        return $this->id;
    }

    function getConfiguration() {
        if (!$this->_config) {
            $this->_config = $this->get('configuration');
            if (is_string($this->_config))
                $this->_config = JsonDataParser::parse($this->_config);
            elseif (!$this->_config)
                $this->_config = array();
            foreach ($this->getImpl()->getConfigurationOptions() as $name=>$field)
                if (!isset($this->_config[$name]))
                    $this->_config[$name] = $field->get('default');
        }
        return $this->_config;
    }

    function setConfiguration(&$errors=array(), $source=false) {
        $config = array();
        foreach ($this->getImpl()->getConfigurationForm($source ?: $_POST)
                ->getFields() as $name=>$field) {
            $config[$name] = $field->to_php($field->getClean());
            $errors = array_merge($errors, $field->errors());
        }
        if (count($errors) === 0)
            $this->set('configuration', JsonDataEncoder::encode($config));
        return count($errors) === 0;
    }

    function getImpl() {
        if (!isset($this->_impl)) {
            if (!($I = self::lookupByType($this->type, $this)))
                throw new Exception(sprintf(
                    '%s: No such filter action registered', $this->type));
            $this->_impl = $I;
        }
        return $this->_impl;
    }

    function apply(&$ticket, array $info) {
        return $this->getImpl()->apply($ticket, $info);
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    static function register($class, $type=false) {
        // TODO: Check if $class implements TriggerAction
        self::$registry[$type ?: $class::$type] = $class;
    }

    static function lookupByType($type, $thisObj=false) {
        if (!isset(self::$registry[$type]))
            return null;

        $class = self::$registry[$type];
        return new $class($thisObj);
    }

    static function allRegistered() {
        $types = array();
        foreach (self::$registry as $type=>$class) {
            $types[$type] = $class::getName();
        }
        return $types;
    }
}

abstract class TriggerAction {
    function __construct($action=false) {
        $this->action = $action;
    }

    function getConfiguration() {
        if ($this->action)
            return $this->action->getConfiguration();
        return array();
    }

    function getConfigurationForm($source=false) {
        if (!$this->_cform) {
            $config = $this->getConfiguration();
            $options = $this->getConfigurationOptions();
            // Find a uid offset for this guy
            $uid = 1000;
            foreach (FilterAction::$registry as $type=>$class) {
                $uid += 100;
                if ($type == $this->getType())
                    break;
            }
            // Ensure IDs are unique
            foreach ($options as $f) {
                $f->set('id', $uid++);
            }
            $this->_cform = new Form($options, $source);
            if (!$source) {
                foreach ($this->_cform->getFields() as $name=>$f) {
                    if ($config && isset($config[$name]))
                        $f->value = $config[$name];
                    elseif ($f->get('default'))
                        $f->value = $f->get('default');
                }
            }
        }
        return $this->_cform;
    }

    static function getType() { return static::$type; }
    static function getName() { return __(static::$name); }

    abstract function apply(&$ticket, array $info);
    abstract function getConfigurationOptions();
}

class FA_UseReplyTo extends TriggerAction {
    static $type = 'replyto';
    static $name = /* trans */ 'Reply-To Email';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['enable'] && $info['reply-to']) {
            $ticket['email'] = $info['reply-to'];
            if ($info['reply-to-name'])
                $ticket['name'] = $info['reply-to-name'];
        }
    }

    function getConfigurationOptions() {
        return array(
            'enable' => new BooleanField(array(
                'configuration' => array(
                    'desc' => __('Use the Reply-To email header')
                )
            )),
        );
    }
}
FilterAction::register('FA_UseReplyTo');

class FA_DisableAutoResponse extends TriggerAction {
    static $type = 'noresp';
    static $name = /* trans */ "Ticket auto-response";

    function apply(&$ticket, array $info) {
        # TODO: Disable alerting
        # XXX: Does this imply turning it on as well? (via ->sendAlerts())
        $config = $this->getConfiguration();
        if ($config['enable']) {
            $ticket['autorespond']=false;
        }
    }

    function getConfigurationOptions() {
        return array(
            'enable' => new BooleanField(array(
                'configuration' => array(
                    'desc' => __('<strong>Disable</strong> auto-response')
                ),
            )),
        );
    }
}
FilterAction::register('FA_DisableAutoResponse');

class FA_AutoCannedResponse extends TriggerAction {
    static $type = 'canned';
    static $name = /* trans */ "Canned Response";

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['canned_id']) {
            $ticket['cannedResponseId'] = $config['canned_id'];
        }
    }

    function getConfigurationOptions() {
        $sql='SELECT canned_id, title, isenabled FROM '.CANNED_TABLE .' ORDER by title';
        $choices = array(false => '— '.__('None').' —');
        if ($res=db_query($sql)) {
            while (list($id, $title, $isenabled)=db_fetch_row($res)) {
                if (!$isenabled)
                    $title .= ' ' . __('(disabled)');
                $choices[$id] = $title;
            }
        }
        return array(
            'canned_id' => new ChoiceField(array(
                'default' => false,
                'choices' => $choices,
            )),
        );
    }
}
FilterAction::register('FA_AutoCannedResponse');

class FA_RouteDepartment extends TriggerAction {
    static $type = 'dept';
    static $name = /* trans */ 'Department';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['dept_id'])
            $ticket['deptId'] = $config['dept_id'];
    }

    function getConfigurationOptions() {
        $sql='SELECT dept_id,dept_name FROM '.DEPT_TABLE.' dept ORDER by dept_name';
        $choices = array();
        if(($res=db_query($sql)) && db_num_rows($res)){
            while(list($id,$name)=db_fetch_row($res)){
                $choices[$id] = $name;
            }
        }
        return array(
            'dept_id' => new ChoiceField(array(
                'configuration' => array('prompt' => __('Unchanged')),
                'choices' => $choices,
            )),
        );
    }
}
FilterAction::register('FA_RouteDepartment');

class FA_AssignPriority extends TriggerAction {
    static $type = 'pri';
    static $name = /* trans */ "Priority";

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['priority'])
            $ticket['priority_id'] = $config['priority']->getId();
    }

    function getConfigurationOptions() {
        $sql = 'SELECT priority_id, priority_desc FROM '.PRIORITY_TABLE
              .' ORDER BY priority_urgency DESC';
        $choices = array();
        if ($res = db_query($sql)) {
            while ($row = db_fetch_row($res))
                $choices[$row[0]] = $row[1];
        }
        return array(
            'priority' => new ChoiceField(array(
                'configuration' => array('prompt' => __('Unchanged')),
                'choices' => $choices,
            )),
        );
    }
}
FilterAction::register('FA_AssignPriority');

class FA_AssignSLA extends TriggerAction {
    static $type = 'sla';
    static $name = /* trans */ 'SLA Plan';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['sla_id'])
            $ticket['slaId'] = $config['sla_id'];
    }

    function getConfigurationOptions() {
        $choices = SLA::getSLAs();
        return array(
            'sla_id' => new ChoiceField(array(
                'configuration' => array('prompt' => __('Unchanged')),
                'choices' => $choices,
            )),
        );
    }
}
FilterAction::register('FA_AssignSLA');

class FA_AssignTeam extends TriggerAction {
    static $type = 'team';
    static $name = /* trans */ 'Assign Team';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['team_id'])
            $ticket['teamId'] = $config['team_id'];
    }

    function getConfigurationOptions() {
        $sql='SELECT team_id, isenabled, name FROM '.TEAM_TABLE .' ORDER BY name';
        $choices = array();
        if(($res=db_query($sql)) && db_num_rows($res)){
            while (list($id, $isenabled, $name) = db_fetch_row($res)){
                if (!$isenabled)
                    $name .= ' '.__('(disabled)');
                $choices[$id] = $name;
            }
        }
        return array(
            'team_id' => new ChoiceField(array(
                'configuration' => array('prompt' => __('Unchanged')),
                'choices' => $choices,
            )),
        );
    }
}
FilterAction::register('FA_AssignTeam');

class FA_AssignAgent extends TriggerAction {
    static $type = 'agent';
    static $name = /* trans */ 'Assign Agent';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['staff_id'])
            $ticket['staffId'] = $config['staff_id'];
    }

    function getConfigurationOptions() {
        $choices = Staff::getStaffMembers();
        return array(
            'staff_id' => new ChoiceField(array(
                'configuration' => array('prompt' => __('Unchanged')),
                'choices' => $choices,
            )),
        );
    }
}
FilterAction::register('FA_AssignAgent');

class FA_AssignTopic extends TriggerAction {
    static $type = 'topic';
    static $name = /* trans */ 'Help Topic';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['topic_id'])
            $ticket['topicId'] = $config['topic_id'];
    }

    function getConfigurationOptions() {
        $choices = HelpTopic::getAllHelpTopics();
        return array(
            'topic_id' => new ChoiceField(array(
                'configuration' => array('prompt' => __('Unchanged')),
                'choices' => $choices,
            )),
        );
    }
}
FilterAction::register('FA_AssignTopic');

class FA_SetStatus extends TriggerAction {
    static $type = 'status';
    static $name = /* trans */ 'Ticket Status';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['status_id'])
            $ticket['statusId'] = $config['status_id'];
    }

    function getConfigurationOptions() {
        $choices = array();
        foreach (TicketStatusList::getStatuses() as $S) {
            // TODO: Move this to TicketStatus::getName
            $name = $S->getName();
            if (!($isenabled = $S->isEnabled()))
                $name.=' '.__('(disabled)');
            $choices[$S->getId()] = $name;
        }
        return array(
            'status_id' => new ChoiceField(array(
                'configuration' => array('prompt' => __('Unchanged')),
                'choices' => $choices,
            )),
        );
    }
}
FilterAction::register('FA_SetStatus');

class FA_SendEmail extends TriggerAction {
    static $type = 'email';
    static $name = /* trans */ 'Send an Email';

    function apply(&$ticket, array $info) {
        global $ost;

        $config = $this->getConfiguration();
        $info = array('subject' => $config['subject'],
            'message' => $config['message']);
        $info = $ost->replaceTemplateVariables(
            $info, array('ticket' => $ticket)
        );
        $mailer = new Mailer();
        $mailer->send($config['recipients'], $info['subject'], $info['message']);
    }

    function getConfigurationOptions() {
        return array(
            'recipients' => new TextboxField(array(
                'label' => __('Recipients'), 'required' => true,
                'configuration' => array(
                    'size' => 80
                ),
                'validators' => function($self, $value) {
                    if (!($q=Validator::is_email($value, true)))
                        $self->addError('Unable to parse address list. '
                            .'Use commas to separate addresses.');
                }
            )),
            'subject' => new TextboxField(array(
                'configuration' => array(
                    'size' => 80,
                    'placeholder' => __('Subject')
                ),
            )),
            'message' => new TextareaField(array(
                'configuration' => array(
                    'placeholder' => __('Message'),
                    'html' => true,
                ),
            )),
        );
    }
}
FilterAction::register('FA_SendEmail');
