<?php
/*********************************************************************
    class.config.php

    osTicket config info manager.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Config {
    var $config = array();

    var $section = null;                    # Default namespace ('core')
    var $table = CONFIG_TABLE;              # Table name (with prefix)
    var $section_column = 'namespace';      # namespace column name

    var $session = null;                    # Session-backed configuration

    # Defaults for this configuration. If settings don't exist in the
    # database yet, the ->getInfo() method will not include the (default)
    # values in the returned array. $defaults allows developers to define
    # new settings and the corresponding default values.
    var $defaults = array();                # List of default values

    function Config($section=null) {
        if ($section)
            $this->section = $section;

        if ($this->section === null)
            return false;

        if (!isset($_SESSION['cfg:'.$this->section]))
            $_SESSION['cfg:'.$this->section] = array();
        $this->session = &$_SESSION['cfg:'.$this->section];

        $sql='SELECT id, `key`, value, `updated` FROM '.$this->table
            .' WHERE `'.$this->section_column.'` = '.db_input($this->section);

        if(($res=db_query($sql)) && db_num_rows($res))
            while ($row = db_fetch_array($res))
                $this->config[$row['key']] = $row;
    }

    function getNamespace() {
        return $this->section;
    }

    function getInfo() {
        $info = $this->defaults;
        foreach ($this->config as $key=>$setting)
            $info[$key] = $setting['value'];
        return $info;
    }

    function get($key, $default=null) {
        if (isset($this->session[$key]))
            return $this->session[$key];
        elseif (isset($this->config[$key]))
            return $this->config[$key]['value'];
        elseif (isset($this->defaults[$key]))
            return $this->defaults[$key];

        return $default;
    }

    function exists($key) {
        return $this->get($key, null) ? true : false;
    }

    function set($key, $value) {
        return ($this->update($key, $value)) ? $value : null;
    }

    function persist($key, $value) {
        $this->session[$key] = $value;
        return true;
    }

    function lastModified($key) {
        if (isset($this->config[$key]))
            return $this->config[$key]['updated'];
        else
            return false;
    }

    function create($key, $value) {
        $sql = 'INSERT INTO '.$this->table
            .' SET `'.$this->section_column.'`='.db_input($this->section)
            .', `key`='.db_input($key)
            .', value='.db_input($value);
        if (!db_query($sql) || !($id=db_insert_id()))
            return false;

        $this->config[$key] = array('key'=>$key, 'value'=>$value, 'id'=>$id);
        return true;
    }

    function update($key, $value) {
        if (!$key)
            return false;
        elseif (!isset($this->config[$key]))
            return $this->create($key, $value);

        $setting = &$this->config[$key];
        if ($setting['value'] == $value)
            return true;

        if (!db_query('UPDATE '.$this->table.' SET updated=NOW(), value='
                .db_input($value).' WHERE id='.db_input($setting['id'])))
            return false;

        $setting['value'] = $value;
        return true;
    }

    function updateAll($updates) {
        foreach ($updates as $key=>$value)
            if (!$this->update($key, $value))
                return false;
        return true;
    }

    function destroy() {

        $sql='DELETE FROM '.$this->table
            .' WHERE `'.$this->section_column.'` = '.db_input($this->section);

        db_query($sql);
        unset($this->session);
    }
}

class OsticketConfig extends Config {
    var $table = CONFIG_TABLE;
    var $section = 'core';

    var $defaultDept;   //Default Department
    var $defaultSLA;   //Default SLA
    var $defaultEmail;  //Default Email
    var $alertEmail;  //Alert Email
    var $defaultSMTPEmail; //Default  SMTP Email

    var $defaults = array(
        'allow_pw_reset' =>     true,
        'pw_reset_window' =>    30,
        'enable_html_thread' => true,
        'allow_attachments' =>  true,
        'name_format' =>        'full', # First Last
        'auto_claim_tickets'=>  true,
        'system_language' =>    'en_US',
        'default_storage_bk' => 'D',
        'allow_client_updates' => false,
        'message_autoresponder_collabs' => true,
        'add_email_collabs' => true,
        'clients_only' => false,
        'client_registration' => 'closed',
        'accept_unregistered_email' => true,
        'default_help_topic' => 0,
        'help_topic_sort_mode' => 'a',
        'client_verify_email' => 1,
    );

    function OsticketConfig($section=null) {
        parent::Config($section);

        if (count($this->config) == 0) {
            // Fallback for osticket < 1.7@852ca89e
            $sql='SELECT * FROM '.$this->table.' WHERE id = 1';
            if (($res=db_query($sql)) && db_num_rows($res))
                foreach (db_fetch_array($res) as $key=>$value)
                    $this->config[$key] = array('value'=>$value);
        }

        //Get the default time zone
        // We can't JOIN timezone table above due to upgrade support.
        if ($this->get('default_timezone_id')) {
            if (!$this->exists('tz_offset'))
                $this->persist('tz_offset',
                    Timezone::getOffsetById($this->get('default_timezone_id')));
        } else
            // Previous osTicket versions saved the offset value instead of
            // a timezone instance. This is compatibility for the upgrader
            $this->persist('tz_offset', 0);

        return true;
    }

    function lastModified($key=false) {
        return max(array_map(array('parent', 'lastModified'),
            array_keys($this->config)));
    }

    function isHelpDeskOffline() {
        return !$this->isOnline();
    }

    function isHelpDeskOnline() {
        return $this->isOnline();
    }

    function isOnline() {
        return ($this->get('isonline'));
    }

    function isKnowledgebaseEnabled() {
        require_once(INCLUDE_DIR.'class.faq.php');
        return ($this->get('enable_kb') && FAQ::countPublishedFAQs());
    }

    function getVersion() {
        return THIS_VERSION;
    }

    function getSchemaSignature($section=null) {

        if ((!$section || $section == $this->section)
                && ($v=$this->get('schema_signature')))
            return $v;

        // 1.7 after namespaced configuration, other namespace
        if ($section) {
            $sql='SELECT value FROM '.$this->table
                .' WHERE `key` = "schema_signature" and namespace='.db_input($section);
            if (($res=db_query($sql, false)) && db_num_rows($res))
                return db_result($res);
        }

        // 1.7 before namespaced configuration
        $sql='SELECT `schema_signature` FROM '.$this->table
            .' WHERE id=1';
        if (($res=db_query($sql, false)) && db_num_rows($res))
            return db_result($res);

        // old version 1.6
        return md5(self::getDBVersion());
    }

    function getDBTZoffset() {
        if (!$this->exists('db_tz_offset')) {
            $sql='SELECT (TIME_TO_SEC(TIMEDIFF(NOW(), UTC_TIMESTAMP()))/3600) as db_tz_offset';
            if(($res=db_query($sql)) && db_num_rows($res))
                $this->persist('db_tz_offset', db_result($res));
        }
        return $this->get('db_tz_offset');
    }

    function getDefaultTimezoneId() {
        return $this->get('default_timezone_id');
    }

    /* Date & Time Formats */
    function observeDaylightSaving() {
        return ($this->get('enable_daylight_saving'));
    }
    function getTimeFormat() {
        return $this->get('time_format');
    }
    function getDateFormat() {
        return $this->get('date_format');
    }

    function getDateTimeFormat() {
        return $this->get('datetime_format');
    }

    function getDayDateTimeFormat() {
        return $this->get('daydatetime_format');
    }

    function getConfigInfo() {
        return $this->getInfo();
    }

    function getTitle() {
        return $this->get('helpdesk_title');
    }

    function getUrl() {
        return $this->get('helpdesk_url');
    }

    function getBaseUrl() { //Same as above with no trailing slash.
        return rtrim($this->getUrl(),'/');
    }

    function getTZOffset() {
        return $this->get('tz_offset');
    }

    function getPageSize() {
        return $this->get('max_page_size');
    }

    function getGracePeriod() {
        return $this->get('overdue_grace_period');
    }

    function getPasswdResetPeriod() {
        return $this->get('passwd_reset_period');
    }

    function isHtmlThreadEnabled() {
        return $this->get('enable_html_thread');
    }

    function allowClientUpdates() {
        return $this->get('allow_client_updates');
    }

    function getClientTimeout() {
        return $this->getClientSessionTimeout();
    }

    function getClientSessionTimeout() {
        return $this->get('client_session_timeout')*60;
    }

    function getClientLoginTimeout() {
        return $this->get('client_login_timeout')*60;
    }

    function getClientMaxLogins() {
        return $this->get('client_max_logins');
    }

    function getStaffTimeout() {
        return $this->getStaffSessionTimeout();
    }

    function getStaffSessionTimeout() {
        return $this->get('staff_session_timeout')*60;
    }

    function getStaffLoginTimeout() {
        return $this->get('staff_login_timeout')*60;
    }

    function getStaffMaxLogins() {
        return $this->get('staff_max_logins');
    }

    function getLockTime() {
        return $this->get('autolock_minutes');
    }

    function getDefaultNameFormat() {
        return $this->get('name_format');
    }

    function getDefaultDeptId() {
        return $this->get('default_dept_id');
    }

    function getDefaultDept() {

        if(!$this->defaultDept && $this->getDefaultDeptId())
            $this->defaultDept=Dept::lookup($this->getDefaultDeptId());

        return $this->defaultDept;
    }

    function getDefaultEmailId() {
        return $this->get('default_email_id');
    }

    function getDefaultEmail() {

        if(!$this->defaultEmail && $this->getDefaultEmailId())
            $this->defaultEmail = Email::lookup($this->getDefaultEmailId());

        return $this->defaultEmail;
    }

    function getDefaultEmailAddress() {
        return ($email=$this->getDefaultEmail()) ? $email->getAddress() : null;
    }

    function getDefaultTicketStatusId() {
        return $this->get('default_ticket_status_id', 1);
    }

    function getDefaultSLAId() {
        return $this->get('default_sla_id');
    }

    function getDefaultSLA() {

        if(!$this->defaultSLA && $this->getDefaultSLAId())
            $this->defaultSLA = SLA::lookup($this->getDefaultSLAId());

        return $this->defaultSLA;
    }

    function getAlertEmailId() {
        return $this->get('alert_email_id');
    }

    function getAlertEmail() {

        if(!$this->alertEmail)
            if(!($this->alertEmail = Email::lookup($this->getAlertEmailId())))
                $this->alertEmail = $this->getDefaultEmail();

        return $this->alertEmail;
    }

    function getDefaultSMTPEmail() {

        if(!$this->defaultSMTPEmail && $this->get('default_smtp_id'))
            $this->defaultSMTPEmail = Email::lookup($this->get('default_smtp_id'));

        return $this->defaultSMTPEmail;
    }

    function getDefaultPriorityId() {
        return $this->get('default_priority_id');
    }

    function getDefaultPriority() {
        if (!isset($this->defaultPriority))
            $this->defaultPriority = Priority::lookup($this->getDefaultPriorityId());

        return $this->defaultPriority;
    }

    function getDefaultTopicId() {
        return $this->get('default_help_topic');
    }

    function getDefaultTopic() {
        return Topic::lookup($this->getDefaultTopicId());
    }

    function getTopicSortMode() {
        return $this->get('help_topic_sort_mode');
    }

    function setTopicSortMode($mode) {
        $modes = static::allTopicSortModes();
        if (!isset($modes[$mode]))
            throw new InvalidArgumentException(sprintf(
                '%s: Unsupported help topic sort mode', $mode));

        $this->update('help_topic_sort_mode', $mode);
    }

    static function allTopicSortModes() {
        return array(
            'a' => __('Alphabetically'),
            'm' => __('Manually'),
        );
    }

    function getDefaultTemplateId() {
        return $this->get('default_template_id');
    }

    function getDefaultTemplate() {

        if(!$this->defaultTemplate && $this->getDefaultTemplateId())
            $this->defaultTemplate = EmailTemplateGroup::lookup($this->getDefaultTemplateId());

        return $this->defaultTemplate;
    }

    function getLandingPageId() {
        return $this->get('landing_page_id');
    }

    function getLandingPage() {

        if(!$this->landing_page && $this->getLandingPageId())
            $this->landing_page = Page::lookup($this->getLandingPageId());

        return $this->landing_page;
    }

    function getOfflinePageId() {
        return $this->get('offline_page_id');
    }

    function getOfflinePage() {

        if(!$this->offline_page && $this->getOfflinePageId())
            $this->offline_page = Page::lookup($this->getOfflinePageId());

        return $this->offline_page;
    }

    function getThankYouPageId() {
        return $this->get('thank-you_page_id');
    }

    function getThankYouPage() {

        if(!$this->thankyou_page && $this->getThankYouPageId())
            $this->thankyou_page = Page::lookup($this->getThankYouPageId());

        return $this->thankyou_page;
    }

    function getDefaultPages() {
        /* Array of ids...as opposed to objects */
        return array(
                $this->getLandingPageId(),
                $this->getOfflinePageId(),
                $this->getThankYouPageId(),
                );
    }

    function getMaxOpenTickets() {
         return $this->get('max_open_tickets');
    }

    function getMaxFileSize() {
        return $this->get('max_file_size');
    }

    function getLogLevel() {
        return $this->get('log_level');
    }

    function getLogGracePeriod() {
        return $this->get('log_graceperiod');
    }

    function enableStaffIPBinding() {
        return ($this->get('staff_ip_binding'));
    }

    /**
     * Configuration: allow_pw_reset
     *
     * TRUE if the <a>Forgot my password</a> link and system should be
     * enabled, and FALSE otherwise.
     */
    function allowPasswordReset() {
        return $this->get('allow_pw_reset');
    }

    /**
     * Configuration: pw_reset_window
     *
     * Number of minutes for which the password reset token is valid.
     *
     * Returns: Number of seconds the password reset token is valid. The
     *      number of minutes from the database is automatically converted
     *      to seconds here.
     */
    function getPwResetWindow() {
        // pw_reset_window is stored in minutes. Return value in seconds
        return $this->get('pw_reset_window') * 60;
    }

    function isClientLoginRequired() {
        return $this->get('clients_only');
    }

    function isClientRegistrationEnabled() {
        return in_array($this->getClientRegistrationMode(),
            array('public', 'auto'));
    }

    function getClientRegistrationMode() {
        return $this->get('client_registration');
    }

    function isClientEmailVerificationRequired() {
        return $this->get('client_verify_email');
    }

    function isCaptchaEnabled() {
        return (extension_loaded('gd') && function_exists('gd_info') && $this->get('enable_captcha'));
    }

    function isAutoCronEnabled() {
        return ($this->get('enable_auto_cron'));
    }

    function isEmailPollingEnabled() {
        return ($this->get('enable_mail_polling'));
    }

    function useEmailPriority() {
        return ($this->get('use_email_priority'));
    }

    function acceptUnregisteredEmail() {
        return $this->get('accept_unregistered_email');
    }

    function addCollabsViaEmail() {
        return ($this->get('add_email_collabs'));
    }

    function getAdminEmail() {
         return $this->get('admin_email');
    }

    function getReplySeparator() {
        return $this->get('reply_separator');
    }

    function stripQuotedReply() {
        return ($this->get('strip_quoted_reply'));
    }

    function saveEmailHeaders() {
        return true; //No longer an option...hint: big plans for headers coming!!
    }

    function getDefaultSequence() {
        if ($this->get('sequence_id'))
            $sequence = Sequence::lookup($this->get('sequence_id'));
        if (!$sequence)
            $sequence = new RandomSequence();
        return $sequence;
    }
    function getDefaultNumberFormat() {
        return $this->get('number_format');
    }
    function getNewTicketNumber() {
        $s = $this->getDefaultSequence();
        return $s->next($this->getDefaultNumberFormat(),
            array('Ticket', 'isTicketNumberUnique'));
    }

    /* autoresponders  & Alerts */
    function autoRespONNewTicket() {
        return ($this->get('ticket_autoresponder'));
    }

    function autoRespONNewMessage() {
        return ($this->get('message_autoresponder'));
    }

    function notifyCollabsONNewMessage() {
        return ($this->get('message_autoresponder_collabs'));
    }

    function notifyONNewStaffTicket() {
        return ($this->get('ticket_notice_active'));
    }

    function alertONNewMessage() {
        return ($this->get('message_alert_active'));
    }

    function alertLastRespondentONNewMessage() {
        return ($this->get('message_alert_laststaff'));
    }

    function alertAssignedONNewMessage() {
        return ($this->get('message_alert_assigned'));
    }

    function alertDeptManagerONNewMessage() {
        return ($this->get('message_alert_dept_manager'));
    }

    function alertAcctManagerONNewMessage() {
        return ($this->get('message_alert_acct_manager'));
    }

    function alertONNewNote() {
        return ($this->get('note_alert_active'));
    }

    function alertLastRespondentONNewNote() {
        return ($this->get('note_alert_laststaff'));
    }

    function alertAssignedONNewNote() {
        return ($this->get('note_alert_assigned'));
    }

    function alertDeptManagerONNewNote() {
        return ($this->get('note_alert_dept_manager'));
    }

    function alertONNewTicket() {
        return ($this->get('ticket_alert_active'));
    }

    function alertAdminONNewTicket() {
        return ($this->get('ticket_alert_admin'));
    }

    function alertDeptManagerONNewTicket() {
        return ($this->get('ticket_alert_dept_manager'));
    }

    function alertDeptMembersONNewTicket() {
        return ($this->get('ticket_alert_dept_members'));
    }

    function alertAcctManagerONNewTicket() {
        return ($this->get('ticket_alert_acct_manager'));
    }

    function alertONTransfer() {
        return ($this->get('transfer_alert_active'));
    }

    function alertAssignedONTransfer() {
        return ($this->get('transfer_alert_assigned'));
    }

    function alertDeptManagerONTransfer() {
        return ($this->get('transfer_alert_dept_manager'));
    }

    function alertDeptMembersONTransfer() {
        return ($this->get('transfer_alert_dept_members'));
    }

    function alertONAssignment() {
        return ($this->get('assigned_alert_active'));
    }

    function alertStaffONAssignment() {
        return ($this->get('assigned_alert_staff'));
    }

    function alertTeamLeadONAssignment() {
        return ($this->get('assigned_alert_team_lead'));
    }

    function alertTeamMembersONAssignment() {
        return ($this->get('assigned_alert_team_members'));
    }


    function alertONOverdueTicket() {
        return ($this->get('overdue_alert_active'));
    }

    function alertAssignedONOverdueTicket() {
        return ($this->get('overdue_alert_assigned'));
    }

    function alertDeptManagerONOverdueTicket() {
        return ($this->get('overdue_alert_dept_manager'));
    }

    function alertDeptMembersONOverdueTicket() {
        return ($this->get('overdue_alert_dept_members'));
    }

    function autoClaimTickets() {
        return $this->get('auto_claim_tickets');
    }

    function showAssignedTickets() {
        return ($this->get('show_assigned_tickets'));
    }

    function showAnsweredTickets() {
        return ($this->get('show_answered_tickets'));
    }

    function hideStaffName() {
        return ($this->get('hide_staff_name'));
    }

    function sendOverLimitNotice() {
        return ($this->get('overlimit_notice_active'));
    }

    /* Error alerts sent to admin email when enabled */
    function alertONSQLError() {
        return ($this->get('send_sql_errors'));
    }
    function alertONLoginError() {
        return ($this->get('send_login_errors'));
    }



    /* Attachments */
    function getAllowedFileTypes() {
        return trim($this->get('allowed_filetypes'));
    }

    function emailAttachments() {
        return ($this->get('email_attachments'));
    }

    function allowAttachments() {
        return ($this->get('allow_attachments'));
    }

    function getSystemLanguage() {
        return $this->get('system_language');
    }

    /* Needed by upgrader on 1.6 and older releases upgrade - not not remove */
    function getUploadDir() {
        return $this->get('upload_dir');
    }

    function getDefaultStorageBackendChar() {
        return $this->get('default_storage_bk');
    }

    function getVar($name) {
        return $this->get($name);
    }

    function updateSettings($vars, &$errors) {

        if(!$vars || $errors)
            return false;

        switch(strtolower($vars['t'])) {
            case 'system':
                return $this->updateSystemSettings($vars, $errors);
                break;
            case 'tickets':
                return $this->updateTicketsSettings($vars, $errors);
                break;
            case 'emails':
                return $this->updateEmailsSettings($vars, $errors);
                break;
            case 'pages':
                return $this->updatePagesSettings($vars, $errors);
                break;
            case 'access':
                return $this->updateAccessSettings($vars, $errors);
                break;
            case 'autoresp':
                return $this->updateAutoresponderSettings($vars, $errors);
                break;
            case 'alerts':
                return $this->updateAlertsSettings($vars, $errors);
                break;
            case 'kb':
                return $this->updateKBSettings($vars, $errors);
                break;
            default:
                $errors['err']=__('Unknown setting option. Get technical support.');
        }

        return false;
    }

    function updateSystemSettings($vars, &$errors) {

        $f=array();
        $f['helpdesk_url']=array('type'=>'string',   'required'=>1, 'error'=>__('Helpdesk URL is required'));
        $f['helpdesk_title']=array('type'=>'string',   'required'=>1, 'error'=>__('Helpdesk title is required'));
        $f['default_dept_id']=array('type'=>'int',   'required'=>1, 'error'=>__('Default Department is required'));
        //Date & Time Options
        $f['time_format']=array('type'=>'string',   'required'=>1, 'error'=>__('Time format is required'));
        $f['date_format']=array('type'=>'string',   'required'=>1, 'error'=>__('Date format is required'));
        $f['datetime_format']=array('type'=>'string',   'required'=>1, 'error'=>__('Datetime format is required'));
        $f['daydatetime_format']=array('type'=>'string',   'required'=>1, 'error'=>__('Day, Datetime format is required'));
        $f['default_timezone_id']=array('type'=>'int',   'required'=>1, 'error'=>__('Default Timezone is required'));

        if(!Validator::process($f, $vars, $errors) || $errors)
            return false;

        return $this->updateAll(array(
            'isonline'=>$vars['isonline'],
            'helpdesk_title'=>$vars['helpdesk_title'],
            'helpdesk_url'=>$vars['helpdesk_url'],
            'default_dept_id'=>$vars['default_dept_id'],
            'max_page_size'=>$vars['max_page_size'],
            'log_level'=>$vars['log_level'],
            'log_graceperiod'=>$vars['log_graceperiod'],
            'name_format'=>$vars['name_format'],
            'time_format'=>$vars['time_format'],
            'date_format'=>$vars['date_format'],
            'datetime_format'=>$vars['datetime_format'],
            'daydatetime_format'=>$vars['daydatetime_format'],
            'default_timezone_id'=>$vars['default_timezone_id'],
            'enable_daylight_saving'=>isset($vars['enable_daylight_saving'])?1:0,
        ));
    }

    function updateAccessSettings($vars, &$errors) {
        $f=array();
        $f['staff_session_timeout']=array('type'=>'int',   'required'=>1, 'error'=>'Enter idle time in minutes');
        $f['client_session_timeout']=array('type'=>'int',   'required'=>1, 'error'=>'Enter idle time in minutes');
        $f['pw_reset_window']=array('type'=>'int', 'required'=>1, 'min'=>1,
            'error'=>__('Valid password reset window required'));


        if(!Validator::process($f, $vars, $errors) || $errors)
            return false;

        return $this->updateAll(array(
            'passwd_reset_period'=>$vars['passwd_reset_period'],
            'staff_max_logins'=>$vars['staff_max_logins'],
            'staff_login_timeout'=>$vars['staff_login_timeout'],
            'staff_session_timeout'=>$vars['staff_session_timeout'],
            'staff_ip_binding'=>isset($vars['staff_ip_binding'])?1:0,
            'client_max_logins'=>$vars['client_max_logins'],
            'client_login_timeout'=>$vars['client_login_timeout'],
            'client_session_timeout'=>$vars['client_session_timeout'],
            'allow_pw_reset'=>isset($vars['allow_pw_reset'])?1:0,
            'pw_reset_window'=>$vars['pw_reset_window'],
            'clients_only'=>isset($vars['clients_only'])?1:0,
            'client_registration'=>$vars['client_registration'],
            'client_verify_email'=>isset($vars['client_verify_email'])?1:0,
        ));
    }

    function updateTicketsSettings($vars, &$errors) {
        $f=array();
        $f['default_sla_id']=array('type'=>'int',   'required'=>1, 'error'=>__('Selection required'));
        $f['default_ticket_status_id'] = array('type'=>'int', 'required'=>1, 'error'=>__('Selection required'));
        $f['default_priority_id']=array('type'=>'int',   'required'=>1, 'error'=>__('Selection required'));
        $f['max_open_tickets']=array('type'=>'int',   'required'=>1, 'error'=>__('Enter valid numeric value'));
        $f['autolock_minutes']=array('type'=>'int',   'required'=>1, 'error'=>__('Enter lock time in minutes'));


        if($vars['enable_captcha']) {
            if (!extension_loaded('gd'))
                $errors['enable_captcha']=__('The GD extension is required');
            elseif(!function_exists('imagepng'))
                $errors['enable_captcha']=__('PNG support is required for Image Captcha');
        }

        if ($vars['default_help_topic']
                && ($T = Topic::lookup($vars['default_help_topic']))
                && !$T->isActive()) {
            $errors['default_help_topic'] = __('Default help topic must be set to active');
        }

        if (!preg_match('`(?!<\\\)#`', $vars['number_format']))
            $errors['number_format'] = 'Ticket number format requires at least one hash character (#)';

        if(!Validator::process($f, $vars, $errors) || $errors)
            return false;

        if (isset($vars['default_storage_bk']))
            $this->update('default_storage_bk', $vars['default_storage_bk']);

        return $this->updateAll(array(
            'number_format'=>$vars['number_format'] ?: '######',
            'sequence_id'=>$vars['sequence_id'] ?: 0,
            'default_priority_id'=>$vars['default_priority_id'],
            'default_help_topic'=>$vars['default_help_topic'],
            'default_ticket_status_id'=>$vars['default_ticket_status_id'],
            'default_sla_id'=>$vars['default_sla_id'],
            'max_open_tickets'=>$vars['max_open_tickets'],
            'autolock_minutes'=>$vars['autolock_minutes'],
            'enable_captcha'=>isset($vars['enable_captcha'])?1:0,
            'auto_claim_tickets'=>isset($vars['auto_claim_tickets'])?1:0,
            'show_assigned_tickets'=>isset($vars['show_assigned_tickets'])?0:1,
            'show_answered_tickets'=>isset($vars['show_answered_tickets'])?0:1,
            'show_related_tickets'=>isset($vars['show_related_tickets'])?1:0,
            'hide_staff_name'=>isset($vars['hide_staff_name'])?1:0,
            'enable_html_thread'=>isset($vars['enable_html_thread'])?1:0,
            'allow_client_updates'=>isset($vars['allow_client_updates'])?1:0,
            'max_file_size'=>$vars['max_file_size'],
        ));
    }


    function updateEmailsSettings($vars, &$errors) {
        $f=array();
        $f['default_template_id']=array('type'=>'int',   'required'=>1, 'error'=>__('You must select template'));
        $f['default_email_id']=array('type'=>'int',   'required'=>1, 'error'=>__('Default email is required'));
        $f['alert_email_id']=array('type'=>'int',   'required'=>1, 'error'=>__('Selection required'));
        $f['admin_email']=array('type'=>'email',   'required'=>1, 'error'=>__('System admin email is required'));

        if($vars['strip_quoted_reply'] && !trim($vars['reply_separator']))
            $errors['reply_separator']=__('Reply separator is required to strip quoted reply.');

        if($vars['admin_email'] && Email::getIdByEmail($vars['admin_email'])) //Make sure admin email is not also a system email.
            $errors['admin_email']=__('Email already setup as system email');

        if(!Validator::process($f,$vars,$errors) || $errors)
            return false;

        return $this->updateAll(array(
            'default_template_id'=>$vars['default_template_id'],
            'default_email_id'=>$vars['default_email_id'],
            'alert_email_id'=>$vars['alert_email_id'],
            'default_smtp_id'=>$vars['default_smtp_id'],
            'admin_email'=>$vars['admin_email'],
            'enable_auto_cron'=>isset($vars['enable_auto_cron'])?1:0,
            'enable_mail_polling'=>isset($vars['enable_mail_polling'])?1:0,
            'strip_quoted_reply'=>isset($vars['strip_quoted_reply'])?1:0,
            'use_email_priority'=>isset($vars['use_email_priority'])?1:0,
            'accept_unregistered_email'=>isset($vars['accept_unregistered_email'])?1:0,
            'add_email_collabs'=>isset($vars['add_email_collabs'])?1:0,
            'reply_separator'=>$vars['reply_separator'],
            'email_attachments'=>isset($vars['email_attachments'])?1:0,
         ));
    }

    function getLogo($site) {
        $id = $this->get("{$site}_logo_id", false);
        return ($id) ? AttachmentFile::lookup($id) : null;
    }
    function getClientLogo() {
        return $this->getLogo('client');
    }
    function getLogoId($site) {
        return $this->get("{$site}_logo_id", false);
    }
    function getClientLogoId() {
        return $this->getLogoId('client');
    }

    function updatePagesSettings($vars, &$errors) {
        global $ost;

        $f=array();
        $f['landing_page_id'] = array('type'=>'int',   'required'=>1, 'error'=>'required');
        $f['offline_page_id'] = array('type'=>'int',   'required'=>1, 'error'=>'required');
        $f['thank-you_page_id'] = array('type'=>'int',   'required'=>1, 'error'=>'required');

        if ($_FILES['logo']) {
            $error = false;
            list($logo) = AttachmentFile::format($_FILES['logo']);
            if (!$logo)
                ; // Pass
            elseif ($logo['error'])
                $errors['logo'] = $logo['error'];
            elseif (!($id = AttachmentFile::uploadLogo($logo, $error)))
                $errors['logo'] = sprintf(__('Unable to upload logo image: %s'), $error);
        }

        $company = $ost->company;
        $company_form = $company->getForm();
        $company_form->setSource($_POST);
        if (!$company_form->isValid())
            $errors += $company_form->errors();

        if(!Validator::process($f, $vars, $errors) || $errors)
            return false;

        $company_form->save();

        if (isset($vars['delete-logo']))
            foreach ($vars['delete-logo'] as $id)
                if (($vars['selected-logo'] != $id)
                        && ($f = AttachmentFile::lookup($id)))
                    $f->delete();

        return $this->updateAll(array(
            'landing_page_id' => $vars['landing_page_id'],
            'offline_page_id' => $vars['offline_page_id'],
            'thank-you_page_id' => $vars['thank-you_page_id'],
            'client_logo_id' => (
                (is_numeric($vars['selected-logo']) && $vars['selected-logo'])
                ? $vars['selected-logo'] : false),
           ));
    }

    function updateAutoresponderSettings($vars, &$errors) {

        if($errors) return false;

        return $this->updateAll(array(
            'ticket_autoresponder'=>isset($vars['ticket_autoresponder']) ? 1 : 0,
            'message_autoresponder'=>isset($vars['message_autoresponder']) ? 1 : 0,
            'message_autoresponder_collabs'=>isset($vars['message_autoresponder_collabs']) ? 1 : 0,
            'ticket_notice_active'=>isset($vars['ticket_notice_active']) ? 1 : 0,
            'overlimit_notice_active'=>isset($vars['overlimit_notice_active']) ? 1 : 0,
        ));
    }


    function updateKBSettings($vars, &$errors) {

        if($errors) return false;

        return $this->updateAll(array(
            'enable_kb'=>isset($vars['enable_kb'])?1:0,
               'enable_premade'=>isset($vars['enable_premade'])?1:0,
        ));
    }


    function updateAlertsSettings($vars, &$errors) {

       if($vars['ticket_alert_active']
                && (!isset($vars['ticket_alert_admin'])
                    && !isset($vars['ticket_alert_dept_manager'])
                    && !isset($vars['ticket_alert_dept_members'])
                    && !isset($vars['ticket_alert_acct_manager']))) {
            $errors['ticket_alert_active']=__('Select recipient(s)');
        }
        if($vars['message_alert_active']
                && (!isset($vars['message_alert_laststaff'])
                    && !isset($vars['message_alert_assigned'])
                    && !isset($vars['message_alert_dept_manager'])
                    && !isset($vars['message_alert_acct_manager']))) {
            $errors['message_alert_active']=__('Select recipient(s)');
        }

        if($vars['note_alert_active']
                && (!isset($vars['note_alert_laststaff'])
                    && !isset($vars['note_alert_assigned'])
                    && !isset($vars['note_alert_dept_manager']))) {
            $errors['note_alert_active']=__('Select recipient(s)');
        }

        if($vars['transfer_alert_active']
                && (!isset($vars['transfer_alert_assigned'])
                    && !isset($vars['transfer_alert_dept_manager'])
                    && !isset($vars['transfer_alert_dept_members']))) {
            $errors['transfer_alert_active']=__('Select recipient(s)');
        }

        if($vars['overdue_alert_active']
                && (!isset($vars['overdue_alert_assigned'])
                    && !isset($vars['overdue_alert_dept_manager'])
                    && !isset($vars['overdue_alert_dept_members']))) {
            $errors['overdue_alert_active']=__('Select recipient(s)');
        }

        if($vars['assigned_alert_active']
                && (!isset($vars['assigned_alert_staff'])
                    && !isset($vars['assigned_alert_team_lead'])
                    && !isset($vars['assigned_alert_team_members']))) {
            $errors['assigned_alert_active']=__('Select recipient(s)');
        }

        if($errors) return false;

        return $this->updateAll(array(
            'ticket_alert_active'=>$vars['ticket_alert_active'],
            'ticket_alert_admin'=>isset($vars['ticket_alert_admin'])?1:0,
            'ticket_alert_dept_manager'=>isset($vars['ticket_alert_dept_manager'])?1:0,
            'ticket_alert_dept_members'=>isset($vars['ticket_alert_dept_members'])?1:0,
            'ticket_alert_acct_manager'=>isset($vars['ticket_alert_acct_manager'])?1:0,
            'message_alert_active'=>$vars['message_alert_active'],
            'message_alert_laststaff'=>isset($vars['message_alert_laststaff'])?1:0,
            'message_alert_assigned'=>isset($vars['message_alert_assigned'])?1:0,
            'message_alert_dept_manager'=>isset($vars['message_alert_dept_manager'])?1:0,
            'message_alert_acct_manager'=>isset($vars['message_alert_acct_manager'])?1:0,
            'note_alert_active'=>$vars['note_alert_active'],
            'note_alert_laststaff'=>isset($vars['note_alert_laststaff'])?1:0,
            'note_alert_assigned'=>isset($vars['note_alert_assigned'])?1:0,
            'note_alert_dept_manager'=>isset($vars['note_alert_dept_manager'])?1:0,
            'assigned_alert_active'=>$vars['assigned_alert_active'],
            'assigned_alert_staff'=>isset($vars['assigned_alert_staff'])?1:0,
            'assigned_alert_team_lead'=>isset($vars['assigned_alert_team_lead'])?1:0,
            'assigned_alert_team_members'=>isset($vars['assigned_alert_team_members'])?1:0,
            'transfer_alert_active'=>$vars['transfer_alert_active'],
            'transfer_alert_assigned'=>isset($vars['transfer_alert_assigned'])?1:0,
            'transfer_alert_dept_manager'=>isset($vars['transfer_alert_dept_manager'])?1:0,
            'transfer_alert_dept_members'=>isset($vars['transfer_alert_dept_members'])?1:0,
            'overdue_alert_active'=>$vars['overdue_alert_active'],
            'overdue_alert_assigned'=>isset($vars['overdue_alert_assigned'])?1:0,
            'overdue_alert_dept_manager'=>isset($vars['overdue_alert_dept_manager'])?1:0,
            'overdue_alert_dept_members'=>isset($vars['overdue_alert_dept_members'])?1:0,
            'send_sys_errors'=>isset($vars['send_sys_errors'])?1:0,
            'send_sql_errors'=>isset($vars['send_sql_errors'])?1:0,
            'send_login_errors'=>isset($vars['send_login_errors'])?1:0,
        ));
    }

    //Used to detect version prior to 1.7 (useful during upgrade)
    /* static */ function getDBVersion() {
        $sql='SELECT `ostversion` FROM '.TABLE_PREFIX.'config '
            .'WHERE id=1';
        return db_result(db_query($sql));
    }
}
?>
