<form action="settings.php?t=general" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="general" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>General Settings</h4>
                <em>Offline mode will disable client interface and only allow admins to login to Staff Control Panel</em>
            </th>
        </tr>
    </thead>
    <tbody>

        <tr>
            <td width="220" class="required">Helpdesk Status:</td>
            <td>
                <input type="radio" name="isonline"  value="1"   <?php echo $config['isonline']?'checked="checked"':''; ?> /><b>Online</b> (Active)
                <input type="radio" name="isonline"  value="0"   <?php echo !$config['isonline']?'checked="checked"':''; ?> /><b>Offline</b> (Disabled)
                &nbsp;<font class="error">&nbsp;<?php echo $config['isoffline']?'osTicket offline':''; ?></font>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">Helpdesk URL:</td>
            <td>
                <input type="text" size="40" name="helpdesk_url" value="<?php echo $config['helpdesk_url']; ?>">
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['helpdesk_url']; ?></font></td>
        </tr>
        <tr>
            <td width="220" class="required">Helpdesk Name/Title:</td>
            <td><input type="text" size="40" name="helpdesk_title" value="<?php echo $config['helpdesk_title']; ?>">
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['helpdesk_title']; ?></font></td>
        </tr>
        <tr>
            <td width="220" class="required">Default Department:</td>
            <td>
                <select name="default_dept_id">
                    <option value="">&mdash; Select Default Department &mdash;</option>
                    <?php
                    $sql='SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE ispublic=1';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while (list($id,$name) = db_fetch_row($res)){
                            $selected = ($config['default_dept_id']==$id)?'selected="selected"':''; ?>
                            <option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $name; ?> Dept</option>
                        <?php
                        }
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['default_dept_id']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">Default Email Templates:</td>
            <td>
                <select name="default_template_id">
                    <option value="">&mdash; Select Default Template &mdash;</option>
                    <?php
                    $sql='SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_TABLE.' WHERE isactive=1 AND cfg_id='.db_input($cfg->getId()).' ORDER BY name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while (list($id,$name) = db_fetch_row($res)){
                            $selected = ($config['default_template_id']==$id)?'selected="selected"':''; ?>
                            <option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php
                        }
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['default_template_id']; ?></font>
            </td>
        </tr>

        <tr><td>Default Page Size:</td>
            <td>
                <select name="max_page_size">
                    <?php
                     $pagelimit=$config['max_page_size'];
                    for ($i = 5; $i <= 50; $i += 5) {
                        ?>
                        <option <?php echo $config['max_page_size']==$i?'selected="selected"':''; ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php
                    } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Default Log Level:</td>
            <td>
                <select name="log_level">
                    <option value=0 <?php echo $config['log_level'] == 0 ? 'selected="selected"':''; ?>>None (Disable Logger)</option>
                    <option value=3 <?php echo $config['log_level'] == 3 ? 'selected="selected"':''; ?>> DEBUG</option>
                    <option value=2 <?php echo $config['log_level'] == 2 ? 'selected="selected"':''; ?>> WARN</option>
                    <option value=1 <?php echo $config['log_level'] == 1 ? 'selected="selected"':''; ?>> ERROR</option>
                </select>
                <font class="error">&nbsp;<?php echo $errors['log_level']; ?></font>
            </td>
        </tr>
        <tr>
            <td>Purge Logs:</td>
            <td>        
                <select name="log_graceperiod">
                    <option value=0 selected>Never Purge Logs</option>
                    <?php
                    for ($i = 1; $i <=12; $i++) {
                        ?>
                        <option <?php echo $config['log_graceperiod']==$i?'selected="selected"':''; ?> value="<?php echo $i; ?>">
                            After&nbsp;<?php echo $i; ?>&nbsp;<?php echo ($i>1)?'Months':'Month'; ?></option>
                        <?php
                    } ?>
                </select>
            </td>
        </tr>
        <tr><td>Password Reset Policy:</th>
            <td>
                <select name="passwd_reset_period">
                   <option value="0"> &mdash; None &mdash;</option>
                  <?php
                    for ($i = 1; $i <= 12; $i++) {
                        echo sprintf('<option value="%d" %s>%s%s</option>',
                                $i,(($config['passwd_reset_period']==$i)?'selected="selected"':''),$i>1?"Every $i ":'',$i>1?' Months':'Monthly');
                    }
                    ?>
                </select>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['passwd_reset_period']; ?></font>
            </td>
        </tr>
        <tr><td>Staff Excessive Logins:</td>
            <td>
                <select name="staff_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['staff_max_logins']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select> failed login attempt(s) allowed before a
                <select name="staff_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['staff_login_timeout']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select> minute lock-out is enforced.
            </td>
        </tr>
        <tr><td>Staff Session Timeout:</td>
            <td>
              <input type="text" name="staff_session_timeout" size=6 value="<?php echo $config['staff_session_timeout']; ?>">
                Maximum idle time in minutes before a staff member must log in again (enter 0 to disable).
            </td>
        </tr>
       <tr><td>Bind Staff Session to IP:</td>
            <td>
              <input type="checkbox" name="staff_ip_binding" <?php echo $config['staff_ip_binding']?'checked="checked"':''; ?>>
              <em>(binds staff session to originating IP address upon login)</em>
            </td>
        </tr>
        <tr><td>Client Excessive Logins:</td>
            <td>
                <select name="client_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['client_max_logins']==$i)?'selected="selected"':''),$i);
                    }

                    ?>
                </select> failed login attempt(s) allowed before a
                <select name="client_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['client_login_timeout']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select> minute lock-out is enforced. 
            </td>
        </tr>

        <tr><td>Client Session Timeout:</td>
            <td>
              <input type="text" name="client_session_timeout" size=6 value="<?php echo $config['client_session_timeout']; ?>">
                &nbsp;Maximum idle time in minutes before a client must log in again (enter 0 to disable).
            </td>
        </tr>
        <tr><td>Clickable URLs:</td>
            <td>
              <input type="checkbox" name="clickable_urls" <?php echo $config['clickable_urls']?'checked="checked"':''; ?>>
               <em>(converts URLs in messages to clickable links)</em>
            </td>
        </tr>
        <tr><td>Enable Auto Cron:</td>
            <td>
              <input type="checkbox" name="enable_auto_cron" <?php echo $config['enable_auto_cron']?'checked="checked"':''; ?>>
                <em>(executes cron jobs based on staff activity - not recommended)</em>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>

