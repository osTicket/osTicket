<?php
// If the form was removed using the trashcan option, and there was some
// other validation error, don't render the deleted form the second time
if (isset($options['entry']) && $options['mode'] == 'edit' && $_POST && ($_POST['forms'] && !in_array($options['entry']->getId(), $_POST['forms']))) {
    return;
}

if (isset($options['entry']) && $options['mode'] == 'edit') :
    ?>
    <tbody>
    <?php endif; ?>
    <tr><td style="width:<?= $options['width'] ? : 150; ?>px;"></td><td></td></tr>
    <?php
// Keep up with the entry id in a hidden field to decide what to add and
// delete when the parent form is submitted
    if (isset($options['entry']) && $options['mode'] == 'edit') :
        ?>
    <input type="hidden" name="forms[]" value="<?= $options['entry']->getId(); ?>" />
    <?php
endif;
if ($form->getTitle()) :
    ?>
    <tr><th colspan="2">
            <em><strong><?= Format::htmlchars($form->getTitle()); ?></strong>:
                <?= Format::htmlchars($form->getInstructions()); ?>
                <?php if ($options['mode'] == 'edit') : ?>
                    <div class="pull-right">
                        <?php if ($options['entry'] && $options['entry']->getForm()->get('type') == 'G') : ?>
                            <a href="#" title="Delete Entry" onclick="javascript: $(this).closest('tbody').remove();
                                                return false;"><i class="icon-trash"></i></a>&nbsp;
                           <?php endif; ?>
                        <i class="icon-sort" title="Drag to Sort"></i>
                    </div>
                <?php endif; ?></em>
        </th></tr>
    <?php
endif;
foreach ($form->getFields() as $field) :
    try {
        if (!$field->isVisibleToStaff()) {
            continue;
        }
    } catch (Exception $e) {
        // Not connected to a DynamicFormField
    }
    ?>
    <tr><?php if ($field->isBlockLevel()) : ?>
            <td colspan="2">
            <?php else : ?>
            <td class="multi-line <?php
            if ($field->get('required')) {
                echo 'required';
            }
            ?>" style="min-width:120px;" <?php
                if ($options['width']) {
                    echo "width=\"{$options['width']}\"";
                }
                ?>>
                <?= Format::htmlchars($field->get('label')); ?>:</td>
            <td><div style="position:relative"><?php
                endif;
                $field->render();
                ?>
                <?php if ($field->get('required')) : ?>
                    <font class="error">*</font>
                    <?php
                endif;
                if (($a = $field->getAnswer()) && $a->isDeleted()) :
                    ?><a class="action-button float-right danger overlay" title="Delete this data"
                       href="#delete-answer" onclick="javascript:if (confirm('<?= __('You sure?'); ?>'))
                                           $.ajax({
                                               url: 'ajax.php/form/answer/'
                                                       + $(this).data('entryId') + '/' + $(this).data('fieldId'),
                                               type: 'delete',
                                               success: $.proxy(function () {
                                                   $(this).closest('tr').fadeOut();
                                               }, this)
                                           });"
                       data-field-id="<?= $field->getAnswer()->get('field_id');
                    ?>" data-entry-id="<?= $field->getAnswer()->get('entry_id');
                    ?>"> <i class="icon-trash"></i> </a></div><?php
                   endif;
                   if ($field->get('hint') && !$field->isBlockLevel()) :
                       ?>
                <br /><em style="color:gray;display:inline-block"><?= Format::htmlchars($field->get('hint')); ?></em>
                <?php
            endif;
            foreach ($field->errors() as $e) :
                ?>
                <br />
                <font class="error"><?= Format::htmlchars($e); ?></font>
            <?php endforeach; ?>
        </div></td>
    </tr>
    <?php
endforeach;
if (isset($options['entry']) && $options['mode'] == 'edit') :
    ?>
    </tbody>
    <?php




    endif;
