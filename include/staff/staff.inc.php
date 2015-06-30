<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info = $qs = array();
if ($staff && $_REQUEST['a']!='add'){
    //Editing Department.
    $title=__('Manage Agent');
    $action='update';
    $submit_text=__('Save Changes');
    $info = $staff->getInfo();
    $info['id'] = $staff->getId();
    $info['teams'] = $staff->getTeams();
    $info['signature'] = Format::viewableImages($info['signature']);
    $qs += array('id' => $staff->getId());
}
$info = Format::htmlchars($info);
?>

<form action="staff.php?<?php echo Http::build_query($qs); ?>" method="post" id="save" autocomplete="off">
  <?php csrf_token(); ?>
  <input type="hidden" name="do" value="<?php echo $action; ?>">
  <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
  <input type="hidden" name="id" value="<?php echo $info['id']; ?>">

  <h2><?php echo $title; ?>
    <div>
      <small><?php echo $info['firstname'].' '.$info['lastname'];?></small>
    </div>
  </h2>

  <ul class="clean tabs">
    <li class="active"><a href="#account"><?php echo __('Account'); ?></a></li>
    <li><a href="#access"><?php echo __('Access'); ?></a></li>
    <li><a href="#permissions"><?php echo __('Permisions'); ?></a></li>
    <li><a href="#teams"><?php echo __('Teams'); ?></a></li>
  </ul>

  <div class="tab_content" id="account">
    <table class="table two-column" width="940" border="0" cellspacing="0" cellpadding="2">
      <tbody>
        <tr>
          <td class="required"><?php echo __('Name'); ?>:</td>
          <td>
            <input type="text" size="20" maxlength="64" style="width: 145px" name="firstname"
              autofocus value="<?php echo $info['firstname']; ?>"
              placeholder="<?php echo __("First Name"); ?>" />
            <input type="text" size="20" maxlength="64" style="width: 145px" name="lastname"
              value="<?php echo $info['lastname']; ?>"
              placeholder="<?php echo __("Last Name"); ?>" />
            <div class="error"><?php echo $errors['firstname']; ?></div>
            <div class="error"><?php echo $errors['lastname']; ?></div>
          </td>
        </tr>
        <tr>
          <td class="required"><?php echo __('Email Address'); ?>:</td>
          <td>
            <input type="email" size="40" maxlength="64" style="width: 300px" name="email"
              value="<?php echo $info['email']; ?>"
              placeholder="<?php echo __('e.g. me@mycompany.com'); ?>" />
            <div class="error"><?php echo $errors['email']; ?></div>
          </td>
        </tr>
        <tr>
          <td><?php echo __('Phone Number');?>:</td>
          <td>
            <input type="tel" size="18" name="phone" class="auto phone"
              value="<?php echo $info['phone']; ?>" />
            <?php echo __('Ext');?>
            <input type="text" size="5" name="phone_ext"
              value="<?php echo $info['phone_ext']; ?>">
            <div class="error"><?php echo $errors['phone']; ?></div>
            <div class="error"><?php echo $errors['phone_ext']; ?></div>
          </td>
        </tr>
        <tr>
          <td><?php echo __('Mobile Number');?>:</td>
          <td>
            <input type="tel" size="18" name="mobile" class="auto phone"
              value="<?php echo $info['mobile']; ?>" />
            <div class="error"><?php echo $errors['mobile']; ?></div>
          </td>
        </tr>
      </tbody>
      <!-- ================================================ -->
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Authentication'); ?>
          </th>
        </tr>
        <tr>
          <td class="required"><?php echo __('Username'); ?>:
            <span class="error">*</span></td>
          <td>
            <input type="text" size="40" style="width:300px"
              class="staff-username typeahead"
              name="username" value="<?php echo $info['username']; ?>" />
            <button type="button" class="action-button">
              <i class="icon-refresh"></i> <?php echo __('Set Password'); ?>
            </button>
            <i class="offset help-tip icon-question-sign" href="#username"></i>
            <div class="error"><?php echo $errors['username']; ?></div>
          </td>
        </tr>
<?php
$bks = array();
foreach (StaffAuthenticationBackend::allRegistered() as $ab) {
  if (!$ab->supportsInteractiveAuthentication()) continue;
  $bks[] = $ab;
}
if (count($bks) > 1) {
?>
        <tr>
          <td><?php echo __('Authentication Backend'); ?>:</td>
          <td>
            <select name="backend" id="backend-selection"
              style="width:300px" onchange="javascript:
                if (this.value != '' && this.value != 'local')
                    $('#password-fields').hide();
                else if (!$('#welcome-email').is(':checked'))
                    $('#password-fields').show();
                ">
              <option value="">&mdash; <?php echo __('Use any available backend'); ?> &mdash;</option>
<?php foreach ($bks as $ab) { ?>
              <option value="<?php echo $ab::$id; ?>" <?php
                if ($info['backend'] == $ab::$id)
                  echo 'selected="selected"'; ?>><?php
                echo $ab->getName(); ?></option>
<?php } ?>
            </select>
          </td>
        </tr>
<?php
} ?>
      </tbody>
      <!-- ================================================ -->
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Status and Settings'); ?>
          </th>
        </tr>
        <tr>
          <td colspan="2">
            <div class="error"><?php echo $errors['isadmin']; ?></div>
            <div class="error"><?php echo $errors['isactive']; ?></div>
            <label>
            <input type="checkbox" name="islocked" value="1"
              <?php echo (!$staff->isactive) ? 'checked="checked"' : ''; ?> />
              <?php echo __('Locked'); ?>
            </label>
            <br/>
            <label>
            <input type="checkbox" name="isadmin" value="1"
              <?php echo ($info['isadmin']) ? 'checked="checked"' : ''; ?> />
              <?php echo __('Administrator'); ?>
            </label>
            <br/>
            <label>
            <input type="checkbox" name="assigned_only"
              <?php echo ($info['assigned_only']) ? 'checked="checked"' : ''; ?> />
              <?php echo __('Limit ticket access to ONLY assigned tickets'); ?>
            </label>
            <br/>
            <label>
            <input type="checkbox" name="onvacation"
              <?php echo ($info['onvacation']) ? 'checked="checked"' : ''; ?> />
              <?php echo __('Vacation Mode'); ?>
            </label>
            <br/>
        </tr>
      </tbody>
    </table>

    <div style="padding:8px 3px; margin-top: 1.6em">
        <strong class="big"><?php echo __('Internal Notes');?></strong><br/>
        <?php echo __("be liberal, they're internal.");?>
    </div>

    <textarea name="notes" class="richtext">
      <?php echo $info['notes']; ?>
    </textarea>
  </div>

  <!-- ============== DEPARTMENT ACCESS =================== -->

  <div class="hidden tab_content" id="access">
    <table class="table two-column" width="940" border="0" cellspacing="0" cellpadding="2">
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Primary Department and Role'); ?>
            <span class="error">*</span>
            <div><small><?php echo __(
            "Select the departments the agent is allowed to access and optionally select an effective role."
          ); ?>
            </small></div>
          </th>
        </tr>
        <tr>
          <td>
            <select name="dept_id" id="dept_id" data-quick-add="department">
              <option value="0">&mdash; <?php echo __('Select Department');?> &mdash;</option>
              <?php
              foreach (Dept::getDepartments() as $id=>$name) {
                $sel=($info['dept_id']==$id)?'selected="selected"':'';
                echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
              }
              ?>
              <option value="0" data-quick-add>&mdash; <?php echo __('Add New');?> &mdash;</option>
            </select>
            <i class="offset help-tip icon-question-sign" href="#primary_department"></i>
            <div class="error"><?php echo $errors['dept_id']; ?></div>
            <div class="error"><?php echo $errors['role_id']; ?></div>
          </td>
          <td>
            <select name="role_id">
              <option value="0">&mdash; <?php echo __('Select Role');?> &mdash;</option>
              <?php
              foreach (Role::getRoles() as $id=>$name) {
                $sel=($info['role_id']==$id)?'selected="selected"':'';
                echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
              }
              ?>
            </select>
            <i class="offset help-tip icon-question-sign" href="#primary_role"></i>
          </td>
        </tr>
      </tbody>
      <tbody>
        <tr id="extended_access_template" class="hidden">
          <td>
            <input type="hidden" data-name="dept_access[]" value="" />
          </td>
          <td>
            <select data-name="dept_access_role">
              <option value="0">&mdash; <?php echo __('Select Role');?> &mdash;</option>
              <?php
              foreach (Role::getRoles() as $id=>$name) {
                echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
              }
              ?>
            </select>
            <span style="display:inline-block;width:20px"> </span>
            <input type="checkbox" data-name="dept_access_alerts" value="1" />
            <?php echo __('Alerts'); ?>
            <a href="#" class="pull-right drop-access" title="<?php echo __('Delete');
              ?>"><i class="icon-trash"></i></a>
          </td>
        </tr>
      </tbody>
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Extended Access'); ?>
          </th>
        </tr>
<?php
$depts = Dept::getDepartments();
foreach ($staff->dept_access as $dept_access) {
  unset($depts[$dept_access->dept_id]);
}
?>
        <tr id="add_extended_access">
          <td colspan="2">
            <i class="icon-plus-sign"></i>
            <select id="add_access" data-quick-add="department">
              <option value="0">&mdash; <?php echo __('Select Department');?> &mdash;</option>
              <?php
              foreach ($depts as $id=>$name) {
                echo sprintf('<option value="%d">%s</option>',$id,$name);
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
    </table>
  </div>

  <!-- ================= PERMISSIONS ====================== -->

  <div id="permissions" class="hidden">
<?php
    $permissions = array();
    foreach (RolePermission::allPermissions() as $g => $perms) {
        foreach ($perms as $k=>$P) {
            if (!$P['primary'])
                continue;
            if (!isset($permissions[$g]))
                $permissions[$g] = array();
            $permissions[$g][$k] = $P;
        }
    }
?>
    <ul class="alt tabs">
<?php
    $first = true;
    foreach ($permissions as $g => $perms) { ?>
      <li <?php if ($first) { echo 'class="active"'; $first=false; } ?>>
        <a href="#<?php echo Format::slugify($g); ?>"><?php echo Format::htmlchars(__($g));?></a>
      </li>
<?php } ?>
    </ul>
<?php
    $first = true;
    foreach ($permissions as $g => $perms) { ?>
    <div class="tab_content <?php if (!$first) { echo 'hidden'; } else { $first = false; }
      ?>" id="<?php echo Format::slugify($g); ?>">
      <table class="table">
<?php foreach ($perms as $k => $v) { ?>
        <tr>
          <td>
            <label>
            <?php
            echo sprintf('<input type="checkbox" name="perms[]" value="%s" %s />',
              $k, ($staff->hasPerm($k)) ? 'checked="checked"' : '');
            ?>
            &nbsp;
            <?php echo Format::htmlchars(__($v['title'])); ?>
            —
            <em><?php echo Format::htmlchars(__($v['desc'])); ?></em>
           </label>
          </td>
        </tr>
<?php   } ?>
      </table>
    </div>
<?php } ?>
  </div>

  <!-- ============== TEAM MEMBERSHIP =================== -->

  <div class="hidden tab_content" id="teams">
    <table class="table two-column" width="100%">
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Assigned Teams'); ?>
            <div><small><?php echo __(
            "Agent will have access to tickets assigned to a team they belong to regardless of the ticket's department. Alerts can be enabled for each associated team."
            ); ?>
            </small></div>
          </th>
        </tr>
<?php
$teams = Team::getTeams();
foreach ($staff->teams as $TM) {
  unset($teams[$TM->team_id]);
}
?>
        <tr id="join_team">
          <td colspan="2">
            <i class="icon-plus-sign"></i>
            <select id="add_team" data-quick-add="team">
              <option value="0">&mdash; <?php echo __('Select Team');?> &mdash;</option>
              <?php
              foreach ($teams as $id=>$name) {
                echo sprintf('<option value="%d">%s</option>', $id, $name);
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
        <tr id="team_member_template" class="hidden">
          <td>
            <input type="hidden" data-name="teams[]" value="" />
          </td>
          <td>
            <input type="checkbox" data-name="team_alerts" value="1" />
            <?php echo __('Alerts'); ?>
            <a href="#" class="pull-right drop-membership" title="<?php echo __('Delete');
              ?>"><i class="icon-trash"></i></a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <p style="text-align:center;">
      <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
      <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
      <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="helptopics.php"'>
  </p>
</form>

<script type="text/javascript">
var addAccess = function(daid, name, role, alerts, error) {
  var copy = $('#extended_access_template').clone();

  copy.find('[data-name=dept_access\\[\\]]')
    .attr('name', 'dept_access[]')
    .val(daid);
  copy.find('[data-name^=dept_access_role]')
    .attr('name', 'dept_access_role['+daid+']')
    .val(role || 0);
  copy.find('[data-name^=dept_access_alerts]')
    .attr('name', 'dept_access_alerts['+daid+']')
    .prop('checked', alerts);
  copy.find('td:first').append(document.createTextNode(name));
  copy.attr('id', '').show().insertBefore($('#add_extended_access'));
  copy.removeClass('hidden')
  if (error)
      $('<div class="error">').text(error).appendTo(copy.find('td:last'));
};

$('#add_extended_access').find('button').on('click', function() {
  var selected = $('#add_access').find(':selected');
  addAccess(selected.val(), selected.text(), 0, true);
  selected.remove();
  return false;
});

$(document).on('click', 'a.drop-access', function() {
  var tr = $(this).closest('tr');
  $('#add_access').append(
    $('<option>')
    .attr('value', tr.find('input[name^=dept_access][type=hidden]').val())
    .text(tr.find('td:first').text())
  );
  tr.fadeOut(function() { $(this).remove(); });
  return false;
});

var joinTeam = function(teamid, name, alerts, error) {
  var copy = $('#team_member_template').clone();

  copy.find('[data-name=teams\\[\\]]')
    .attr('name', 'teams[]')
    .val(teamid);
  copy.find('[data-name^=team_alerts]')
    .attr('name', 'team_alerts['+teamid+']')
    .prop('checked', alerts);
  copy.find('td:first').append(document.createTextNode(name));
  copy.attr('id', '').show().insertBefore($('#join_team'));
  copy.removeClass('hidden');
  if (error)
      $('<div class="error">').text(error).appendTo(copy.find('td:last'));
};

$('#join_team').find('button').on('click', function() {
  var selected = $('#add_team').find(':selected');
  joinTeam(selected.val(), selected.text(), true);
  selected.remove();
  return false;
});

<?php
foreach ($staff->dept_access as $dept_access) {
  echo sprintf('addAccess(%d, %s, %d, %d, %s);', $dept_access->dept_id,
    JsonDataEncoder::encode($dept_access->dept->getName()),
    $dept_access->role_id,
    $dept_access->isAlertsEnabled(),
    JsonDataEncoder::encode(@$errors['dept_access'][$dept_access->dept_id])
  );
}

foreach ($staff->teams as $member) {
  echo sprintf('joinTeam(%d, %s, %d, %s);', $member->team_id,
    JsonDataEncoder::encode($member->team->getName()),
    $member->isAlertsEnabled(),
    JsonDataEncoder::encode(@$errors['teams'][$member->team_id])
  );
}

?>
</script>
