<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');

$gmtime = Misc::gmtime();
?>
<h2><?php echo __('System Settings and Preferences');?> - <span class="ltr">osTicket (<?php echo $cfg->getVersion(); ?>)</span></h2>
<form action="settings.php?t=system" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="system" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo __('System Settings and Preferences'); ?></h4>
                <em><b><?php echo __('General Settings'); ?></b></em>
            </th>
        </tr>
    </thead>
    <tbody>

        <tr>
            <td width="220" class="required"><?php echo __('Helpdesk Status');?>:</td>
            <td>
                <span>
                <label><input type="radio" name="isonline"  value="1"   <?php echo $config['isonline']?'checked="checked"':''; ?> />&nbsp;<b><?php echo __('Online'); ?></b>&nbsp;</label>
                <label><input type="radio" name="isonline"  value="0"   <?php echo !$config['isonline']?'checked="checked"':''; ?> />&nbsp;<b><?php echo __('Offline'); ?></b></label>
                &nbsp;<font class="error"><?php echo $config['isoffline']?'osTicket '.__('Offline'):''; ?></font>
                <i class="help-tip icon-question-sign" href="#helpdesk_status"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="220" class="required"><?php echo __('Helpdesk URL');?>:</td>
            <td>
                <input type="text" size="40" name="helpdesk_url" value="<?php echo $config['helpdesk_url']; ?>">
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['helpdesk_url']; ?></font>
                <i class="help-tip icon-question-sign" href="#helpdesk_url"></i>
        </td>
        </tr>
        <tr>
            <td width="220" class="required"><?php echo __('Helpdesk Name/Title');?>:</td>
            <td><input type="text" size="40" name="helpdesk_title" value="<?php echo $config['helpdesk_title']; ?>">
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['helpdesk_title']; ?></font>
                <i class="help-tip icon-question-sign" href="#helpdesk_name_title"></i>
            </td>
        </tr>
        <tr>
            <td width="220" class="required"><?php echo __('Default Department');?>:</td>
            <td>
                <select name="default_dept_id">
                    <option value="">&mdash; <?php echo __('Select Default Department');?> &mdash;</option>
                    <?php
                    $sql='SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE ispublic=1';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while (list($id, $name) = db_fetch_row($res)){
                            $selected = ($config['default_dept_id']==$id)?'selected="selected"':''; ?>
                            <option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $name; ?> <?php echo __('Dept');?></option>
                        <?php
                        }
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['default_dept_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#default_department"></i>
            </td>
        </tr>

        <tr><td><?php echo __('Default Page Size');?>:</td>
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
                <i class="help-tip icon-question-sign" href="#default_page_size"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Default Log Level');?>:</td>
            <td>
                <select name="log_level">
                    <option value=0 <?php echo $config['log_level'] == 0 ? 'selected="selected"':''; ?>><?php echo __('None (Disable Logger)');?></option>
                    <option value=3 <?php echo $config['log_level'] == 3 ? 'selected="selected"':''; ?>> <?php echo __('DEBUG');?></option>
                    <option value=2 <?php echo $config['log_level'] == 2 ? 'selected="selected"':''; ?>> <?php echo __('WARN');?></option>
                    <option value=1 <?php echo $config['log_level'] == 1 ? 'selected="selected"':''; ?>> <?php echo __('ERROR');?></option>
                </select>
                <font class="error">&nbsp;<?php echo $errors['log_level']; ?></font>
                <i class="help-tip icon-question-sign" href="#default_log_level"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Purge Logs');?>:</td>
            <td>
                <select name="log_graceperiod">
                    <option value=0 selected><?php echo __('Never Purge Logs');?></option>
                    <?php
                    for ($i = 1; $i <=12; $i++) {
                        ?>
                        <option <?php echo $config['log_graceperiod']==$i?'selected="selected"':''; ?> value="<?php echo $i; ?>">
                            <?php echo __('After');?>&nbsp;<?php echo $i; ?>&nbsp;<?php echo ($i>1)?__('Months'):__('Month'); ?></option>
                        <?php
                    } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#purge_logs"></i>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Default Name Formatting'); ?>:</td>
            <td>
                <select name="name_format">
<?php foreach (PersonsName::allFormats() as $n=>$f) {
    list($desc, $func) = $f;
    $selected = ($config['name_format'] == $n) ? 'selected="selected"' : ''; ?>
                    <option value="<?php echo $n; ?>" <?php echo $selected;
                        ?>><?php echo __($desc); ?></option>
<?php } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#default_name_formatting"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('Date and Time Options'); ?></b>&nbsp;
                <i class="help-tip icon-question-sign" href="#date_time_options"></i>
                </em>
            </th>
        </tr>
        <tr><td width="220" class="required"><?php echo __('Time Format');?>:</td>
            <td>
                <input type="text" name="time_format" value="<?php echo $config['time_format']; ?>">
                    &nbsp;<font class="error">*&nbsp;<?php echo $errors['time_format']; ?></font>
                    <em><?php echo Format::date($config['time_format'], $gmtime, $config['tz_offset'], $config['enable_daylight_saving']); ?></em></td>
        </tr>
        <tr><td width="220" class="required"><?php echo __('Date Format');?>:</td>
            <td><input type="text" name="date_format" value="<?php echo $config['date_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['date_format']; ?></font>
                        <em><?php echo Format::date($config['date_format'], $gmtime, $config['tz_offset'], $config['enable_daylight_saving']); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="required"><?php echo __('Date and Time Format');?>:</td>
            <td><input type="text" name="datetime_format" value="<?php echo $config['datetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['datetime_format']; ?></font>
                        <em><?php echo Format::date($config['datetime_format'], $gmtime, $config['tz_offset'], $config['enable_daylight_saving']); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="required"><?php echo __('Day, Date and Time Format');?>:</td>
            <td><input type="text" name="daydatetime_format" value="<?php echo $config['daydatetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['daydatetime_format']; ?></font>
                        <em><?php echo Format::date($config['daydatetime_format'], $gmtime, $config['tz_offset'], $config['enable_daylight_saving']); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="required"><?php echo __('Default Time Zone');?>:</td>
            <td>
                <select name="default_timezone_id">
                    <option value="">&mdash; <?php echo __('Select Default Time Zone');?> &mdash;</option>
                    <?php
                    $sql='SELECT id, offset,timezone FROM '.TIMEZONE_TABLE.' ORDER BY id';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id, $offset, $tz)=db_fetch_row($res)){
                            $sel=($config['default_timezone_id']==$id)?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>GMT %s - %s</option>', $id, $sel, $offset, $tz);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['default_timezone_id']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="220"><?php echo __('Daylight Saving');?>:</td>
            <td>
                <input type="checkbox" name="enable_daylight_saving" <?php echo $config['enable_daylight_saving'] ? 'checked="checked"': ''; ?>><?php echo __('Observe daylight savings');?>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes');?>">
    <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes');?>">
</p>
</form>
