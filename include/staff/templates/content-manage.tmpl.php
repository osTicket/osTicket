<h3><?php echo __('Manage Content'); ?> &mdash; <?php echo Format::htmlchars($content->getName()); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<?php if ($langs) { ?>
<div class="banner">&nbsp;
    <span class="pull-left">
        <i class="icon-globe icon-large"></i>
        <?php echo __('This content is translatable'); ?>
    </span>
    <span class="pull-right">
        <select onchange="javascript:
    $('option', this).each(function(){$($(this).val()).hide()});
    $($('option:selected', this).val()).show(); ">
            <option value="#reference-text"><?php echo
            Internationalization::getLanguageDescription($cfg->getPrimaryLanguage());
            ?> — <?php echo __('Primary'); ?></option>
<?php   foreach ($langs as $tag) { ?>
            <option value="#translation-<?php echo $tag; ?>"><?php echo
            Internationalization::getLanguageDescription($tag);
            ?></option>
<?php   } ?>
        </select>
    </span>
</div>
<?php } ?>
<form method="post" action="#content/<?php echo $content->getId(); ?>">
    <div id="reference-text" lang="<?php echo $cfg->getPrimaryLanguage(); ?>">
    <input type="text" style="width: 100%; font-size: 14pt" name="name" value="<?php
        echo Format::htmlchars($content->getName()); ?>" />
    <div style="margin-top: 5px">
    <textarea class="richtext no-bar" name="body"><?php
    echo Format::htmlchars(Format::viewableImages($content->getBody()));
?></textarea>
    </div>
    </div>

<?php foreach ($langs as $tag) { ?>
    <div id="translation-<?php echo $tag; ?>" style="display:none" lang="<?php echo $tag; ?>">
    <input type="text" style="width: 100%; font-size: 14pt" name="trans[<?php echo $tag; ?>][title]" value="<?php
        echo Format::htmlchars($info['title'][$tag]); ?>" />
    <div style="margin-top: 5px">
    <textarea class="richtext no-bar" name="trans[<?php echo $tag; ?>][body]"><?php
    echo Format::htmlchars(Format::viewableImages($info['body'][$tag]));
?></textarea>
    </div>
    </div>
<?php } ?>

    <div id="msg_info" style="margin-top:7px"><?php
echo $content->getNotes(); ?></div>
    <hr/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="<?php
                echo $user ? 'cancel' : 'close'; ?>" value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Save Changes'); ?>">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>
