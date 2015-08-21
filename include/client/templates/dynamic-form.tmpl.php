<?php
    // Form headline and deck with a horizontal divider above and an extra
    // space below.
    // XXX: Would be nice to handle the decoration with a CSS class
    ?> 
    <tr><td colspan="2">
    <div class="form-header" style="margin-bottom:0.5em">
    <h3><?php echo Format::htmlchars($form->getTitle()); ?></h3>
    <div><?php echo Format::display($form->getInstructions()); ?></div>
    </div>
    </td></tr>
    <?php
    // Form fields, each with corresponding errors follows. Fields marked 
    // 'private' are not included in the output for clients
    global $thisclient;
    foreach ($form->getFields() as $field) {
        if (isset($options['mode']) && $options['mode'] == 'create') {
            if (!$field->isVisibleToUsers() && !$field->isRequiredForUsers())
                continue;
        }
        elseif (!$field->isVisibleToUsers() && !$field->isEditableToUsers()) {
            continue;
        }
        ?>
        <tr>
            <td colspan="2" style="padding-top:1px;">
            <?php if (!$field->isBlockLevel()) { ?>
                <label for="<?php echo $field->getFormName(); ?>"><span class="<?php if ($field->isRequiredForUsers()) echo 'required'; ?>">
                <?php echo Format::htmlchars($field->getLocal('label')); ?>
            <?php if ($field->isRequiredForUsers()) { ?>
                <span class="error">*</span>
            <?php }
            ?></span></label>
        <tr>
		</tr>
			<td colspan="2" style="padding-top:1px;">
			   <div class="dynamic-field">
				<?php
				}
				$field->render(array('client'=>true));
				?><?php
				foreach ($field->errors() as $e) { ?>
					<div class="alert-danger"><?php echo $e; ?></div>
				<?php }
				$field->renderExtras(array('client'=>true));
				?>
				</div>
				<div class="dynamic-field-hint"><?php
					if ($field->get('hint')) { ?> <?php echo Format::viewableImages($field->getLocal('hint')); ?>
					<?php
					} ?>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
			&nbsp;
			</td>
        </tr>
        <?php
    }
?>
