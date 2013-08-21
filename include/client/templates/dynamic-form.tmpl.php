<?php
    // Form headline and deck with a horizontal divider above and an extra
    // space below.
    // XXX: Would be nice to handle the decoration with a CSS class
    ?>
    <tr><td colspan="2"><hr />
    <div class="form-header" style="margin-bottom:0.5em">
    <h3><?php echo Format::htmlchars($form->getTitle()); ?></h3>
    <em><?php echo Format::htmlchars($form->getInstructions()); ?></em>
    </div>
    </td></tr>
    <?php
    // Form fields, each with corresponding errors follows. Fields marked
    // 'private' are not included in the output for clients
    global $thisclient;
    foreach ($form->getFields() as $field) {
        if ($thisclient) {
            switch ($field->get('name')) {
                case 'name':
                    $field->value = $thisclient->getName();
                    break;
                case 'email':
                    $field->value = $thisclient->getEmail();
                    break;
                case 'phone':
                    $field->value = $thisclient->getPhone();
                    break;
            }
        }
        if ($field->get('private'))
            continue;
        ?>
        <tr><td class="<?php if ($field->get('required')) echo 'required'; ?>">
            <?php echo Format::htmlchars($field->get('label')); ?>:</td>
            <td><?php $field->render(); ?>
            <?php if ($field->get('required')) { ?>
                <font class="error">*</font>
            <?php
            }
            if ($field->get('hint')) { ?>
                <br /><em style="color:gray;display:inline-block"><?php
                    echo Format::htmlchars($field->get('hint')); ?></em>
            <?php
            }
            foreach ($field->errors() as $e) { ?>
                <br />
                <font class="error"><?php echo $e; ?></font>
            <?php } ?>
            </td>
        </tr>
        <?php
    }
?>
