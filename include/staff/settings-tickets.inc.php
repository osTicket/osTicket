<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');
if(!($maxfileuploads=ini_get('max_file_uploads')))
    $maxfileuploads=DEFAULT_MAX_FILE_UPLOADS;
?>
<h2><?php echo __('Ticket Settings and Options');?></h2>
<form action="settings.php?t=tickets" method="post" class="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="tickets" >

<ul class="clean tabs">
    <li class="active"><a href="#settings"><i class="icon-asterisk"></i>
        <?php echo __('Settings'); ?></a></li>
    <li><a href="#autoresp"><i class="icon-mail-reply-all"></i>
        <?php echo __('Autoresponder'); ?></a></li>
    <li><a href="#alerts"><i class="icon-bell-alt"></i>
        <?php echo __('Alerts and Notices'); ?></a></li>
    <li><a href="#queues"><i class="icon-table"></i>
        <?php echo __('Queues'); ?></a></li>
</ul>
<div class="tab_content" id="settings">
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
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
                <input type="text" name="ticket_number_format" value="<?php
                echo $config['ticket_number_format']; ?>"/>
                <span class="faded"><?php echo __('e.g.'); ?> <span id="format-example"><?php
                    if ($config['ticket_sequence_id'])
                        $seq = Sequence::lookup($config['ticket_sequence_id']);
                    if (!isset($seq))
                        $seq = new RandomSequence();
                    echo $seq->current($config['ticket_number_format']);
                    ?></span></span>
                <i class="help-tip icon-question-sign" href="#number_format"></i>
                <div class="error"><?php echo $errors['ticket_number_format']; ?></div>
            </td>
        </tr>
        <tr><td width="220"><?php echo __('Default Ticket Number Sequence'); ?>:</td>
<?php $selected = 'selected="selected"'; ?>
            <td>
                <select name="ticket_sequence_id">
                <option value="0" <?php if ($config['ticket_sequence_id'] == 0) echo $selected;
                    ?>>&mdash; <?php echo __('Random'); ?> &mdash;</option>
<?php foreach (Sequence::objects() as $s) { ?>
                <option value="<?php echo $s->id; ?>" <?php
                    if ($config['ticket_sequence_id'] == $s->id) echo $selected;
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
        <tr><td width="220"><?php echo __('Top-Level Ticket Counts'); ?>:</td>
            <td>
                <input type="checkbox" name="queue_bucket_counts" <?php echo $config['queue_bucket_counts']?'checked="checked"':''; ?>>
                <?php echo __('Enable'); ?>&nbsp;<i class="help-tip icon-question-sign" href="#queue_bucket_counts"></i>
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
                <span class="error"><?php echo $errors['default_ticket_status_id']; ?></span>
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
                &nbsp;<span class="error"><?php echo $errors['default_priority_id']; ?></span> <i class="help-tip icon-question-sign" href="#default_priority"></i>
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
                &nbsp;<span class="error"><?php echo $errors['default_sla_id']; ?></span>  <i class="help-tip icon-question-sign" href="#default_sla"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Default Help Topic'); ?>:</td>
            <td>
                <select name="default_help_topic">
                    <option value="0">&mdash; <?php echo __('None'); ?> &mdash;</option><?php
                    $topics = Topic::getHelpTopics(false, Topic::DISPLAY_DISABLED);
                    foreach ($topics as $id=>$topic) { ?>
                        <option value="<?php echo $id; ?>"<?php echo ($config['default_help_topic']==$id)?'selected':''; ?>><?php echo $topic; ?></option>
                    <?php
                    } ?>
                </select><br/>
                <span class="error"><?php echo $errors['default_help_topic']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Lock Semantics'); ?>:</td>
            <td>
                <select name="ticket_lock" <?php if ($cfg->getLockTime() == 0) echo 'disabled="disabled"'; ?>>
<?php foreach (array(
    Lock::MODE_DISABLED => __('Disabled'),
    Lock::MODE_ON_VIEW => __('Lock on view'),
    Lock::MODE_ON_ACTIVITY => __('Lock on activity'),
) as $v => $desc) { ?>
                <option value="<?php echo $v; ?>" <?php
                    if ($config['ticket_lock'] == $v) echo 'selected="selected"';
                    ?>><?php echo $desc; ?></option>
<?php } ?>
                </select>
                <div class="error"><?php echo $errors['ticket_lock']; ?></div>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo __('Default Ticket Queue'); ?>:
            </td>
            <td>
                <select name="default_ticket_queue">
<?php foreach (CustomQueue::queues() as $cq) {
?>
                  <option value="<?php echo $cq->id; ?>"
            <?php if ($cq->getId() == $config['default_ticket_queue']) echo 'selected="selected"'; ?>
            ><?php echo $cq->getFullName(); ?></option>
<?php } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#default_ticket_queue"></i>
                <div class="error"><?php echo $errors['default_ticket_queue']; ?></div>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Maximum <b>Open</b> Tickets');?>:</td>
            <td>
                <input type="text" name="max_open_tickets" size=4 value="<?php echo $config['max_open_tickets']; ?>">
                <?php echo __('per end user'); ?>
                <span class="error"><?php echo $errors['max_open_tickets']; ?></span>
                <i class="help-tip icon-question-sign" href="#maximum_open_tickets"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Human Verification');?>:</td>
            <td>
                <input type="checkbox" name="enable_captcha" <?php echo $config['enable_captcha']?'checked="checked"':''; ?>>
                <?php echo __('Enable CAPTCHA on new web tickets.');?>
                &nbsp;<font class="error"><?php echo $errors['enable_captcha']; ?></font>
                &nbsp;<i class="help-tip icon-question-sign" href="#human_verification"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Collaborator Tickets Visibility'); ?>:</td>
            <td>
                <input type="checkbox" name="collaborator_ticket_visibility" <?php echo $config['collaborator_ticket_visibility']?'checked="checked"':''; ?>>
                <?php echo __('Enable'); ?>&nbsp;<i class="help-tip icon-question-sign" href="#collaborator_ticket_visibility"></i>
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
            <td><?php echo __('Auto-refer on Close'); ?>:</td>
            <td>
                <input type="checkbox" name="auto_refer_closed" <?php echo $config['auto_refer_closed']?'checked="checked"':''; ?>>
                <?php echo __('Enable'); ?>&nbsp;<i class="help-tip
                icon-question-sign" href="#auto_refer"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Require Help Topic to Close'); ?>:</td>
            <td>
                <input type="checkbox" name="require_topic_to_close" <?php echo $config['require_topic_to_close']?'checked="checked"':''; ?>>
                <?php echo __('Enable'); ?>&nbsp;<i class="help-tip icon-question-sign" href="#require_topic_to_close"></i>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Allow External Images'); ?>:</td>
            <td>
                <input type="checkbox" name="allow_external_images" <?php echo $config['allow_external_images']?'checked="checked"':''; ?>>
                <?php echo __('Enable'); ?>&nbsp;<i class="help-tip icon-question-sign" href="#allow_external_images"></i>
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
    </tbody>
</table>
</div>
<div class="hidden tab_content" id="autoresp"
    data-tip-namespace="settings.autoresponder">
    <?php include STAFFINC_DIR . 'settings-autoresp.inc.php'; ?>
</div>
<div class="hidden tab_content" id="alerts"
    data-tip-namespace="settings.alerts">
    <?php include STAFFINC_DIR . 'settings-alerts.inc.php'; ?>
</div>

<div class="hidden tab_content" id="queues">
    <?php include STAFFINC_DIR . 'queues-ticket.inc.php'; ?>
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
        + $('[name=ticket_sequence_id] :selected').val(),
        {'format': $('[name=ticket_number_format]').val()},
        function(data) { $('#format-example').text(data); }
      );
    };
    $('[name=ticket_sequence_id]').on('change', update_example);
    $('[name=ticket_number_format]').on('keyup', update_example);
});
</script>
