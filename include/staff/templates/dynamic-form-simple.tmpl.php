<?php
        echo $form->getMedia();
        foreach ($form->getFields() as $name=>$f) { ?>
            <div class="flush-left custom-field" id="field<?php echo $f->getWidget()->id;
                ?>" <?php if (!$f->isVisible()) echo 'style="display:none;"'; ?>>
            <div class="field-label <?php if ($f->get('required')) echo 'required'; ?>">
            <label for="<?php echo $f->getWidget()->name; ?>">
      <?php if ($f->get('label')) { ?>
                <?php echo Format::htmlchars($f->get('label')); ?>:
      <?php } ?>
      <?php if ($f->get('required')) { ?>
                <span class="error">*</span>
      <?php } ?>
            </label>
            <?php
            if ($f->get('hint')) { ?>
                <br/><em style="color:gray;display:inline-block"><?php
                    echo Format::viewableImages($f->get('hint')); ?></em>
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
        <?php
        }
?>
