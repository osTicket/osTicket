<h3><?php echo __('Edit Thread Entry'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>

<form method="post" action="<?php
    echo str_replace('ajax.php/','#',$this->getAjaxUrl()); ?>">

<input type="text" style="width:100%;font-size:14px" placeholder="<?php
    echo __('Title'); ?>" name="title" value="<?php
    echo Format::htmlchars($this->entry->title); ?>"/>
<hr style="height:0"/>
<textarea style="display: block; width: 100%; height: auto; min-height: 150px;"
    name="body"
    class="large <?php
        if ($cfg->isHtmlThreadEnabled() && $this->entry->format == 'html')
            echo 'richtext';
    ?>"><?php echo Format::viewableImages($this->entry->body);
?></textarea>

<hr>
<p class="full-width">
    <span class="buttons pull-left">
        <input type="button" name="cancel" class="close"
            value="<?php echo __('Cancel'); ?>">
    </span>
    <span class="buttons pull-right">
        <input type="submit" name="save"
            value="<?php echo __('Save Changes'); ?>">
    </span>
</p>

</form>
