<?php

require_once INCLUDE_DIR . 'class.orm.php';

class FilterAction extends VerySimpleModel {
    static $meta = array(
        'table' => FILTER_ACTION_TABLE,
        'pk' => array('id'),
        'ordering' => array('sort'),
    );

    static $registry = array();
    static $registry_group = array();

    var $_impl;
    var $_config;
    var $_filter;

    function getId() {
        return $this->id;
    }

    function setFilter($filter) {
        $this->_filter = $filter;
    }
    function getFilter() {
        return $this->_filter;
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
            if (!$field->hasData())
                continue;
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

    static function register($class, $group=false) {
        if (!$class::$type)
            throw new Exception('Filter actions must specify ::$type');
        elseif (!is_subclass_of($class, 'TriggerAction'))
            throw new Exception('Filter actions must extend from TriggerAction');

        self::$registry[$class::$type] = $class;
        self::$registry_group[$group ?: ''][$class::$type] = $class;
    }

    static function lookupByType($type, $thisObj=false) {
        if (!isset(self::$registry[$type]))
            return null;

        $class = self::$registry[$type];
        return new $class($thisObj);
    }

    static function allRegistered($group=false) {
        $types = array();
        foreach (self::$registry_group as $group=>$actions) {
            $G = $group ? __($group) : '';
            foreach ($actions as $type=>$class) {
                $types[$G][$type] = __($class::getName());
            }
        }
        return $types;
    }
}

abstract class TriggerAction {
    static $type = false;
    static $flags = 0;

    const FLAG_MULTI_USE    = 0x0001;   // Action can be used multiple times

    var $action;

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
            $this->_cform = new SimpleForm($options, $source);
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

    function hasFlag($flag) {
        return static::$flags & $flag > 0;
    }

    static function getType() { return static::$type; }
    static function getName() { return __(static::$name); }

    abstract function apply(&$ticket, array $info);
    abstract function getConfigurationOptions();
}

class FA_RejectTicket extends TriggerAction {
    static $type = 'reject';
    static $name = /* @trans */ 'Reject Ticket';

    function apply(&$ticket, array $info) {
        throw new RejectedException($this->action->getFilter(), $ticket);
    }

    function getConfigurationOptions() {
        return array(
            '' => new FreeTextField(array(
                'configuration' => array(
                    'content' => sprintf('<span style="color:red"><b>%s</b></span>',
                        __('Reject Ticket')),
                )
            )),
        );
    }
}
FilterAction::register('FA_RejectTicket', /* @trans */ 'Ticket');

class FA_UseReplyTo extends TriggerAction {
    static $type = 'replyto';
    static $name = /* @trans */ 'Use Reply-To Email';

    function apply(&$ticket, array $info) {
        if (!$info['reply-to']
            || !$ticket['email']
            || !strcasecmp($info['reply-to'], $ticket['email']))
            // Nothing to do
            return;

        // Change email and  throw data changed exception
        $ticket['email'] = $info['reply-to'];
        if ($info['reply-to-name'])
            $ticket['name'] = $info['reply-to-name'];

        throw new FilterDataChanged($ticket);
    }

    function getConfigurationOptions() {
        return array(
            '' => new FreeTextField(array(
                'configuration' => array(
                    'content' => __('<strong>Use</strong> the Reply-To email header')
                )
            )),
        );
    }
}
FilterAction::register('FA_UseReplyTo', /* @trans */ 'Communication');

class FA_DisableAutoResponse extends TriggerAction {
    static $type = 'noresp';
    static $name = /* @trans */ "Disable autoresponse";

    function apply(&$ticket, array $info) {
        # TODO: Disable alerting
        # XXX: Does this imply turning it on as well? (via ->sendAlerts())
        $ticket['autorespond']=false;
    }

    function getConfigurationOptions() {
        return array(
            '' => new FreeTextField(array(
                'configuration' => array(
                    'content' => __('<strong>Disable</strong> new ticket auto-response')
                ),
            )),
        );
    }
}
FilterAction::register('FA_DisableAutoResponse', /* @trans */ 'Communication');

class FA_AutoCannedResponse extends TriggerAction {
    static $type = 'canned';
    static $name = /* @trans */ "Attach Canned Response";

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
FilterAction::register('FA_AutoCannedResponse', /* @trans */ 'Communication');

class FA_RouteDepartment extends TriggerAction {
    static $type = 'dept';
    static $name = /* @trans */ 'Set Department';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['dept_id'])
            $ticket['deptId'] = $config['dept_id'];
    }

    function getConfigurationOptions() {
        return array(
            'dept_id' => new ChoiceField(array(
                'configuration' => array(
                    'prompt' => __('Unchanged'),
                    'data' => array('quick-add' => 'department'),
                ),
                'choices' =>
                    Dept::getDepartments() +
                    array(':new:' => '— '.__('Add New').' —'),
                'validators' => function($self, $clean) {
                    if ($clean === ':new:')
                        $self->addError(__('Select a department'));
                }
            )),
        );
    }
}
FilterAction::register('FA_RouteDepartment', /* @trans */ 'Ticket');

class FA_AssignPriority extends TriggerAction {
    static $type = 'pri';
    static $name = /* @trans */ "Set Priority";

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['priority'])
            $ticket['priorityId'] = $config['priority'];
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
FilterAction::register('FA_AssignPriority', /* @trans */ 'Ticket');

class FA_AssignSLA extends TriggerAction {
    static $type = 'sla';
    static $name = /* @trans */ 'Set SLA Plan';

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
FilterAction::register('FA_AssignSLA', /* @trans */ 'Ticket');

class FA_AssignTeam extends TriggerAction {
    static $type = 'team';
    static $name = /* @trans */ 'Assign Team';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['team_id'])
            $ticket['teamId'] = $config['team_id'];
    }

    function getConfigurationOptions() {
        $choices = Team::getTeams();
        return array(
            'team_id' => new ChoiceField(array(
                'configuration' => array(
                    'prompt' => __('Unchanged'),
                    'data' => array('quick-add' => 'team'),
                ),
                'choices' =>
                    Team::getTeams() +
                    array(':new:' => '— '.__('Add New').' —'),
                'validators' => function($self, $clean) {
                    if ($clean === ':new:')
                        $self->addError(__('Select a Team'));
                }
            )),
        );
    }
}
FilterAction::register('FA_AssignTeam', /* @trans */ 'Ticket');

class FA_AssignAgent extends TriggerAction {
    static $type = 'agent';
    static $name = /* @trans */ 'Assign Agent';

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
FilterAction::register('FA_AssignAgent', /* @trans */ 'Ticket');

class FA_AssignTopic extends TriggerAction {
    static $type = 'topic';
    static $name = /* @trans */ 'Set Help Topic';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['topic_id'])
            $ticket['topicId'] = $config['topic_id'];
    }

    function getConfigurationOptions() {
        $choices = Topic::getHelpTopics(false, Topic::DISPLAY_DISABLED);
        return array(
            'topic_id' => new ChoiceField(array(
                'configuration' => array('prompt' => __('Unchanged')),
                'choices' => $choices,
            )),
        );
    }
}
FilterAction::register('FA_AssignTopic', /* @trans */ 'Ticket');

class FA_SetStatus extends TriggerAction {
    static $type = 'status';
    static $name = /* @trans */ 'Set Ticket Status';

    function apply(&$ticket, array $info) {
        $config = $this->getConfiguration();
        if ($config['status_id'])
            $ticket['statusId'] = $config['status_id'];
    }

    function getConfigurationOptions() {
        $choices = array();
        foreach (TicketStatusList::getStatuses(array(
            'states' => array('open', 'closed')
        ))
        as $S) {
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
FilterAction::register('FA_SetStatus', /* @trans */ 'Ticket');

class FA_SendEmail extends TriggerAction {
    static $type = 'email';
    static $name = /* @trans */ 'Send an Email';
    static $flags = TriggerAction::FLAG_MULTI_USE;

    function apply(&$ticket, array $info) {
        global $ost;

        $config = $this->getConfiguration();
        $info = array('subject' => $config['subject'],
            'message' => $config['message']);
        $info = $ost->replaceTemplateVariables(
            $info, array('ticket' => $ticket)
        );

        // Honor FROM address settings
        if (!$config['from'] || !($mailer = Email::lookup($config['from'])))
            $mailer = new Mailer();

        // Allow %{user} in the To: line
        $replacer = new VariableReplacer();
        $replacer->assign(array(
            'user' => sprintf('"%s" <%s>', $ticket['name'], $ticket['email'])
        ));
        $to = $replacer->replaceVars($config['recipients']);

        require_once PEAR_DIR . 'Mail/RFC822.php';
        require_once PEAR_DIR . 'PEAR.php';

        if (!($mails = Mail_RFC822::parseAddressList($to)) || PEAR::isError($mails))
            return false;

        // Allow %{recipient} in the body
        foreach ($mails as $R) {
            $recipient = sprintf('%s <%s@%s>', $R->personal, $R->mailbox, $R->host);
            $replacer->assign(array(
                'recipient' => new EmailAddress($recipient),
            ));
            $I = $replacer->replaceVars($info);
            $mailer->send($recipient, $I['subject'], $I['message']);
        }

    }

    static function getVarScope() {
        $context = array(
            'ticket' => array(
                'class' => 'FA_SendEmail_TicketInfo', 'desc' => __('Ticket'),
            ),
            'user' => __('Ticket Submitter'),
            'recipient' => array(
                'class' => 'EmailAddress', 'desc' => __('Recipient'),
            ),
        ) + osTicket::getVarScope();
        return VariableReplacer::compileScope($context);
    }

    function getConfigurationOptions() {
        $choices = array('' => __('Default System Email'));
        $choices += Email::getAddresses();

        return array(
            'recipients' => new TextboxField(array(
                'label' => __('Recipients'), 'required' => true,
                'configuration' => array(
                    'size' => 80, 'length' => 1000,
                ),
                'validators' => function($self, $value) {
                    if (!($mails = Mail_RFC822::parseAddressList($value)) || PEAR::isError($mails))
                        $self->addError('Unable to parse address list. '
                            .'Use commas to separate addresses.');

                    $valid = array('user',);
                    foreach ($mails as $M) {
                        // Check placeholders like '%{user}'
                        $P = array();
                        if (preg_match('`%\{([^}]+)\}`', $M->mailbox, $P)) {
                            if (!in_array($P[1], $valid))
                                $self->addError(sprintf('%s: Not a valid variable', $P[0]));
                        }
                        elseif ($M->host == 'localhost' || !$M->mailbox) {
                            $self->addError(sprintf(__('%s: Not a valid email address'),
                                $M->mailbox . '@' . $M->host));
                        }
                    }
                }
            )),
            'subject' => new TextboxField(array(
                'required' => true,
                'configuration' => array(
                    'size' => 80,
                    'placeholder' => __('Subject')
                ),
            )),
            'message' => new TextareaField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __('Message'),
                    'html' => true,
                    'context' => 'fa:send_email',
                ),
            )),
            'from' => new ChoiceField(array(
                'label' => __('From Email'),
                'choices' => $choices,
                'default' => '',
            )),
        );
    }
}
FilterAction::register('FA_SendEmail', /* @trans */ 'Communication');

class FA_SendEmail_TicketInfo {
    static function getVarScope() {
        return array(
            'message' => __('Message from the EndUser'),
            'source' => __('Source'),
        );
    }
}
