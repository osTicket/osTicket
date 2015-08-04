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
<form action="" method="post" id="save">
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
        <li class="active"><a href="#definition"><i class="icon-file"></i> <?php echo __('Definition'); ?></a></li>
        <li><a href="#permissions"><i class="icon-lock"></i> <?php echo __('Permissions'); ?></a></li>
    </ul>
    <div id="definition" class="tab_content">
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
    <div id="permissions" class="hidden">
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
        <ul class="alt tabs">
            <?php
                $first = true;
                foreach ($buckets as $g => $perms) { ?>
                    <li <?php if ($first) { echo 'class="active"'; $first=false; } ?>>
                        <a href="#<?php echo Format::slugify($g); ?>"><?php echo Format::htmlchars(__($g));?></a>
                    </li>
            <?php } ?>
        </ul>
        <?php
        $first = true;
        foreach ($buckets as $g => $perms) { ?>
        <div class="tab_content <?php if (!$first) { echo 'hidden'; } else { $first = false; }
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
        <?php } ?>
    </div>
    <p class="centered">
        <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
        <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>">
        <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>"
            onclick='window.location.href="?"'>
    </p>
</form>
