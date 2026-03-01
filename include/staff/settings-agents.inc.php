<?php
if (!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');

?>
<h2><?php echo __('Agents Settings'); ?></h2>
<form action="settings.php?t=agents" method="post" class="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="t" value="agents" >
    <ul class="tabs" id="agents-tabs">
        <li class="active"><a href="#settings">
            <i class="icon-asterisk"></i> <?php echo __('Settings'); ?></a></li>
        <li><a href="#templates">
            <i class="icon-file-text"></i> <?php echo __('Templates'); ?></a></li>
    </ul>
    <div id="agents-tabs_container">
        <div id="settings" class="tab_content">
            <table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
                <tbody>
                    <tr>
                        <th colspan="2">
                            <em><b><?php echo __('General Settings'); ?></b></em>
                        </th>
                    </tr>
                    <tr>
                        <td width="180"><?php echo __('Name Formatting'); ?>:</td>
                        <td>
                            <select name="agent_name_format">
                                <?php foreach (PersonsName::allFormats() as $n=>$f) {
                                list($desc, $func) = $f;
                                $selected = ($config['agent_name_format'] == $n) ? 'selected="selected"' : ''; ?>
                                <option value="<?php echo $n; ?>" <?php echo $selected;
                                ?>><?php echo __($desc); ?></option>
                                <?php } ?>
                            </select>
                            <i class="help-tip icon-question-sign" href="#agent_name_format"></i>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo __('Agent Identity Masking'); ?>:</td>
                        <td>
                            <input type="checkbox" name="hide_staff_name" <?php echo $config['hide_staff_name']?'checked="checked"':''; ?>>
                            <?php echo __("Hide agent's name on responses."); ?>
                            <i class="help-tip icon-question-sign" href="#staff_identity_masking"></i>
                        </td>
                    </tr>
                    <tr>
                        <td width="180"><?php echo __('Avatar Source'); ?>:</td>
                        <td>
                            <select name="agent_avatar">
<?php                       require_once INCLUDE_DIR . 'class.avatar.php';
                            foreach (AvatarSource::allSources() as $id=>$class) {
                                $modes = $class::getModes();
                                if ($modes) {
                                    echo "<optgroup label=\"{$class::getName()}\">";
                                    foreach ($modes as $mid=>$mname) {
                                        $oid = "$id.$mid";
                                        $selected = ($config['agent_avatar'] == $oid) ? 'selected="selected"' : '';
                                        echo "<option {$selected} value=\"{$oid}\">{$class::getName()} / {$mname}</option>";
                                    }
                                    echo "</optgroup>";
                                }
                                else {
                                    $selected = ($config['agent_avatar'] == $id) ? 'selected="selected"' : '';
                                    echo "<option {$selected} value=\"{$id}\">{$class::getName()}</option>";
                                }
                            } ?>
                            </select>
                            <div class="error"><?php echo Format::htmlchars($errors['agent_avatar']); ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo __('Disable Agent Collaborators'); ?>:</td>
                        <td>
                            <input type="checkbox" name="disable_agent_collabs"
                                <?php echo $config['disable_agent_collabs']?'checked="checked"':''; ?>>
                            <?php echo __('Enable'); ?>&nbsp;<i class="help-tip icon-question-sign"
                                href="#disable_agent_collabs"></i>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2">
                            <em><b><?php echo __('Authentication Settings'); ?></b></em>
                        </th>
                    </tr>
                    <tr>
                        <td><?php echo __('Password Policy'); ?>:</td>
                        <td>
                            <select name="agent_passwd_policy">
                            <option value=" "> &mdash; <?php echo __('All Active Policies'); ?> &mdash;</option>
                            <?php
                                foreach (PasswordPolicy::allActivePolicies()
                                        as $P) {
                                echo sprintf('<option value="%s" %s>%s</option>',
                                    $P::$id,
                                    (($config['agent_passwd_policy'] == $P::$id) ? 'selected="selected"' : ''),
                                    $P->getName());
                                }
                            ?>
                            </select>
                            <font class="error"><?php echo
                            $errors['agent_passwd_policy']; ?></font>
                            <i class="help-tip icon-question-sign" href="#agent_password_policy"></i>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo __('Allow Password Resets'); ?>:</td>
                        <td>
                            <input type="checkbox" name="allow_pw_reset" <?php echo $config['allow_pw_reset']?'checked="checked"':''; ?>>
                            &nbsp;<i class="help-tip icon-question-sign" href="#allow_password_resets"></i>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo __('Reset Token Expiration'); ?>:</td>
                        <td>
                            <input type="text" name="pw_reset_window" size="6" value="<?php
                                echo $config['pw_reset_window']; ?>">
                                <em><?php echo __('minutes'); ?></em>
                                <i class="help-tip icon-question-sign" href="#reset_token_expiration"></i>
                                &nbsp;<font class="error"><?php echo $errors['pw_reset_window']; ?></font>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo __('Multifactor Authentication'); ?>:</td>
                        <td>
                            <input type="checkbox" name="require_agent_2fa" <?php
                            echo $config['require_agent_2fa'] ? 'checked="checked"' : ''; ?>>
                            &nbsp;
                            <?php
                            echo __('Require agents to turn on 2FA');
                            ?>
                            &nbsp;<i class="help-tip icon-question-sign"
                            href="#require_agent_2fa"></i>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo __('Agent Excessive Logins'); ?>:</td>
                        <td>
                            <select name="staff_max_logins">
                                <?php
                                    for ($i = 1; $i <= 10; $i++) {
                                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['staff_max_logins']==$i)?'selected="selected"':''), $i);
                                    }
                                ?>
                            </select> <?php echo __(
                                'failed login attempt(s) allowed before a lock-out is enforced'); ?>
                            <br/>
                            <select name="staff_login_timeout">
                                <?php
                                    for ($i = 1; $i <= 10; $i++) {
                                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['staff_login_timeout']==$i)?'selected="selected"':''), $i);
                                    }
                                ?>
                            </select> <?php echo __('minutes locked out'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo __('Agent Session Timeout'); ?>:</td>
                        <td>
                            <input type="text" name="staff_session_timeout" size=6 value="<?php echo $config['staff_session_timeout']; ?>">
                            <?php echo __('minutes'); ?> <em><?php echo __('(0 to disable)'); ?></em>. <i class="help-tip icon-question-sign" href="#staff_session_timeout"></i>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo __('Bind Agent Session to IP'); ?>:</td>
                        <td>
                            <input type="checkbox" name="staff_ip_binding" <?php echo $config['staff_ip_binding']?'checked="checked"':''; ?>>
                            <i class="help-tip icon-question-sign" href="#bind_staff_session_to_ip"></i>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="templates" class="tab_content hidden">
            <table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
                <tbody>
                    <?php
                        $res = db_query('select distinct(`type`), id, notes, name, updated from '
                        .PAGE_TABLE
                        .' where isactive=1 group by `type`');
                        $contents = array();
                        while (list($type, $id, $notes, $name, $u) = db_fetch_row($res))
                        $contents[$type] = array($id, $name, $notes, $u);

                        $manage_content = function($title, $content) use ($contents) {
                        list($id, $name, $notes, $upd) = $contents[$content];
                        $notes = explode('. ', $notes);
                        $notes = $notes[0];
                    ?>
                    <tr>
                        <td colspan="2">
                            <div style="padding:2px 5px">
                                <a href="#ajax.php/content/<?php echo $id; ?>/manage"
                                   onclick="javascript:
                                    $.dialog($(this).attr('href').substr(1), 201);
                                    return false;" class="pull-left">
                                    <i class="icon-file-text icon-2x" style="color:#bbb;"></i>
                                </a>
                                <span style="display:inline-block;width:90%;width:calc(100% - 32px);padding-left:10px;line-height:1.2em">
                                <a href="#ajax.php/content/<?php echo $id; ?>/manage"
                                   onclick="javascript:
                                    $.dialog($(this).attr('href').substr(1), 201, null, {size:'large'});
                                    return false;"><?php
                                    echo Format::htmlchars($title); ?>
                                    </a>
                                </span>
                                <span class="faded"><?php
                                    echo Format::display($notes); ?>
                                    <br />
                                    <em><?php echo sprintf(__('Last Updated %s'), Format::datetime($upd));
                                    ?></em>
                                </span>
                            </div>
                        </td>
                    </tr>
                        <?php
                        }; ?>
                    <tr>
                        <th colspan="2">
                            <em><b><?php echo __(
                            'Authentication and Registration Templates &amp; Pages'); ?></b></em>
                        </th>
                    </tr>
                    <?php $manage_content(__('Agent Welcome Email'), 'registration-staff'); ?>
                    <?php $manage_content(__('Sign-in Login Banner'), 'banner-staff'); ?>
                    <?php $manage_content(__('Password Reset Email'), 'pwreset-staff'); ?>
                    <?php $manage_content(__('Two Factor Authentication Email'), 'email2fa-staff'); ?>
                </tbody>
            </table>
        </div>
    <p style="text-align:center">
        <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes'); ?>">
        <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes'); ?>">
    </p>
    </div>
</form>
