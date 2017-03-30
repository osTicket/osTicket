<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info = $members = $qs = array();
if ($team && $_REQUEST['a']!='add') {
    //Editing Team
    $title=__('Update Team');
    $action='update';
    $submit_text=__('Save Changes');
    $trans['name'] = $team->getTranslateTag('name');
    $members = $team->getMembers();
    $qs += array('id' => $team->getId());
} else {
    $title=__('Add New Team');
    $action='create';
    $submit_text=__('Create Team');
    if (!$team) {
        $team = Team::create(array(
            'flags' => Team::FLAG_ENABLED,
        ));
    }
    $qs += array('a' => $_REQUEST['a']);
}

$info = $team->getInfo();
?>
<form action="teams.php?<?php echo Http::build_query($qs); ?>" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $team->getId(); ?>">
 <h2><?php echo $title; ?>
    <?php if (isset($team->name)) { ?><small>
    — <?php echo $team->getName(); ?></small>
    <?php } ?>
    <i class="help-tip icon-question-sign" href="#teams"></i>
</h2>
<br>
<ul class="clean tabs">
    <li class="active"><a href="#team">
        <i class="icon-file"></i> <?php echo __('Team'); ?></a></li>
    <li><a href="#members">
        <i class="icon-group"></i> <?php echo __('Members'); ?></a></li>
</ul>

<div id="team" class="tab_content">
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Team Information'); ?></strong>:</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
                <?php echo __('Name');?>:
            </td>
            <td>
                <input type="text" size="30" name="name" value="<?php echo Format::htmlchars($team->name); ?>"
                    autofocus data-translate-tag="<?php echo $trans['name']; ?>"/>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Status');?>:
            </td>
            <td>
                <span>
                <input type="radio" name="isenabled" value="1" <?php echo $team->isEnabled()?'checked="checked"':''; ?>><strong><?php echo __('Active');?></strong>
                &nbsp;
                <input type="radio" name="isenabled" value="0" <?php echo !$team->isEnabled()?'checked="checked"':''; ?>><?php echo __('Disabled');?>
                &nbsp;<span class="error">*&nbsp;</span>
                <i class="help-tip icon-question-sign" href="#status"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Team Lead');?>:
            </td>
            <td>
                <span>
                <select id="team-lead-select" name="lead_id" data-quick-add="staff">
                    <option value="0">&mdash; <?php echo __('None');?> &mdash;</option>
<?php               if ($members) {
                        foreach($members as $k=>$staff){
                            $selected=($team->lead_id && $staff->getId()==$team->lead_id)?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$staff->getId(),$selected,$staff->getName());
                        }
                    } ?>
                    <option value="0" data-quick-add>&mdash; <?php echo __('Add New');?> &mdash;</option>
                </select>
                &nbsp;<span class="error"><?php echo $errors['lead_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#lead"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Assignment Alert');?>:
            </td>
            <td>
                <input type="checkbox" name="noalerts" value="1" <?php echo !$team->alertsEnabled()?'checked="checked"':''; ?> >
                <?php echo sprintf(__('<strong>Disable</strong> for %s'), __('this team')); ?>
                <i class="help-tip icon-question-sign" href="#assignment_alert"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Admin Notes');?></strong>: <?php echo __('Internal notes viewable by all admins.');?>&nbsp;</em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="notes" cols="21"
                    rows="8" style="width: 80%;"><?php echo Format::htmlchars($team->notes); ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
</div>

<?php
$agents = Staff::getStaffMembers();
foreach ($members as $m)
    unset($agents[$m->staff_id]);
?>

<div id="members" class="tab_content" style="display:none">
   <table class="two-column table" width="100%">
    <tbody>
        <tr class="header">
            <td colspan="2">
                <?php echo __('Team Members'); ?>
                <div><small>
                <?php echo sprintf(__('Agents who are members of %s'), __('this team')); ?>
                <i class="help-tip icon-question-sign" href="#members"></i>
                </small></div>
            </td>
        </tr>
      <tr id="add_member">
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
          <label>
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
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="?"'>
</p>
</form>

<script type="text/javascript">
var addMember = function(staffid, name, alerts, error) {
  if (!staffid) return;
  var copy = $('#member_template').clone();

  copy.find('[data-name=members\\[\\]]')
    .attr('name', 'members[]')
    .val(staffid);
  copy.find('[data-name^=member_alerts]')
    .attr('name', 'member_alerts['+staffid+']')
    .prop('checked', alerts);
  copy.find('td:first').append(document.createTextNode(name));
  copy.attr('id', '').show().insertBefore($('#add_member'));
  copy.removeClass('hidden')
  if (error)
      $('<div class="error">').text(error).appendTo(copy.find('td:last'));
};

$('#add_member').find('button').on('click', function() {
  var selected = $('#add_access').find(':selected'),
      id = parseInt(selected.val());
  if (!id)
    return;
  addMember(id, selected.text(), true);
  if ($('#team-lead-select option[value='+id+']').length === 0) {
    $('#team-lead-select').find('option[data-quick-add]')
    .before(
      $('<option>').val(selected.val()).text(selected.text())
    );
  }
  selected.remove();
  return false;
});

$(document).on('click', 'a.drop-membership', function() {
  var tr = $(this).closest('tr'),
      id = tr.find('input[name^=members][type=hidden]').val();
  $('#add_access').append(
    $('<option>')
    .attr('value', id)
    .text(tr.find('td:first').text())
  );
  $('#team-lead-select option[value='+id+']').remove();
  tr.fadeOut(function() { $(this).remove(); });
  return false;
});

<?php
if ($team) {
    foreach ($team->members->sort(function($a) { return $a->staff->getName(); }) as $member) {
        echo sprintf('addMember(%d, %s, %d, %s);',
            $member->staff_id,
            JsonDataEncoder::encode((string) $member->staff->getName()),
            $member->isAlertsEnabled(),
            JsonDataEncoder::encode($errors['members'][$member->staff_id])
        );
    }
}
?>
</script>
