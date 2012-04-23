<?php
/*********************************************************************
    class.config.php

    osTicket config info manager. 

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once(INCLUDE_DIR.'class.email.php');

class Config {
    
    var $id=0;
    var $mysqltzoffset=0;
    var $config=array();

    var $defaultDept;   //Default Department    
    var $defaultSLA;   //Default SLA
    var $defaultEmail;  //Default Email 
    var $alertEmail;  //Alert Email
    var $defaultSMTPEmail; //Default  SMTP Email

    function Config($id) { 
        $this->load($id);
    }

    function load($id=0) {
        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT * FROM '.CONFIG_TABLE
            .' WHERE id='.db_input($id);
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

            
        $this->config=db_fetch_array($res);
        $this->id=$this->config['id'];

        return true;
    }

    //Initialize some default values.
    function init() {
        list($mysqltz)=db_fetch_row(db_query('SELECT @@session.time_zone '));
        $this->setMysqlTZ($mysqltz);
    }
    
    function reload() {
        if(!$this->load($this->getId()))
            return false;

        $this->init();

        return true;
    }

    function isHelpDeskOffline() {
        return !$this->isSystemOnline();
    }

    function isHelpDeskOnline() {
        return $this->isSystemOnline();
    }

    function isSystemOnline() {
        return ($this->config['isonline'] && !$this->isUpgradePending());
    }

    function isUpgradePending() {
        return (defined('SCHEMA_SIGNATURE') && strcasecmp($this->getSchemaSignature(), SCHEMA_SIGNATURE));
    }

    function isKnowledgebaseEnabled() {
        require_once(INCLUDE_DIR.'class.faq.php');
        return ($this->config['enable_kb'] && FAQ::countPublishedFAQs());
    }

    function getVersion() {
        return THIS_VERSION;
    }

    function getSchemaSignature() {

        if($this->config['schema_signature'])
            return $this->config['schema_signature'];
        elseif($this->config['ostversion']) //old version 1.6 st.
            return md5(strtoupper($this->config['ostversion']));

        return null;
    }

    function setMysqlTZ($tz) {
        //TODO: Combine the 2 replace regex
        if($tz=='SYSTEM')
            $this->mysqltzoffset=preg_replace('/([+-]\d{2})(\d{2})/','\1',date('O'));
        else
            $this->mysqltzoffset=preg_replace('/([+-]\d{2})(:)(\d{2})/','\1',$tz);
    }
    
    function getMysqlTZoffset() {
        return $this->mysqltzoffset;
    }

    /* Date & Time Formats */
    function observeDaylightSaving() {
        return ($this->config['enable_daylight_saving']);
    }
    function getTimeFormat() {
        return $this->config['time_format'];
    }
    function getDateFormat() {
        return $this->config['date_format'];
    }

    function getDateTimeFormat() {
        return $this->config['datetime_format'];
    }

    function getDayDateTimeFormat() {
        return $this->config['daydatetime_format'];
    }

    function getId() {
        return $this->id;
    }
   
    function getTitle() {
        return $this->config['helpdesk_title'];
    }
    
    function getUrl() {
        return $this->config['helpdesk_url'];        
    }
    
    function getBaseUrl() { //Same as above with no trailing slash.
        return rtrim($this->getUrl(),'/');
    }

    function getConfig() {
        return $this->config;
    }

    function getTZOffset() {
        return $this->config['timezone_offset'];
    }

    function getPageSize() {
        return $this->config['max_page_size'];
    }

    function getGracePeriod() {
        return $this->config['overdue_grace_period'];
    }

    function getPasswdResetPeriod() {
        return $this->config['passwd_reset_period'];
    }

    function showRelatedTickets() {
        return $this->config['show_related_tickets'];
    }
        
    function getClientTimeout() {
        return $this->getClientSessionTimeout();
    }
 
    function getClientSessionTimeout() {
        return $this->config['client_session_timeout']*60;
    }

    function getClientLoginTimeout() {
        return $this->config['client_login_timeout']*60;
    }

    function getClientMaxLogins() {
        return $this->config['client_max_logins'];
    }

    function getStaffTimeout() {
        return $this->getStaffSessionTimeout();
    }
        
    function getStaffSessionTimeout() {                
        return $this->config['staff_session_timeout']*60;
    }

    function getStaffLoginTimeout() {
        return $this->config['staff_login_timeout']*60;
    }

    function getStaffMaxLogins() {
        return $this->config['staff_max_logins'];
    }

    function getLockTime() {
        return $this->config['autolock_minutes'];
    }

    function getDefaultDeptId() {
        return $this->config['default_dept_id'];
    }

    function getDefaultDept() {

        if(!$this->defaultDept && $this->getDefaultDeptId())
            $this->defaultDept=Dept::lookup($this->getDefaultDeptId());

        return $this->defaultDept;
    }   

    function getDefaultEmailId() {
        return $this->config['default_email_id'];
    }

    function getDefaultEmail() {

        if(!$this->defaultEmail && $this->getDefaultEmailId())
            $this->defaultEmail=Email::lookup($this->getDefaultEmailId());

        return $this->defaultEmail;
    }

    function getDefaultEmailAddress() {
        $email=$this->getDefaultEmail();
        return $email?$email->getAddress():null;
    }

    function getDefaultSLAId() {
        return $this->config['default_sla_id'];
    }

    function getDefaultSLA() {

        if(!$this->defaultSLA && $this->getDefaultSLAId())
            $this->defaultSLA=SLA::lookup($this->getDefaultSLAId());

        return $this->defaultSLA;
    }

    function getAlertEmailId() {
        return $this->config['alert_email_id'];
    }

    function getAlertEmail() {

        if(!$this->alertEmail && $this->config['alert_email_id'])
            $this->alertEmail= new Email($this->config['alert_email_id']);
        return $this->alertEmail;
    }

    function getDefaultSMTPEmail() {

        if(!$this->defaultSMTPEmail && $this->config['default_smtp_id'])
            $this->defaultSMTPEmail= new Email($this->config['default_smtp_id']);
        return $this->defaultSMTPEmail;
    }

    function allowSMTPSpoofing() {
        return $this->config['spoof_default_smtp'];
    }

    function getDefaultPriorityId() {
        return $this->config['default_priority_id'];
    }

    function getDefaultTemplateId() {
        return $this->config['default_template_id'];
    }

    function getDefaultTemplate() {
    
        if(!$this->defaultTemplate && $this->getDefaultTemplateId())
            $this->defaultTemplate = Template::lookup($this->getDefaultTemplateId());

        return $this->defaultTemplate;
    }

    function getMaxOpenTickets() {
         return $this->config['max_open_tickets'];
    }

    function getMaxFileSize() {
        return $this->config['max_file_size'];
    }

    function getMaxFileUploads() {
        return $this->config['max_staff_file_uploads'];
    }

    function getLogLevel() {
        return $this->config['log_level'];
    }

    function getLogGracePeriod() {
        return $this->config['log_graceperiod'];
    }

    function logTicketActivity() {
        return $this->config['log_ticket_activity'];
    }

    function clickableURLS() {
        return ($this->config['clickable_urls']);
    }
        
    function canFetchMail() {
        return ($this->config['enable_mail_fetch']);
    }

    function enableStaffIPBinding() {
        return ($this->config['staff_ip_binding']);
    }

    function enableCaptcha() {
        return (extension_loaded('gd') && function_exists('gd_info') && $this->config['enable_captcha']);
    }

    function enableAutoCron() {
        return ($this->config['enable_auto_cron']);
    }
        
    function enableEmailPiping() {
        return ($this->config['enable_email_piping']);
    }

    function allowPriorityChange() {
        return ($this->config['allow_priority_change']);
    }

        
    function useEmailPriority() {
        return ($this->config['use_email_priority']);
    }

    function getAdminEmail() {
         return $this->config['admin_email'];
    }

    function getReplySeparator() {
        return $this->config['reply_separator'];
    }
  
    function stripQuotedReply() {
        return ($this->config['strip_quoted_reply']);
    }

    function saveEmailHeaders() {
        return true; //No longer an option...hint: big plans for headers coming!!
    }
    
    function useRandomIds() {
        return ($this->config['random_ticket_ids']);
    }

    /* autoresponders  & Alerts */
    function autoRespONNewTicket() {
        return ($this->config['ticket_autoresponder']);
    }
    
    function autoRespONNewMessage() {
        return ($this->config['message_autoresponder']);
    }

    function notifyONNewStaffTicket() {
        return ($this->config['ticket_notice_active']);
    }

    function alertONNewMessage() {
        return ($this->config['message_alert_active']);
    }

    function alertLastRespondentONNewMessage() {
        return ($this->config['message_alert_laststaff']);
    }
   
    function alertAssignedONNewMessage() {
        return ($this->config['message_alert_assigned']);
    }
    
    function alertDeptManagerONNewMessage() {
        return ($this->config['message_alert_dept_manager']);
    }

    function alertONNewNote() {
        return ($this->config['note_alert_active']);
    }

    function alertLastRespondentONNewNote() {
        return ($this->config['note_alert_laststaff']);
    }

    function alertAssignedONNewNote() {
        return ($this->config['note_alert_assigned']);
    }

    function alertDeptManagerONNewNote() {
        return ($this->config['note_alert_dept_manager']);
    }

    function alertONNewTicket() {
        return ($this->config['ticket_alert_active']);
    }

    function alertAdminONNewTicket() {
        return ($this->config['ticket_alert_admin']);
    }
     
    function alertDeptManagerONNewTicket() {
        return ($this->config['ticket_alert_dept_manager']);
    }

    function alertDeptMembersONNewTicket() {
        return ($this->config['ticket_alert_dept_members']);
    }

    function alertONTransfer() {
        return ($this->config['transfer_alert_active']);
    }

    function alertAssignedONTransfer() {
        return ($this->config['transfer_alert_assigned']);
    }
    
    function alertDeptManagerONTransfer() {
        return ($this->config['transfer_alert_dept_manager']);
    }
    
    function alertDeptMembersONTransfer() {
        return ($this->config['transfer_alert_dept_members']);
    }

    function alertONAssignment() {
        return ($this->config['assigned_alert_active']);
    }

    function alertStaffONAssignment() {
        return ($this->config['assigned_alert_staff']);
    }

    function alertTeamLeadONAssignment() {
        return ($this->config['assigned_alert_team_lead']);
    }

    function alertTeamMembersONAssignment() {
        return ($this->config['assigned_alert_team_members']);
    }


    function alertONOverdueTicket() {
        return ($this->config['overdue_alert_active']);
    }

    function alertAssignedONOverdueTicket() {
        return ($this->config['overdue_alert_assigned']);
    }

    function alertDeptManagerONOverdueTicket() {
        return ($this->config['overdue_alert_dept_manager']);
    }

    function alertDeptMembersONOverdueTicket() {
        return ($this->config['overdue_alert_dept_members']);
    }

    function autoAssignReopenedTickets() {
        return ($this->config['auto_assign_reopened_tickets']);
    }

    function showAssignedTickets() {
        return ($this->config['show_assigned_tickets']);
    }

    function showAnsweredTickets() {
        return ($this->config['show_answered_tickets']);
    }
        
    function hideStaffName() {
        return ($this->config['hide_staff_name']);
    }

    function sendOverLimitNotice() {
        return ($this->config['overlimit_notice_active']);
    }
        
    /* Error alerts sent to admin email when enabled */
    function alertONSQLError() {
        return ($this->config['send_sql_errors']);                    
    }
    function alertONLoginError() {
        return ($this->config['send_login_errors']);
    }

    function alertONMailParseError() {
        return ($this->config['send_mailparse_errors']);
    }

    

    /* Attachments */

    function emailAttachments() {
        return ($this->config['email_attachments']);
    }

    function allowAttachments() {
        return ($this->config['allow_attachments']);
    }

    function allowOnlineAttachments() {
        return ($this->allowAttachments() && $this->config['allow_online_attachments']);
    }

    function allowAttachmentsOnlogin() {
        return ($this->allowOnlineAttachments() && $this->config['allow_online_attachments_onlogin']);
    }
    
    function allowEmailAttachments() {
        return ($this->allowAttachments() && $this->config['allow_email_attachments']);
    }

    function getUploadDir() {
        return $this->config['upload_dir'];
    }
    
    //simply checking if destination dir is usable..nothing to do with permission to upload!
    function canUploadFiles() {   
        $dir=$this->config['upload_dir'];
        return ($dir && is_writable($dir))?TRUE:FALSE;
    }

    function canUploadFileType($filename) {       
        $ext = strtolower(preg_replace("/.*\.(.{3,4})$/", "$1", $filename));
        $allowed=$this->config['allowed_filetypes']?array_map('trim',explode(',',strtolower($this->config['allowed_filetypes']))):null;
        return ($ext && is_array($allowed) && (in_array(".$ext",$allowed) || in_array(".*",$allowed)))?TRUE:FALSE;
    }

    function updateSettings($vars,&$errors) {

        if(!$vars || $errors)
            return false;
        
        switch(strtolower($vars['t'])) {
            case 'general':
                return $this->updateGeneralSetting($vars,$errors);
                break;
            case 'dates':
                return $this->updateDateTimeSetting($vars,$errors);
                break;
            case 'tickets':
                return $this->updateTicketsSetting($vars,$errors);
                break;
            case 'emails':
                return $this->updateEmailsSetting($vars,$errors);
                break;
           case 'attachments':
                return $this->updateAttachmentsSetting($vars,$errors);
                break;
           case 'autoresponders':
                return $this->updateAutoresponderSetting($vars,$errors);
                break;
            case 'alerts':
                return $this->updateAlertsSetting($vars,$errors);
                break;
            case 'kb':
                return $this->updateKBSetting($vars,$errors);
                break;
            default:
                $errors['err']='Unknown setting option. Get technical support.';
        }

        return false;
    }

    function updateGeneralSetting($vars,&$errors) {

        $f=array();
        $f['helpdesk_url']=array('type'=>'string',   'required'=>1, 'error'=>'Helpdesk URl required');
        $f['helpdesk_title']=array('type'=>'string',   'required'=>1, 'error'=>'Helpdesk title required');
        $f['default_dept_id']=array('type'=>'int',   'required'=>1, 'error'=>'Default Dept. required');
        $f['default_template_id']=array('type'=>'int',   'required'=>1, 'error'=>'You must select template.');
        $f['staff_session_timeout']=array('type'=>'int',   'required'=>1, 'error'=>'Enter idle time in minutes');
        $f['client_session_timeout']=array('type'=>'int',   'required'=>1, 'error'=>'Enter idle time in minutes');

        if(!Validator::process($f,$vars,$errors) || $errors)
            return false;

        $sql='UPDATE '.CONFIG_TABLE.' SET updated=NOW() '.
             ',isonline='.db_input($vars['isonline']).
             ',helpdesk_title='.db_input($vars['helpdesk_title']).
             ',helpdesk_url='.db_input($vars['helpdesk_url']).
             ',default_dept_id='.db_input($vars['default_dept_id']).
             ',default_template_id='.db_input($vars['default_template_id']).
             ',max_page_size='.db_input($vars['max_page_size']).
             ',log_level='.db_input($vars['log_level']).
             ',log_graceperiod='.db_input($vars['log_graceperiod']).
             ',passwd_reset_period='.db_input($vars['passwd_reset_period']).
             ',staff_max_logins='.db_input($vars['staff_max_logins']).
             ',staff_login_timeout='.db_input($vars['staff_login_timeout']).
             ',staff_session_timeout='.db_input($vars['staff_session_timeout']).
             ',staff_ip_binding='.db_input(isset($vars['staff_ip_binding'])?1:0).
             ',client_max_logins='.db_input($vars['client_max_logins']).
             ',client_login_timeout='.db_input($vars['client_login_timeout']).
             ',client_session_timeout='.db_input($vars['client_session_timeout']).
             ',clickable_urls='.db_input(isset($vars['clickable_urls'])?1:0).
             ',enable_auto_cron='.db_input(isset($vars['enable_auto_cron'])?1:0).
             ' WHERE id='.$this->getId();
        
        return (db_query($sql));
    }

    function updateDateTimeSetting($vars,&$errors) {

        $f=array();
        $f['time_format']=array('type'=>'string',   'required'=>1, 'error'=>'Time format required');
        $f['date_format']=array('type'=>'string',   'required'=>1, 'error'=>'Date format required');
        $f['datetime_format']=array('type'=>'string',   'required'=>1, 'error'=>'Datetime format required');
        $f['daydatetime_format']=array('type'=>'string',   'required'=>1, 'error'=>'Day, Datetime format required');
        $f['default_timezone_id']=array('type'=>'int',   'required'=>1, 'error'=>'Default Timezone required');
             
        if(!Validator::process($f,$vars,$errors) || $errors)
            return false;

        $sql='UPDATE '.CONFIG_TABLE.' SET updated=NOW() '.
             ',time_format='.db_input($vars['time_format']).
             ',date_format='.db_input($vars['date_format']).
             ',datetime_format='.db_input($vars['datetime_format']).
             ',daydatetime_format='.db_input($vars['daydatetime_format']).
             ',default_timezone_id='.db_input($vars['default_timezone_id']).
             ',enable_daylight_saving='.db_input(isset($vars['enable_daylight_saving'])?1:0).
             ' WHERE id='.$this->getId();

        return (db_query($sql));
    }

    function updateTicketsSetting($vars,&$errors) {


        $f=array();
        $f['default_sla_id']=array('type'=>'int',   'required'=>1, 'error'=>'Selection required');
        $f['default_priority_id']=array('type'=>'int',   'required'=>1, 'error'=>'Selection required');
        $f['max_open_tickets']=array('type'=>'int',   'required'=>1, 'error'=>'Enter valid numeric value');
        $f['autolock_minutes']=array('type'=>'int',   'required'=>1, 'error'=>'Enter lock time in minutes');


        if($vars['enable_captcha']) {
            if (!extension_loaded('gd'))
                $errors['enable_captcha']='The GD extension required';
            elseif(!function_exists('imagepng'))
                $errors['enable_captcha']='PNG support required for Image Captcha';
        }

        if(!Validator::process($f,$vars,$errors) || $errors)
            return false;

        $sql='UPDATE '.CONFIG_TABLE.' SET updated=NOW() '.
             ',random_ticket_ids='.db_input($vars['random_ticket_ids']).
             ',default_priority_id='.db_input($vars['default_priority_id']).
             ',default_sla_id='.db_input($vars['default_sla_id']).
             ',max_open_tickets='.db_input($vars['max_open_tickets']).
             ',autolock_minutes='.db_input($vars['autolock_minutes']).
             ',allow_priority_change='.db_input(isset($vars['allow_priority_change'])?1:0).
             ',use_email_priority='.db_input(isset($vars['use_email_priority'])?1:0).
             ',enable_captcha='.db_input(isset($vars['enable_captcha'])?1:0).
             ',log_ticket_activity='.db_input(isset($vars['log_ticket_activity'])?1:0).
             ',auto_assign_reopened_tickets='.db_input(isset($vars['auto_assign_reopened_tickets'])?1:0).
             ',show_assigned_tickets='.db_input(isset($vars['show_assigned_tickets'])?1:0).
             ',show_answered_tickets='.db_input(isset($vars['show_answered_tickets'])?1:0).
             ',show_related_tickets='.db_input(isset($vars['show_related_tickets'])?1:0).
             ',hide_staff_name='.db_input(isset($vars['hide_staff_name'])?1:0);

        return (db_query($sql));
    }


    function updateEmailsSetting($vars,&$errors) {

        $f=array();
        $f['default_email_id']=array('type'=>'int',   'required'=>1, 'error'=>'Default email required');
        $f['alert_email_id']=array('type'=>'int',   'required'=>1, 'error'=>'Selection required');
        $f['admin_email']=array('type'=>'email',   'required'=>1, 'error'=>'System admin email required');
       
        if($vars['strip_quoted_reply'] && !$vars['reply_separator'])
            $errors['reply_separator']='Reply separator required to strip quoted reply.';
        
        if($vars['admin_email'] && Email::getIdByEmail($vars['admin_email'])) //Make sure admin email is not also a system email.
            $errors['admin_email']='Email already setup as system email';

        if(!Validator::process($f,$vars,$errors) || $errors)
            return false;

        $sql='UPDATE '.CONFIG_TABLE.' SET updated=NOW() '.
             ',default_email_id='.db_input($vars['default_email_id']).
             ',alert_email_id='.db_input($vars['alert_email_id']).
             ',default_smtp_id='.db_input($vars['default_smtp_id']).
             ',admin_email='.db_input($vars['admin_email']).
             ',enable_mail_polling='.db_input(isset($vars['enable_mail_polling'])?1:0).
             ',enable_email_piping='.db_input(isset($vars['enable_email_piping'])?1:0).
             ',strip_quoted_reply='.db_input(isset($vars['strip_quoted_reply'])?1:0).
             ',reply_separator='.db_input($vars['reply_separator']);
        
        return (db_query($sql));
    }

    function updateAttachmentsSetting($vars,&$errors) {


        if($vars['allow_attachments']) {
        
            if(!ini_get('file_uploads'))
                $errors['err']='The \'file_uploads\' directive is disabled in php.ini';
                
            if(!is_numeric($vars['max_file_size']))
                $errors['max_file_size']='Maximum file size required';       
               
            if(!$vars['allowed_filetypes'])
                $errors['allowed_filetypes']='Allowed file extentions required';
        
            if(!($maxfileuploads=ini_get('max_file_uploads')))
                $maxfileuploads=DEFAULT_MAX_FILE_UPLOADS;

            if(!$vars['max_user_file_uploads'] || $vars['max_user_file_uploads']>$maxfileuploads)
                $errors['max_user_file_uploads']='Invalid selection';

            if(!$vars['max_staff_file_uploads'] || $vars['max_staff_file_uploads']>$maxfileuploads)
                $errors['max_staff_file_uploads']='Invalid selection';
        }

        if($errors) return false;

        $sql= 'UPDATE '.CONFIG_TABLE.' SET updated=NOW() '.
              ',allow_attachments='.db_input(isset($vars['allow_attachments'])?1:0).
              ',allowed_filetypes='.db_input(strtolower(preg_replace("/\n\r|\r\n|\n|\r/", '',trim($vars['allowed_filetypes'])))).
              ',max_file_size='.db_input($vars['max_file_size']).
              ',max_user_file_uploads='.db_input($vars['max_user_file_uploads']).
              ',max_staff_file_uploads='.db_input($vars['max_staff_file_uploads']).
              ',email_attachments='.db_input(isset($vars['email_attachments'])?1:0).
              ',allow_email_attachments='.db_input(isset($vars['allow_email_attachments'])?1:0).
              ',allow_online_attachments='.db_input(isset($vars['allow_online_attachments'])?1:0).
              ',allow_online_attachments_onlogin='.db_input(isset($vars['allow_online_attachments_onlogin'])?1:0).
              ' WHERE id='.db_input($this->getId());
        

        return (db_query($sql));

    }


    function updateAutoresponderSetting($vars,&$errors) {

        if($errors) return false;

        $sql= 'UPDATE '.CONFIG_TABLE.' SET updated=NOW() '.
              ',ticket_autoresponder='.db_input($vars['ticket_autoresponder']).
              ',message_autoresponder='.db_input($vars['message_autoresponder']).
              ',ticket_notice_active='.db_input($vars['ticket_notice_active']).
              ',overlimit_notice_active='.db_input($vars['overlimit_notice_active']);

        return (db_query($sql));

    }


    function updateKBSetting($vars,&$errors) {

        if($errors) return false;

        $sql= 'UPDATE '.CONFIG_TABLE.' SET updated=NOW() '.
              ',enable_kb='.db_input(isset($vars['enable_kb'])?1:0).
              ',enable_premade='.db_input(isset($vars['enable_premade'])?1:0);
    
        return (db_query($sql));
    }


    function updateAlertsSetting($vars,&$errors) {


       if($vars['ticket_alert_active']
                && (!isset($vars['ticket_alert_admin'])
                    && !isset($vars['ticket_alert_dept_manager'])
                    && !isset($vars['ticket_alert_dept_members']))) {
            $errors['ticket_alert_active']='Select recipient(s)';
        }
        if($vars['message_alert_active']
                && (!isset($vars['message_alert_laststaff'])
                    && !isset($vars['message_alert_assigned'])
                    && !isset($vars['message_alert_dept_manager']))) {
            $errors['message_alert_active']='Select recipient(s)';
        }

        if($vars['note_alert_active']
                && (!isset($vars['note_alert_laststaff'])
                    && !isset($vars['note_alert_assigned'])
                    && !isset($vars['note_alert_dept_manager']))) {
            $errors['note_alert_active']='Select recipient(s)';
        }

        if($vars['transfer_alert_active']
                && (!isset($vars['transfer_alert_assigned'])
                    && !isset($vars['transfer_alert_dept_manager'])
                    && !isset($vars['transfer_alert_dept_members']))) {
            $errors['transfer_alert_active']='Select recipient(s)';
        }

        if($vars['overdue_alert_active']
                && (!isset($vars['overdue_alert_assigned'])
                    && !isset($vars['overdue_alert_dept_manager'])
                    && !isset($vars['overdue_alert_dept_members']))) {
            $errors['overdue_alert_active']='Select recipient(s)';
        }

        if($vars['assigned_alert_active']
                && (!isset($vars['assigned_alert_staff'])
                    && !isset($vars['assigned_alert_team_lead'])
                    && !isset($vars['assigned_alert_team_members']))) {
            $errors['assigned_alert_active']='Select recipient(s)';
        }

        if($errors) return false;
        
        $sql= 'UPDATE '.CONFIG_TABLE.' SET updated=NOW() '.
              ',ticket_notice_active='.db_input($vars['ticket_notice_active']).
              ',ticket_alert_active='.db_input($vars['ticket_alert_active']).
              ',ticket_alert_admin='.db_input(isset($vars['ticket_alert_admin'])?1:0).
              ',ticket_alert_dept_manager='.db_input(isset($vars['ticket_alert_dept_manager'])?1:0).
              ',ticket_alert_dept_members='.db_input(isset($vars['ticket_alert_dept_members'])?1:0).
              ',message_alert_active='.db_input($vars['message_alert_active']).
              ',message_alert_laststaff='.db_input(isset($vars['message_alert_laststaff'])?1:0).
              ',message_alert_assigned='.db_input(isset($vars['message_alert_assigned'])?1:0).
              ',message_alert_dept_manager='.db_input(isset($vars['message_alert_dept_manager'])?1:0).
              ',note_alert_active='.db_input($vars['note_alert_active']).
              ',note_alert_laststaff='.db_input(isset($vars['note_alert_laststaff'])?1:0).
              ',note_alert_assigned='.db_input(isset($vars['note_alert_assigned'])?1:0).
              ',note_alert_dept_manager='.db_input(isset($vars['note_alert_dept_manager'])?1:0).
              ',assigned_alert_active='.db_input($vars['assigned_alert_active']).
              ',assigned_alert_staff='.db_input(isset($vars['assigned_alert_staff'])?1:0).
              ',assigned_alert_team_lead='.db_input(isset($vars['assigned_alert_team_lead'])?1:0).
              ',assigned_alert_team_members='.db_input(isset($vars['assigned_alert_team_members'])?1:0).
              ',transfer_alert_active='.db_input($vars['transfer_alert_active']).
              ',transfer_alert_assigned='.db_input(isset($vars['transfer_alert_assigned'])?1:0).
              ',transfer_alert_dept_manager='.db_input(isset($vars['transfer_alert_dept_manager'])?1:0).
              ',transfer_alert_dept_members='.db_input(isset($var['transfer_alert_dept_members'])?1:0).
              ',overdue_alert_active='.db_input($vars['overdue_alert_active']).
              ',overdue_alert_assigned='.db_input(isset($vars['overdue_alert_assigned'])?1:0).
              ',overdue_alert_dept_manager='.db_input(isset($vars['overdue_alert_dept_manager'])?1:0).
              ',overdue_alert_dept_members='.db_input(isset($var['overdue_alert_dept_members'])?1:0).
              ',send_sys_errors='.db_input(isset($vars['send_sys_errors'])?1:0).
              ',send_sql_errors='.db_input(isset($vars['send_sql_errors'])?1:0).
              ',send_login_errors='.db_input(isset($vars['send_login_errors'])?1:0);

        return (db_query($sql));

    }
}
?>
