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
        <tr><td width="220" class="required"><?php echo __('Default Locale');?>:</td>
            <td>
                <select name="default_locale">
                    <option value=""><?php echo __('Use Language Preference'); ?></option>
<?php foreach (Internationalization::allLocales() as $code=>$name) { ?>
                    <option value="<?php echo $code; ?>" <?php
                        if ($code == $config['default_locale'])
                            echo 'selected="selected"';
                    ?>><?php echo $name; ?></option>
<?php } ?>
                </select>
            </td>
        </tr>
        <tr><td width="220" class="required"><?php echo __('Default Time Zone');?>:</td>
            <td>
                <select name="default_timezone" multiple="multiple" id="timezone-dropdown">
<?php foreach (DateTimeZone::listIdentifiers() as $zone) { ?>
                    <option value="<?php echo $zone; ?>" <?php
                    if ($config['default_timezone'] == $zone)
                        echo 'selected="selected"';
                    ?>><?php echo $zone; ?></option>
<?php } ?>
                </select>
                <button class="action-button" onclick="javascript:
    $('head').append($('<script>').attr('src', '<?php
        echo ROOT_PATH; ?>/js/jstz.min.js'));
    var recheck = setInterval(function() {
        if (window.jstz !== undefined) {
            clearInterval(recheck);
            var zone = jstz.determine();
            $('#timezone-dropdown').multiselect('widget')
                .find('[value=\'' + zone.name() + '\']')
                .trigger('click');
        }
    }, 200);
    return false;"><i class="icon-map-marker"></i> <?php echo __('Auto Detect'); ?></button>
            </td>
        </tr>
        <tr><td width="220" class="required"><?php echo __('Date and Time Format');?>:</td>
            <td>
                <select name="date_formats" onchange="javascript:
    $('#advanced-time').toggle($(this).find(':selected').val() == 'custom');
">
<?php foreach (array(
    '' => __('Locale Defaults'),
    '24' => __('Locale Defaults, 24-hour Time'),
    'custom' => '— '.__("Advanced").' —',
) as $v=>$name) { ?>
                    <option value="<?php echo $v; ?>" <?php
                    if ($v == $config['date_formats'])
                        echo 'selected="selected"';
                    ?>><?php echo $name; ?></option>
<?php } ?>
                </select>
            </td>
        </tr>

    </tbody>
    <tbody id="advanced-time" <?php if ($config['date_formats'] != 'custom')
        echo 'style="display:none;"'; ?>>
        <tr>
            <td width="220" class="indented required"><?php echo __('Time Format');?>:</td>
            <td>
                <input type="text" name="time_format" value="<?php echo $config['time_format']; ?>">
                    &nbsp;<font class="error">*&nbsp;<?php echo $errors['time_format']; ?></font>
                    <em><?php echo Format::time(null, false); ?></em></td>
        </tr>
        <tr><td width="220" class="indented required"><?php echo __('Date Format');?>:</td>
            <td><input type="text" name="date_format" value="<?php echo $config['date_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['date_format']; ?></font>
                        <em><?php echo Format::date(null, false); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="indented required"><?php echo __('Date and Time Format');?>:</td>
            <td><input type="text" name="datetime_format" value="<?php echo $config['datetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['datetime_format']; ?></font>
                        <em><?php echo Format::datetime(null, false); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="indented required"><?php echo __('Day, Date and Time Format');?>:</td>
            <td><input type="text" name="daydatetime_format" value="<?php echo $config['daydatetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['daydatetime_format']; ?></font>
                        <em><?php echo Format::daydatetime(null, false); ?></em>
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('System Languages'); ?></b>&nbsp;
                <i class="help-tip icon-question-sign" href="#languages"></i>
                </em>
            </th>
        </tr>
        <tr><td><?php echo __('Primary Language'); ?>:</td>
            <td>
        <?php
        $langs = Internationalization::availableLanguages(); ?>
                <select name="system_language">
                    <option value="">&mdash; <?php echo __('Select a Language'); ?> &mdash;</option>
<?php foreach($langs as $l) {
    $selected = ($config['system_language'] == $l['code']) ? 'selected="selected"' : ''; ?>
                    <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                        ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
<?php } ?>
                </select>
                <span class="error">&nbsp;<?php echo $errors['system_language']; ?></span>
                <i class="help-tip icon-question-sign" href="#primary_language"></i>
            </td>
        </tr>
        <tr>
            <td style="vertical-align:top;padding-top:4px;"><?php echo __('Secondary Languages'); ?>:</td>
            <td><div id="secondary_langs" style="width: 300px"><?php
        foreach ($cfg->getSecondaryLanguages() as $lang) { ?>
            <div class="secondary_lang" style="cursor:move">
            <i class="icon-sort"></i>
            <?php echo Internationalization::getLanguageDescription($lang); ?>
            <input type="hidden" name="secondary_langs[]" value="<?php echo $lang; ?>"/>
            <div class="pull-right">
            <a href="#<?php echo $lang; ?>" onclick="javascript:
                if (confirm('<?php echo __('You sure?'); ?>')) {
                    $(this).closest('.secondary_lang')
                        .find('input').remove();
                    $(this).closest('.secondary_lang').slideUp();
                }
                return false;
                "><i class="icon-trash"></i></a>
            </div>
            </div>
<?php   } ?>
            <script type="text/javascript">
            </script>
            </div>
            <i class="icon-plus-sign"></i>&nbsp;
            <select name="add_secondary_language">
                <option value="">&mdash; <?php echo __('Add a Language'); ?> &mdash;</option>
<?php foreach($langs as $l) {
    $selected = ($config['add_secondary_language'] == $l['code']) ? 'selected="selected"' : '';
    if (!$selected && $l['code'] == $cfg->getPrimaryLanguage())
        continue;
    if (!$selected && in_array($l['code'], $cfg->getSecondaryLanguages()))
        continue; ?>
                <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                    ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
<?php } ?>
            </select>
            <span class="error">&nbsp;<?php echo $errors['add_secondary_language']; ?></span>
            <i class="help-tip icon-question-sign" href="#secondary_language"></i>
        </td></tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes');?>">
    <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes');?>">
</p>
</form>
<link rel="stylesheet" href="<?php echo ROOT_PATH; ?>/css/jquery.multiselect.css"/>
<link rel="stylesheet" href="<?php echo ROOT_PATH; ?>/css/jquery.multiselect.filter.css"/>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>/js/jquery.multiselect.filter.min.js"></script>
<script type="text/javascript">
$('#secondary_langs').sortable({
    cursor: 'move'
});
$('#timezone-dropdown').multiselect({
    multiple: false,
    header: <?php echo JsonDataEncoder::encode(__('Time Zones')); ?>,
    noneSelectedText: <?php echo JsonDataEncoder::encode(__('Select Default Time Zone')); ?>,
    selectedList: 1,
    minWidth: 400
}).multiselectfilter({
    placeholder: <?php echo JsonDataEncoder::encode(__('Search')); ?>
});
</script>
