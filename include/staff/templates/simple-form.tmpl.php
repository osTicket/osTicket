<?php if ($form->getTitle()) { ?>
    <h1><strong><?php echo Format::htmlchars($form->getTitle()); ?></strong>:
        <div><small><?php echo Format::htmlchars($form->getInstructions()); ?></small></div>
    </h1>
    <?php
    }
    foreach ($form->getFields() as $field) { ?>
        <div class="form-field"><?php
        if (!$field->isBlockLevel()) { ?>
            <div class="<?php if ($field->isRequired()) echo 'required';
                ?>" style="display:inline-block;width:27%;">
                <?php echo Format::htmlchars($field->getLocal('label')); ?>:
            <?php if ($field->isRequired()) { ?>
                <span class="error">*</span>
            <?php
            }
            if ($field->get('hint')) { ?>
                <div class="faded hint"><?php
                echo Format::viewableImages($field->getLocal('hint'));
                ?></div>
<?php       } ?>
            </div>
            <div style="display:inline-block;max-width:73%"><?php
        }
        $field->render($options);
        foreach ($field->errors() as $e) { ?>
            <div class="error"><?php echo Format::htmlchars($e); ?></div>
        <?php }
        if (!$field->isBlockLevel()) { ?>
            </div>
        <?php } ?>
        </div>
<?php } ?>
<style type="text/css">
.form-field div {
  vertical-align: top;
}
.form-field div + div {
  padding-left: 10px;
}
.form-field .hint {
  font-size: 95%;
}
.form-field {
  margin-top: 5px;
  padding: 5px 0;
}
</style>
