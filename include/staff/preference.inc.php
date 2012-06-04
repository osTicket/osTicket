<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

//Get the config info.
$config=($errors && $_POST)?Format::input($_POST):Format::htmlchars($cfg->getConfig());
//Basic checks for warnings...
$warn=array();
if($config['allow_attachments'] && !$config['upload_dir']) {
    $errors['allow_attachments']='You need to setup upload dir.';    
}else{
    if(!$config['allow_attachments'] && $config['allow_email_attachments'])
        $warn['allow_email_attachments']='*Attachments Disabled.';
    if(!$config['allow_attachments'] && ($config['allow_online_attachments'] or $config['allow_online_attachments_onlogin']))
        $warn['allow_online_attachments']='<br>*Attachments Disabled.';
}

if(!$errors['enable_captcha'] && $config['enable_captcha'] && !extension_loaded('gd'))
    $errors['enable_captcha']='GD required for captcha to work';
    

//Not showing err on post to avoid alarming the user...after an update.
if(!$errors['err'] &&!$msg && $warn )
    $errors['err']='Possible errors detected, please check the warnings below';
    
$gmtime=Misc::gmtime();
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE ispublic=1');
$templates=db_query('SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_TABLE.' WHERE cfg_id='.db_input($cfg->getId()));
?>
<div class="msg">System Preferences and Settings&nbsp;&nbsp;(v<?php echo $config['ostversion']; ?>)</div>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
 <form action="admin.php?t=pref" method="post">
 <input type="hidden" name="t" value="pref">
 <tr><td>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header" ><td colspan=2>General Settings</td></tr>
        <tr class="subheader">
            <td colspan=2">Offline mode will disable client interface and <b>only</b> allow <b>super admins</b> to login to Staff Control Panel</td>
        </tr>
        <tr><th><b>Helpdesk Status</b></th>
            <td>
                <input type="radio" name="isonline"  value="1"   <?php echo $config['isonline']?'checked':''; ?> /><b>Online</b> (Active)
                <input type="radio" name="isonline"  value="0"   <?php echo !$config['isonline']?'checked':''; ?> /><b>Offline</b> (Disabled)
                &nbsp;<font class="warn">&nbsp;<?php echo $config['isoffline']?'osTicket offline':''; ?></font>
            </td>
        </tr>
        <tr><th>Helpdesk URL:</th>
            <td>
                <input type="text" size="40" name="helpdesk_url" value="<?php echo $config['helpdesk_url']; ?>"> 
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['helpdesk_url']; ?></font></td>
        </tr>
        <tr><th>Helpdesk Name/Title:</th>
            <td><input type="text" size="40" name="helpdesk_title" value="<?php echo $config['helpdesk_title']; ?>"> </td>
        </tr>
        <tr><th>Default Email Templates:</th>
            <td>
                <select name="default_template_id">
                    <option value=0>Select Default Template</option>
                    <?php
                    while (list($id,$name) = db_fetch_row($templates)){
                        $selected = ($config['default_template_id']==$id)?'SELECTED':''; ?>
                        <option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>
                    <?php
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['default_template_id']; ?></font>
            </td>
        </tr>
        <tr><th>Default Department:</th>
            <td>
                <select name="default_dept_id">
                    <option value=0>Select Default Dept</option>
                    <?php
                    while (list($id,$name) = db_fetch_row($depts)){
                    $selected = ($config['default_dept_id']==$id)?'SELECTED':''; ?>
                    <option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $name; ?> Dept</option>
                    <?php
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['default_dept_id']; ?></font>
            </td>
        </tr>
        <tr><th>Default Page Size:</th>
            <td>
                <select name="max_page_size">
                    <?php
                     $pagelimit=$config['max_page_size'];
                    for ($i = 5; $i <= 50; $i += 5) {
                        ?>
                        <option <?php echo $config['max_page_size'] == $i ? 'SELECTED':''; ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php
                    } ?>
                </select>
            </td>
        </tr>
        <tr><th>System Log Level:</th>
            <td>
                <select name="log_level">
                    <option value=0 <?php echo $config['log_level'] == 0 ? 'selected="selected"':''; ?>>None (Disable Logger)</option>
                    <option value=3 <?php echo $config['log_level'] == 3 ? 'selected="selected"':''; ?>> DEBUG</option>
                    <option value=2 <?php echo $config['log_level'] == 2 ? 'selected="selected"':''; ?>> WARN</option>
                    <option value=1 <?php echo $config['log_level'] == 1 ? 'selected="selected"':''; ?>> ERROR</option>
                </select>
                &nbsp;Purge logs after
                <select name="log_graceperiod">
                    <option value=0 selected> None (Disable)</option>
                    <?php
                    for ($i = 1; $i <=12; $i++) {
                        ?>
                        <option <?php echo $config['log_graceperiod'] == $i ? 'SELECTED':''; ?> value="<?php echo $i; ?>"><?php echo $i; ?>&nbsp;<?php echo ($i>1)?'Months':'Month'; ?></option>
                        <?php
                    } ?>
                </select>
            </td>
        </tr>
        <tr><th>Staff Excessive Logins:</th>
            <td>
                <select name="staff_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['staff_max_logins']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select> attempt(s) allowed
                &nbsp;before a
                <select name="staff_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['staff_login_timeout']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select> min. timeout (penalty in minutes)
            </td>
        </tr>
        <tr><th>Staff Session Timeout:</th>
            <td>
              <input type="text" name="staff_session_timeout" size=6 value="<?php echo $config['staff_session_timeout']; ?>">
                (<i>Staff's max Idle time in minutes. Enter 0 to disable timeout</i>)
            </td>
        </tr>
       <tr><th>Bind Staff Session to IP:</th>
            <td>
              <input type="checkbox" name="staff_ip_binding" <?php echo $config['staff_ip_binding']?'checked':''; ?>>
               Bind staff's session to login IP.
            </td>
        </tr>

        <tr><th>Client Excessive Logins:</th>
            <td>
                <select name="client_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['client_max_logins']==$i)?'selected="selected"':''),$i);
                    }

                    ?>
                </select> attempt(s) allowed
                &nbsp;before a
                <select name="client_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['client_login_timeout']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select> min. timeout (penalty in minutes)
            </td>
        </tr>

        <tr><th>Client Session Timeout:</th>
            <td>
              <input type="text" name="client_session_timeout" size=6 value="<?php echo $config['client_session_timeout']; ?>">
                (<i>Client's max Idle time in minutes. Enter 0 to disable timeout</i>)
            </td>
        </tr>
        <tr><th>Clickable URLs:</th>
            <td>
              <input type="checkbox" name="clickable_urls" <?php echo $config['clickable_urls']?'checked':''; ?>>
                Make URLs clickable
            </td>
        </tr>
        <tr><th>Enable Auto Cron:</th>
            <td>
              <input type="checkbox" name="enable_auto_cron" <?php echo $config['enable_auto_cron']?'checked':''; ?>>
                Enable cron call on staff's activity
            </td>
        </tr>
    </table>
    
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Date &amp; Time</td></tr>
        <tr class="subheader">
            <td colspan=2>Please refer to <a href="http://php.net/date" target="_blank">PHP Manual</a> for supported parameters.</td>
        </tr>
        <tr><th>Time Format:</th>
            <td>
                <input type="text" name="time_format" value="<?php echo $config['time_format']; ?>">
                    &nbsp;<font class="error">*&nbsp;<?php echo $errors['time_format']; ?></font>
                    <i><?php echo Format::date($config['time_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving']); ?></i></td>
        </tr>
        <tr><th>Date Format:</th>
            <td><input type="text" name="date_format" value="<?php echo $config['date_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['date_format']; ?></font>
                        <i><?php echo Format::date($config['date_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving']); ?></i>
            </td>
        </tr>
        <tr><th>Date &amp; Time Format:</th>
            <td><input type="text" name="datetime_format" value="<?php echo $config['datetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['datetime_format']; ?></font>
                        <i><?php echo Format::date($config['datetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving']); ?></i>
            </td>
        </tr>
        <tr><th>Day, Date &amp; Time Format:</th>
            <td><input type="text" name="daydatetime_format" value="<?php echo $config['daydatetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['daydatetime_format']; ?></font>
                        <i><?php echo Format::date($config['daydatetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving']); ?></i>
            </td>
        </tr>
        <tr><th>Default Timezone:</th>
            <td>
                <select name="timezone_offset">
                    <?php
                    $gmoffset = date("Z") / 3600; //Server's offset.
                    echo"<option value=\"$gmoffset\">Server Time (GMT $gmoffset:00)</option>"; //Default if all fails.
                    $timezones= db_query('SELECT offset,timezone FROM '.TIMEZONE_TABLE);
                    while (list($offset,$tz) = db_fetch_row($timezones)){
                        $selected = ($config['timezone_offset'] ==$offset) ?'SELECTED':'';
                        $tag=($offset)?"GMT $offset ($tz)":" GMT ($tz)";
                        ?>
                        <option value="<?php echo $offset; ?>"<?php echo $selected; ?>><?php echo $tag; ?></option>
                        <?php
                    } ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>Daylight Saving:</th>
            <td>
                <input type="checkbox" name="enable_daylight_saving" <?php echo $config['enable_daylight_saving'] ? 'checked': ''; ?>>Observe daylight savings
            </td>
        </tr>
    </table>
   
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Ticket Options &amp; Settings</td></tr>
        <tr class="subheader"><td colspan=2>If enabled ticket lock get auto-renewed on form activity.</td></tr>
        <tr><th valign="top">Ticket IDs:</th>
            <td>
                <input type="radio" name="random_ticket_ids"  value="0"   <?php echo !$config['random_ticket_ids']?'checked':''; ?> /> Sequential
                <input type="radio" name="random_ticket_ids"  value="1"   <?php echo $config['random_ticket_ids']?'checked':''; ?> />Random  (recommended)
            </td>
        </tr>
        <tr><th valign="top">Ticket Priority:</th>
            <td>
                <select name="default_priority_id">
                    <?php
                    $priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
                    while (list($id,$tag) = db_fetch_row($priorities)){ ?>
                        <option value="<?php echo $id; ?>"<?php echo ($config['default_priority_id']==$id)?'selected':''; ?>><?php echo $tag; ?></option>
                    <?php
                    } ?>
                </select> &nbsp;Default priority<br/>
                <input type="checkbox" name="allow_priority_change" <?php echo $config['allow_priority_change'] ?'checked':''; ?>>
                    Allow user to overwrite/set priority (new web tickets)<br/>
                <input type="checkbox" name="use_email_priority" <?php echo $config['use_email_priority'] ?'checked':''; ?> >
                    Use email priority when available (new emailed tickets)

            </td>
        </tr>
        <tr><th>Maximum <b>Open</b> Tickets:</th>
            <td>
              <input type="text" name="max_open_tickets" size=4 value="<?php echo $config['max_open_tickets']; ?>"> 
                per email. (<i>Helps with spam and flood control. Enter 0 for unlimited</i>)
            </td>
        </tr>
        <tr><th>Auto-Lock Time:</td>
            <td>
              <input type="text" name="autolock_minutes" size=4 value="<?php echo $config['autolock_minutes']; ?>">
                 <font class="error"><?php echo $errors['autolock_minutes']; ?></font>
                (<i>Minutes to lock a ticket on activity. Enter 0 to disable locking</i>)
            </td>
        </tr>
        <tr><th>Ticket Grace Period:</th>
            <td>
              <input type="text" name="overdue_grace_period" size=4 value="<?php echo $config['overdue_grace_period']; ?>">
                (<i>Hours before ticket is marked overdue. Enter 0 to disable aging.</i>)
            </td>
        </tr>
        <tr><th>Reopened Tickets:</th>
            <td>
              <input type="checkbox" name="auto_assign_reopened_tickets" <?php echo $config['auto_assign_reopened_tickets'] ? 'checked': ''; ?>> 
                Auto-assign reopened tickets to last respondent 'available'. (<i> 3 months limit</i>)
            </td>
        </tr>
        <tr><th>Assigned Tickets:</th>
            <td>
              <input type="checkbox" name="show_assigned_tickets" <?php echo $config['show_assigned_tickets']?'checked':''; ?>>
                Show assigned tickets on open queue.
            </td>
        </tr>
        <tr><th>Answered Tickets:</th>
            <td>
              <input type="checkbox" name="show_answered_tickets" <?php echo $config['show_answered_tickets']?'checked':''; ?>>
                Show answered tickets on open queue.
            </td>
        </tr>
        <tr><th>Ticket Activity Log:</th>
            <td>
              <input type="checkbox" name="log_ticket_activity" <?php echo $config['log_ticket_activity']?'checked':''; ?>>
                Log ticket's activity as internal notes.
            </td>
        </tr>
        <tr><th>Staff Identity:</th>
            <td>
              <input type="checkbox" name="hide_staff_name" <?php echo $config['hide_staff_name']?'checked':''; ?>>
                Hide staff's name on responses.
            </td>
        </tr>
        <tr><th>Human Verification:</th>
            <td>
                <?php
                   if($config['enable_captcha'] && !$errors['enable_captcha']) { ?>
                        <img src="../captcha.php" border="0" align="left">&nbsp;
                <?php } ?>
              <input type="checkbox" name="enable_captcha" <?php echo $config['enable_captcha']?'checked':''; ?>>
                Enable captcha on new web tickets.&nbsp;<font class="error">&nbsp;<?php echo $errors['enable_captcha']; ?></font><br/>
            </td>
        </tr>

    </table>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2 >Email Settings</td></tr>
        <tr class="subheader"><td colspan=2>Note that global settings can be disabled at dept/email level.</td></tr>
        <tr><th valign="top"><br><b>Incoming Emails</b>:</th>
            <td><i>For mail fetcher (POP/IMAP) to work you must set a cron job or simply enable auto-cron</i><br/>
                <input type="checkbox" name="enable_mail_fetch" value=1 <?php echo $config['enable_mail_fetch']? 'checked': ''; ?>  > Enable POP/IMAP email fetch
                    &nbsp;&nbsp;(<i>Global setting which can be disabled at email level</i>) <br/>
                <input type="checkbox" name="enable_email_piping" value=1 <?php echo $config['enable_email_piping']? 'checked': ''; ?>  > Enable email piping
                   &nbsp;(<i>You pipe we accept policy</i>)<br/>
                <input type="checkbox" name="strip_quoted_reply" <?php echo $config['strip_quoted_reply'] ? 'checked':''; ?>>
                    Strip quoted reply (<i>depends on the tag below</i>)<br/>
                <input type="text" name="reply_separator" value="<?php echo $config['reply_separator']; ?>"> Reply Separator Tag
                &nbsp;<font class="error">&nbsp;<?php echo $errors['reply_separator']; ?></font>
            </td>
        </tr>
        <tr><th valign="top"><br><b>Outgoing Emails</b>:</th>
            <td>
                <i><b>Default Email:</b> Only applies to outgoing emails with no SMTP settings.</i><br/>
                <select name="default_smtp_id"
                    onChange="document.getElementById('overwrite').style.display=(this.options[this.selectedIndex].value>0)?'block':'none';">
                    <option value=0>Select One</option>
                    <option value=0 selected="selected">None: Use PHP mail function</option>
                    <?php
                    $emails=db_query('SELECT email_id,email,name,smtp_host FROM '.EMAIL_TABLE.' WHERE smtp_active=1');
                    if($emails && db_num_rows($emails)) {
                        while (list($id,$email,$name,$host) = db_fetch_row($emails)){
                            $email=$name?"$name &lt;$email&gt;":$email;
                            $email=sprintf('%s (%s)',$email,$host);
                            ?>
                            <option value="<?php echo $id; ?>"<?php echo ($config['default_smtp_id']==$id)?'selected="selected"':''; ?>><?php echo $email; ?></option>
                        <?php
                        }
                    } ?>
                 </select>&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['default_smtp_id']; ?></font><br/>
                 <span id="overwrite" style="display:<?php echo ($config['default_smtp_id']?'display':'none'); ?>">
                    <input type="checkbox" name="spoof_default_smtp" <?php echo $config['spoof_default_smtp'] ? 'checked':''; ?>>
                        Allow spoofing (No Overwrite).&nbsp;<font class="error">&nbsp;<?php echo $errors['spoof_default_smtp']; ?></font><br/>
                        </span>
             </td>
        </tr>
        <tr><th>Default System Email:</th>
            <td>
                <select name="default_email_id">
                    <option value=0 disabled>Select One</option>
                    <?php
                    $emails=db_query('SELECT email_id,email,name FROM '.EMAIL_TABLE);
                    while (list($id,$email,$name) = db_fetch_row($emails)){ 
                        $email=$name?"$name &lt;$email&gt;":$email;
                        ?>
                     <option value="<?php echo $id; ?>"<?php echo ($config['default_email_id']==$id)?'selected':''; ?>><?php echo $email; ?></option>
                    <?php
                    } ?>
                 </select>
                 &nbsp;<font class="error">*&nbsp;<?php echo $errors['default_email_id']; ?></font></td>
        </tr>
        <tr><th valign="top">Default Alert Email:</th>
            <td>
                <select name="alert_email_id">
                    <option value=0 disabled>Select One</option>
                    <option value=0 selected="selected">Use Default System Email (above)</option>
                    <?php
                    $emails=db_query('SELECT email_id,email,name FROM '.EMAIL_TABLE.' WHERE email_id != '.db_input($config['default_email_id']));
                    while (list($id,$email,$name) = db_fetch_row($emails)){
                        $email=$name?"$name &lt;$email&gt;":$email;
                        ?>
                     <option value="<?php echo $id; ?>"<?php echo ($config['alert_email_id']==$id)?'selected':''; ?>><?php echo $email; ?></option>
                    <?php
                    } ?>
                 </select>
                 &nbsp;<font class="error">*&nbsp;<?php echo $errors['alert_email_id']; ?></font>
                <br/><i>Used to send out alerts and notices to staff.</i>
            </td>
        </tr>
        <tr><th>System Admin Email Address:</th>
            <td>
                <input type="text" size=25 name="admin_email" value="<?php echo $config['admin_email']; ?>">
                    &nbsp;<font class="error">*&nbsp;<?php echo $errors['admin_email']; ?></font></td>
        </tr>
    </table>

    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Autoresponders &nbsp;(Global Setting)</td></tr>
        <tr class="subheader"><td colspan=2">This is global setting which can be disabled at department level.</td></tr>
        <tr><th valign="top">New Ticket:</th>
            <td><i>Autoresponse includes the ticket ID required to check status of the ticket</i><br>
                <input type="radio" name="ticket_autoresponder"  value="1"   <?php echo $config['ticket_autoresponder']?'checked':''; ?> />Enable
                <input type="radio" name="ticket_autoresponder"  value="0"   <?php echo !$config['ticket_autoresponder']?'checked':''; ?> />Disable
            </td>
        </tr>
        <tr><th valign="top">New Ticket by Staff:</th>
            <td><i>Notice sent when staff creates a ticket on behalf of the user (Staff can disable)</i><br>
                <input type="radio" name="ticket_notice_active"  value="1"   <?php echo $config['ticket_notice_active']?'checked':''; ?> />Enable
                <input type="radio" name="ticket_notice_active"  value="0"   <?php echo !$config['ticket_notice_active']?'checked':''; ?> />Disable
            </td>
        </tr>
        <tr><th valign="top">New Message:</th>
            <td><i>Message appended to an existing ticket confirmation</i><br>
                <input type="radio" name="message_autoresponder"  value="1"   <?php echo $config['message_autoresponder']?'checked':''; ?> />Enable
                <input type="radio" name="message_autoresponder"  value="0"   <?php echo !$config['message_autoresponder']?'checked':''; ?> />Disable
            </td>
        </tr>
        <tr><th valign="top">Overlimit notice:</th>
            <td><i>Ticket denied notice sent <b>only once</b> on limit violation to the user.</i><br/>               
                <input type="radio" name="overlimit_notice_active"  value="1"   <?php echo $config['overlimit_notice_active']?'checked':''; ?> />Enable
                <input type="radio" name="overlimit_notice_active"  value="0"   <?php echo !$config['overlimit_notice_active']?'checked':''; ?> />Disable
                <br><i><b>Note:</b> Admin gets alerts on ALL denials by default.</i><br>
            </td>
        </tr>
    </table>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>&nbsp;Alerts &amp; Notices</td></tr>
        <tr class="subheader"><td colspan=2>
            Notices sent to user use 'No Reply Email' whereas alerts to staff use 'Alert Email' set above as FROM address respectively.</td>
        </tr>
        <tr><th valign="top">New Ticket Alert:</th>
            <td>
                <input type="radio" name="ticket_alert_active"  value="1"   <?php echo $config['ticket_alert_active']?'checked':''; ?> />Enable
                <input type="radio" name="ticket_alert_active"  value="0"   <?php echo !$config['ticket_alert_active']?'checked':''; ?> />Disable
                <br><i>Select recipients</i>&nbsp;<font class="error">&nbsp;<?php echo $errors['ticket_alert_active']; ?></font><br>
                <input type="checkbox" name="ticket_alert_admin" <?php echo $config['ticket_alert_admin']?'checked':''; ?>> Admin Email
                <input type="checkbox" name="ticket_alert_dept_manager" <?php echo $config['ticket_alert_dept_manager']?'checked':''; ?>> Department Manager
                <input type="checkbox" name="ticket_alert_dept_members" <?php echo $config['ticket_alert_dept_members']?'checked':''; ?>> Department Members (spammy)
            </td>
        </tr>
        <tr><th valign="top">New Message Alert:</th>
            <td>
              <input type="radio" name="message_alert_active"  value="1"   <?php echo $config['message_alert_active']?'checked':''; ?> />Enable
              <input type="radio" name="message_alert_active"  value="0"   <?php echo !$config['message_alert_active']?'checked':''; ?> />Disable
              <br><i>Select recipients</i>&nbsp;<font class="error">&nbsp;<?php echo $errors['message_alert_active']; ?></font><br>
              <input type="checkbox" name="message_alert_laststaff" <?php echo $config['message_alert_laststaff']?'checked':''; ?>> Last Respondent
              <input type="checkbox" name="message_alert_assigned" <?php echo $config['message_alert_assigned']?'checked':''; ?>> Assigned Staff
              <input type="checkbox" name="message_alert_dept_manager" <?php echo $config['message_alert_dept_manager']?'checked':''; ?>> Department Manager (spammy)
            </td>
        </tr>
        <tr><th valign="top">New Internal Note Alert:</th>
            <td>
              <input type="radio" name="note_alert_active"  value="1"   <?php echo $config['note_alert_active']?'checked':''; ?> />Enable
              <input type="radio" name="note_alert_active"  value="0"   <?php echo !$config['note_alert_active']?'checked':''; ?> />Disable
              <br><i>Select recipients</i>&nbsp;<font class="error">&nbsp;<?php echo $errors['note_alert_active']; ?></font><br>
              <input type="checkbox" name="note_alert_laststaff" <?php echo $config['note_alert_laststaff']?'checked':''; ?>> Last Respondent
              <input type="checkbox" name="note_alert_assigned" <?php echo $config['note_alert_assigned']?'checked':''; ?>> Assigned Staff
              <input type="checkbox" name="note_alert_dept_manager" <?php echo $config['note_alert_dept_manager']?'checked':''; ?>> Department Manager (spammy)
            </td>
        </tr>
        <tr><th valign="top">Overdue Ticket Alert:</th>
            <td>
              <input type="radio" name="overdue_alert_active"  value="1"   <?php echo $config['overdue_alert_active']?'checked':''; ?> />Enable
              <input type="radio" name="overdue_alert_active"  value="0"   <?php echo !$config['overdue_alert_active']?'checked':''; ?> />Disable
              <br><i>Admin Email gets an alert by default. Select additional recipients below</i>&nbsp;<font class="error">&nbsp;<?php echo $errors['overdue_alert_active']; ?></font><br>
              <input type="checkbox" name="overdue_alert_assigned" <?php echo $config['overdue_alert_assigned']?'checked':''; ?>> Assigned Staff
              <input type="checkbox" name="overdue_alert_dept_manager" <?php echo $config['overdue_alert_dept_manager']?'checked':''; ?>> Department Manager
              <input type="checkbox" name="overdue_alert_dept_members" <?php echo $config['overdue_alert_dept_members']?'checked':''; ?>> Department Members (spammy)
            </td>
        </tr>
        <tr><th valign="top">System Errors:</th>
            <td><i>Enabled errors are sent to admin email set above</i><br>
              <input type="checkbox" name="send_sys_errors" <?php echo $config['send_sys_errors']?'checked':'checked'; ?> disabled>System Errors
              <input type="checkbox" name="send_sql_errors" <?php echo $config['send_sql_errors']?'checked':''; ?>>SQL errors
              <input type="checkbox" name="send_login_errors" <?php echo $config['send_login_errors']?'checked':''; ?>>Excessive Login attempts
            </td>
        </tr> 
        
    </table>
 </td></tr>
 <tr>
    <td style="padding:10px 0 10px 240px;">
        <input class="button" type="submit" name="submit" value="Save Changes">
        <input class="button" type="reset" name="reset" value="Reset Changes">
    </td>
 </tr>
 </form>
</table>
