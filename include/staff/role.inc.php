<?php

$info=array();
if ($role) {
    $title = __('Update Role');
    $action = 'update';
    $submit_text = __('Save Changes');
    $info = $role->getInfo();
    $trans['name'] = $role->getTranslateTag('name');
    $newcount=2;
} else {
    $title = __('Add New Role');
    $action = 'add';
    $submit_text = __('Add Role');
    $newcount=4;
}

$info = Format::htmlchars(($errors && $_POST) ? array_merge($info, $_POST) : $info);

?>
<div class="subnav">

    <div class="float-left subnavtitle" id="ticketviewtitle">
       <?php echo __('Update Role');?> <?php if (isset($info['name'])) { ?>
    -  <span class ="text-pink"><?php echo $info['name']; ?><span>
    <?php } ?>
    </div>

    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
    &nbsp;
    </div>
    <div class="clearfix"></div>
</div>

<div class="card-box">

<div class="row">
    <div class="col">
<form action="" method="post" class="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="<?php echo $action; ?>">
    <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
    
    <ul class="nav nav-tabs" role="tablist" style="margin-top:10px;">
  <li class="nav-item">
    <a class="nav-link active" href="#definition" role="tab" data-toggle="tab"><i class="icon-user"></i>&nbsp;<?php echo __('Definition'); ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="#permissions" role="tab" data-toggle="tab"><i class="icon-pushpin"></i>&nbsp;<?php echo __('Permissions'); ?></a>
  </li>
</ul>
<div class="tab-content">
<div role="tabpanel" class="tab-pane active" id="definition">

        <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
            <thead>
                <tr>
                    <th colspan="2">
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
                        <input size="50" type="text" name="name" value="<?php echo
                        $info['name']; ?>" data-translate-tag="<?php echo $trans['name']; ?>"
                        autofocus/>
                        <span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
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
    <div role="tabpanel" class="tab-pane " id="permissions">
        <?php
            $setting = $role ? $role->getPermissionInfo() : array();
            // Eliminate groups without any department-specific permissions
            $buckets = array();
            foreach (RolePermission::allPermissions() as $g => $perms) {
                foreach ($perms as $k => $v) {
                if ($v['primary'])
                    continue;
                    $buckets[$g][$k] = $v;
            }
        } ?>
        <ul class="nav nav-tabs" role="tablist" style="margin-top:10px;">
            <?php
                $first = true;
                foreach ($buckets as $g => $perms) { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($first) { echo ' active '; $first=false; } ?>" role="tab" data-toggle="tab"  href="#<?php echo Format::slugify($g); ?>"><?php echo Format::htmlchars(__($g));?></a>
                    </li>
            <?php } ?>
        </ul>
        <div class="tab-content">
        <?php
        $first = true;
        foreach ($buckets as $g => $perms) { ?>
        <div role="tabpanel" class="tab-pane <?php if ($first) { echo 'active'; } else { $first = false; }
            ?>" id="<?php echo Format::slugify($g); ?>">
            <table class="table">
                <?php foreach ($perms as $k => $v) { ?>
                <tr>
                    <td>
                        <label>
                            <?php
                            echo sprintf('<input type="checkbox" name="perms[]" value="%s" %s />',
                            $k, (isset($setting[$k]) && $setting[$k]) ?  'checked="checked"' : ''); ?>
                            &nbsp;
                            <?php echo Format::htmlchars(__($v['title'])); ?>
                            —
                            <em><?php echo Format::htmlchars(__($v['desc']));
                            ?></em>
                        </label>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>
       
        <?php } ?> </div>
    </div>
    </div>
    <div><br>
        <input type="submit" name="submit" value="<?php echo $submit_text; ?>" class=" btn btn-sm btn-primary">
        <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>" class=" btn btn-sm btn-warning">
        <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>" class=" btn btn-sm btn-danger"
            onclick='window.location.href="?"'>
    
    </div>
</form>

</div></div></div>