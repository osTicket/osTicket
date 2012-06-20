<?php
$gmtime=Misc::gmtime();
?>
<form action="settings.php?t=dates" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="dates" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Date and Time Options</h4>
                <em>Please refer to <a href="http://php.net/date" target="_blank">PHP Manual</a> for supported parameters.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr><td width="220" class="required">Time Format:</td>
            <td>
                <input type="text" name="time_format" value="<?php echo $config['time_format']; ?>">
                    &nbsp;<font class="error">*&nbsp;<?php echo $errors['time_format']; ?></font>
                    <em><?php echo Format::date($config['time_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving']); ?></em></td>
        </tr>
        <tr><td width="220" class="required">Date Format:</td>
            <td><input type="text" name="date_format" value="<?php echo $config['date_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['date_format']; ?></font>
                        <em><?php echo Format::date($config['date_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving']); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="required">Date &amp; Time Format:</td>
            <td><input type="text" name="datetime_format" value="<?php echo $config['datetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['datetime_format']; ?></font>
                        <em><?php echo Format::date($config['datetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving']); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="required">Day, Date &amp; Time Format:</td>
            <td><input type="text" name="daydatetime_format" value="<?php echo $config['daydatetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['daydatetime_format']; ?></font>
                        <em><?php echo Format::date($config['daydatetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving']); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="required">Default Time Zone:</td>
            <td>
                <select name="default_timezone_id">
                    <option value="">&mdash; Select Default Time Zone &mdash;</option>
                    <?php
                    $sql='SELECT id, offset,timezone FROM '.TIMEZONE_TABLE.' ORDER BY id';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$offset, $tz)=db_fetch_row($res)){
                            $sel=($config['default_timezone_id']==$id)?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>GMT %s - %s</option>',$id,$sel,$offset,$tz);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['default_timezone_id']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="220">Daylight Saving:</td>
            <td>
                <input type="checkbox" name="enable_daylight_saving" <?php echo $config['enable_daylight_saving'] ? 'checked="checked"': ''; ?>>Observe daylight savings
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
