<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');
if(!($maxfileuploads=ini_get('max_file_uploads')))
    $maxfileuploads=DEFAULT_MAX_FILE_UPLOADS;
?>
<h2><?php echo __('Ticket Settings and Options');?></h2>
<form action="settings.php?t=tickets" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="tickets" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo __('Global Ticket Settings');?></h4>
                <em><?php echo __('System-wide default ticket settings and options.'); ?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <?php echo __('Default Ticket Number Format'); ?>:
            </td>
            <td>
                <input type="text" name="number_format" value="<?php echo $config['number_format']; ?>"/>
                <span class="faded"><?php echo __('e.g.'); ?> <span id="format-example"><?php
                    if ($config['sequence_id'])
                        $seq = Sequence::lookup($config['sequence_id']);
                    if (!isset($seq))
                        $seq = new RandomSequence();
                    echo $seq->current($config['number_format']);
                    ?></span></span>
                <i class="help-tip icon-question-sign" href="#number_format"></i>
                <div class="error"><?php echo $errors['number_format']; ?></div>
            </td>
        </tr>
        <tr><td width="220"><?php echo __('Default Ticket Number Sequence'); ?>:</td>
<?php $selected = 'selected="selected"'; ?>
            <td>
                <select name="sequence_id">
                <option value="0" <?php if ($config['sequence_id'] == 0) echo $selected;
                    ?>>&mdash; <?php echo __('Random'); ?> &mdash;</option>
<?php foreach (Sequence::objects() as $s) { ?>
                <option value="<?php echo $s->id; ?>" <?php
                    if ($config['sequence_id'] == $s->id) echo $selected;
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
            <td width="180" class="required">
                <?php echo __('Default Status'); ?>:
            </td>
            <td>
                <span>
                <select name="default_ticket_status_id">
                <?php
                $criteria = array('states' => array('open'));
                foreach (TicketStatusList::getStatuses($criteria) as $status) {
                    $name = $status->getName();
                    if (!($isenabled = $status->isEnabled()))
                        $name.=' '.__('(disabled)');

                    echo sprintf('<option value="%d" %s %s>%s</option>',
                            $status->getId(),
                            ($config['default_ticket_status_id'] ==
                             $status->getId() && $isenabled)
                             ? 'selected="selected"' : '',
                             $isenabled ? '' : 'disabled="disabled"',
                             $name
                            );
                }
                ?>
                </select>
                &nbsp;
                <span class="error">*&nbsp;<?php echo $errors['default_ticket_status_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#default_ticket_status"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required"><?php echo __('Default Priority');?>:</td>
            <td>
                <select name="default_priority_id">
                    <?php
                    $priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
                    while (list($id,$tag) = db_fetch_row($priorities)){ ?>
                        <option value="<?php echo $id; ?>"<?php echo ($config['default_priority_id']==$id)?'selected':''; ?>><?php echo $tag; ?></option>
                    <?php
                    } ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['default_priority_id']; ?></span> <i class="help-tip icon-question-sign" href="#default_priority"></i>
             </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Default SLA');?>:
            </td>
            <td>
                <span>
                <select name="default_sla_id">
                    <option value="0">&mdash; <?php echo __('None');?> &mdash;</option>
                    <?php
                    if($slas=SLA::getSLAs()) {
                        foreach($slas as $id => $name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id,
                                    ($config['default_sla_id'] && $id==$config['default_sla_id'])?'selected="selected"':'',
                                    $name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['default_sla_id']; ?></span>  <i class="help-tip icon-question-sign" href="#default_sla"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Default Help Topic'); ?>:</td>
            <td>
                <select name="default_help_topic">
                    <option value="0">&mdash; <?php echo __('None'); ?> &mdash;</option><?php
                    $topics = Topic::getHelpTopics(false, Topic::DISPLAY_DISABLED);
                    while (list($id,$topic) = each($topics)) { ?>
                        <option value="<?php echo $id; ?>"<?php echo ($config['default_help_topic']==$id)?'selected':''; ?>><?php echo $topic; ?></option>
                    <?php
                    } ?>
                </select><br/>
                <span class="error"><?php echo $errors['default_help_topic']; ?></span>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Maximum <b>Open</b> Tickets');?>:</td>
            <td>
                <input type="text" name="max_open_tickets" size=4 value="<?php echo $config['max_open_tickets']; ?>">
                <?php echo __('per end user'); ?> <i class="help-tip icon-question-sign" href="#maximum_open_tickets"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Agent Collision Avoidance Duration'); ?>:</td>
            <td>
                <input type="text" name="autolock_minutes" size=4 value="<?php echo $config['autolock_minutes']; ?>">
                <font class="error"><?php echo $errors['autolock_minutes']; ?></font>&nbsp;<?php echo __('minutes'); ?>&nbsp;<i class="help-tip icon-question-sign" href="#agent_collision_avoidance"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Human Verification');?>:</td>
            <td>
                <input type="checkbox" name="enable_captcha" <?php echo $config['enable_captcha']?'checked="checked"':''; ?>>
                <?php echo __('Enable CAPTCHA on new web tickets.');?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['enable_captcha']; ?></font>
                &nbsp;<i class="help-tip icon-question-sign" href="#human_verification"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Claim on Response'); ?>:</td>
            <td>
                <input type="checkbox" name="auto_claim_tickets" <?php echo $config['auto_claim_tickets']?'checked="checked"':''; ?>>
                <?php echo __('Enable'); ?>&nbsp;<i class="help-tip icon-question-sign" href="#claim_tickets"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Assigned Tickets');?>:</td>
            <td>
                <input type="checkbox" name="show_assigned_tickets" <?php
                echo !$config['show_assigned_tickets']?'checked="checked"':''; ?>>
                <?php echo __('Exclude assigned tickets from open queue.'); ?>
                <i class="help-tip icon-question-sign" href="#assigned_tickets"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Answered Tickets');?>:</td>
            <td>
                <input type="checkbox" name="show_answered_tickets" <?php
                echo !$config['show_answered_tickets']?'checked="checked"':''; ?>>
                <?php echo __('Exclude answered tickets from open queue.'); ?>
                <i class="help-tip icon-question-sign" href="#answered_tickets"></i>
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
            <td><?php echo __('Enable HTML Ticket Thread'); ?>:</td>
            <td>
                <input type="checkbox" name="enable_html_thread" <?php
                echo $config['enable_html_thread']?'checked="checked"':''; ?>>
                <?php echo __('Enable rich text in ticket thread and autoresponse emails.'); ?>
                <i class="help-tip icon-question-sign" href="#enable_html_ticket_thread"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Allow Client Updates'); ?>:</td>
            <td>
                <input type="checkbox" name="allow_client_updates" <?php
                echo $config['allow_client_updates']?'checked="checked"':''; ?>>
                <?php echo __('Allow clients to update ticket details via the web portal'); ?>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('Attachments');?></b>:  <?php echo __('Size and maximum uploads setting mainly apply to web tickets.');?></em>
            </th>
        </tr>
        <tr>
            <td width="180"><?php echo __('Ticket Attachment Settings');?>:</td>
            <td>
<?php
                $tform = TicketForm::objects()->one()->getForm();
                $f = $tform->getField('message');
?>
                <a class="action-button field-config" style="overflow:inherit"
                    href="#ajax.php/form/field-config/<?php
                        echo $f->get('id'); ?>"
                    onclick="javascript:
                        $.dialog($(this).attr('href').substr(1), [201]);
                        return false;
                    "><i class="icon-edit"></i> <?php echo __('Config'); ?></a>
                <i class="help-tip icon-question-sign" href="#ticket_attachment_settings"></i>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Maximum File Size');?>:</td>
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
        <?php if (($bks = FileStorageBackend::allRegistered())
                && count($bks) > 1) { ?>
        <tr>
            <td width="180"><?php echo __('Store Attachments'); ?>:</td>
            <td><select name="default_storage_bk"><?php
                foreach ($bks as $char=>$class) {
                    $selected = $config['default_storage_bk'] == $char
                        ? 'selected="selected"' : '';
                    ?><option <?php echo $selected; ?> value="<?php echo $char; ?>"
                    ><?php echo $class::$desc; ?></option><?php
                } ?>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<p style="padding-left:250px;">
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
        + $('[name=sequence_id] :selected').val(),
        {'format': $('[name=number_format]').val()},
        function(data) { $('#format-example').text(data); }
      );
    };
    $('[name=sequence_id]').on('change', update_example);
    $('[name=number_format]').on('keyup', update_example);
});
</script>
