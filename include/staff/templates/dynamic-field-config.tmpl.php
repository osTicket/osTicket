    <h3 class="drag-handle"><?php echo __('Field Configuration'); ?> &mdash; <?php echo $field->get('label') ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <form method="post" action="#form/field-config/<?php
            echo $field->get('id'); ?>">
<ul class="tabs" id="fieldtabs">
    <li class="active"><a href="#config"><i class="icon-cogs"></i> <?php echo __('Field Setup'); ?></a></li>
    <li><a href="#visibility"><i class="icon-beaker"></i> <?php echo __('Settings'); ?></a></li>
</ul>

<div class="hidden tab_content" id="visibility">
    <div>
    <div class="span4">
        <div style="margin-bottom:5px"><strong><?php echo __('Enabled'); ?></strong>
        <i class="help-tip icon-question-sign"
            data-title="<?php echo __('Enabled'); ?>"
            data-content="<?php echo __('This field can be disabled which will remove it from the form for new entries, but will preserve the data on all current entries.'); ?>"></i>
        </div>
    </div>
    <div class="span6">
    <input type="checkbox" name="flags[]" value="<?php
            echo DynamicFormField::FLAG_ENABLED; ?>" <?php
            if ($field->hasFlag(DynamicFormField::FLAG_ENABLED)) echo 'checked="checked"';
            if ($field->hasFlag(DynamicFormField::FLAG_MASK_DISABLE)) echo ' disabled="disabled"';
        ?>> <?php echo __('Enabled'); ?><br/>
    </div>
    <hr class="faded"/>

    <div class="span4">
        <div style="margin-bottom:5px"><strong><?php echo __('Visible'); ?></strong>
        <i class="help-tip icon-question-sign"
            data-title="<?php echo __('Visible'); ?>"
            data-content="<?php echo __('Making fields <em>visible</em> allows agents and endusers to view and create information in this field.'); ?>"></i>
        </div>
    </div>
    <div class="span3">
        <input type="checkbox" name="flags[]" value="<?php
            echo DynamicFormField::FLAG_CLIENT_VIEW; ?>" <?php
            if ($field->hasFlag(DynamicFormField::FLAG_CLIENT_VIEW)) echo 'checked="checked"';
            if ($field->isPrivacyForced()) echo ' disabled="disabled"';
        ?>> <?php echo __('For EndUsers'); ?><br/>
    </div>
    <div class="span3">
        <input type="checkbox" name="flags[]" value="<?php
            echo DynamicFormField::FLAG_AGENT_VIEW; ?>" <?php
            if ($field->hasFlag(DynamicFormField::FLAG_AGENT_VIEW)) echo 'checked="checked"';
            if ($field->isPrivacyForced()) echo ' disabled="disabled"';
        ?>> <?php echo __('For Agents'); ?><br/>
    </div>

<?php if ($field->getImpl()->hasData()) { ?>
    <hr class="faded"/>

    <div class="span4">
        <div style="margin-bottom:5px"><strong><?php echo __('Required'); ?></strong>
        <i class="help-tip icon-question-sign"
            data-title="<?php echo __('Required'); ?>"
            data-content="<?php echo __('New entries cannot be created unless all <em>required</em> fields have valid data.'); ?>"></i>
        </div>
    </div>
    <div class="span3">
        <input type="checkbox" name="flags[]" value="<?php
            echo DynamicFormField::FLAG_CLIENT_REQUIRED; ?>" <?php
            if ($field->hasFlag(DynamicFormField::FLAG_CLIENT_REQUIRED)) echo 'checked="checked"';
            if ($field->isRequirementForced()) echo ' disabled="disabled"';
        ?>> <?php echo __('For EndUsers'); ?><br/>
    </div>
    <div class="span3">
        <input type="checkbox" name="flags[]" value="<?php
            echo DynamicFormField::FLAG_AGENT_REQUIRED; ?>" <?php
            if ($field->hasFlag(DynamicFormField::FLAG_AGENT_REQUIRED)) echo 'checked="checked"';
            if ($field->isRequirementForced()) echo ' disabled="disabled"';
        ?>> <?php echo __('For Agents'); ?><br/>
    </div>
    <hr class="faded"/>

    <div class="span4">
        <div style="margin-bottom:5px"><strong><?php echo __('Editable'); ?></strong>
        <i class="help-tip icon-question-sign"
            data-content="<?php echo __('Fields marked editable allow agents and endusers to update the content of this field after the form entry has been created.'); ?>"
            data-title="<?php echo __('Editable'); ?>"></i>
        </div>
    </div>

    <div class="span3">
        <input type="checkbox" name="flags[]" value="<?php
            echo DynamicFormField::FLAG_CLIENT_EDIT; ?>" <?php
            if ($field->hasFlag(DynamicFormField::FLAG_CLIENT_EDIT)) echo 'checked="checked"';
        ?>> <?php echo __('For EndUsers'); ?><br/>
    </div>
    <div class="span3">
        <input type="checkbox" name="flags[]" value="<?php
            echo DynamicFormField::FLAG_AGENT_EDIT; ?>" <?php
            if ($field->hasFlag(DynamicFormField::FLAG_AGENT_EDIT)) echo 'checked="checked"';
        ?>> <?php echo __('For Agents'); ?><br/>
    </div>

<?php if (in_array($field->get('form')->get('type'), array('G', 'T', 'A'))) { ?>
    <hr class="faded"/>

    <div class="span4">
        <div style="margin-bottom:5px"><strong><?php echo __('Data Integrity');
    ?></strong>
        <i class="help-tip icon-question-sign"
            data-title="<?php echo __('Required to close a thread'); ?>"
            data-content="<?php echo __('Optionally, this field can prevent closing a thread until it has valid data.'); ?>"></i>
        </div>
    </div>
    <div class="span6">
        <input type="checkbox" name="flags[]" value="<?php
            echo DynamicFormField::FLAG_CLOSE_REQUIRED; ?>" <?php
            if ($field->hasFlag(DynamicFormField::FLAG_CLOSE_REQUIRED)) echo 'checked="checked"';
        ?>> <?php echo __('Require entry to close a thread'); ?><br/>
    </div>
<?php } ?>
<?php } ?>
    </div>
</div>

<div class="tab_content" id="config">
        <?php
        echo csrf_token();
        $form = $field->getConfigurationForm();
        echo $form->getMedia();
        foreach ($form->getFields() as $name=>$f) { ?>
            <div class="flush-left custom-field" id="field<?php echo $f->getWidget()->id;
                ?>" <?php if (!$f->isVisible()) echo 'style="display:none;"'; ?>>
            <div class="field-label <?php if ($f->get('required')) echo 'required'; ?>">
            <label for="<?php echo $f->getWidget()->name; ?>">
                <?php echo Format::htmlchars($f->getLocal('label')); ?>:
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
        <div style="width:100%">
        <textarea style="width:90%; width:calc(100% - 20px)" name="hint" rows="2" cols="40"
            class="richtext small"
            data-translate-tag="<?php echo $field->getTranslateTag('hint'); ?>"><?php
            echo Format::htmlchars($field->get('hint')); ?></textarea>
        </div>
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

<script type="text/javascript">
   // Make translatable fields translatable
   $('input[data-translate-tag]').translatable();
</script>

<style type="text/css">
.span3 {
    width: 22.25%;
    margin: 0 1%;
    display: inline-block;
    vertical-align: top;
}
.span4 {
    width: 30.25%;
    margin: 0 1%;
    display: inline-block;
    vertical-align: top;
}
.span6 {
    width: 47.25%;
    margin: 0 1%;
    display: inline-block;
    vertical-align: top;
}
.span12 {
    width: 97%;
    margin: 0 1%;
    display: inline-block;
    vertical-align: top;
}
.dialog input[type=text], .dialog select {
    margin: 2px;
}
hr.faded {
    opacity: 0.3;
}
</style>
