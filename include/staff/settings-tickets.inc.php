<form action="settings.php?t=tickets" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="tickets" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Ticket Settings and Options</h4>
                <em>Global ticket settings and options.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr><td width="220" class="required">Ticket IDs:</td>
            <td>
                <input type="radio" name="random_ticket_ids"  value="0" <?php echo !$config['random_ticket_ids']?'checked="checked"':''; ?> />
                Sequential
                <input type="radio" name="random_ticket_ids"  value="1" <?php echo $config['random_ticket_ids']?'checked="checked"':''; ?> />
                Random  <em>(highly recommended)</em>
            </td>
        </tr>

        <tr>
            <td width="180" class="required">
                Default SLA:
            </td>
            <td>
                <select name="default_sla_id">
                    <option value="0">&mdash; None &mdash;</option>
                    <?php
                    $sql='SELECT id,name FROM '.SLA_TABLE.' sla ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($config['default_sla_id'] && $id==$config['default_sla_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['default_sla_id']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">Default Priority:</td>
            <td>
                <select name="default_priority_id">
                    <?php
                    $priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
                    while (list($id,$tag) = db_fetch_row($priorities)){ ?>
                        <option value="<?php echo $id; ?>"<?php echo ($config['default_priority_id']==$id)?'selected':''; ?>><?php echo $tag; ?></option>
                    <?php
                    } ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['default_priority_id']; ?></span>
             </td>
        </tr>
        <tr>
            <td width="180">Web Tickets Priority</td>
            <td>
                <input type="checkbox" name="allow_priority_change" value="1" <?php echo $config['allow_priority_change'] ?'checked="checked"':''; ?>>
                <em>(Allow user to overwrite/set priority)</em>
            </td>
        </tr>
        <tr>
            <td width="180">Emailed Tickets Priority</td>
            <td>
                <input type="checkbox" name="use_email_priority" value="1" <?php echo $config['use_email_priority'] ?'checked="checked"':''; ?> >
                <em>(Use email priority when available)</em>
            </td>
        </tr>
        <tr>
            <td width="180">Show Related Tickets</td>
            <td>
                <input type="checkbox" name="show_related_tickets" value="1" <?php echo $config['show_related_tickets'] ?'checked="checked"':''; ?> >
                <em>(Show all related tickets on user login - otherwise access is restricted to one ticket view per login)</em>
            </td>
        </tr>        
        <tr>
            <td width="180">Show Notes Inline</td>
            <td>
                <input type="checkbox" name="show_notes_inline" value="1" <?php echo $config['show_notes_inline'] ?'checked="checked"':''; ?> >
                <em>(Show internal notes  inline)</em>
              </td>
        </tr>  
        <tr>
            <td>Human Verification:</td>
            <td>
                <input type="checkbox" name="enable_captcha" <?php echo $config['enable_captcha']?'checked="checked"':''; ?>>
                Enable CAPTCHA on new web tickets.<em>(requires GDLib)</em> &nbsp;<font class="error">&nbsp;<?php echo $errors['enable_captcha']; ?></font><br/>
            </td>
        </tr>
        <tr>
            <td>Maximum <b>Open</b> Tickets:</td>
            <td>
                <input type="text" name="max_open_tickets" size=4 value="<?php echo $config['max_open_tickets']; ?>">
                per email/user. <em>(Helps with spam and email flood control - enter 0 for unlimited)</em>
            </td>
        </tr>
        <tr>
            <td>Ticket Auto-lock Time:</td>
            <td>
                <input type="text" name="autolock_minutes" size=4 value="<?php echo $config['autolock_minutes']; ?>">
                <font class="error"><?php echo $errors['autolock_minutes']; ?></font>
                <em>(Minutes to lock a ticket on activity - enter 0 to disable locking)</em>
            </td>
        </tr>
        <tr>
            <td>Reopened Tickets:</td>
            <td>
                <input type="checkbox" name="auto_assign_reopened_tickets" <?php echo $config['auto_assign_reopened_tickets']?'checked="checked"':''; ?>>
                Auto-assign reopened tickets to the last available respondent.
            </td>
        </tr>
        <tr>
            <td>Assigned Tickets:</td>
            <td>
                <input type="checkbox" name="show_assigned_tickets" <?php echo $config['show_assigned_tickets']?'checked="checked"':''; ?>>
                Show assigned tickets on open queue.
            </td>
        </tr>
        <tr>
            <td>Answered Tickets:</td>
            <td>
                <input type="checkbox" name="show_answered_tickets" <?php echo $config['show_answered_tickets']?'checked="checked"':''; ?>>
                Show answered tickets on open queue.
            </td>
        </tr>
        <tr>
            <td>Ticket Activity Log:</td>
            <td>
                <input type="checkbox" name="log_ticket_activity" <?php echo $config['log_ticket_activity']?'checked="checked"':''; ?>>
                Log ticket activity as internal notes.
            </td>
        </tr>
        <tr>
            <td>Staff Identity Masking:</td>
            <td>
                <input type="checkbox" name="hide_staff_name" <?php echo $config['hide_staff_name']?'checked="checked"':''; ?>>
                Hide staff's name on responses.
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>

