<?php

$info=array();
if($group && $_REQUEST['a']!='add') {
    $title = 'Update dynamic form';
    $action = 'update';
    $submit_text='Save Changes';
    $info = $group->ht;
    $newcount=2;
} else {
    $title = 'Add new dynamic form';
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
            <th colspan="4">
                <h4><?php echo $title; ?></h4>
                <em>Dynamic forms are used to combine several form sections
                into a larger form for use in the ticketing system. This
                allows for common sections to be reused among various forms
                </em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">Title:</td>
            <td colspan="3"><input size="40" type="text" name="title"
                value="<?php echo $info['title']; ?>"/></td>
        </tr>
        <tr>
            <td width="180">Description:</td>
            <td colspan="3"><textarea name="notes" rows="3" cols="40"><?php
                echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
    <tbody>
       <tr><th>Delete</th><th>Form name</th></tr>
    </tbody>
    <tbody class="sortable-rows" data-sort="sort-">
       <?php if ($group) foreach ($group->getForms() as $formatt) {
           $form = $formatt->getForm();
           $errors = $formatt->errors(); ?>
           <tr>
               <td>
                    <input type="checkbox" name="delete-<?php echo $formatt->get('id'); ?>"/>
                    <input type="hidden" name="sort-<?php echo $formatt->get('id'); ?>"
                       value="<?php echo $formatt->get('sort'); ?>"/>
                    <font class="error"><?php
                        if ($errors['sort']) echo '<br/>'; echo $errors['sort'];
                    ?></font>
               </td><td>
                   <select name="section_id-<?php echo $formatt->get('id'); ?>">
                   <?php foreach (DynamicFormSection::objects() as $form) { ?>
                       <option value="<?php echo $form->get('id'); ?>" <?php
                            if ($formatt->get('section_id') == $form->get('id'))
                                echo 'selected="selected"'; ?>>
                           <?php echo $form->get('title'); ?>
                       </option>
                   <?php } ?>
                   </select>
                    <a class="action-button" style="float:none"
                        href="dynamic-form-sections.php?id=<?php
                            echo $formatt->get('section_id'); ?>"><i class="icon-edit"></i
                            > Edit</a>
               </td>
           </tr>
       <?php }
       for ($i=0; $i<$newcount; $i++) { ?>
       <tr>
           <td><em>add</em>
               <input type="hidden" name="sort-new-<?php echo $i; ?>" size="4"/>
           </td><td>
               <select name="section_id-new-<?php echo $i; ?>">
                   <option value="0">&mdash; Select Form &mdash;</option>
               <?php foreach (DynamicFormSection::objects() as $form) { ?>
                   <option value="<?php echo $form->get('id'); ?>">
                       <?php echo $form->get('title'); ?>
                   </option>
               <?php } ?>
               </select>
            </td>
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
