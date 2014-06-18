<?php

$info=array();
if($form && $_REQUEST['a']!='add') {
    $title = 'Update custom form section';
    $action = 'update';
    $url = "?id=".urlencode($_REQUEST['id']);
    $submit_text='Save Changes';
    $info = $form->ht;
    $newcount=2;
} else {
    $title = 'Add new custom form section';
    $action = 'add';
    $url = '?a=add';
    $submit_text='Add Form';
    $newcount=4;
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);

?>
<form class="manage-form" action="<?php echo $url ?>" method="post" id="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="<?php echo $action; ?>">
    <input type="hidden" name="a" value="<?php echo $action; ?>">
    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
    <h2>Custom Form</h2>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Custom forms are used to allow custom data to be
                associated with tickets</em>
            </th>
        </tr>
    </thead>
    <tbody style="vertical-align:top">
        <tr>
            <td width="180" class="required">Title:</td>
            <td><input type="text" name="title" size="40" value="<?php
                echo $info['title']; ?>"/>
                <i class="help-tip icon-question-sign" href="#form_title"></i>
                <font class="error"><?php
                    if ($errors['title']) echo '<br/>'; echo $errors['title']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="180">Instructions:</td>
            <td><textarea name="instructions" rows="3" cols="40"><?php
                echo $info['instructions']; ?></textarea>
                <i class="help-tip icon-question-sign" href="#form_instructions"></i>
            </td>
        </tr>
    </tbody>
    </table>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <?php if ($form && $form->get('type') == 'T') { ?>
    <thead>
        <tr>
            <th colspan="7">
                <em><strong>User Information Fields</strong> more information here</em>
            </th>
        </tr>
        <tr>
            <th></th>
            <th>Label</th>
            <th>Type</th>
            <th>Internal</th>
            <th>Required</th>
            <th>Variable</th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody>
    <?php
        $uform = UserForm::objects()->all();
        $ftypes = FormField::allTypes();
        foreach ($uform[0]->getFields() as $f) {
            if ($f->get('private')) continue;
        ?>
        <tr>
            <td></td>
            <td><?php echo $f->get('label'); ?></td>
            <td><?php $t=FormField::getFieldType($f->get('type')); echo $t[0]; ?></td>
            <td><input type="checkbox" disabled="disabled"/></td>
            <td><input type="checkbox" disabled="disabled"
                <?php echo $f->get('required') ? 'checked="checked"' : ''; ?>/></td>
            <td><?php echo $f->get('name'); ?></td>
            <td><input type="checkbox" disabled="disabled"/></td></tr>

        <?php } ?>
    </tbody>
    <?php } # form->type == 'T' ?>
    <thead>
        <tr>
            <th colspan="7">
                <em><strong>Form Fields</strong> fields available for ticket information</em>
            </th>
        </tr>
        <tr>
            <th nowrap>Sort
                <i class="help-tip icon-question-sign" href="#field_sort"></i></th>
            <th nowrap>Label
                <i class="help-tip icon-question-sign" href="#field_label"></i></th>
            <th nowrap>Type
                <i class="help-tip icon-question-sign" href="#field_type"></i></th>
            <th nowrap>Internal
                <i class="help-tip icon-question-sign" href="#field_internal"></i></th>
            <th nowrap>Required
                <i class="help-tip icon-question-sign" href="#field_required"></i></th>
            <th nowrap>Variable
                <i class="help-tip icon-question-sign" href="#field_variable"></i></th>
            <th nowrap>Delete
                <i class="help-tip icon-question-sign" href="#field_delete"></i></th>
        </tr>
    </thead>
    <tbody class="sortable-rows" data-sort="sort-">
    <?php if ($form) foreach ($form->getDynamicFields() as $f) {
        $id = $f->get('id');
        $deletable = !$f->isDeletable() ? 'disabled="disabled"' : '';
        $force_name = $f->isNameForced() ? 'disabled="disabled"' : '';
        $force_privacy = $f->isPrivacyForced() ? 'disabled="disabled"' : '';
        $force_required = $f->isRequirementForced() ? 'disabled="disabled"' : '';
        $fi = $f->getImpl();
        $ferrors = $f->errors(); ?>
        <tr>
            <td><i class="icon-sort"></i></td>
            <td><input type="text" size="32" name="label-<?php echo $id; ?>"
                value="<?php echo Format::htmlchars($f->get('label')); ?>"/>
                <font class="error"><?php
                    if ($ferrors['label']) echo '<br/>'; echo $ferrors['label']; ?>
            </td>
            <td nowrap><select name="type-<?php echo $id; ?>" <?php
                if (!$fi->isChangeable()) echo 'disabled="disabled"'; ?>>
                <?php foreach (FormField::allTypes() as $group=>$types) {
                        ?><optgroup label="<?php echo Format::htmlchars($group); ?>"><?php
                        foreach ($types as $type=>$nfo) {
                            if ($f->get('type') != $type
                                    && isset($nfo[2]) && !$nfo[2]) continue; ?>
                <option value="<?php echo $type; ?>" <?php
                    if ($f->get('type') == $type) echo 'selected="selected"'; ?>>
                    <?php echo $nfo[0]; ?></option>
                    <?php } ?>
                </optgroup>
                <?php } ?>
            </select>
            <?php if ($f->isConfigurable()) { ?>
                <a class="action-button" style="float:none;overflow:inherit"
                    href="#ajax.php/form/field-config/<?php
                        echo $f->get('id'); ?>"
                    onclick="javascript:
                        $('#overlay').show();
                        $('#field-config .body').load($(this).attr('href').substr(1));
                        $('#field-config').show();
                        return false;
                    "><i class="icon-edit"></i> Config</a>
            <?php } ?>
            <div class="error" style="white-space:normal"><?php
                if ($ferrors['type']) echo $ferrors['type'];
            ?></div>
            </td>
            <td><input type="checkbox" name="private-<?php echo $id; ?>"
                <?php if ($f->get('private')) echo 'checked="checked"'; ?>
                <?php echo $force_privacy ?>/></td>
            <td><input type="checkbox" name="required-<?php echo $id; ?>"
                <?php if ($f->get('required')) echo 'checked="checked"'; ?>
                <?php echo $force_required ?>/>
            </td>
            <td>
                <input type="text" size="20" name="name-<?php echo $id; ?>"
                    value="<?php echo Format::htmlchars($f->get('name'));
                    ?>" <?php echo $force_name ?>/>
                <font class="error"><?php
                    if ($ferrors['name']) echo '<br/>'; echo $ferrors['name'];
                ?></font>
                </td>
            <td><input class="delete-box" type="checkbox" name="delete-<?php echo $id; ?>"
                    data-field-label="<?php echo $f->get('label'); ?>"
                    data-field-id="<?php echo $id; ?>"
                    <?php echo $deletable; ?>/>
                <input type="hidden" name="sort-<?php echo $id; ?>"
                    value="<?php echo $f->get('sort'); ?>"/>
                </td>
        </tr>
    <?php
    }
    for ($i=0; $i<$newcount; $i++) { ?>
            <td><em>+</em>
                <input type="hidden" name="sort-new-<?php echo $i; ?>"
                    value="<?php echo $info["sort-new-$i"]; ?>"/></td>
            <td><input type="text" size="32" name="label-new-<?php echo $i; ?>"
                value="<?php echo $info["label-new-$i"]; ?>"/></td>
            <td><select name="type-new-<?php echo $i; ?>">
                <?php foreach (FormField::allTypes() as $group=>$types) {
                    ?><optgroup label="<?php echo Format::htmlchars($group); ?>"><?php
                    foreach ($types as $type=>$nfo) {
                        if (isset($nfo[2]) && !$nfo[2]) continue; ?>
                <option value="<?php echo $type; ?>"
                    <?php if ($info["type-new-$i"] == $type) echo 'selected="selected"'; ?>>
                    <?php echo $nfo[0]; ?>
                </option>
                    <?php } ?>
                </optgroup>
                <?php } ?>
            </select></td>
            <td><input type="checkbox" name="private-new-<?php echo $i; ?>"
            <?php if ($info["private-new-$i"]
                || (!$_POST && $form && $form->get('type') == 'U'))
                    echo 'checked="checked"'; ?>/></td>
            <td><input type="checkbox" name="required-new-<?php echo $i; ?>"
                <?php if ($info["required-new-$i"]) echo 'checked="checked"'; ?>/></td>
            <td><input type="text" size="20" name="name-new-<?php echo $i; ?>"
                value="<?php echo $info["name-new-$i"]; ?>"/>
                <font class="error"><?php
                    if ($errors["new-$i"]['name']) echo '<br/>'; echo $errors["new-$i"]['name'];
                ?></font>
            <td></td>
        </tr>
    <?php } ?>
    </tbody>
    <tbody>
        <tr>
            <th colspan="7">
                <em><strong>Internal Notes:</strong> be liberal, they're internal</em>
            </th>
        </tr>
        <tr>
            <td colspan="7"><textarea class="richtext no-bar" name="notes"
                rows="6" cols="80"><?php
                echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
    </table>
<p class="centered">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="?"'>
</p>

<div style="display:none;" class="draggable dialog" id="delete-confirm">
    <h3><i class="icon-trash"></i> Remove Existing Data?</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p>
        <strong>You are about to delete <span id="deleted-count"></span> fields.</strong>
        Would you also like to remove data currently entered for this field?
        <em>If you opt not to remove the data now, you will have the option
        to delete the the data when editing it</em>
    </p><p style="color:red">
        Deleted data CANNOT be recovered.
    </p>
    <hr>
    <div id="deleted-fields"></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="button" value="No, Cancel" class="close">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Continue" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
</form>

<div style="display:none;" class="dialog draggable" id="field-config">
    <div class="body"></div>
</div>

<script type="text/javascript">
$('form.manage-form').on('submit.inline', function(e) {
    var formObj = this, deleted = $('input.delete-box:checked', this);
    if (deleted.length) {
        e.stopImmediatePropagation();
        $('#overlay').show();
        $('#deleted-fields').empty();
        deleted.each(function(i, e) {
            $('#deleted-fields').append($('<p></p>')
                .append($('<input/>').attr({type:'checkbox',name:'delete-data-'
                    + $(e).data('fieldId')})
                ).append($('<strong>').html(
                    'Remove all data entered for <u>' + $(e).data('fieldLabel') + '</u>'
                ))
            );
        });
        $('#delete-confirm').show().delegate('input.confirm', 'click.confirm', function() {
            $('.dialog#delete-confirm').hide();
            $(formObj).unbind('submit.inline');
            $(window).unbind('beforeunload');
            $('#loading').show();
        })
        return false;
    }
    // TODO: Popup the 'please wait' dialog
    $(window).unbind('beforeunload');
    $('#loading').show();
});
</script>
