<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');
?>
<h2><?php echo __('Knowledge Base Settings and Options');?></h2>
<form action="settings.php?t=kb" method="post" class="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="kb" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __("Disabling knowledge base disables clients' interface.");?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" valign="top"><?php echo __('Knowledge Base Status'); ?>:</td>
            <td>
                <input type="checkbox" name="enable_kb" value="1" <?php echo $config['enable_kb']?'checked="checked"':''; ?>>
                <?php echo __('Enable Knowledge Base'); ?>
                <i class="help-tip icon-question-sign" href="#knowledge_base_status"></i>
                <div class="error"><?php echo $errors['enable_kb']; ?></div>
                <input type="checkbox" name="restrict_kb" value="1" <?php echo $config['restrict_kb']?'checked="checked"':''; ?> >
                <?php echo __('Require Client Login'); ?>
                <i class="help-tip icon-question-sign" href="#restrict_kb"></i>
                <div class="error"><?php echo $errors['restrict_kb']; ?></div>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Canned Responses');?>:</td>
            <td>
                <input type="checkbox" name="enable_premade" value="1" <?php echo $config['enable_premade']?'checked="checked"':''; ?> >
                <?php echo __('Enable Canned Responses'); ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['enable_premade']; ?></font>
                <i class="help-tip icon-question-sign" href="#canned_responses"></i>
            </td>
        </tr>
    </tbody>
</table>
<p style="text-align:center;">
    <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes'); ?>">
    <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes'); ?>">
</p>
</form>
