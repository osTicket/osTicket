<?php
    $properties_form = $item ? $item->getConfigurationForm($_POST ?: null)
        : $list->getConfigurationForm($_POST ?: null);
    $hasProperties = count($properties_form->getFields()) > 0;
?>
<h3 class="drag-handle"><?php echo $list->getName(); ?> &mdash; <?php
    echo $item ? $item->getValue() : __('Add New List Item'); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>

<?php if ($hasProperties) { ?>
<ul class="tabs" id="item_tabs">
    <li class="active">
        <a href="#value"><i class="icon-reorder"></i>
        <?php echo __('Value'); ?></a>
    </li>
    <li><a href="#item-properties"><i class="icon-asterisk"></i>
        <?php echo __('Schedule Properties'); ?></a>
    </li>
</ul>
<?php } ?>

<form method="post" id="item_tabs_container" action="<?php echo $action; ?>">
    <?php
    echo csrf_token();
    $internal = $item ? $item->isInternal() : false;
?>

<div class="tab_content" id="value">
<?php
    $form = $item_form;
    include 'dynamic-form-simple.tmpl.php';
?>
</div>

<div class="tab_content hidden" id="item-properties">
<?php
    if ($hasProperties) {
        $form = $properties_form; ?>
        <div class="form-simple">
            <?php
            echo $form->getMedia();
            foreach ($form->getFields() as $name=>$f) {
                if ($f->get('label') === 'Time Zone') { ?>
                    <div class="flush-left custom-field" id="field<?php echo $f->getWidget()->id; ?>">
                    <div class="field-label">
                    <label for="<?php echo $f->getWidget()->name; ?>">
                        <?php echo $f->get('label'); ?>:
                    </label>
                    </div>
                    <div>
                        <?php
                        $TZ_NAME = 'timezone';
                        $TZ_TIMEZONE = $f->value;
                        include STAFFINC_DIR.'templates/timezone.tmpl.php'; ?>
                        <div class="error"><?php echo $errors['timezone']; ?></div>
                    </div>
                    </div>
                    <?php
                    continue;
                } ?>

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
                <em><i class="help-tip icon-question-sign" href="#schedule_hours"></i></em>
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

        <?php
    }
?>
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

<script type="text/javascript">
   // Make translatable fields translatable
   $('input[data-translate-tag], textarea[data-translate-tag]').translatable();
</script>
