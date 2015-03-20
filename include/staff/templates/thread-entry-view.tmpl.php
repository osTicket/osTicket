<h3><?php echo __('Original Thread Entry'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>

<div><strong><?php echo Format::htmlchars($entry->title); ?></strong></div>
<div class="thread-body" style="background-color:transparent">
    <?php echo $entry->getBody()->toHtml(); ?>
</div>

<hr>
<p class="full-width">
    <span class="buttons pull-right">
        <input type="button" name="cancel" class="close"
            value="<?php echo __('Close'); ?>">
    </span>
</p>

</form>
