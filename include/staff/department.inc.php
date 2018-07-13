<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info = $qs = array();
if($dept && $_REQUEST['a']!='add') {
    //Editing Department.
    $title=__('Update Department');
    $action='update';
    $submit_text=__('Save Changes');
    $info = $dept->getInfo();
    $info['id'] = $dept->getId();
    $qs += array('id' => $dept->getId());
} else {
    if (!$dept)
        $dept = Dept::create();
    $title=__('Add New Department');
    $action='create';
    $submit_text=__('Create Dept');
    $info['ispublic']=isset($info['ispublic'])?$info['ispublic']:1;
    $info['ticket_auto_response']=isset($info['ticket_auto_response'])?$info['ticket_auto_response']:1;
    $info['message_auto_response']=isset($info['message_auto_response'])?$info['message_auto_response']:1;
    if (!isset($info['group_membership']))
        $info['group_membership'] = 1;

    $qs += array('a' => $_REQUEST['a']);
}

$info = Format::htmlchars(($errors && $_POST) ? $_POST : $info);
?>
<form action="departments.php?<?php echo Http::build_query($qs); ?>" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
<h2><?php echo $title; ?>
    <?php if (isset($info['name'])) { ?><small>
    — <?php echo $info['name']; ?></small>
    <?php } ?>
</h2>
<ul class="clean tabs">
    <li class="active"><a href="#settings">
        <i class="icon-file"></i> <?php echo __('Settings'); ?></a></li>
    <li><a href="#access">
      <i class="icon-user"></i> <?php echo __('Access'); ?></a></li>
</ul>
<div id="settings" class="tab_content">
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __('Department Information');?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180">
                <?php echo __('Parent');?>:
            </td>
            <td>
                <select name="pid">
                    <option value="">&mdash; <?php echo __('Top-Level Department'); ?> &mdash;</option>
<?php foreach (Dept::getDepartments() as $id=>$name) {
    if ($info['id'] && $id == $info['id'])
        continue; ?>
                    <option value="<?php echo $id; ?>" <?php
                    if ($info['pid'] == $id) echo 'selected="selected"';
                    ?>><?php echo $name; ?></option>
<?php } ?>
                </select>
                &nbsp;<span class="error"><?php echo $errors['pid']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Name');?>:
            </td>
            <td>
                <input data-translate-tag="<?php echo $dept ? $dept->getTranslateTag() : '';
                ?>" type="text" size="30" name="name" value="<?php echo $info['name']; ?>"
                autofocus>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Type');?>:
            </td>
            <td>
                <label>
                <input type="radio" name="ispublic" value="1" <?php echo $info['ispublic']?'checked="checked"':''; ?>><strong><?php echo __('Public');?></strong>
                </label>
                &nbsp;
                <label>
                <input type="radio" name="ispublic" value="0" <?php echo !$info['ispublic']?'checked="checked"':''; ?>><strong><?php echo __('Private');?></strong> <?php echo mb_convert_case(__('(internal)'), MB_CASE_TITLE);?>
                </label>
                &nbsp;<i class="help-tip icon-question-sign" href="#type"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('SLA'); ?>:
            </td>
            <td>
                <select name="sla_id">
                    <option value="0">&mdash; <?php echo __('System Default'); ?> &mdash;</option>
                    <?php
                    if($slas=SLA::getSLAs()) {
                        foreach($slas as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['sla_id']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error"><?php echo $errors['sla_id']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#sla"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Manager'); ?>:
            </td>
            <td>
                <span>
                <select name="manager_id">
                    <option value="0">&mdash; <?php echo __('None'); ?> &mdash;</option>
                    <?php
                    $sql='SELECT staff_id,CONCAT_WS(", ",lastname, firstname) as name '
                        .' FROM '.STAFF_TABLE.' staff '
                        .' ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)) {
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['manager_id'] && $id==$info['manager_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error"><?php echo $errors['manager_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#manager"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Ticket Assignment'); ?>:</td>
            <td>
                <label>
                <input type="checkbox" name="assign_members_only" <?php echo
                $info['assign_members_only']?'checked="checked"':''; ?>>
                <?php echo __('Restrict ticket assignment to department members'); ?>
                </label>
                <i class="help-tip icon-question-sign" href="#sandboxing"></i>
            </td>
        </tr>

        <tr>
            <td><?php echo __('Claim on Response'); ?>:</td>
            <td>
                <label>
                <input type="checkbox" name="disable_auto_claim" <?php echo
                 $info['disable_auto_claim'] ? 'checked="checked"' : ''; ?>>
                <?php echo sprintf('<strong>%s</strong> %s',
                        __('Disable'),
                        __('auto claim')); ?>
                </label>
                <i class="help-tip icon-question-sign"
                href="#disable_auto_claim"></i>
            </td>
        </tr>

        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Outgoing Email Settings'); ?></strong>:</em>
            </th>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Outgoing Email'); ?>:
            </td>
            <td>
                <select name="email_id">
                    <option value="0">&mdash; <?php echo __('System Default'); ?> &mdash;</option>
                    <?php
                    $sql='SELECT email_id,email,name FROM '.EMAIL_TABLE.' email ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$email,$name)=db_fetch_row($res)){
                            $selected=($info['email_id'] && $id==$info['email_id'])?'selected="selected"':'';
                            if($name)
                                $email=Format::htmlchars("$name <$email>");
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$email);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['email_id']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#email"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Template Set'); ?>:
            </td>
            <td>
                <select name="tpl_id">
                    <option value="0">&mdash; <?php echo __('System Default'); ?> &mdash;</option>
                    <?php
                    $sql='SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_GRP_TABLE.' tpl WHERE isactive=1 ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['tpl_id'] && $id==$info['tpl_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['tpl_id']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#template"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Autoresponder Settings'); ?></strong>:
                <i class="help-tip icon-question-sign" href="#auto_response_settings"></i></em>
            </th>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('New Ticket');?>:
            </td>
            <td>
                <label>
                <input type="checkbox" name="ticket_auto_response" value="0" <?php echo !$info['ticket_auto_response']?'checked="checked"':''; ?> >

                <?php echo sprintf(__('<strong>Disable</strong> for %s'), __('this department')); ?>
                </label>
                <i class="help-tip icon-question-sign" href="#new_ticket"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('New Message');?>:
            </td>
            <td>
                <label>
                <input type="checkbox" name="message_auto_response" value="0" <?php echo !$info['message_auto_response']?'checked="checked"':''; ?> >
                <?php echo sprintf(__('<strong>Disable</strong> for %s'), __('this department')); ?>
                </label>
                <i class="help-tip icon-question-sign" href="#new_message"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Auto-Response Email'); ?>:
            </td>
            <td>
                <span>
                <select name="autoresp_email_id">
                    <option value="0" selected="selected">&mdash; <?php echo __('Department Email'); ?> &mdash;</option>
                    <?php
                    $sql='SELECT email_id,email,name FROM '.EMAIL_TABLE.' email ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$email,$name)=db_fetch_row($res)){
                            $selected = (isset($info['autoresp_email_id'])
                                    && $id == $info['autoresp_email_id'])
                                ? 'selected="selected"' : '';
                            if($name)
                                $email=Format::htmlchars("$name <$email>");
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$email);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error"><?php echo $errors['autoresp_email_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#auto_response_email"></i>
                </span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Alerts and Notices'); ?>:</strong>
                <i class="help-tip icon-question-sign" href="#group_membership"></i></em>
            </th>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Recipients'); ?>:
            </td>
            <td>
                <span>
                <select name="group_membership">
<?php foreach (array(
    Dept::ALERTS_DISABLED =>        __("No one (disable Alerts and Notices)"),
    Dept::ALERTS_DEPT_ONLY =>       __("Department members only"),
    Dept::ALERTS_DEPT_AND_EXTENDED => __("Department and extended access members"),
) as $mode=>$desc) { ?>
    <option value="<?php echo $mode; ?>" <?php
        if ($info['group_membership'] == $mode) echo 'selected="selected"';
    ?>><?php echo $desc; ?></option><?php
} ?>
                </select>
                <i class="help-tip icon-question-sign" href="#group_membership"></i>
                </span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Department Signature'); ?></strong>:
                <span class="error">&nbsp;<?php echo $errors['signature']; ?></span>
                <i class="help-tip icon-question-sign" href="#department_signature"></i></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="signature" cols="21"
                    rows="5" style="width: 60%;"><?php echo $info['signature']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
</div>

<div id="access" class="hidden tab_content">
  <table class="two-column table" width="100%">
    <tbody>
        <tr class="header" id="primary-members">
            <td colspan="2">
                <?php echo __('Department Members'); ?>
                <div><small>
                <?php echo sprintf(__('Agents who are primary members of %s'), __('this department')); ?>
                </small></div>
            </td>
        </tr>
        <?php
        if (!count($dept->members)) { ?>
        <tr><td colspan=2><em><?php
            echo __('Department does not have primary members'); ?>
           </em> </td>
        </tr>
        <?php
        } ?>
     </tbody>
     <tbody>
        <tr class="header" id="extended-access-members">
            <td colspan="2">
                <div><small>
                <?php echo sprintf(__('Agents who have extended access to %s'), __('this department')); ?>
                </small></div>
            </td>
        </tr>
<?php
$agents = Staff::getStaffMembers();
foreach ($dept->getMembers() as $member) {
    unset($agents[$member->getId()]);
} ?>
      <tr id="add_extended_access">
        <td colspan="2">
          <i class="icon-plus-sign"></i>
          <select id="add_access" data-quick-add="staff">
            <option value="0">&mdash; <?php echo __('Select Agent');?> &mdash;</option>
            <?php
            foreach ($agents as $id=>$name) {
              echo sprintf('<option value="%d">%s</option>',$id,Format::htmlchars($name));
            }
            ?>
            <option value="0" data-quick-add>&mdash; <?php echo __('Add New');?> &mdash;</option>
          </select>
          <button type="button" class="action-button">
            <?php echo __('Add'); ?>
          </button>
        </td>
      </tr>
    </tbody>
    <tbody>
      <tr id="member_template" class="hidden">
        <td>
          <input type="hidden" data-name="members[]" value="" />
        </td>
        <td>
          <select data-name="member_role" data-quick-add="role">
            <option value="0">&mdash; <?php echo __('Select Role');?> &mdash;</option>
            <?php
            foreach (Role::getRoles() as $id=>$name) {
              echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
            }
            ?>
            <option value="0" data-quick-add>&mdash; <?php echo __('Add New');?> &mdash;</option>
          </select>
          <span style="display:inline-block;width:60px"> </span>
          <label class="inline checkbox">
            <input type="checkbox" data-name="member_alerts" value="1" />
            <?php echo __('Alerts'); ?>
          </label>
          <a href="#" class="pull-right drop-membership" title="<?php echo __('Delete');
            ?>"><i class="icon-trash"></i></a>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<p style="text-align:center">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>"
        onclick='window.location.href="?"'>
</p>
</form>

<script type="text/javascript">
var addAccess = function(staffid, name, role, alerts, primary, error) {

  if (!staffid) return;
  var copy = $('#member_template').clone();
  var target = (primary) ? 'extended-access-members' : 'add_extended_access';
  copy.find('td:first').append(document.createTextNode(name));
  if (primary) {
    copy.find('a.drop-membership').remove();
  }
    copy.find('[data-name^=member_alerts]')
      .attr('name', 'member_alerts['+staffid+']')
      .prop('disabled', (primary))
      .prop('checked', primary || alerts);
    copy.find('[data-name^=member_role]')
      .attr('name', 'member_role['+staffid+']')
      .val(role || 0);
    copy.find('[data-name=members\\[\\]]')
      .attr('name', 'members[]')
      .val(staffid);

  copy.attr('id', '').show().insertBefore($('#'+target));
  copy.removeClass('hidden')
  if (error)
      $('<div class="error">').text(error).appendTo(copy.find('td:last'));
  copy.find('.drop-membership').click(function() {
    $('#add_access').append(
      $('<option>')
      .attr('value', copy.find('input[name^=members][type=hidden]').val())
      .text(copy.find('td:first').text())
    );
    copy.fadeOut(function() { $(this).remove(); });
    return false;
  });
};

$('#add_extended_access').find('button').on('click', function() {
  var selected = $('#add_access').find(':selected'),
      id = parseInt(selected.val());
  if (!id)
    return;
  addAccess(id, selected.text(), 0, true);
  selected.remove();
  return false;
});

<?php
if ($dept) {
    // Primary members
    foreach ($dept->getPrimaryMembers() as $member) {
        $primary = $member->dept_id == $info['id'];
        echo sprintf('addAccess(%d, %s, %d, %d, %d, %s);',
            $member->getId(),
            JsonDataEncoder::encode((string) $member->getName()),
            $member->role_id,
            $member->get('alerts', 0),
            ($member->dept_id == $info['id']) ? 1 : 0,
            JsonDataEncoder::encode($errors['members'][$member->staff_id])
        );
    }

    // Extended members.
    foreach ($dept->getExtendedMembers() as $member) {
        echo sprintf('addAccess(%d, %s, %d, %d, %d, %s);',
            $member->getId(),
            JsonDataEncoder::encode((string) $member->getName()),
            $member->role_id,
            $member->get('alerts', 0),
            0,
            JsonDataEncoder::encode($errors['members'][$member->staff_id])
        );
    }
}
?>
</script>
