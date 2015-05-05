<input type="hidden" name="fields[]" value="<?php echo $name; ?>"/>
<?php foreach ($fields as $F) { ?>
<fieldset id="field<?php echo $F->getWidget()->id;
    ?>" <?php if (!$F->isVisible()) echo 'style="display:none;"'; ?>>
    <?php echo $F->render(); ?>
    <?php foreach ($F->errors() as $E) {
        ?><div class="error"><?php echo $E; ?></div><?php
    } ?>
</fieldset>
<?php } ?>
