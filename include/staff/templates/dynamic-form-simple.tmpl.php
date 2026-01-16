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
        if ( $f->getLabel() == "Priority Level") {
            echo "
            <div class='priority-levels'>
				<div class='group' id='priority-4'><div class='color'>&nbsp;</div><div class='input-wrap'><input type='radio' name='" . $f->getWidget()->name . "' value='4' " . ( $f->getWidget()->value == 4 ? "checked='checked'" : "" ) . ">Emergency</div></div>
				<div class='group' id='priority-3'><div class='color'>&nbsp;</div><div class='input-wrap'><input type='radio' name='" . $f->getWidget()->name . "' value='3' " . ( $f->getWidget()->value == 3 ? "checked='checked'" : "" ) . ">High</div></div>
				<div class='group' id='priority-2'><div class='color'>&nbsp;</div><div class='input-wrap'><input type='radio' name='" . $f->getWidget()->name . "' value='2' " . ( $f->getWidget()->value == 2 ? "checked='checked'" : "" ) . ">Normal</div></div>
				<div class='group' id='priority-1'><div class='color'>&nbsp;</div><div class='input-wrap'><input type='radio' name='" . $f->getWidget()->name . "' value='1' " . ( $f->getWidget()->value == 1 ? "checked='checked'" : "" ) . ">Low</div></div>
            </div>";
        }
        else $f->render($options);
        
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
