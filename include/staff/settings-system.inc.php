<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');

$gmtime = Misc::gmtime();
?>
<h2><?php echo __('System Settings and Preferences');?> <small>— <span class="ltr">osTicket (<?php echo $cfg->getVersion(); ?>)</span></small></h2>
<form action="settings.php?t=system" method="post" class="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="system" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
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
                <select name="default_dept_id" data-quick-add="department">
                    <option value="">&mdash; <?php echo __('Select Default Department');?> &mdash;</option>
                    <?php
                    if (($depts=Dept::getPublicDepartments())) {
                        foreach ($depts as $id => $name) {
                            $selected = ($config['default_dept_id']==$id)?'selected="selected"':''; ?>
                            <option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php
                        }
                    } ?>
                    <option value="0" data-quick-add>&mdash; <?php echo __('Add New');?> &mdash;</option>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['default_dept_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#default_department"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Force HTTPS'); ?>:</td>
            <td>
                <input type="checkbox" name="force_https" <?php
                echo $config['force_https'] ? 'checked="checked"' : ''; ?>>
                <?php echo __('Force all requests through HTTPS.'); ?>
                <font class="error"><?php echo $errors['force_https']; ?></font>
                <i class="help-tip icon-question-sign" href="#force_https"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Collision Avoidance Duration'); ?>:</td>
            <td>
                <input type="text" name="autolock_minutes" size=4 value="<?php echo $config['autolock_minutes']; ?>">
                <font class="error"><?php echo $errors['autolock_minutes']; ?></font>&nbsp;<?php echo __('minutes'); ?>
                &nbsp;<i class="help-tip icon-question-sign" href="#collision_avoidance"></i>
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
                            <?php echo sprintf(_N('After %d month', 'After %d months', $i), $i);?>
                        </option>
                        <?php
                    } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#purge_logs"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Show Avatars'); ?>:</td>
            <td>
                <input type="checkbox" name="enable_avatars" <?php
                echo $config['enable_avatars'] ? 'checked="checked"' : ''; ?>>
                <?php echo __('Show Avatars on thread view.'); ?>
                <i class="help-tip icon-question-sign" href="#enable_avatars"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Enable Rich Text'); ?>:</td>
            <td>
                <input type="checkbox" name="enable_richtext" <?php
                echo $config['enable_richtext'] ? 'checked="checked"' : ''; ?>>
                <?php echo __('Enable html in thread entries and email correspondence.'); ?>
                <i class="help-tip icon-question-sign" href="#enable_richtext"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Allow System iFrame'); ?>:</td>
            <td><input type="text" size="40" name="allow_iframes" value="<?php echo $config['allow_iframes']; ?>"
                    placeholder="eg. https://domain.tld, *.domain.tld">
                <i class="help-tip icon-question-sign" href="#allow_iframes"></i>
            <?php if ($errors['allow_iframes']) { ?>
                <br>
                <font class="error">&nbsp;<?php echo $errors['allow_iframes']; ?></font>
            <?php } ?>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Embedded Domain Whitelist'); ?>:</td>
            <td><input type="text" size="40" name="embedded_domain_whitelist"
                    value="<?php echo $config['embedded_domain_whitelist']; ?>"
                    placeholder="eg. domain.tld, sub.domain.tld">
                <i class="help-tip icon-question-sign" href="#embedded_domain_whitelist"></i>
            <?php if ($errors['embedded_domain_whitelist']) { ?>
                <br>
                <font class="error">&nbsp;<?php echo $errors['embedded_domain_whitelist']; ?></font>
            <?php } ?>
            </td>
        </tr>
        <tr>
            <td><?php echo __('ACL'); ?>:</td>
            <td><input type="text" size="40" name="acl" value="<?php echo $config['acl']; ?>"
                    placeholder="eg. 192.168.1.1, 192.168.2.2, 192.168.3.3">
                &nbsp;Apply To:
                <select name="acl_backend">
                    <?php foreach($cfg->getACLBackendOpts() as $k=>$v) { ?>
                    <option <?php if ($cfg->getACLBackend() == $k) echo 'selected="selected"'; ?>
                    value="<?php echo $k; ?>">
                        <?php echo $v; ?>
                    </option>
                    <?php } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#acl"></i>
            <?php if ($errors['acl']) { ?>
                <br>
                <font class="error">&nbsp;<?php echo $errors['acl']; ?></font>
            <?php } ?>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('Date and Time Options'); ?></b>&nbsp;
                <i class="help-tip icon-question-sign" href="#date_time_options"></i>
                </em>
            </th>
        </tr>
<?php if (extension_loaded('intl')) { ?>
        <tr><td width="220" class="required"><?php echo __('Default Locale');?>:</td>
            <td>
                <select name="default_locale">
                    <option value=""><?php echo __('Use Language Preference'); ?></option>
                    <?php
                    foreach (Internationalization::allLocales() as $code=>$name) { ?>
                    <option value="<?php echo $code; ?>" <?php
                        if ($code == $config['default_locale'])
                            echo 'selected="selected"';
                    ?>><?php echo $name; ?></option>

                    <?php
                    } ?>
                </select>
            </td>
        </tr>
<?php } ?>
        <tr><td width="220" class="required"><?php echo __('Default Time Zone');?>:</td>
            <td>
                <?php
                $TZ_TIMEZONE = $config['default_timezone'];
                $TZ_NAME = 'default_timezone';
                $TZ_ALLOW_DEFAULT = false;
                include STAFFINC_DIR.'templates/timezone.tmpl.php'; ?>
                <div class="error"><?php echo $errors['default_timezone']; ?></div>
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
                <input type="text" name="time_format" value="<?php echo $config['time_format']; ?>" class="date-format-preview">
                    &nbsp;<font class="error">*&nbsp;<?php echo $errors['time_format']; ?></font>
                    <em><?php echo Format::time(null, false); ?></em>
                <span class="faded date-format-preview" data-for="time_format">
                    <?php echo Format::time('now'); ?>
                </span>
            </td>
        </tr>
        <tr><td width="220" class="indented required"><?php echo __('Date Format');?>:</td>
            <td><input type="text" name="date_format" value="<?php echo $config['date_format']; ?>" class="date-format-preview">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['date_format']; ?></font>
                        <em><?php echo Format::date(null, false); ?></em>
                <span class="faded date-format-preview" data-for="date_format">
                    <?php echo Format::date('now'); ?>
                </span>
            </td>
        </tr>
        <tr><td width="220" class="indented required"><?php echo __('Date and Time Format');?>:</td>
            <td><input type="text" name="datetime_format" value="<?php echo $config['datetime_format']; ?>" class="date-format-preview">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['datetime_format']; ?></font>
                        <em><?php echo Format::datetime(null, false); ?></em>
                <span class="faded date-format-preview" data-for="datetime_format">
                    <?php echo Format::datetime('now'); ?>
                </span>
            </td>
        </tr>
        <tr><td width="220" class="indented required"><?php echo __('Day, Date and Time Format');?>:</td>
            <td><input type="text" name="daydatetime_format" value="<?php echo $config['daydatetime_format']; ?>" class="date-format-preview">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['daydatetime_format']; ?></font>
                        <em><?php echo Format::daydatetime(null, false); ?></em>
                <span class="faded date-format-preview" data-for="daydatetime_format">
                    <?php echo Format::daydatetime('now'); ?>
                </span>
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <td width="220">
                <?php echo sprintf('%s %s', __('Default'), __('Schedule'));?>:
            </td>
            <td>
                <select name="schedule_id">
                    <option value="0" selected="selected" >&mdash; <?php
                    echo __('None');?> &mdash;</option>
                    <?php
                    if ($schedules=BusinessHoursSchedule::getSchedules()) {
                        foreach ($schedules as $s) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $s->getId(), ($config['schedule_id']==$s->getId()) ? 'selected="selected"' : '', $s->getName());
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error"><?php echo $errors['schedule_id'];
                ?></span>&nbsp;<i class="help-tip icon-question-sign"
                href="#default_schedule"></i>
            </td>
        </tr>
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
            foreach ($cfg->getSecondaryLanguages() as $lang) {
                $info = Internationalization::getLanguageInfo($lang); ?>
            <div class="secondary_lang" style="cursor:move">
            <i class="icon-sort"></i>&nbsp;
            <span class="flag flag-<?php echo $info['flag']; ?>"></span>&nbsp;
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
        <tr>
            <th colspan="2">
                <em><b><?php echo __('Attachments Storage and Settings');?></b>:<i
                class="help-tip icon-question-sign" href="#attachments"></i></em>
            </th>
        </tr>
        <tr>
            <td width="180"><?php echo __('Store Attachments'); ?>:</td>
            <td><select name="default_storage_bk"><?php
                if (($bks = FileStorageBackend::allRegistered())) {
                    foreach ($bks as $char=>$class) {
                        $selected = $config['default_storage_bk'] == $char
                            ? 'selected="selected"' : '';
                        ?><option <?php echo $selected; ?> value="<?php echo $char; ?>"
                        ><?php echo $class::$desc; ?></option><?php
                    }
                } else {
                 echo sprintf('<option value="">%s</option>',
                         __('Select Storage Backend'));
                }?>
                </select>
                &nbsp;<font class="error">*&nbsp;<?php echo
                $errors['default_storage_bk']; ?></font>
                <i class="help-tip icon-question-sign"
                href="#default_storage_bk"></i>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __(
                // Maximum size for agent-uploaded files (via SCP)
                'Agent Maximum File Size');?>:</td>
            <td>
                <select name="max_file_size">
                    <option value="262144">&mdash; <?php echo __('Small'); ?> &mdash;</option>
                    <?php $next = 512 << 10;
                    $max = strtoupper(ini_get('upload_max_filesize'));
                    $limit = (int) $max;
                    if (!$limit) $limit = 2 << 20; # 2M default value
                    elseif (strpos($max, 'K')) $limit <<= 10;
                    elseif (strpos($max, 'M')) $limit <<= 20;
                    elseif (strpos($max, 'G')) $limit <<= 30;
                    while ($next <= $limit) {
                        // Select the closest, larger value (in case the
                        // current value is between two)
                        $diff = $next - $config['max_file_size'];
                        $selected = ($diff >= 0 && $diff < $next / 2)
                            ? 'selected="selected"' : ''; ?>
                        <option value="<?php echo $next; ?>" <?php echo $selected;
                             ?>><?php echo Format::file_size($next);
                             ?></option><?php
                        $next *= 2;
                    }
                    // Add extra option if top-limit in php.ini doesn't fall
                    // at a power of two
                    if ($next < $limit * 2) {
                        $selected = ($limit == $config['max_file_size'])
                            ? 'selected="selected"' : ''; ?>
                        <option value="<?php echo $limit; ?>" <?php echo $selected;
                             ?>><?php echo Format::file_size($limit);
                             ?></option><?php
                    }
                    ?>
                </select>
                <i class="help-tip icon-question-sign" href="#max_file_size"></i>
                <div class="error"><?php echo $errors['max_file_size']; ?></div>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Login required');?>:</td>
            <td>
                <input type="checkbox" name="files_req_auth" <?php
                    if ($config['files_req_auth']) echo 'checked="checked"';
                    ?> />
                <?php echo __('Require login to view any attachments'); ?>
                <i class="help-tip icon-question-sign" href="#files_req_auth"></i>
            </td>
        </tr>
    </tbody>
</table>
<p style="text-align:center;">
    <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes');?>">
    <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes');?>">
</p>
</form>
<script type="text/javascript">
$(function() {
    $('#secondary_langs').sortable({
        cursor: 'move'
    });
    var prev = [];
    $('input.date-format-preview').keyup(function() {
        var name = $(this).attr('name'),
            div = $('span.date-format-preview[data-for='+name+']'),
            current = $(this).val();
        if (prev[name] && prev[name] == current)
            return;
        prev[name] = current;
        div.text('...');
        $.get('ajax.php/config/date-format', {format:$(this).val()})
            .done(function(html) { div.html(html); });
    });
});
</script>
