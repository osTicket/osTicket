<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');
if(!($maxfileuploads=ini_get('max_file_uploads')))
    $maxfileuploads=DEFAULT_MAX_FILE_UPLOADS;
?>
<h2><?php echo __('Task Settings and Options');?></h2>
<form action="settings.php?t=tasks" method="post" class="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="tasks" >

<ul class="tabs" id="tasks-tabs">
    <li class="active"><a href="#settings">
        <i class="icon-asterisk"></i> <?php echo __('Settings'); ?></a></li>
    <li><a href="#alerts">
        <i class="icon-bell-alt"></i> <?php echo __('Alerts &amp; Notices'); ?></a></li>
</ul>
<div id="tasks-tabs_container">
   <div id="settings" class="tab_content">
    <table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
        <thead>
            <tr>
                <th colspan="2">
                    <em><?php echo __('Global default task settings and options.'); ?></em>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <?php echo __('Default Task Number Format'); ?>:
                </td>
                <td>
                    <input type="text" name="task_number_format" value="<?php
                    echo $config['task_number_format']; ?>"/>
                    <span class="faded"><?php echo __('e.g.'); ?> <span id="format-example"><?php
                        if ($config['task_sequence_id'])
                            $seq = Sequence::lookup($config['task_sequence_id']);
                        if (!isset($seq))
                            $seq = new RandomSequence();
                        echo $seq->current($config['task_number_format']);
                        ?></span></span>
                    <i class="help-tip icon-question-sign" href="#number_format"></i>
                    <div class="error"><?php echo $errors['task_number_format']; ?></div>
                </td>
            </tr>
            <tr><td width="220"><?php echo __('Default Task Number Sequence'); ?>:</td>
    <?php $selected = 'selected="selected"'; ?>
                <td>
                    <select name="task_sequence_id">
                    <option value="0" <?php if ($config['task_sequence_id'] == 0) echo $selected;
                        ?>>&mdash; <?php echo __('Random'); ?> &mdash;</option>
    <?php foreach (Sequence::objects() as $s) { ?>
                    <option value="<?php echo $s->id; ?>" <?php
                        if ($config['task_sequence_id'] == $s->id) echo $selected;
                        ?>><?php echo $s->name; ?></option>
    <?php } ?>
                    </select>
                    <button class="action-button pull-right" onclick="javascript:
                    $.dialog('ajax.php/sequence/manage', 205);
                    return false;
                    "><i class="icon-gear"></i> <?php echo __('Manage'); ?></button>
                    <i class="help-tip icon-question-sign" href="#sequence_id"></i>
                </td>
            </tr>
            <tr>
                <td width="180" class="required"><?php echo __('Default Priority');?>:</td>
                <td>
                    <select name="default_task_priority_id">
                        <?php
                        $priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
                        while (list($id,$tag) = db_fetch_row($priorities)){ ?>
                            <option value="<?php echo $id; ?>"<?php echo
                                ($config['default_task_priority_id']==$id)?'selected':''; ?>><?php echo $tag; ?></option>
                        <?php
                        } ?>
                    </select>
                    &nbsp;<span class="error">*&nbsp;<?php echo
                    $errors['default_task_priority_id']; ?></span> <i class="help-tip icon-question-sign" href="#default_priority"></i>
                 </td>
            </tr>
            <tr>
                <th colspan="2">
                    <em><b><?php echo __('Attachments');?></b>:</em>
                </th>
            </tr>
            <tr>
                <td width="180"><?php echo __('Task Attachment Settings');?>:</td>
                <td>
    <?php
                    $tform = TaskForm::objects()->one()->getForm();
                    $f = $tform->getField('description');
    ?>
                    <a class="action-button field-config" style="overflow:inherit"
                        href="#ajax.php/form/field-config/<?php
                            echo $f->get('id'); ?>"
                        onclick="javascript:
                            $.dialog($(this).attr('href').substr(1), [201]);
                            return false;
                        "><i class="icon-edit"></i> <?php echo __('Config'); ?></a>
                    <i class="help-tip icon-question-sign" href="#task_attachment_settings"></i>
                </td>
            </tr>
        </tbody>
    </table>
   </div>
   <div id="alerts" class="tab_content" style="display:none;">
    <table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
        <tbody>
            <tr><th><em><b><?php echo __('New Task Alert'); ?></b>:
                <i class="help-tip icon-question-sign" href="#task_alert"></i>
                </em></th></tr>
            <tr>
                <td><em><b><?php echo __('Status'); ?>:</b></em> &nbsp;
                    <input type="radio" name="task_alert_active"  value="1"
                    <?php echo $config['task_alert_active'] ? 'checked="checked"' : ''; ?>
                    /> <?php echo __('Enable'); ?>
                    <input type="radio" name="task_alert_active"  value="0"
                    <?php echo !$config['task_alert_active'] ? 'checked="checked"' : ''; ?> />
                    <?php echo __('Disable'); ?>
                    &nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['task_alert_active']; ?></font></em>
                 </td>
            </tr>
            <tr>
                <td>
                    <input type="checkbox" name="task_alert_admin" <?php
                        echo $config['task_alert_admin'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Admin Email'); ?> <em>(<?php echo $cfg->getAdminEmail(); ?>)</em>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="checkbox" name="task_alert_dept_manager"
                    <?php echo $config['task_alert_dept_manager'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Department Manager'); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="checkbox" name="task_alert_dept_members"
                    <?php echo $config['task_alert_dept_members'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Department Members'); ?>
                </td>
            </tr>
            <tr><th><em><b><?php echo __('New Activity Alert'); ?></b>:
                <i class="help-tip icon-question-sign" href="#activity_alert"></i>
                </em></th></tr>
            <tr>
                <td><em><b><?php echo __('Status'); ?>:</b></em> &nbsp;
                  <input type="radio" name="task_activity_alert_active" value="1"
                  <?php echo $config['task_activity_alert_active'] ? 'checked="checked"' : ''; ?> />
                    <?php echo __('Enable'); ?>
                  &nbsp;&nbsp;
                  <input type="radio" name="task_activity_alert_active"  value="0"
                  <?php echo !$config['task_activity_alert_active'] ? 'checked="checked"' : ''; ?> />
                    <?php echo __('Disable'); ?>
                  &nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['task_activity_alert_active']; ?></font>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_activity_alert_laststaff" <?php
                  echo $config['task_activity_alert_laststaff'] ? 'checked="checked"' : ''; ?>>
                  <?php echo __('Last Respondent'); ?>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_activity_alert_assigned"
                  <?php echo $config['task_activity_alert_assigned'] ? 'checked="checked"' : ''; ?>>
                  <?php echo __('Assigned Agent / Team'); ?>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_activity_alert_dept_manager"
                  <?php echo $config['task_activity_alert_dept_manager'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Department Manager'); ?>
                </td>
            </tr>
            <tr><th><em><b><?php echo __('Task Assignment Alert'); ?></b>:
                <i class="help-tip icon-question-sign" href="#assignment_alert"></i>
                </em></th></tr>
            <tr>
                <td><em><b><?php echo __('Status'); ?>: </b></em> &nbsp;
                  <input name="task_assignment_alert_active" value="1" type="radio"
                    <?php echo $config['task_assignment_alert_active'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Enable'); ?>
                    &nbsp;&nbsp;
                  <input name="task_assignment_alert_active" value="0" type="radio"
                    <?php echo !$config['task_assignment_alert_active'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Disable'); ?>
                   &nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['task_assignment_alert_active']; ?></font>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_assignment_alert_staff" <?php echo
                  $config['task_assignment_alert_staff'] ? 'checked="checked"' : ''; ?>>
                  <?php echo __('Assigned Agent / Team'); ?>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox"name="task_assignment_alert_team_lead" <?php
                  echo $config['task_assignment_alert_team_lead'] ? 'checked="checked"' : ''; ?>>
                  <?php echo __('Team Lead'); ?>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox"name="task_assignment_alert_team_members"
                  <?php echo $config['task_assignment_alert_team_members'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Team Members'); ?>
                </td>
            </tr>
            <tr><th><em><b><?php echo __('Task Transfer Alert'); ?></b>:
                <i class="help-tip icon-question-sign" href="#transfer_alert"></i>
                </em></th></tr>
            <tr>
                <td><em><b><?php echo __('Status'); ?>:</b></em> &nbsp;
                <input type="radio" name="task_transfer_alert_active"  value="1"
                <?php echo $config['task_transfer_alert_active'] ? 'checked="checked"' : ''; ?> />
                    <?php echo __('Enable'); ?>
                <input type="radio" name="task_transfer_alert_active"  value="0"
                <?php echo !$config['task_transfer_alert_active'] ? 'checked="checked"' : ''; ?> />
                    <?php echo __('Disable'); ?>
                  &nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?php
                  echo $errors['task_transfer_alert_active']; ?></font>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_transfer_alert_assigned"
                  <?php echo $config['task_transfer_alert_assigned']?'checked="checked"':''; ?>>
                    <?php echo __('Assigned Agent / Team'); ?>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_transfer_alert_dept_manager"
                  <?php echo $config['task_transfer_alert_dept_manager'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Department Manager'); ?>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_transfer_alert_dept_members"
                  <?php echo $config['task_transfer_alert_dept_members'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Department Members'); ?>
                </td>
            </tr>
            <tr><th><em><b><?php echo __('Overdue Task Alert'); ?></b>:
                <i class="help-tip icon-question-sign" href="#overdue_alert"></i>
                </em></th></tr>
            <tr>
                <td><em><b><?php echo __('Status'); ?>:</b></em> &nbsp;
                  <input type="radio" name="task_overdue_alert_active"  value="1"
                    <?php echo $config['task_overdue_alert_active'] ? 'checked="checked"' : ''; ?> /> <?php echo __('Enable'); ?>
                  <input type="radio" name="task_overdue_alert_active"  value="0"
                    <?php echo !$config['task_overdue_alert_active'] ? 'checked="checked"' : ''; ?> /> <?php echo __('Disable'); ?>
                  &nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['task_overdue_alert_active']; ?></font>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_overdue_alert_assigned" <?php
                    echo $config['task_overdue_alert_assigned'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Assigned Agent / Team'); ?>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_overdue_alert_dept_manager" <?php
                    echo $config['task_overdue_alert_dept_manager'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Department Manager'); ?>
                </td>
            </tr>
            <tr>
                <td>
                  <input type="checkbox" name="task_overdue_alert_dept_members" <?php
                    echo $config['task_overdue_alert_dept_members'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Department Members'); ?>
                </td>
            </tr>
        </tbody>
    </table>
   </div>
</div>
<p style="text-align:center;">
    <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes');?>">
    <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes');?>">
</p>
</form>
<script type="text/javascript">
$(function() {
    var request = null,
      update_example = function() {
      request && request.abort();
      request = $.get('ajax.php/sequence/'
        + $('[name=task_sequence_id] :selected').val(),
        {'format': $('[name=task_number_format]').val()},
        function(data) { $('#format-example').text(data); }
      );
    };
    $('[name=task_sequence_id]').on('change', update_example);
    $('[name=task_number_format]').on('keyup', update_example);
});
</script>
