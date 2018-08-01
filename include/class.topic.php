<?php
/*********************************************************************
    class.topic.php

    Help topic helper

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once INCLUDE_DIR . 'class.sequence.php';
require_once INCLUDE_DIR . 'class.filter.php';

class Topic extends VerySimpleModel
implements TemplateVariable {

    static $meta = array(
        'table' => TOPIC_TABLE,
        'pk' => array('topic_id'),
        'ordering' => array('topic'),
        'joins' => array(
            'parent' => array(
                'list' => false,
                'constraint' => array(
                    'topic_pid' => 'Topic.topic_id',
                ),
            ),
            'faqs' => array(
                'list' => true,
                'reverse' => 'FaqTopic.topic'
            ),
            'page' => array(
                'null' => true,
                'constraint' => array(
                    'page_id' => 'Page.id',
                ),
            ),
            'dept' => array(
                'null' => true,
                'constraint' => array(
                    'dept_id' => 'Dept.id',
                ),
            ),
            'priority' => array(
                'null' => true,
                'constraint' => array(
                    'priority_id' => 'Priority.priority_id',
                ),
            ),
            'forms' => array(
                'reverse' => 'TopicFormModel.topic',
                'null' => true,
            ),
        ),
    );

    var $_forms;

    const DISPLAY_DISABLED = 2;

    const FORM_USE_PARENT = 4294967295;

    const FLAG_CUSTOM_NUMBERS = 0x0001;

    const SORT_ALPHA = 'a';
    const SORT_MANUAL = 'm';

    function asVar() {
        return $this->getName();
    }

    static function getVarScope() {
        return array(
            'dept' => array(
                'class' => 'Dept', 'desc' => __('Department'),
            ),
            'fullname' => __('Help topic full path'),
            'name' => __('Help topic'),
            'parent' => array(
                'class' => 'Topic', 'desc' => __('Parent'),
            ),
            'sla' => array(
                'class' => 'SLA', 'desc' => __('Service Level Agreement'),
            ),
        );
    }

    function getId() {
        return $this->topic_id;
    }

    function getPid() {
        return $this->topic_pid;
    }

    function getParent() {
        return $this->parent;
    }

    function getName() {
        return $this->topic;
    }

    function getLocalName() {
        return $this->getLocal('name');
    }

    function getFullName() {
        return self::getTopicName($this->getId()) ?: $this->topic;
    }

    static function getTopicName($id) {
        $names = static::getHelpTopics(false, true);
        return $names[$id];
    }

    function getDeptId() {
        return $this->dept_id;
    }

    function getSLAId() {
        return $this->sla_id;
    }

    function getPriorityId() {
        return $this->priority_id;
    }

    function getStatusId() {
        return $this->status_id;
    }

    function getStaffId() {
        return $this->staff_id;
    }

    function getTeamId() {
        return $this->team_id;
    }

    function getPageId() {
        return $this->page_id;
    }

    function getPage() {
        return $this->page;
    }

    function getForms() {
        if (!isset($this->_forms)) {
            $this->_forms = array();
            foreach ($this->forms->select_related('form') as $F) {
                $extra = JsonDataParser::decode($F->extra) ?: array();
                $F->form->disableFields($extra['disable'] ?: array());
                $this->_forms[] = $F->form;
            }
        }
        return $this->_forms;
    }

    function autoRespond() {
        return !$this->noautoresp;
    }

    function isEnabled() {
        return $this->isActive();
    }

    /**
     * Determine if the help topic is currently enabled. The ancestry of
     * this topic will be considered to see if any of the parents are
     * disabled. If any are disabled, then this topic will be considered
     * disabled.
     *
     * Parameters:
     * $chain - array<id:bool> recusion chain used to detect loops. The
     *      chain should be maintained and passed to a parent's ::isActive()
     *      method. When consulting a parent, if the local topic ID is a key
     *      in the chain, then this topic has already been considered, and
     *      there is a loop in the ancestry
     */
    function isActive(array $chain=array()) {
        if (!$this->isactive)
            return false;

        if (!isset($chain[$this->getId()]) && ($p = $this->getParent())) {
            $chain[$this->getId()] = true;
            return $p->isActive($chain);
        }
        else {
            return $this->isactive;
        }
    }

    function isPublic() {
        return ($this->ispublic);
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        $base = $this->getHashtable();
        $base['custom-numbers'] = $this->hasFlag(self::FLAG_CUSTOM_NUMBERS);
        return $base;
    }

    function hasFlag($flag) {
        return $this->flags & $flag != 0;
    }

    function getNewTicketNumber() {
        global $cfg;

        if (!$this->hasFlag(self::FLAG_CUSTOM_NUMBERS))
            return $cfg->getNewTicketNumber();

        if ($this->sequence_id)
            $sequence = Sequence::lookup($this->sequence_id);
        if (!$sequence)
            $sequence = new RandomSequence();

        return $sequence->next($this->number_format ?: '######',
            array('Ticket', 'isTicketNumberUnique'));
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('topic.%s.%s', $subtag, $this->getId()));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->ht[$subtag];
    }

    function setSortOrder($i) {
        if ($i != $this->sort) {
            $this->sort = $i;
            return $this->save();
        }
        // Noop
        return true;
    }

    function delete() {
        global $cfg;

        if ($this->getId() == $cfg->getDefaultTopicId())
            return false;

        if (parent::delete()) {
            self::objects()->filter(array(
                'topic_pid' => $this->getId()
            ))->update(array(
                'topic_pid' => 0
            ));
            FaqTopic::objects()->filter(array(
                'topic_id' => $this->getId()
            ))->delete();
            db_query('UPDATE '.TICKET_TABLE.' SET topic_id=0 WHERE topic_id='.db_input($this->getId()));
        }

        return true;
    }

    function __toString() {
        return $this->getFullName();
    }

    /*** Static functions ***/

    static function create($vars=array()) {
        $topic = new static($vars);
        $topic->created = SqlFunction::NOW();
        return $topic;
    }

    static function __create($vars, &$errors) {
        $topic = self::create($vars);
        if (!isset($vars['dept_id']))
            $vars['dept_id'] = 0;
        $vars['id'] = $vars['topic_id'];
        $topic->update($vars, $errors);
        return $topic;
    }

    static function getHelpTopics($publicOnly=false, $disabled=false, $localize=true) {
        global $cfg;
        static $topics, $names = array();

        // If localization is specifically requested, then rebuild the list.
        if (!$names || $localize) {
            $objects = self::objects()->values_flat(
                'topic_id', 'topic_pid', 'ispublic', 'isactive', 'topic'
            )
            ->order_by('sort');

            // Fetch information for all topics, in declared sort order
            $topics = array();
            foreach ($objects as $T) {
                list($id, $pid, $pub, $act, $topic) = $T;
                $topics[$id] = array('pid'=>$pid, 'public'=>$pub,
                    'disabled'=>!$act, 'topic'=>$topic);
            }

            $localize_this = function($id, $default) use ($localize) {
                if (!$localize)
                    return $default;

                $tag = _H("topic.name.{$id}");
                $T = CustomDataTranslation::translate($tag);
                return $T != $tag ? $T : $default;
            };

            // Resolve parent names
            foreach ($topics as $id=>$info) {
                $name = $localize_this($id, $info['topic']);
                $loop = array($id=>true);
                $parent = false;
                while (($pid = $info['pid']) && ($info = $topics[$info['pid']])) {
                    $name = sprintf('%s / %s', $localize_this($pid, $info['topic']),
                        $name);
                    if ($parent && $parent['disabled'])
                        // Cascade disabled flag
                        $topics[$id]['disabled'] = true;
                    if (isset($loop[$info['pid']]))
                        break;
                    $loop[$info['pid']] = true;
                    $parent = $info;
                }
                $names[$id] = $name;
            }
        }

        // Apply requested filters
        $requested_names = array();
        foreach ($names as $id=>$n) {
            $info = $topics[$id];
            if ($publicOnly && !$info['public'])
                continue;
            if (!$disabled && $info['disabled'])
                continue;
            if ($disabled === self::DISPLAY_DISABLED && $info['disabled'])
                $n .= " - ".__("(disabled)");
            $requested_names[$id] = $n;
        }

        // If localization requested and the current locale is not the
        // primary, the list may need to be sorted. Caching is ok here,
        // because the locale is not going to be changed within a single
        // request.
        if ($localize && $cfg->getTopicSortMode() == self::SORT_ALPHA)
            return Internationalization::sortKeyedList($requested_names);

        return $requested_names;
    }

    static function getPublicHelpTopics() {
        return self::getHelpTopics(true);
    }

    static function getAllHelpTopics($localize=false) {
        return self::getHelpTopics(false, true, $localize);
    }

    static function getLocalNameById($id) {
        $topics = static::getHelpTopics(false, true);
        return $topics[$id];
    }

    static function getIdByName($name, $pid=0) {
        $list = self::objects()->filter(array(
            'topic'=>$name,
            'topic_pid'=>$pid,
        ))->values_flat('topic_id')->first();

        if ($list)
            return $list[0];
    }

    function update($vars, &$errors) {
        global $cfg;

        $vars['topic'] = Format::striptags(trim($vars['topic']));

        if (isset($this->topic_id) && $this->getId() != $vars['id'])
            $errors['err']=__('Internal error occurred');

        if (!$vars['topic'])
            $errors['topic']=__('Help topic name is required');
        elseif (strlen($vars['topic'])<5)
            $errors['topic']=__('Topic is too short. Five characters minimum');
        elseif (($tid=self::getIdByName($vars['topic'], $vars['topic_pid']))
                && (!isset($this->topic_id) || $tid!=$this->getId()))
            $errors['topic']=__('Topic already exists');

        if (!is_numeric($vars['dept_id']))
            $errors['dept_id']=__('Department selection is required');

        if ($vars['custom-numbers'] && !preg_match('`(?!<\\\)#`', $vars['number_format']))
            $errors['number_format'] =
                'Ticket number format requires at least one hash character (#)';

        if ($errors)
            return false;

        $this->topic = $vars['topic'];
        $this->topic_pid = $vars['topic_pid'] ?: 0;
        $this->dept_id = $vars['dept_id'];
        $this->priority_id = $vars['priority_id'] ?: 0;
        $this->status_id = $vars['status_id'] ?: 0;
        $this->sla_id = $vars['sla_id'] ?: 0;
        $this->page_id = $vars['page_id'] ?: 0;
        $this->isactive = !!$vars['isactive'];
        $this->ispublic = !!$vars['ispublic'];
        $this->sequence_id = $vars['custom-numbers'] ? $vars['sequence_id'] : 0;
        $this->number_format = $vars['custom-numbers'] ? $vars['number_format'] : '';
        $this->flags = $vars['custom-numbers'] ? self::FLAG_CUSTOM_NUMBERS : 0;
        $this->noautoresp = !!$vars['noautoresp'];
        $this->notes = Format::sanitize($vars['notes']);

        //Auto assign ID is overloaded...
        if ($vars['assign'] && $vars['assign'][0] == 's') {
            $this->team_id = 0;
            $this->staff_id = preg_replace("/[^0-9]/", "", $vars['assign']);
        }
        elseif ($vars['assign'] && $vars['assign'][0] == 't') {
            $this->staff_id = 0;
            $this->team_id = preg_replace("/[^0-9]/", "", $vars['assign']);
        }
        else {
            $this->staff_id = 0;
            $this->team_id = 0;
        }

        $rv = false;
        if ($this->__new__) {
            if (isset($this->topic_pid)
                    && ($parent = Topic::lookup($this->topic_pid))) {
                $this->sort = ($parent->sort ?: 0) + 1;
            }
            if (!($rv = $this->save())) {
                $errors['err']=sprintf(__('Unable to create %s.'), __('this help topic'))
               .' '.__('Internal error occurred');
            }
        }
        elseif (!($rv = $this->save())) {
            $errors['err']=sprintf(__('Unable to update %s.'), __('this help topic'))
            .' '.__('Internal error occurred');
        }
        if ($rv) {
            if (!$cfg || $cfg->getTopicSortMode() == 'a') {
                static::updateSortOrder();
            }
            $this->updateForms($vars, $errors);
        }
        return $rv;
    }

    function updateForms($vars, &$errors) {
        $find_disabled = function($form) use ($vars) {
            $fields = $vars['fields'];
            $disabled = array();
            foreach ($form->fields->values_flat('id') as $row) {
                list($id) = $row;
                if (false === ($idx = array_search($id, $fields))) {
                    $disabled[] = $id;
                }
            }
            return $disabled;
        };

        // Consider all the forms in the request
        $current = array();
        if (is_array($form_ids = $vars['forms'])) {
            $forms = TopicFormModel::objects()
                ->select_related('form')
                ->filter(array('topic_id' => $this->getId()));
            foreach ($forms as $F) {
                if (false !== ($idx = array_search($F->form_id, $form_ids))) {
                    $current[] = $F->form_id;
                    $F->sort = $idx + 1;
                    $F->extra = JsonDataEncoder::encode(
                        array('disable' => $find_disabled($F->form))
                    );
                    $F->save();
                    unset($form_ids[$idx]);
                }
                elseif ($F->form->get('type') != 'T') {
                    $F->delete();
                }
            }
            foreach ($form_ids as $sort=>$id) {
                if (!($form = DynamicForm::lookup($id))) {
                    continue;
                }
                elseif (in_array($id, $current)) {
                    // Don't add a form more than once
                    continue;
                }
                $tf = new TopicFormModel(array(
                    'topic_id' => $this->getId(),
                    'form_id' => $id,
                    'sort' => $sort + 1,
                    'extra' => JsonDataEncoder::encode(
                        array('disable' => $find_disabled($form))
                    )
                ));
                $tf->save();
            }
        }
        return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    static function updateSortOrder() {
        global $cfg;

        // Fetch (un)sorted names
        if (!($names = static::getHelpTopics(false, true, false)))
            return;

        $names = Internationalization::sortKeyedList($names);

        $update = array_keys($names);
        foreach ($update as $idx=>&$id) {
            $id = sprintf("(%s,%s)", db_input($id), db_input($idx+1));
        }
        if (!count($update))
            return;

        // Thanks, http://stackoverflow.com/a/3466
        $sql = sprintf('INSERT INTO `%s` (topic_id,`sort`) VALUES %s
            ON DUPLICATE KEY UPDATE `sort`=VALUES(`sort`)',
            TOPIC_TABLE, implode(',', $update));
        db_query($sql);
    }
}

// Add fields from the standard ticket form to the ticket filterable fields
Filter::addSupportedMatches(/* @trans */ 'Help Topic', array('topicId' => 'Topic ID'), 100);

class TopicFormModel extends VerySimpleModel {
    static $meta = array(
        'table' => TOPIC_FORM_TABLE,
        'pk' => array('id'),
        'ordering' => array('sort'),
        'joins' => array(
            'topic' => array(
                'constraint' => array('topic_id' => 'Topic.topic_id'),
            ),
            'form' => array(
                'constraint' => array('form_id' => 'DynamicForm.id'),
            ),
        ),
    );
}
