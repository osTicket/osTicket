<div class="form-simple">
    <?php
    echo $form->getMedia($options);
    foreach ($form->getFields() as $name=>$f) { ?>
        <div class="flush-left custom-field" id="field<?php echo $f->getWidget()->id;
            ?>" <?php if (!$f->isVisible()) echo 'style="display:none;"'; ?>>
        <div>
  <?php if ($f->get('label')) { ?>
        <div class="field-label <?php if ($f->get('required')) echo 'required'; ?>">
        <label for="<?php echo $f->getWidget()->name; ?>">
            <?php echo Format::htmlchars($f->get('label')); ?>:
  <?php if ($f->get('required')) { ?>
            <span class="error">*</span>
  <?php } ?>
        </label>
        </div>
  <?php } ?>
        <?php
        if ($f->get('hint')) { ?>
            <em style="color:gray;display:block"><?php
                echo Format::viewableImages($f->get('hint')); ?></em>
        <?php
        } ?>
        </div><div>
        <?php
        $f->render($options);
        ?>
        </div>
        <?php
        if ($f->errors()) { ?>
            <div id="field<?php echo $f->getWidget()->id; ?>_error">
            <?php
            foreach ($f->errors() as $e) { ?>
                <div class="error"><?php echo $e; ?></div>
            <?php
            } ?>
            </div>
        <?php
        } ?>
        </div>
    <?php
    }
    $form->emitJavascript($options);
    ?>
</div>
