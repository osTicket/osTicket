<h3 class="drag-handle"><?php echo __('Delete Custom Queue'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>

<form method="post" action="#queue/<?php echo $queue->id; ?>/delete">
    <div>
        <span style="color:red"><strong><?php echo sprintf(
            __('Are you sure you want to DELETE %s?'), __('this
            queue'));?></strong></span>
        <br/><br/>
        <?php echo __('Deleted data CANNOT be recovered.');?>
    </div>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" name="cancel" class="close"
            value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" class="red button" value="<?php
            echo $verb ?: __('Delete'); ?>">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>
