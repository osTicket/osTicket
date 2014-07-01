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

class Topic {
    var $id;

    var $ht;

    var $parent;
    var $page;
    var $form;

    const DISPLAY_DISABLED = 2;

    const FORM_USE_PARENT = 4294967295;

    function Topic($id) {
        $this->id=0;
        $this->load($id);
    }

    function load($id=0) {
        global $cfg;

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT ht.* '
            .' FROM '.TOPIC_TABLE.' ht '
            .' WHERE ht.topic_id='.db_input($id);

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht = db_fetch_array($res);
        $this->id = $this->ht['topic_id'];

        $this->page = $this->form = null;

        // Handle upgrade case where sort has not yet been defined
        if (!$this->ht['sort'] && $cfg->getTopicSortMode() == 'a') {
            static::updateSortOrder();
        }

        return true;
    }

    function reload() {
        return $this->load();
    }

    function asVar() {
        return $this->getName();
    }

    function getId() {
        return $this->id;
    }

    function getPid() {
        return $this->ht['topic_pid'];
    }

    function getParent() {
        if(!$this->parent && $this->getPid())
            $this->parent = self::lookup($this->getPid());

        return $this->parent;
    }

    function getName() {
        return $this->ht['topic'];
    }

    function getFullName() {
        return self::getTopicName($this->getId());
    }

    static function getTopicName($id) {
        $names = static::getHelpTopics(false, true);
        return $names[$id];
    }

    function getDeptId() {
        return $this->ht['dept_id'];
    }

    function getSLAId() {
        return $this->ht['sla_id'];
    }

    function getPriorityId() {
        return $this->ht['priority_id'];
    }

    function getStaffId() {
        return $this->ht['staff_id'];
    }

    function getTeamId() {
        return $this->ht['team_id'];
    }

    function getPageId() {
        return $this->ht['page_id'];
    }

    function getPage() {
        if(!$this->page && $this->getPageId())
            $this->page = Page::lookup($this->getPageId());

        return $this->page;
    }

    function getFormId() {
        return $this->ht['form_id'];
    }

    function getForm() {
        $id = $this->getFormId();

        if ($id == self::FORM_USE_PARENT && ($p = $this->getParent()))
            $this->form = $p->getForm();
        elseif ($id && !$this->form)
            $this->form = DynamicForm::lookup($id);

        return $this->form;
    }

    function autoRespond() {
        return (!$this->ht['noautoresp']);
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
        if (!$this->ht['isactive'])
            return false;

        if (!isset($chain[$this->getId()]) && ($p = $this->getParent())) {
            $chain[$this->getId()] = true;
            return $p->isActive($chain);
        }
        else {
            return $this->ht['isactive'];
        }
    }

    function isPublic() {
        return ($this->ht['ispublic']);
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function setSortOrder($i) {
        if ($i != $this->ht['sort']) {
            $sql = 'UPDATE '.TOPIC_TABLE.' SET `sort`='.db_input($i)
                .' WHERE `topic_id`='.db_input($this->getId());
            return (db_query($sql) && db_affected_rows() == 1);
        }
        // Noop
        return true;
    }

    function update($vars, &$errors) {

        if(!$this->save($this->getId(), $vars, $errors))
            return false;

        $this->reload();
        return true;
    }

    function delete() {
        global $cfg;

        if ($this->getId() == $cfg->getDefaultTopicId())
            return false;

        $sql='DELETE FROM '.TOPIC_TABLE.' WHERE topic_id='.db_input($this->getId()).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            db_query('UPDATE '.TOPIC_TABLE.' SET topic_pid=0 WHERE topic_pid='.db_input($this->getId()));
            db_query('UPDATE '.TICKET_TABLE.' SET topic_id=0 WHERE topic_id='.db_input($this->getId()));
            db_query('DELETE FROM '.FAQ_TOPIC_TABLE.' WHERE topic_id='.db_input($this->getId()));
        }

        return $num;
    }
    /*** Static functions ***/
    function create($vars, &$errors) {
        return self::save(0, $vars, $errors);
    }

    static function getHelpTopics($publicOnly=false, $disabled=false) {
        global $cfg;
        static $topics, $names;

        if (!$names) {
            $sql = 'SELECT topic_id, topic_pid, ispublic, isactive, topic FROM '.TOPIC_TABLE
                . ' ORDER BY `sort`';
            $res = db_query($sql);

            // Fetch information for all topics, in declared sort order
            $topics = array();
            while (list($id, $pid, $pub, $act, $topic) = db_fetch_row($res))
                $topics[$id] = array('pid'=>$pid, 'public'=>$pub,
                    'disabled'=>!$act, 'topic'=>$topic);

            // Resolve parent names
            foreach ($topics as $id=>$info) {
                $name = $info['topic'];
                $loop = array($id=>true);
                $parent = false;
                while ($info['pid'] && ($info = $topics[$info['pid']])) {
                    $name = sprintf('%s / %s', $info['topic'], $name);
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
                $n .= " &mdash; (disabled)";
            $requested_names[$id] = $n;
        }

        return $requested_names;
    }

    function getPublicHelpTopics() {
        return self::getHelpTopics(true);
    }

    function getAllHelpTopics() {
        return self::getHelpTopics(false, true);
    }

    function getIdByName($name, $pid=0) {

        $sql='SELECT topic_id FROM '.TOPIC_TABLE
            .' WHERE topic='.db_input($name)
            .' AND topic_pid='.db_input($pid);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id) = db_fetch_row($res);

        return $id;
    }

    static function lookup($id) {
        return ($id && is_numeric($id) && ($t= new Topic($id)) && $t->getId()==$id)?$t:null;
    }

    function save($id, $vars, &$errors) {
        global $cfg;

        $vars['topic']=Format::striptags(trim($vars['topic']));

        if($id && $id!=$vars['id'])
            $errors['err']='Internal error. Try again';

        if(!$vars['topic'])
            $errors['topic']='Help topic required';
        elseif(strlen($vars['topic'])<5)
            $errors['topic']='Topic is too short. 5 chars minimum';
        elseif(($tid=self::getIdByName($vars['topic'], $vars['topic_pid'])) && $tid!=$id)
            $errors['topic']='Topic already exists';

        if (!is_numeric($vars['dept_id']))
            $errors['dept_id']='You must select a department';

        if($errors) return false;

        foreach (array('sla_id','form_id','page_id','topic_pid') as $f)
            if (!isset($vars[$f]))
                $vars[$f] = 0;

        $sql=' updated=NOW() '
            .',topic='.db_input($vars['topic'])
            .',topic_pid='.db_input($vars['topic_pid'])
            .',dept_id='.db_input($vars['dept_id'])
            .',priority_id='.db_input($vars['priority_id'])
            .',sla_id='.db_input($vars['sla_id'])
            .',form_id='.db_input($vars['form_id'])
            .',page_id='.db_input($vars['page_id'])
            .',isactive='.db_input($vars['isactive'])
            .',ispublic='.db_input($vars['ispublic'])
            .',noautoresp='.db_input(isset($vars['noautoresp']) && $vars['noautoresp']?1:0)
            .',notes='.db_input(Format::sanitize($vars['notes']));

        //Auto assign ID is overloaded...
        if($vars['assign'] && $vars['assign'][0]=='s')
             $sql.=',team_id=0, staff_id='.db_input(preg_replace("/[^0-9]/", "", $vars['assign']));
        elseif($vars['assign'] && $vars['assign'][0]=='t')
            $sql.=',staff_id=0, team_id='.db_input(preg_replace("/[^0-9]/", "", $vars['assign']));
        else
            $sql.=',staff_id=0, team_id=0 '; //no auto-assignment!

        $rv = false;
        if ($id) {
            $sql='UPDATE '.TOPIC_TABLE.' SET '.$sql.' WHERE topic_id='.db_input($id);
            if (!($rv = db_query($sql)))
                $errors['err']='Unable to update topic. Internal error occurred';
        } else {
            if (isset($vars['topic_id']))
                $sql .= ', topic_id='.db_input($vars['topic_id']);
            // If in manual sort mode, place the new item directly below the
            // parent item
            if ($vars['topic_pid'] && $cfg && $cfg->getTopicSortMode() != 'a') {
                $sql .= ', `sort`='.db_input(
                    db_result(db_query('SELECT COALESCE(`sort`,0)+1 FROM '.TOPIC_TABLE
                        .' WHERE `topic_id`='.db_input($vars['topic_pid']))));
            }

            $sql='INSERT INTO '.TOPIC_TABLE.' SET '.$sql.',created=NOW()';
            if (db_query($sql) && ($id = db_insert_id()))
                $rv = $id;
            else
                $errors['err']='Unable to create the topic. Internal error';
        }
        if (!$cfg || $cfg->getTopicSortMode() == 'a') {
            static::updateSortOrder();
        }
        return $rv;
    }

    static function updateSortOrder() {
        // Fetch (un)sorted names
        if (!($names = static::getHelpTopics(false, true)))
            return;

        uasort($names, function($a, $b) { return strcmp($a, $b); });

        $update = array_keys($names);
        foreach ($update as $idx=>&$id) {
            $id = sprintf("(%s,%s)", db_input($id), db_input($idx+1));
        }
        // Thanks, http://stackoverflow.com/a/3466
        $sql = sprintf('INSERT INTO `%s` (topic_id,`sort`) VALUES %s
            ON DUPLICATE KEY UPDATE `sort`=VALUES(`sort`)',
            TOPIC_TABLE, implode(',', $update));
        db_query($sql);
    }
}

// Add fields from the standard ticket form to the ticket filterable fields
Filter::addSupportedMatches('Help Topic', array('topicId' => 'Topic ID'), 100);
