<h3>Manage Content &mdash; <?php echo Format::htmlchars($content->getName()); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<form method="post" action="#content/<?php echo $content->getId(); ?>">
    <input type="text" style="width: 100%; font-size: 14pt" name="name" value="<?php
        echo Format::htmlchars($content->getName()); ?>" />
    <div style="margin-top: 5px">
    <textarea class="richtext no-bar" name="body"><?php
    echo Format::viewableImages($content->getBody());
?></textarea>
    </div>
    <div id="msg_info" style="margin-top:7px"><?php
echo $content->getNotes(); ?></div>
    <hr/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" name="cancel" class="<?php echo $user ? 'cancel' : 'close' ?>"  value="Cancel">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Save Changes">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>
