<?php
/*********************************************************************
    class.template.php

    Email Template

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Template {

    var $id;
    var $ht;
    
    function Template($id){
        $this->id=0;
        $this->load($id);
    }

    function load($id) {

        if(!$id && !($id=$this->getId()))
            return false;
           
        $sql='SELECT tpl.*,count(dept.tpl_id) as depts '
            .' FROM '.EMAIL_TEMPLATE_TABLE.' tpl '
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

        $sql='UPDATE '.EMAIL_TEMPLATE_TABLE.' SET updated=NOW(), isactive='.db_input($status?1:0)
            .' WHERE tpl_id='.db_input($this->getId());

        return (db_query($sql) && db_affected_rows());
    }

    function getMsgTemplate($name) {
        global $ost;

        //TODO: Don't preload - do ondemand fetch!
        $tpl=array();
        switch(strtolower($name)) {
            case 'ticket_autoresp':
                 $tpl=array('subj'=>$this->ht['ticket_autoresp_subj'],'body'=>$this->ht['ticket_autoresp_body']);
                 break;
            case 'msg_autoresp':
                 $tpl=array('subj'=>$this->ht['message_autoresp_subj'],'body'=>$this->ht['message_autoresp_body']);
                 break;
            case 'ticket_notice':
                 $tpl=array('subj'=>$this->ht['ticket_notice_subj'],'body'=>$this->ht['ticket_notice_body']);
                 break;
            case 'overlimit_notice':
                 $tpl=array('subj'=>$this->ht['ticket_overlimit_subj'],'body'=>$this->ht['ticket_overlimit_body']);
                 break;
            case 'ticket_reply':
                 $tpl=array('subj'=>$this->ht['ticket_reply_subj'],'body'=>$this->ht['ticket_reply_body']);
                 break;
            case 'ticket_alert':
                 $tpl=array('subj'=>$this->ht['ticket_alert_subj'],'body'=>$this->ht['ticket_alert_body']);
                 break;
            case 'msg_alert':
                 $tpl=array('subj'=>$this->ht['message_alert_subj'],'body'=>$this->ht['message_alert_body']);
                 break;
            case 'note_alert':
                 $tpl=array('subj'=>$this->ht['note_alert_subj'],'body'=>$this->ht['note_alert_body']);
                 break;
            case 'assigned_alert':
                 $tpl=array('subj'=>$this->ht['assigned_alert_subj'],'body'=>$this->ht['assigned_alert_body']);
                 break;
            case 'transfer_alert':
                 $tpl=array('subj'=>$this->ht['transfer_alert_subj'],'body'=>$this->ht['transfer_alert_body']);
                 break;
            case 'overdue_alert':
                 $tpl=array('subj'=>$this->ht['ticket_overdue_subj'],'body'=>$this->ht['ticket_overdue_body']);
                 break;
            default:
                 $ost->logWarning('Template Fetch Error', "Unable to fetch '$name' template - id #".$this->getId());
                 $tpl=array();
        }

        return $tpl;
    }

    
    function getNewTicketAlertMsgTemplate() {
        return $this->getMsgTemplate('ticket_alert');
    }

    function getNewMessageAlertMsgTemplate() {
        return $this->getMsgTemplate('msg_alert');
    }

    function getNewTicketNoticeMsgTemplate() {
        return $this->getMsgTemplate('ticket_notice');
    }

    function getNewMessageAutorepMsgTemplate() {
        return $this->getMsgTemplate('msg_autoresp');
    }

    function getAutoRespMsgTemplate() {
        return $this->getMsgTemplate('ticket_autoresp');
    }

    function getReplyMsgTemplate() {
        return $this->getMsgTemplate('ticket_reply');
    }

    function getOverlimitMsgTemplate() {
        return $this->getMsgTemplate('overlimit_notice');
    }

    function getNoteAlertMsgTemplate() {
        return $this->getMsgTemplate('note_alert');
    }

    function getTransferAlertMsgTemplate() {
        return $this->getMsgTemplate('transfer_alert');
    }

    function getAssignedAlertMsgTemplate() {
        return $this->getMsgTemplate('assigned_alert');
    }

    function getOverdueAlertMsgTemplate() {
        return $this->getMsgTemplate('overdue_alert');
    }

    function updateMsgTemplate($vars, &$errors) {

        if(!($tpls=Template::message_templates()) || !$tpls[$vars['tpl']])
            $errors['tpl']='Unknown or invalid template';

        if(!$vars['subj'])
            $errors['subj']='Message subject required';

        if(!$vars['body'])
            $errors['body']='Message body required';


        if($errors) return false;

        $sql='UPDATE '.EMAIL_TEMPLATE_TABLE.' SET updated=NOW() ';
        switch(strtolower($vars['tpl'])) {
            case 'ticket_autoresp':
                $sql.=',ticket_autoresp_subj='.db_input($vars['subj']).',ticket_autoresp_body='.db_input($vars['body']);
                break;
            case 'msg_autoresp':
                $sql.=',message_autoresp_subj='.db_input($vars['subj']).',message_autoresp_body='.db_input($vars['body']);
                break;
            case 'ticket_notice':
                $sql.=',ticket_notice_subj='.db_input($vars['subj']).',ticket_notice_body='.db_input($vars['body']);
                break;
            case 'overlimit_notice':
                $sql.=',ticket_overlimit_subj='.db_input($vars['subj']).',ticket_overlimit_body='.db_input($vars['body']);
                break;
            case 'ticket_reply':
                $sql.=',ticket_reply_subj='.db_input($vars['subj']).',ticket_reply_body='.db_input($vars['body']);
                break;
            case 'ticket_alert':
                $sql.=',ticket_alert_subj='.db_input($vars['subj']).',ticket_alert_body='.db_input($vars['body']);
                break;
            case 'msg_alert':
                $sql.=',message_alert_subj='.db_input($vars['subj']).',message_alert_body='.db_input($vars['body']);
                break;
            case 'note_alert':
                $sql.=',note_alert_subj='.db_input($vars['subj']).',note_alert_body='.db_input($vars['body']);
                break;
            case 'assigned_alert':
                $sql.=',assigned_alert_subj='.db_input($vars['subj']).',assigned_alert_body='.db_input($vars['body']);
                break;
            case 'transfer_alert':
                $sql.=',transfer_alert_subj='.db_input($vars['subj']).',transfer_alert_body='.db_input($vars['body']);
                break;
            case 'overdue_alert':
                $sql.=',ticket_overdue_subj='.db_input($vars['subj']).',ticket_overdue_body='.db_input($vars['body']);
                break;
            default:
                $errors['tpl']='Unknown or invalid template';
                return false;
        }

        $sql.=' WHERE  tpl_id='.db_input($this->getId());

        return (db_query($sql));

    }

    function update($vars,&$errors) {

        if(!$vars['isactive'] && $this->isInUse())
            $errors['isactive']='Template in-use can not be disabled!';

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

        $sql='DELETE FROM '.EMAIL_TEMPLATE_TABLE.' WHERE tpl_id='.db_input($this->getId()).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            //isInuse check is enough - but it doesn't hurt make sure deleted tpl is not in-use. 
            db_query('UPDATE '.DEPT_TABLE.' SET tpl_id=0 WHERE tpl_id='.db_input($this->getId()));
        }

        return $num;
    }

    /*** Static functions ***/
    function message_templates(){

        //TODO: Make it database driven and dynamic
        $messages=array('ticket_autoresp'=>array('name'=>'New Ticket Autoresponse',
                                                 'desc'=>'Autoresponse sent to user, if enabled, on new ticket.'),
                        'msg_autoresp'=>array('name'=>'New Message Auto-response',
                                              'desc'=>'Confirmation sent to user when a new message is appended to an existing ticket.'),
                        'ticket_notice'=>array('name'=>'New Ticket Notice',
                                               'desc'=>'Notice sent to user, if enabled, on new ticket created by staff on their behalf (e.g phone calls).'),
                        'overlimit_notice'=>array('name'=>'Over Limit Notice',
                                                  'desc'=>'A one time notice sent, if enabled, when user has reached the maximum allowed open tickets.'),
                        'ticket_reply'=>array('name'=>'Response/Reply Template',
                                              'desc'=>'Template used on ticket response/reply'),
                        'ticket_alert'=>array('name'=>'New Ticket Alert',
                                              'desc'=>'Alert sent to staff, if enabled, on new ticket.'),
                        'msg_alert'=>array('name'=>'New Message Alert',
                                           'desc'=>'Alert sent to staff, if enabled, when user replies to an existing ticket.'),
                        'note_alert'=>array('name'=>'Internal Note Alert',
                                            'desc'=>'Alert sent to selected staff, if enabled, on new internal note.'),
                        'assigned_alert'=>array('name'=>'Ticket Assignment Alert',
                                                'desc'=>'Alert sent to staff on ticket assignment.'),
                        'transfer_alert'=>array('name'=>'Ticket Transfer Alert',
                                                'desc'=>'Alert sent to staff on ticket transfer.'),
                        'overdue_alert'=>array('name'=>'Overdue Ticket Alert',
                                               'desc'=>'Alert sent to staff on stale or overdue tickets.')
                        );
        return $messages;
    }


    function create($vars,&$errors) { 

        return Template::save(0,$vars,$errors);
    }

    function getIdByName($name){
        $sql='SELECT tpl_id FROM '.EMAIL_TEMPLATE_TABLE.' WHERE name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function lookup($id){
        return ($id && is_numeric($id) && ($t= new Template($id)) && $t->getId()==$id)?$t:null;
    }

    function save($id, $vars, &$errors) {
        global $ost;

        $tpl=null;
        $vars['name']=Format::striptags(trim($vars['name']));

        if($id && $id!=$vars['id'])
            $errors['err']='Internal error. Try again';

        if(!$vars['name'])
            $errors['name']='Name required';
        elseif(($tid=Template::getIdByName($vars['name'])) && $tid!=$id)
            $errors['name']='Template name already exists';

        if(!$id && (!$vars['tpl_id'] || !($tpl=Template::lookup($vars['tpl_id']))))
            $errors['tpl_id']='Selection required';
           
        if($errors) return false;

        $sql=' updated=NOW() '
            .' ,name='.db_input($vars['name'])
            .' ,isactive='.db_input($vars['isactive'])
            .' ,notes='.db_input($vars['notes']);
        
        if($id) {
            $sql='UPDATE '.EMAIL_TEMPLATE_TABLE.' SET '.$sql.' WHERE tpl_id='.db_input($id);
            if(db_query($sql))
                return true;

            $errors['err']='Unable to update the template. Internal error occurred';
        
        } elseif($tpl && ($info=$tpl->getInfo())) {

            $sql='INSERT INTO '.EMAIL_TEMPLATE_TABLE.' SET '.$sql
                .' ,created=NOW() '
                .' ,cfg_id='.db_input($ost->getConfigId())
                .' ,ticket_autoresp_subj='.db_input($info['ticket_autoresp_subj'])
                .' ,ticket_autoresp_body='.db_input($info['ticket_autoresp_body'])
                .' ,ticket_notice_subj='.db_input($info['ticket_notice_subj'])
                .' ,ticket_notice_body='.db_input($info['ticket_notice_body'])
                .' ,ticket_alert_subj='.db_input($info['ticket_alert_subj'])
                .' ,ticket_alert_body='.db_input($info['ticket_alert_body'])
                .' ,message_autoresp_subj='.db_input($info['message_autoresp_subj'])
                .' ,message_autoresp_body='.db_input($info['message_autoresp_body'])
                .' ,message_alert_subj='.db_input($info['message_alert_subj'])
                .' ,message_alert_body='.db_input($info['message_alert_body'])
                .' ,note_alert_subj='.db_input($info['note_alert_subj'])
                .' ,note_alert_body='.db_input($info['note_alert_body'])
                .' ,transfer_alert_subj='.db_input($info['transfer_alert_subj'])
                .' ,transfer_alert_body='.db_input($info['transfer_alert_body'])
                .' ,assigned_alert_subj='.db_input($info['assigned_alert_subj'])
                .' ,assigned_alert_body='.db_input($info['assigned_alert_body'])
                .' ,ticket_overdue_subj='.db_input($info['ticket_overdue_subj'])
                .' ,ticket_overdue_body='.db_input($info['ticket_overdue_body'])
                .' ,ticket_overlimit_subj='.db_input($info['ticket_overlimit_subj'])
                .' ,ticket_overlimit_body='.db_input($info['ticket_overlimit_body'])
                .' ,ticket_reply_subj='.db_input($info['ticket_reply_subj'])
                .' ,ticket_reply_body='.db_input($info['ticket_reply_body']);

            if(db_query($sql) && ($id=db_insert_id()))
                return $id;
            
            $errors['err']='Unable to create template. Internal error';
        }
        
        return false;
    }
}
?>
