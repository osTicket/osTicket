<?php
    // Form headline and deck with a horizontal divider above and an extra
    // space below.
    // XXX: Would be nice to handle the decoration with a CSS class
    ?>
    <tr><td colspan="2"><hr />
    <div class="form-header" style="margin-bottom:0.5em">
    <?php print ($form instanceof DynamicFormEntry) 
        ? $form->getForm()->getMedia() : $form->getMedia(); ?>
    <h3><?php echo Format::htmlchars($form->getTitle()); ?></h3>
    <em><?php echo Format::htmlchars($form->getInstructions()); ?></em>
    </div>
    </td></tr>
    <?php
    // Form fields, each with corresponding errors follows. Fields marked
    // 'private' are not included in the output for clients
    global $thisclient;
    foreach ($form->getFields() as $field) {
        if (!$field->isVisibleToUsers())
            continue;
        ?>
        <tr>
            <?php if ($field->isBlockLevel()) { ?>
                <td colspan="2">
            <?php
            }
            else { ?>
                <td><label for="<?php echo $field->getFormName(); ?>" class="<?php
                    if ($field->get('required')) echo 'required'; ?>">
                <?php echo Format::htmlchars($field->get('label')); ?>:</label></td><td>
            <?php
            }
            $field->render('client'); ?>
            <?php if ($field->get('required')) { ?>
                <font class="error">*</font>
            <?php
            }
            if ($field->get('hint') && !$field->isBlockLevel()) { ?>
                <br /><em style="color:gray;display:inline-block"><?php
                    echo Format::htmlchars($field->get('hint')); ?></em>
            <?php
            }
            foreach ($field->errors() as $e) { ?>
                <br />
                <font class="error"><?php echo $e; ?></font>
            <?php }
            $field->renderExtras('client');
            ?>
            </td>
        </tr>
        <?php
    }
?>
