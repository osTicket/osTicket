<?php

$info=array();
if($form && $_REQUEST['a']!='add') {
    $title = 'Update dynamic form section';
    $action = 'update';
    $submit_text='Save Changes';
    $info = $form->ht;
    $newcount=2;
} else {
    $title = 'Add new dynamic form section';
    $action = 'add';
    $submit_text='Add Form';
    $newcount=4;
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);

?>
<form action="?" method="post" id="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="<?php echo $action; ?>">
    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
    <h2>Dynamic Form</h2>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Dynamic forms are used to allow custom data to be
                associated with tickets</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">Title:</td>
            <td><input type="text" name="title" size="40" value="<?php
                echo $info['title']; ?>"/></td>
        </tr>
        <tr>
            <td width="180">Instructions:</td>
            <td><textarea name="instructions" rows="3" cols="40"><?php
                echo $info['instructions']; ?></textarea>
            </td>
        </tr>
        <tr>
            <td width="180">Internal Notes:</td>
            <td><textarea name="notes" rows="4" cols="80"><?php
                echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
    </table>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="7">
                <em>Form Fields</em>
            </th>
        </tr>
        <tr>
            <th>Delete</th>
            <th>Label</th>
            <th>Type</th>
            <th>Name</th>
            <th>Private</th>
            <th>Required</th>
        </tr>
    </thead>
    <tbody class="sortable-rows" data-sort="sort-">
    <?php if ($form) foreach ($form->getFields() as $f) {
        $id = $f->get('id');
        $errors = $f->errors(); ?>
        <tr>
            <td><input type="checkbox" name="delete-<?php echo $id; ?>"/>
                <input type="hidden" name="sort-<?php echo $id; ?>"
                    value="<?php echo $f->get('sort'); ?>"/>
                <font class="error"><?php
                    if ($errors['sort']) echo '<br/>'; echo $errors['sort'];
                ?></font>
                </td>
            <td><input type="text" size="32" name="label-<?php echo $id; ?>"
                value="<?php echo $f->get('label'); ?>"/></td>
            <td><select name="type-<?php echo $id; ?>">
                <?php foreach (FormField::allTypes() as $type=>$nfo) { ?>
                <option value="<?php echo $type; ?>" <?php
                    if ($f->get('type') == $type) echo 'selected="selected"'; ?>>
                    <?php echo $nfo[0]; ?></option>
                <?php } ?>
            </select>
            <?php if ($f->isConfigurable()) { ?>
                <a class="action-button" style="float:none"
                    href="ajax.php/form/field-config/<?php
                        echo $f->get('id'); ?>"
                    onclick="javascript:
                        $('#overlay').show();
                        $('#field-config .body').load(this.href);
                        $('#field-config').show();
                        return false;
                    "><i class="icon-edit"></i> Config</a>
            <?php } ?></td>
            <td>
                <input type="text" size="20" name="name-<?php echo $id; ?>"
                    value="<?php echo $f->get('name'); ?>"/>
                <font class="error"><?php
                    if ($errors['name']) echo '<br/>'; echo $errors['name'];
                ?></font>
                </td>
            <td><input type="checkbox" name="private-<?php echo $id; ?>"
                <?php if ($f->get('private')) echo 'checked="checked"'; ?>/></td>
            <td><input type="checkbox" name="required-<?php echo $id; ?>"
                <?php if ($f->get('required')) echo 'checked="checked"'; ?>/></td>
        </tr>
    <?php
    }
    for ($i=0; $i<$newcount; $i++) { ?>
            <td><em>add</em>
                <input type="hidden" name="sort-new-<?php echo $i; ?>"/></td>
            <td><input type="text" size="32" name="label-new-<?php echo $i; ?>"/></td>
            <td><select name="type-new-<?php echo $i; ?>">
                <?php foreach (FormField::allTypes() as $type=>$nfo) { ?>
                <option value="<?php echo $type; ?>">
                    <?php echo $nfo[0]; ?></option>
                <?php } ?>
            </select></td>
            <td><input type="text" size="20" name="name-new-<?php echo $i; ?>"/></td>
            <td><input type="checkbox" name="private-new-<?php echo $i; ?>"/></td>
            <td><input type="checkbox" name="required-new-<?php echo $i; ?>"/></td>
        </tr>
    <?php } ?>
    </tbody>
    </table>
<p style="padding-left:225px;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="?"'>
</p>
</form>

<div style="display:none;" class="dialog" id="field-config">
    <div class="body"></div>
</div>
