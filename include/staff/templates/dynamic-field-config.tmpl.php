<h3><?= __('Field Configuration'); ?> &mdash; <?= $field->get('label') ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<form method="post" action="#form/field-config/<?php echo $field->get('id'); ?>">
    <?php
    echo csrf_token();
    $form = $field->getConfigurationForm();
    echo $form->getMedia();
    foreach ($form->getFields() as $name => $f) :
        ?>
        <div class="flush-left custom-field" id="field<?= $f->getWidget()->id; ?>" 
             <?php if (!$f->isVisible()) : ?>style="display:none;"<?php endif; ?>>
            <div class="field-label <?php if ($f->get('required')) : ?>required<?php endif; ?>">
                <label for="<?= $f->getWidget()->name; ?>">
                    <?= Format::htmlchars($f->get('label')); ?>:
                    <?php if ($f->get('required')) : ?><span class="error">*</span><?php endif; ?>
                </label>
                <?php if ($f->get('hint')) : ?>
                    <br/><em style="color:gray;display:inline-block"><?= Format::htmlchars($f->get('hint')); ?></em>
                <?php endif; ?>
            </div><div>
                <?php $f->render(); ?>
            </div>
            <?php foreach ($f->errors() as $e) : ?>
                <div class="error"><?= $e; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    <hr/>
    <div class="flush-left custom-field">
        <div class="field-label">
            <label for="hint" style="vertical-align:top;padding-top:0.2em"><?= __('Help Text') ?>:</label>
            <br />
            <em style="color:gray;display:inline-block"><?= __('Help text shown with the field'); ?></em>
        </div>
        <div>
            <textarea style="width:100%" name="hint" rows="2" cols="40"><?php echo Format::htmlchars($field->get('hint')); ?></textarea>
        </div>
    </div>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?= __('Reset'); ?>">
            <input type="button" value="<?= __('Cancel'); ?>" class="close">
        </span>
        <span class="buttons pull-right"><input type="submit" value="<?= __('Save'); ?>"></span>
    </p>
</form>
<div class="clear"></div>
