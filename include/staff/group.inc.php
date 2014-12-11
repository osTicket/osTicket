<?php

$info=array();
if ($group) {
    $title = __('Update Group');
    $action = 'update';
    $submit_text = __('Save Changes');
    $info = $group->getInfo();
    $info['id'] = $group->getId();
    $info['depts'] = $group->getDepartments();
    $trans['name'] = $group->getTranslateTag('name');
} else {
    $title = __('Add New Group');
    $action = 'add';
    $submit_text = __('Create Group');
    $info['isactive'] = isset($info['isactive']) ? $info['isactive'] : 1;
}

$info = Format::htmlchars(($errors && $_POST) ? array_merge($info, $_POST) : $info);
$roles = Role::getActiveRoles();

?>
<form action="" method="post" id="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="<?php echo $action; ?>">
    <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
<h2> <?php echo $group ?: __('New Group'); ?></h2>
<br>
<ul class="tabs">
    <li class="active"><a href="#group">
        <i class="icon-file"></i> <?php echo __('Group'); ?></a></li>
    <li><a href="#departments">
        <i class="icon-lock"></i> <?php echo __('Departments Access'); ?></a></li>
</ul>
<div id="group" class="tab_content">
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em><?php echo __(
                'Roles are used to define agents\' permissions'
                ); ?>&nbsp;<i class="help-tip icon-question-sign"
                href="#roles"></i></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required"><?php echo __('Name'); ?>:</td>
            <td>
                <input size="50" type="text" name="name" value="<?php echo $info['name']; ?>"
                data-translate-tag="<?php echo $trans['name']; ?>"/>
                <span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Status');?>:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php
                    echo $info['isactive'] ? 'checked="checked"' : ''; ?>><strong><?php echo __('Active');?></strong>
                &nbsp;
                <input type="radio" name="isactive" value="0" <?php
                    echo !$info['isactive'] ? 'checked="checked"' : ''; ?>><strong><?php echo __('Disabled');?></strong>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['status']; ?></span>
                <i class="help-tip icon-question-sign" href="#status"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Default Role');?>:
            </td>
            <td>
                <select name="role_id">
                    <option value="0"><?php echo __('Select One'); ?></option>
                    <?php
                    foreach ($roles as $id => $role) {
                        $sel = ($info['role_id'] == $id) ? 'selected="selected"' : '';
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $id, $sel, $role);
                    } ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['role_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#role"></i>
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <th colspan="7">
                <em><strong><?php echo __('Internal Notes'); ?></strong> </em>
            </th>
        </tr>
        <tr>
            <td colspan="7"><textarea name="notes" class="richtext no-bar"
                rows="6" cols="80"><?php
                echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
    </table>
</div>
<div id="departments" class="tab_content" style="display:none">
   <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan=2>
                <em><?php echo __('Check departments the group is allowed to access and optionally select an effective role.') ?></em>
            </th>
        </tr>
        <tr>
            <th width="40%"><?php echo __('Department'); ?></th>
            <th><?php echo __('Group Role'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach (Dept::getDepartments() as $deptId => $name) { ?>
         <tr>
            <td>
             &nbsp;
             <label>
              <?php
              $ck = ($info['depts'] && in_array($deptId, $info['depts'])) ? 'checked="checked"' : '';
              echo sprintf('%s&nbsp;&nbsp;%s',
                        sprintf('<input type="checkbox" class="dept-ckb"
                            name="depts[]" value="%s" %s />',
                            $deptId, $ck),
                        Format::htmlchars($name));
              ?>
             </label>
            </td>
            <td>
                <?php
                $DeptAccess = $group ? $group->getDepartmentsAccess() : array();
                $_name = 'dept'.$deptId.'_role_id';
                ?>
                <select name="<?php echo $_name; ?>">
                    <option value="0">&mdash; <?php
                    echo __('Group Default'); ?><?php
                    if (isset($group)) echo ' ('.$group->role->getName().')';
                    ?> &mdash;</option>
                    <?php
                    foreach ($roles as $rid => $role) {
                        $sel = '';
                        if (isset($info[$_name]))
                            $sel = ($info[$_name] == $rid) ? 'selected="selected"' : '';
                        elseif ($DeptAccess && isset($DeptAccess[$deptId]))
                            $sel = ($DeptAccess[$deptId] == $rid) ?  'selected="selected"' : '';

                        echo sprintf('<option value="%d" %s>%s</option>',
                                $rid, $sel, $role);
                    } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#dept-role"></i>
            </td>
         </tr>
         <?php
        } ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="2">
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#dept-ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#dept-ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#dept-ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
        </td>
     </tr>
    </tfoot>
   </table>
</div>
<p class="centered">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>"
        onclick='window.location.href="?"'>
</p>
</form>
