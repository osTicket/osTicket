<?php
/*********************************************************************
    class.template.php

    Email Template

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR.'class.i18n.php';
require_once INCLUDE_DIR.'class.yaml.php';

class EmailTemplateGroup {

    var $id;
    var $ht;
    var $_templates;
    static $all_groups = array(
        'sys' => /* @trans */ 'System Management Templates',
        'a.ticket.user' => /* @trans */ 'Ticket End-User Email Templates',
        'b.ticket.staff' => /* @trans */ 'Ticket Agent Email Templates',
        'c.task' => /* @trans */ 'Task Email Templates',
    );
    static $all_names=array(
        'ticket.autoresp'=>array(
            'group'=>'a.ticket.user',
            'name'=>/* @trans */ 'New Ticket Auto-response',
            'desc'=>/* @trans */ 'Autoresponse sent to user, if enabled, on new ticket.',
            'context' => array(
                'ticket', 'signature', 'message', 'recipient'
            ),
        ),
        'ticket.autoreply'=>array(
            'group'=>'a.ticket.user',
            'name'=>/* @trans */ 'New Ticket Auto-reply',
            'desc'=>/* @trans */ 'Canned Auto-reply sent to user on new ticket, based on filter matches. Overwrites "normal" auto-response.',
            'context' => array(
                'ticket', 'signature', 'response', 'recipient',
            ),
        ),
        'message.autoresp'=>array(
            'group'=>'a.ticket.user',
            'name'=>/* @trans */ 'New Message Auto-response',
            'desc'=>/* @trans */ 'Confirmation sent to user when a new message is appended to an existing ticket.',
            'context' => array(
                'ticket', 'signature', 'recipient',
            ),
        ),
        'ticket.notice'=>array(
            'group'=>'a.ticket.user',
            'name'=>/* @trans */ 'New Ticket Notice',
            'desc'=>/* @trans */ 'Notice sent to user, if enabled, on new ticket created by an agent on their behalf (e.g phone calls).',
            'context' => array(
                'ticket', 'signature', 'recipient', 'staff', 'message',
            ),
        ),
        'ticket.overlimit'=>array(
            'group'=>'a.ticket.user',
            'name'=>/* @trans */ 'Overlimit Notice',
            'desc'=>/* @trans */ 'A one-time notice sent, if enabled, when user has reached the maximum allowed open tickets.',
            'context' => array(
                'ticket', 'signature',
            ),
        ),
        'ticket.reply'=>array(
            'group'=>'a.ticket.user',
            'name'=>/* @trans */ 'Response/Reply Template',
            'desc'=>/* @trans */ 'Template used on ticket response/reply',
            'context' => array(
                'ticket', 'signature', 'response', 'staff', 'poster', 'recipient',
            ),
        ),
        'ticket.activity.notice'=>array(
            'group'=>'a.ticket.user',
            'name'=>/* @trans */ 'New Activity Notice',
            'desc'=>/* @trans */ 'Template used to notify collaborators on ticket activity (e.g CC on reply)',
            'context' => array(
                'ticket', 'signature', 'message', 'poster', 'recipient',
            ),
        ),
        'ticket.alert'=>array(
            'group'=>'b.ticket.staff',
            'name'=>/* @trans */ 'New Ticket Alert',
            'desc'=>/* @trans */ 'Alert sent to agents, if enabled, on new ticket.',
            'context' => array(
                'ticket', 'recipient', 'message',
            ),
        ),
        'message.alert'=>array(
            'group'=>'b.ticket.staff',
            'name'=>/* @trans */ 'New Message Alert',
            'desc'=>/* @trans */ 'Alert sent to agents, if enabled, when user replies to an existing ticket.',
            'context' => array(
                'ticket', 'recipient', 'message', 'poster',
            ),
        ),
        'note.alert'=>array(
            'group'=>'b.ticket.staff',
            'name'=>/* @trans */ 'Internal Activity Alert',
            'desc'=>/* @trans */ 'Alert sent out to Agents when internal activity such as an internal note or an agent reply is appended to a ticket.',
            'context' => array(
                'ticket', 'recipient', 'note', 'comments', 'activity',
            ),
        ),
        'assigned.alert'=>array(
            'group'=>'b.ticket.staff',
            'name'=>/* @trans */ 'Ticket Assignment Alert',
            'desc'=>/* @trans */ 'Alert sent to agents on ticket assignment.',
            'context' => array(
                'ticket', 'recipient', 'comments', 'assignee', 'assigner',
            ),
        ),
        'transfer.alert'=>array(
            'group'=>'b.ticket.staff',
            'name'=>/* @trans */ 'Ticket Transfer Alert',
            'desc'=>/* @trans */ 'Alert sent to agents on ticket transfer.',
            'context' => array(
                'ticket', 'recipient', 'comments', 'staff',
            ),
        ),
        'ticket.overdue'=>array(
            'group'=>'b.ticket.staff',
            'name'=>/* @trans */ 'Overdue Ticket Alert',
            'desc'=>/* @trans */ 'Alert sent to agents on stale or overdue tickets.',
            'context' => array(
                'ticket', 'recipient', 'comments',
            ),
        ),
        'task.alert' => array(
            'group'=>'c.task',
            'name'=>/* @trans */ 'New Task Alert',
            'desc'=>/* @trans */ 'Alert sent to agents, if enabled, on new task.',
            'context' => array(
                'task', 'recipient', 'message',
            ),
        ),
        'task.activity.notice' => array(
            'group'=>'c.task',
            'name'=>/* @trans */ 'New Activity Notice',
            'desc'=>/* @trans */ 'Template used to notify collaborators on task activity.',
            'context' => array(
                'task', 'signature', 'message', 'poster', 'recipient',
            ),
        ),
        'task.activity.alert'=>array(
            'group'=>'c.task',
            'name'=>/* @trans */ 'New Activity Alert',
            'desc'=>/* @trans */ 'Alert sent to selected agents, if enabled, on new activity.',
            'context' => array(
                'task', 'recipient', 'note', 'comments', 'activity',
            ),
        ),
        'task.assignment.alert' => array(
            'group'=>'c.task',
            'name'=>/* @trans */ 'Task Assignment Alert',
            'desc'=>/* @trans */ 'Alert sent to agents on task assignment.',
            'context' => array(
                'task', 'recipient', 'comments', 'assignee', 'assigner',
            ),
        ),
        'task.transfer.alert'=>array(
            'group'=>'c.task',
            'name'=>/* @trans */ 'Task Transfer Alert',
            'desc'=>/* @trans */ 'Alert sent to agents on task transfer.',
            'context' => array(
                'task', 'recipient', 'note', 'comments', 'activity',
            ),
        ),
        'task.overdue.alert'=>array(
            'group'=>'c.task',
            'name'=>/* @trans */ 'Overdue Task Alert',
            'desc'=>/* @trans */ 'Alert sent to agents on stale or overdue task.',
            'context' => array(
                'task', 'recipient', 'comments',
            ),
        ),
    );

    function __construct($id=0){
        $this->id=0;
        $this->load($id);
    }

    function load($id) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT tpl.*,count(dept.tpl_id) as depts '
            .' FROM '.EMAIL_TEMPLATE_GRP_TABLE.' tpl '
            .' LEFT JOIN '.DEPT_TABLE.' dept USING(tpl_id) '
            .' WHERE tpl.tpl_id='.db_input($id)
            .' GROUP BY tpl.tpl_id';

        if(!($res=db_query($sql))|| !db_num_rows($res))
            return false;


        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['tpl_id'];

        return true;
    }

    function reload() {
        return $this->load($this->getId());
    }

    function getId(){
        return $this->id;
    }

    function getName(){
        return $this->ht['name'];
    }

    function getNotes(){
        return $this->ht['notes'];
    }

    function isEnabled() {
         return ($this->ht['isactive']);
    }

    function isActive(){
        return $this->isEnabled();
    }

    function getLanguage() {
        return $this->ht['lang'];
    }

    function isInUse(){
        global $cfg;

        return ($this->ht['depts'] || ($cfg && $this->getId()==$cfg->getDefaultTemplateId()));
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function setStatus($status){

        $sql='UPDATE '.EMAIL_TEMPLATE_GRP_TABLE.' SET updated=NOW(), isactive='.db_input($status?1:0)
            .' WHERE tpl_id='.db_input($this->getId());

        return (db_query($sql) && db_affected_rows());
    }

    static function getTemplateDescription($name) {
        return static::$all_names[$name];
    }

    function getMsgTemplate($name) {
        global $ost;

        if ($tpl=EmailTemplate::lookupByName($this->getId(), $name, $this))
            return $tpl;

        if ($tpl=EmailTemplate::fromInitialData($name, $this))
            return $tpl;

        $ost->logWarning(_S('Template Fetch Error'),
            sprintf(_S('Unable to fetch "%1$s" template - id #%d'), $name, $this->getId()));
        return false;
    }

    function getTemplates() {
        if (!$this->_tempates) {
            $this->_templates = array();
            $sql = 'SELECT id, code_name FROM '.EMAIL_TEMPLATE_TABLE
                .' WHERE tpl_id='.db_input($this->getId())
                .' ORDER BY code_name';
            $res = db_query($sql);
            while (list($id, $cn)=db_fetch_row($res))
                $this->_templates[$cn] = EmailTemplate::lookup($id, $this);
        }
        return $this->_templates;
    }

    function getUndefinedTemplateNames() {
        $list = static::$all_names;
        foreach ($this->getTemplates() as $cn=>$tpl)
            unset($list[$cn]);
        return $list;
    }


    function getNewTicketAlertMsgTemplate() {
        return $this->getMsgTemplate('ticket.alert');
    }

    function getNewMessageAlertMsgTemplate() {
        return $this->getMsgTemplate('message.alert');
    }

    function getNewTicketNoticeMsgTemplate() {
        return $this->getMsgTemplate('ticket.notice');
    }

    function getNewMessageAutorepMsgTemplate() {
        return $this->getMsgTemplate('message.autoresp');
    }

    function getAutoRespMsgTemplate() {
        return $this->getMsgTemplate('ticket.autoresp');
    }

    function getAutoReplyMsgTemplate() {
        return $this->getMsgTemplate('ticket.autoreply');
    }

    function getReplyMsgTemplate() {
        return $this->getMsgTemplate('ticket.reply');
    }

    function  getActivityNoticeMsgTemplate() {
        return $this->getMsgTemplate('ticket.activity.notice');
    }

    function getOverlimitMsgTemplate() {
        return $this->getMsgTemplate('ticket.overlimit');
    }

    function getNoteAlertMsgTemplate() {
        return $this->getMsgTemplate('note.alert');
    }

    function getTransferAlertMsgTemplate() {
        return $this->getMsgTemplate('transfer.alert');
    }

    function getAssignedAlertMsgTemplate() {
        return $this->getMsgTemplate('assigned.alert');
    }

    function getOverdueAlertMsgTemplate() {
        return $this->getMsgTemplate('ticket.overdue');
    }

    /* Tasks templates */
    function getNewTaskAlertMsgTemplate() {
        return $this->getMsgTemplate('task.alert');
    }

    function  getTaskActivityAlertMsgTemplate() {
        return $this->getMsgTemplate('task.activity.alert');
    }

    function  getTaskActivityNoticeMsgTemplate() {
        return $this->getMsgTemplate('task.activity.notice');
    }

    function getTaskTransferAlertMsgTemplate() {
        return $this->getMsgTemplate('task.transfer.alert');
    }

    function getTaskAssignmentAlertMsgTemplate() {
        return $this->getMsgTemplate('task.assignment.alert');
    }

    function getTaskOverdueAlertMsgTemplate() {
        return $this->getMsgTemplate('task.overdue.alert');
    }

    function update($vars,&$errors) {
        if(!$vars['isactive'] && $this->isInUse())
            $errors['isactive']=__('In-use template set cannot be disabled!');

        if(!$this->save($this->getId(),$vars,$errors))
            return false;

        $this->reload();

        return true;
    }

    function enable(){
        return ($this->setStatus(1));
    }

    function disable(){
        return (!$this->isInUse() && $this->setStatus(0));
    }

    function delete(){
        global $cfg;

        if($this->isInUse() || $cfg->getDefaultTemplateId()==$this->getId())
            return 0;

        $sql='DELETE FROM '.EMAIL_TEMPLATE_GRP_TABLE
            .' WHERE tpl_id='.db_input($this->getId()).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            //isInuse check is enough - but it doesn't hurt make sure deleted tpl is not in-use.
            db_query('UPDATE '.DEPT_TABLE.' SET tpl_id=0 WHERE tpl_id='.db_input($this->getId()));
            // Drop attachments (images)
            db_query('DELETE a.* FROM '.ATTACHMENT_TABLE.' a
                JOIN '.EMAIL_TEMPLATE_TABLE.' t  ON (a.object_id=t.id AND a.type=\'T\')
                WHERE t.tpl_id='.db_input($this->getId()));
            db_query('DELETE FROM '.EMAIL_TEMPLATE_TABLE
                .' WHERE tpl_id='.db_input($this->getId()));
        }

        $type = array('type' => 'deleted');
        Signal::send('object.deleted', $this, $type);

        return $num;
    }

    static function create($vars,&$errors) {
        $group = new static();
        return $group->save(0,$vars,$errors);
    }

    static function add($vars, &$errors) {
        return self::lookup(self::create($vars, $errors));
    }

    function getIdByName($name){
        $sql='SELECT tpl_id FROM '.EMAIL_TEMPLATE_GRP_TABLE.' WHERE name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    static function lookup($id){
        return ($id && is_numeric($id) && ($t= new EmailTemplateGroup($id)) && $t->getId()==$id)?$t:null;
    }

    function save($id, $vars, &$errors) {
        global $ost;

        $tpl=null;
        $vars['name']=Format::striptags(trim($vars['name']));

        if($id && $id!=$vars['tpl_id'])
            $errors['err']=__('Internal error occurred');

        if(!$vars['name'])
            $errors['name']=__('Name is required');
        elseif(($tid=EmailTemplateGroup::getIdByName($vars['name'])) && $tid!=$id)
            $errors['name']=__('Template name already exists');

        if(!$id && ($vars['tpl_id'] && !($tpl=EmailTemplateGroup::lookup($vars['tpl_id']))))
            $errors['tpl_id']=__('Invalid template set specified');

        if($errors) return false;

        foreach ($vars as $key => $value) {
            if ($id && isset($this->ht[$key]) && ($this->ht[$key] != $value)) {
                $type = array('type' => 'edited', 'key' => $key);
                Signal::send('object.edited', $this, $type);
            }
        }

        $sql=' updated=NOW() '
            .' ,name='.db_input($vars['name'])
            .' ,isactive='.db_input($vars['isactive'])
            .' ,notes='.db_input(Format::sanitize($vars['notes']));

        if ($vars['lang_id'])
            // TODO: Validation of lang_id
            $sql .= ',lang='.db_input($vars['lang_id']);

        if($id) {
            $sql='UPDATE '.EMAIL_TEMPLATE_GRP_TABLE.' SET '.$sql.' WHERE tpl_id='.db_input($id);
            if(db_query($sql))
                return true;

            $errors['err']=sprintf(__('Unable to update %s.'), __('this template set'))
               .' '.__('Internal error occurred');

        } else {

            if (isset($vars['id']))
                $sql .= ', tpl_id='.db_input($vars['id']);
            $sql='INSERT INTO '.EMAIL_TEMPLATE_GRP_TABLE
                .' SET created=NOW(), '.$sql;
            if(!db_query($sql) || !($new_id=db_insert_id())) {
                $errors['err']=sprintf(__('Unable to create %s.'), __('this template set'))
                   .' '.__('Internal error occurred');
                return false;
            }

            if ($tpl && ($info=$tpl->getInfo())) {
                $sql='INSERT INTO '.EMAIL_TEMPLATE_TABLE.'
                    (created, updated, tpl_id, code_name, subject, body)
                    SELECT NOW() as created, NOW() as updated, '.db_input($new_id)
                    .' as tpl_id, code_name, subject, body
                    FROM '.EMAIL_TEMPLATE_TABLE
                    .' WHERE tpl_id='.db_input($tpl->getId());

                if(!db_query($sql) || !db_insert_id())
                    return false;
            }
            return $new_id;
        }

        return false;
    }
}

class EmailTemplate {

    var $id;
    var $ht;
    var $_group;

    function __construct($id=0, $group=null){
        $this->id=0;
        if ($id) $this->load($id);
        if ($group) $this->_group = $group;
    }

    function load($id) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT * FROM '.EMAIL_TEMPLATE_TABLE
            .' WHERE id='.db_input($id);

        if(!($res=db_query($sql))|| !db_num_rows($res))
            return false;

        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['id'];
        $this->attachments = GenericAttachments::forIdAndType($this->id, 'T');

        return true;
    }

    function reload() {
        return $this->load($this->getId());
    }

    function getId(){
        return $this->id;
    }

    function asArray() {
        return array(
            'id' => $this->getId(),
            'subj' => $this->getSubject(),
            'body' => $this->getBody(),
        );
    }

    function getSubject() {
        return $this->ht['subject'];
    }

    function getBody() {
        return $this->ht['body'];
    }

    function getBodyWithImages() {
        return Format::viewableImages($this->getBody());
    }
    function getCodeName() {
        return $this->ht['code_name'];
    }

    function getLastUpdated() {
        return $this->ht['updated'];
    }

    function getTplId() {
        return $this->ht['tpl_id'];
    }

    function getGroup() {
        if (!isset($this->_group))
            $this->_group = EmailTemplateGroup::lookup($this->getTplId());
        return $this->_group;

    }

    function getDescription() {
        return $this->getGroup()->getTemplateDescription($this->ht['code_name']);
    }

    function getInvalidVariableUsage() {
        $context = VariableReplacer::getContextForRoot($this->ht['code_name']);
        $invalid = array();
        foreach (array($this->getSubject(), $this->getBody()) as $B) {
            $variables = array();
            if (!preg_match_all('`%\{([^}]*)\}`', $B, $variables, PREG_SET_ORDER))
                continue;
            foreach ($variables as $V) {
                if (!isset($context[$V[1]])) {
                    $invalid[] = $V[0];
                }
            }
        }
        return $invalid;
    }

    function update($vars, &$errors) {

        if(!$this->save($this->getId(),$vars,$errors))
            return false;

        $this->reload();

        // Inline images (attached to the draft)
        $keepers = Draft::getAttachmentIds($this->getBody());
        // Just keep the IDs only
        $keepers = array_map(function($i) { return $i['id']; }, $keepers);
        $this->attachments->keepOnlyFileIds($keepers, true);

        return true;
    }

    function save($id, $vars, &$errors) {
        if(!$vars['subject'])
            $errors['subject'] = __('Message subject is required');

        if(!$vars['body'])
            $errors['body'] = __('Message body is required');

        if (!$id) {
            if (!$vars['tpl_id'])
                $errors['tpl_id'] = __('Template set is required');
            if (!$vars['code_name'])
                $errors['code_name'] = __('Code name is required');
        }

        if ($errors)
            return false;

        $vars['body'] = Format::sanitize($vars['body'], false);

        if ($id) {
            foreach ($vars as $key => $value) {
                if (isset($this->ht[$key]) && ($this->ht[$key] != $value)) {
                    $type = array('type' => 'edited', 'key' => $this->getCodeName());
                    Signal::send('object.edited', $this->getGroup(), $type);
                }
            }
            $sql='UPDATE '.EMAIL_TEMPLATE_TABLE.' SET updated=NOW() '
                .', subject='.db_input($vars['subject'])
                .', body='.db_input($vars['body'])
                .' WHERE id='.db_input($this->getId());

            return (db_query($sql));
        } else {
            $sql='INSERT INTO '.EMAIL_TEMPLATE_TABLE.' SET created=NOW(),
                updated=NOW(), tpl_id='.db_input($vars['tpl_id'])
                .', code_name='.db_input($vars['code_name'])
                .', subject='.db_input($vars['subject'])
                .', body='.db_input($vars['body']);
            if (db_query($sql) && ($id=db_insert_id())) {
                $template = EmailTemplate::lookup($id);
                foreach ($vars as $key => $value) {
                    if (isset($template->ht[$key]) && ($template->ht[$key] != $value)) {
                        $type = array('type' => 'edited', 'key' => $template->getCodeName());
                        Signal::send('object.edited', $template->getGroup(), $type);
                    }
                }
                return $id;
            }
        }
        return null;
    }

    static function create($vars, &$errors) {
        $template = new static();
        return $template->save(0, $vars, $errors);
    }

    static function add($vars, &$errors) {
        $inst = self::lookup(self::create($vars, $errors));

        // Inline images (attached to the draft)
        if ($inst)
            $inst->attachments->upload(Draft::getAttachmentIds($inst->getBody()), true);

        return $inst;
    }

    static function lookupByName($tpl_id, $name, $group=null) {
        $sql = 'SELECT id FROM '.EMAIL_TEMPLATE_TABLE
            .' WHERE tpl_id='.db_input($tpl_id)
            .' AND code_name='.db_input($name);
        if (($res=db_query($sql)) && ($id=db_result($res)))
            return self::lookup($id, $group);

        return false;
    }

    static function lookup($id, $group=null) {
        return ($id && is_numeric($id) && ($t= new EmailTemplate($id, $group)) && $t->getId()==$id)?$t:null;
    }

    /**
     * Load the template from the initial_data directory. The format of the
     * file should be free flow text. The first line is the subject and the
     * rest of the file is the body.
     */
    static function fromInitialData($name, $group=null) {
        $templ = new EmailTemplate(0, $group);
        $lang = ($group) ? $group->getLanguage() : 'en_US';
        $i18n = new Internationalization($lang);
        if ((!($tpl = $i18n->getTemplate("templates/email/$name.yaml")))
                || (!($info = $tpl->getData())))
            return false;
        if (isset($info['subject']) && isset($info['body'])) {
            $templ->ht = $info;
            return $templ;
        }
        raise_error("$lang/templates/$name.yaml: "
            . _S('Email templates must define both "subject" and "body" parts of the template'),
            'InitialDataError');
        return false;
    }
}
?>
