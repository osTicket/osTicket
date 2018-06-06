<tbody data-form-id="<?php echo $form->get('id'); ?>">
    <tr>
        <td class="handle" colspan="7">
            <input type="hidden" name="forms[]" value="<?php echo $form->get('id'); ?>" />
            <div class="pull-right">
            <i class="icon-large icon-move icon-muted"></i>
            <a href="#" title="<?php echo __('Delete'); ?>" onclick="javascript:
            if (confirm(__('You sure?'))) {
                var tbody = $(this).closest('tbody');
                $(this).closest('form')
                    .find('[name=form_id] [value=' + tbody.data('formId') + ']')
                    .prop('disabled', false);
                tbody.fadeOut(function(){this.remove()});
            }
            return false;"><i class="icon-large icon-trash"></i></a>
            </div>
            <div><strong><?php echo Format::htmlchars($form->getLocal('title')); ?></strong></div>
            <div><?php echo Format::display($form->getLocal('instructions')); ?></div>
        </td>
    </tr>
    <tr class="header">
        <td><?php echo __('Enable'); ?></td>
        <td><?php echo __('Label'); ?></td>
        <td><?php echo __('Type'); ?></td>
        <td><?php echo __('Visibility'); ?></td>
        <td><?php echo __('Variable'); ?></td>
    </tr>
<?php
    foreach ($form->getFields() as $f) { ?>
    <tr>
        <td><input type="checkbox" name="fields[]" value="<?php
            echo $f->get('id'); ?>" <?php
            if ($f->isEnabled()) echo 'checked="checked"'; ?>/></td>
        <td><?php echo $f->get('label'); ?></td>
        <td><?php $t=FormField::getFieldType($f->get('type')); echo __($t[0]); ?></td>
        <td><?php echo $f->getVisibilityDescription(); ?></td>
        <td><?php echo $f->get('name'); ?></td>
    </tr>
    <?php } ?>
</tbody>
