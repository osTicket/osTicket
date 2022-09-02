<?php
global $thisstaff;

$isCreate = (isset($options['mode']) && $options['mode'] == 'create');

if (isset($options['entry']) && $options['mode'] == 'edit'
    && $_POST
    && ($_POST['forms'] && !in_array($options['entry']->getId(), $_POST['forms']))
)
    return;

if (isset($options['entry']) && $options['mode'] == 'edit') { ?>
<tbody>
<?php } ?>
    <tr><td style="width:<?php echo $options['width'] ?: 150;?>px;"></td><td></td></tr>
<?php
// Keep up with the entry id in a hidden field to decide what to add and
// delete when the parent form is submitted
if (isset($options['entry']) && $options['mode'] == 'edit') { ?>
    <input type="hidden" name="forms[]" value="<?php
        echo $options['entry']->getId(); ?>" />
<?php } ?>
<?php if ($form->getTitle()) { ?>
    <tr><th colspan="2">
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
<?php } ?>
        <strong><?php echo Format::htmlchars($form->getTitle()); ?></strong>:
        <div><?php echo Format::display($form->getInstructions()); ?></div>
        </em>
        <?php
        if ($form->getNotice())
            echo sprintf('<p id="msg_warning">%s</p>',
                Format::htmlchars($form->getNotice()));
        ?>
    </th></tr>
    <?php
    }
    foreach ($form->getFields() as $field) {
        try {
            if (!$field->isEnabled())
                continue;
        }
        catch (Exception $e) {
            // Not connected to a DynamicFormField
        }
        ?>
        <tr><?php if ($field->isBlockLevel()) { ?>
                <td colspan="2">
                <?php
            }
            else { ?>
                <td class="multi-line <?php if ($field->isRequiredForStaff() || $field->isRequiredForClose()) echo 'required';
                ?>" style="min-width:120px;" <?php if ($options['width'])
                    echo "width=\"{$options['width']}\""; ?>>
                <?php echo Format::htmlchars($field->getLocal('label')); ?>:</td>
                <td><div style="position:relative"><?php
            }

            if ($field->isEditableToStaff() || $isCreate) {
                $field->render($options); ?>
                <?php if (!$field->isBlockLevel() && $field->isRequiredForStaff()) { ?>
                    <span class="error">*</span>
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
                            });
                        return false;"
                        data-field-id="<?php echo $field->getAnswer()->get('field_id');
                    ?>" data-entry-id="<?php echo $field->getAnswer()->get('entry_id');
                    ?>"> <i class="icon-trash"></i> </a></div><?php
                }
                if ($a && !$a->getValue() && $field->isRequiredForClose() && get_class($field) != 'BooleanField') {
    ?><i class="icon-warning-sign help-tip warning"
        data-title="<?php echo __('Required to close ticket'); ?>"
        data-content="<?php echo __('Data is required in this field in order to close the related ticket'); ?>"
    /></i><?php
                }
                if ($field->get('hint') && !$field->isBlockLevel()) { ?>
                    <br /><em style="color:gray;display:inline-block"><?php
                        echo Format::viewableImages($field->getLocal('hint')); ?></em>
                <?php
                }
                foreach ($field->errors() as $e) { ?>
                    <div class="error"><?php echo Format::htmlchars($e); ?></div>
                <?php }
            } else {
                $val = '';
                if ($field->value)
                    $val = $field->display($field->value);
                elseif (($a= $field->getAnswer()))
                    $val = $a->display();

                echo $val;
            }?>
            </div></td>
        </tr>
    <?php }
if (isset($options['entry']) && $options['mode'] == 'edit') { ?>
</tbody>
<?php } ?>
