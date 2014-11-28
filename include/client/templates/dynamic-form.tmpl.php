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
        if (!$field->isEditableToUsers())
            continue;
        ?>
        <tr>
            <td colspan="2" style="padding-top:8px;">
            <?php if (!$field->isBlockLevel()) { ?>
                <label for="<?php echo $field->getFormName(); ?>"><span class="<?php
                    if ($field->isRequiredForUsers()) echo 'required'; ?>">
                <?php echo Format::htmlchars($field->getLocal('label')); ?>
            <?php if ($field->isRequiredForUsers()) { ?>
                <span class="error">*</span>
            <?php }
            ?></span><?php
                if ($field->get('hint')) { ?>
                    <br /><em style="color:gray;display:inline-block"><?php
                        echo Format::htmlchars($field->getLocal('hint')); ?></em>
                <?php
                } ?>
            <br/>
            <?php
            }
            $field->render(array('client'=>true));
            ?></label><?php
            foreach ($field->errors() as $e) { ?>
                <div class="error"><?php echo $e; ?></div>
            <?php }
            $field->renderExtras(array('client'=>true));
            ?>
            </td>
        </tr>
        <?php
    }
?>
