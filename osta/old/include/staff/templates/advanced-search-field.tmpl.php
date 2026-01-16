<input type="hidden" name="fields[]" value="<?php echo $name; ?>"/>
<?php foreach ($fields as $F) { ?>
<fieldset id="field<?php echo $F->getWidget()->id;
    ?>" <?php 
        $class = array();
        @list($name, $sub) = explode('+', $F->get('name'), 2);
        if (!$F->isVisible()) $class[] = "hidden";
        if ($sub === 'method')
            $class[] = "adv-search-method";
        elseif ($sub === 'search')
            $class[] = "adv-search-field";
        elseif ($F->get('__searchval__'))
            $class[] = "adv-search-val";
        if ($class)
            echo 'class="'.implode(' ', $class).'"';
        ?>>
    <?php echo $F->render(); ?>
    <?php foreach ($F->errors() as $E) {
        ?><div class="error"><?php echo $E; ?></div><?php
    } ?>
</fieldset>
<?php } ?>
