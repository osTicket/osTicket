<div><?php
foreach ($form->getFields() as $field) { ?>
    <span style="display:inline-block;padding-right:5px;vertical-align:top">
        <span class="<?php if ($field->get('required')) echo 'required'; ?>">
            <?php echo Format::htmlchars($field->get('label')); ?></span>
        <div><?php
        $field->render(); ?>
        <?php if ($field->get('required')) { ?>
            <span class="error">*</span>
        <?php
        }
        if ($field->get('hint') && !$field->isBlockLevel()) { ?>
            <br/><em style="color:gray;display:inline-block"><?php
                echo Format::htmlchars($field->get('hint')); ?></em>
        <?php
        }
        foreach ($field->errors() as $e) { ?>
            <br />
            <span class="error"><?php echo Format::htmlchars($e); ?></span>
        <?php } ?>
        </div>
    </span><?php
} ?>
</div>
