<?php
// If the form was removed using the trashcan option, and there was some
// other validation error, don't render the deleted form the second time
if (isset($options['entry']) && $options['mode'] == 'edit'
    && $_POST
    && ($_POST['forms'] && !in_array($options['entry']->getId(), $_POST['forms']))
)
    return;

if (isset($options['entry']) && $options['mode'] == 'edit') { ?>

<?php } ?>
   
<?php
// Keep up with the entry id in a hidden field to decide what to add and
// delete when the parent form is submitted
if (isset($options['entry']) && $options['mode'] == 'edit') { ?>
    <input type="hidden" name="forms[]" value="<?php
        echo $options['entry']->getId(); ?>" />
<?php } ?>
<?php 
if ($options['modal'] !== 'ticketedit'){
if ($form->getTitle()) { ?>
    <div>
        <em>
<?php if ($options['mode'] == 'edit') { ?> 
        <div class="pull-right">
    <?php if ($options['entry']
                && $options['entry']->getDynamicForm()->get('type') == 'G') { ?>
            <a href="#" title="Delete Entry" onclick="javascript:
                $(this).closest('tbody').remove();
                return false;"><i class="icon-trash"></i></a>&nbsp;
    <?php } ?>
            <i class="icon-sort" title="Drag to Sort"></i>
        </div>
<?php } 


?>
        <strong><?php echo Format::htmlchars($form->getTitle()); ?></strong>:
        <div><?php echo Format::display($form->getInstructions()); ?></div>
        </em> 

    </div>
    <?php
    }
    
    }
    
    foreach ($form->getFields() as $field) {
        try {
			if (!$field->isEnabled())
                continue;
            if ($options['mode'] == 'edit' && !$field->isEditableToStaff())
                continue;
        }
        catch (Exception $e) {
            // Not connected to a DynamicFormField
        }
       
		?>
		
        
		
			<?php if ($field->isBlockLevel()) { ?>
                <div <?php if ($field->isRequiredForStaff() || $field->isRequiredForClose()) echo 'id="requiredfield"';
                ?> > <td style="padding-right:16px;" <?php if ($field->isRequiredForStaff() || $field->isRequiredForClose()) echo 'id="requiredfield"';
                ?>> &nbsp
                <?php
            }
            
			else { ?>
                <div class="multi-line dynamicformdatamulti <?php if ($field->isRequiredForStaff() || $field->isRequiredForClose()) echo 'required';
                ?>" style="min-width:120px;" <?php if ($options['width'])
                    echo "width=\"{$options['width']}\""; ?>>
                <label><?php echo Format::htmlchars($field->getLocal('label')); ?>:</label>
                <div <?php if ($field->errors()){echo ' class="has-danger"';}?>style="position:relative" <?php if ($field->isRequiredForStaff() || $field->isRequiredForClose()) echo 'id="requiredfield"';
                ?>><?php
            }
			
            $field->render($options); ?>
            <?php if (!$field->isBlockLevel() && $field->isRequiredForStaff()) { ?>
                <span class="error "></span>
            <?php
            }
            if ($field->isStorable() && ($a = $field->getAnswer()) && $a->isDeleted()) {
                ?><a class="action-button float-right danger overlay" title="Delete this data"
                    href="#delete-answer"
                    onclick="javascript:if (confirm('<?php echo __('You sure?'); ?>'))
                        $.ajax({
                            url: 'ajax.php/form/answer/'
                                +$(this).data('entryId') + '/' + $(this).data('fieldId'),
                            type: 'delete',
                            success: $.proxy(function() {
                                $(this).closest('tr').fadeOut();
                            }, this)
                        });"
                    data-field-id="<?php echo $field->getAnswer()->get('field_id');
                ?>" data-entry-id="<?php echo $field->getAnswer()->get('entry_id');
                ?>"> <i class="icon-trash"></i> </a></div><?php
            }
            if ($a && !$a->getValue() && $field->isRequiredForClose()) {
?><i class="icon-warning-sign help-tip warning"
    data-title="<?php echo __('Required to close ticket'); ?>"
    data-content="<?php echo __('Data is required in this field in order to close the related ticket'); ?>"
/></i><?php
            }
            if ($field->get('hint') && !$field->isBlockLevel()) { ?>
                <em style="color:gray;display:inline-block"><?php
                    echo Format::viewableImages($field->getLocal('hint')); ?></em>
            <?php
            }
                     
            foreach ($field->errors() as $e) { ?>
                <div class="form-control-feedback-danger "><?php echo Format::htmlchars($e); ?></div>
            <?php } ?>
            </div>
        
    <?php }
if (isset($options['entry']) && $options['mode'] == 'edit') { ?>

<?php } ?>
