    <h3><?php echo __('Field Configuration'); ?> &mdash; <?php echo $field->get('label') ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <form method="post" action="#form/field-config/<?php
            echo $field->get('id'); ?>">
        <?php
        echo csrf_token();
        $form = $field->getConfigurationForm();
        echo $form->getMedia();
        foreach ($form->getFields() as $name=>$f) { ?>
            <div class="flush-left custom-field" id="field<?php echo $f->getWidget()->id;
                ?>" <?php if (!$f->isVisible()) echo 'style="display:none;"'; ?>>
            <div class="field-label <?php if ($f->get('required')) echo 'required'; ?>">
            <label for="<?php echo $f->getWidget()->name; ?>">
                <?php echo Format::htmlchars($f->get('label')); ?>:
      <?php if ($f->get('required')) { ?>
                <span class="error">*</span>
      <?php } ?>
            </label>
            <?php
            if ($f->get('hint')) { ?>
                <br/><em style="color:gray;display:inline-block"><?php
                    echo Format::htmlchars($f->get('hint')); ?></em>
            <?php
            } ?>
            </div><div>
            <?php
            $f->render();
            ?>
            </div>
            <?php
            foreach ($f->errors() as $e) { ?>
                <div class="error"><?php echo $e; ?></div>
            <?php } ?>
            </div>
        <?php }
        ?>
        <hr/>
        <div class="flush-left custom-field">
        <div class="field-label">
        <label for="hint"
            style="vertical-align:top;padding-top:0.2em"><?php echo __('Help Text') ?>:</label>
            <br />
            <em style="color:gray;display:inline-block">
                <?php echo __('Help text shown with the field'); ?></em>
        </div>
        <div>
        <textarea style="width:100%" name="hint" rows="2" cols="40"><?php
            echo Format::htmlchars($field->get('hint')); ?></textarea>
        </div>
        </div>
        <hr>
        <p class="full-width">
            <span class="buttons pull-left">
                <input type="reset" value="<?php echo __('Reset'); ?>">
                <input type="button" value="<?php echo __('Cancel'); ?>" class="close">
            </span>
            <span class="buttons pull-right">
                <input type="submit" value="<?php echo __('Save'); ?>">
            </span>
         </p>
    </form>
    <div class="clear"></div>
