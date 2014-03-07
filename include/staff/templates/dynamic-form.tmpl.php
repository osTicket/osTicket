<tr><td style="width:150px"></td><td></td></tr>
<?php if ($form->getTitle()) { ?>
    <tr><th colspan="2">
        <em><strong><?php echo Format::htmlchars($form->getTitle()); ?></strong>:
        <?php echo Format::htmlchars($form->getInstructions()); ?></em>
    </th></tr>
    <?php
    }
    foreach ($form->getFields() as $field) {
        ?>
        <tr><?php if ($field->isBlockLevel()) { ?>
                <td colspan="2">
                <?php
            }
            else { ?>
                <td class="multi-line <?php if ($field->get('required')) echo 'required'; ?>" style="min-width:120px;">
                <?php echo Format::htmlchars($field->get('label')); ?>:</td>
                <td><div style="position:relative"><?php
            }
            $field->render(); ?>
            <?php if ($field->get('required')) { ?>
                <font class="error">*</font>
            <?php
            }
            if (($a = $field->getAnswer()) && $a->isDeleted()) {
                ?><a class="action-button danger overlay" title="Delete this data"
                    href="#delete-answer"
                    onclick="javascript:if (confirm('You sure?'))
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
            if ($field->get('hint') && !$field->isBlockLevel()) { ?>
                <br /><em style="color:gray;display:inline-block"><?php
                    echo Format::htmlchars($field->get('hint')); ?></em>
            <?php
            }
            foreach ($field->errors() as $e) { ?>
                <br />
                <font class="error"><?php echo Format::htmlchars($e); ?></font>
            <?php } ?>
            </div></td>
        </tr>
        <?php
    }
?>
