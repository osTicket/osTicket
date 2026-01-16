<?php
$info = $instance ? $instance->getInfo() : $plugin->getNewInstanceDefaults($_GET);
$info = Format::htmlchars(($errors && $_POST) ? array_merge($info, $_POST) : $info, true);
$form = $instance ? $instance->getForm() : $plugin->getConfigForm($info);
?>
<ul class="clean tabs" id="instance-tabs"> <li class="<?php
    if (!$instance) echo 'active '; ?> "><a href="#instance">
        <i class="icon-info-sign"></i> <?php echo __('Instance'); ?></a></li>
    <li <?php if ($instance) echo 'class="active"'; ?>><a href="#config">
        <i class="icon-cog"></i> <?php echo __('Config'); ?></a></li>
</ul>
<div id="instance-tabs_container">
    <div id="instance" class="tab_content <?php if ($instance) echo 'hidden'; ?>">
        <table class="form_table" width="100%" border="0" cellspacing="0" cellpadding="2">
        <thead>
            <tr>
                <th colspan="2">
                    <em><?php echo __('Instance Name and Status'); ?></em>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="180" class="required"><?php echo __('Name'); ?>:</td>
                <td>
                    <input size="50" type="text" autofocus
                        name="name"
                        value="<?php echo $info['name']; ?>"/><br/>
                    <span class="error"><?php echo  $errors['name']; ?></span>
                </td>
            </tr>
            <tr>
                <td width="180" class="required"><?php echo __('Status'); ?>:</td>
                <td><select name="isactive">
                    <?php
                    foreach (array(1 => __('Enabled'), 0 => __('Disabled')) as $key => $desc) { ?>
                    <option value="<?php echo $key; ?>" <?php
                        if ($key == $info['isactive']) echo 'selected="selected"';
                        ?>><?php echo $desc; ?></option>
                    <?php } ?>
                    </select>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="7">
                    <em><strong><?php echo __('Internal Notes'); ?>:</strong>
                    <?php echo __("Instance description and notes"); ?></em>
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
    <div id="config" class="tab_content <?php if (!$instance) echo 'hidden'; ?>"
        style="padding: 0 2px 0 2px;" >
    <?php
        include 'simple-form.tmpl.php';
    ?>
    </div>
</div>
